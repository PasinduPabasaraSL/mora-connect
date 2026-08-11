#!/usr/bin/env bash
#
# End-to-end check of the writing feature over HTTP.
#
# Registers a throwaway author, walks a draft through autosave, publishing,
# editing and unpublishing, and checks a legacy plain-text article still reads
# correctly. Everything it creates is removed at the end.

set -u

BASE="http://localhost/Blog"
JAR="$(mktemp)"
MYSQL="/opt/lampp/bin/mysql -uroot moraconnect"
USER="verifybot$$"
PASS="Passw0rd!verify"

pass=0
fail=0

check() {
    if [ "$2" = "yes" ]; then
        printf '  \033[32mok\033[0m   %s\n' "$1"
        pass=$((pass + 1))
    else
        printf '  \033[31mFAIL\033[0m %s\n' "$1"
        fail=$((fail + 1))
    fi
}

# Grabs the CSRF token out of a page so posts are accepted.
token_from() {
    curl -s -b "$JAR" -c "$JAR" "$1" \
        | grep -o 'name="_token" value="[^"]*"' \
        | head -1 \
        | sed 's/.*value="//; s/"$//'
}

code_for() {
    curl -s -o /dev/null -w '%{http_code}' -b "$JAR" -c "$JAR" "$1"
}

cleanup() {
    $MYSQL -e "DELETE FROM blogPost WHERE user_id IN (SELECT id FROM users WHERE username = '$USER');
               DELETE FROM users WHERE username = '$USER';
               DELETE FROM blogPost WHERE title = 'Legacy plain text article';" >/dev/null 2>&1
    rm -f "$JAR"
}
trap cleanup EXIT

echo
echo "1. Legacy plain-text article"

# Written the way articles were stored before the rich editor existed.
$MYSQL -e "INSERT INTO blogPost (user_id, title, content, content_format, category, status, visibility, published_at)
           VALUES (3, 'Legacy plain text article',
                   'First paragraph with <script>alert(1)</script> in it.\n\nSecond paragraph.',
                   'text', 'Systems', 'published', 'public', NOW());" >/dev/null 2>&1

LEGACY_ID=$($MYSQL -N -e "SELECT id FROM blogPost WHERE title = 'Legacy plain text article' LIMIT 1;" 2>/dev/null)
LEGACY=$(curl -s "$BASE/posts/$LEGACY_ID")

echo "$LEGACY" | grep -q '<p>First paragraph' && r=yes || r=no
check "plain text renders as paragraphs" "$r"

echo "$LEGACY" | grep -q '&lt;script&gt;' && r=yes || r=no
check "markup inside legacy text stays escaped" "$r"

echo "$LEGACY" | grep -q 'min read' && r=yes || r=no
check "reading time shown" "$r"

echo
echo "2. Author sign-up"

TOKEN=$(token_from "$BASE/register")
curl -s -o /dev/null -b "$JAR" -c "$JAR" -X POST "$BASE/register" \
    --data-urlencode "_token=$TOKEN" \
    --data-urlencode "username=$USER" \
    --data-urlencode "email=$USER@example.com" \
    --data-urlencode "password=$PASS" \
    --data-urlencode "confirm_password=$PASS"

curl -s -b "$JAR" -c "$JAR" "$BASE/profile" | grep -q "$USER" && r=yes || r=no
check "registered and signed in" "$r"

echo
echo "3. Editor page"

EDITOR=$(curl -s -b "$JAR" -c "$JAR" "$BASE/posts/create")

echo "$EDITOR" | grep -q 'id="editorBody"' && r=yes || r=no
check "writing surface present" "$r"

echo "$EDITOR" | grep -q 'js/editor.js' && r=yes || r=no
check "editor script loaded" "$r"

echo "$EDITOR" | grep -q 'id="settingsSheet"' && r=yes || r=no
check "settings dialog present" "$r"

echo "$EDITOR" | grep -q 'id="insertMenu"' && r=yes || r=no
check "insert menu present" "$r"

echo
echo "4. Autosave creates a draft"

TOKEN=$(token_from "$BASE/posts/create")
SAVE=$(curl -s -b "$JAR" -c "$JAR" -X POST "$BASE/posts/autosave" \
    --data-urlencode "_token=$TOKEN" \
    --data-urlencode "id=" \
    --data-urlencode "title=Autosaved draft" \
    --data-urlencode "content=<p>Body <b>bold</b> text.</p><script>alert(1)</script>")

echo "$SAVE" | grep -q '"saved":true' && r=yes || r=no
check "autosave reports success" "$r"

echo "$SAVE" | grep -q '"created":true' && r=yes || r=no
check "first autosave creates the article" "$r"

DRAFT_ID=$(echo "$SAVE" | sed 's/.*"id":\([0-9]*\).*/\1/')

STATUS=$($MYSQL -N -e "SELECT status FROM blogPost WHERE id = $DRAFT_ID;" 2>/dev/null)
[ "$STATUS" = "draft" ] && r=yes || r=no
check "stored as a draft" "$r"

STORED=$($MYSQL -N -e "SELECT content FROM blogPost WHERE id = $DRAFT_ID;" 2>/dev/null)
echo "$STORED" | grep -q '<strong>bold</strong>' && r=yes || r=no
check "b promoted to strong by the sanitiser" "$r"

echo "$STORED" | grep -qi 'script' && r=no || r=yes
check "script tag stripped" "$r"

echo
echo "5. Draft visibility"

# The draft must not leak into anything a reader can see.
curl -s "$BASE/" | grep -q 'Autosaved draft' && r=no || r=yes
check "draft absent from the homepage" "$r"

curl -s "$BASE/search?q=Autosaved" | grep -q 'Autosaved draft' && r=no || r=yes
check "draft absent from search" "$r"

ANON=$(mktemp)
ANON_CODE=$(curl -s -o /dev/null -w '%{http_code}' -c "$ANON" "$BASE/posts/$DRAFT_ID")
rm -f "$ANON"
[ "$ANON_CODE" = "404" ] && r=yes || r=no
check "signed-out visitor gets 404 for a draft (got $ANON_CODE)" "$r"

[ "$(code_for "$BASE/posts/$DRAFT_ID")" = "200" ] && r=yes || r=no
check "author can open their own draft" "$r"

curl -s -b "$JAR" -c "$JAR" "$BASE/profile" | grep -q 'Drafts' && r=yes || r=no
check "draft listed on the author's profile" "$r"

echo
echo "6. Autosave updates the same draft"

TOKEN=$(token_from "$BASE/posts/$DRAFT_ID/edit")
SAVE=$(curl -s -b "$JAR" -c "$JAR" -X POST "$BASE/posts/autosave" \
    --data-urlencode "_token=$TOKEN" \
    --data-urlencode "id=$DRAFT_ID" \
    --data-urlencode "title=Autosaved draft" \
    --data-urlencode "content=<h2>Heading</h2><p>More words here now.</p>")

echo "$SAVE" | grep -q '"created"' && r=no || r=yes
check "second autosave reuses the row" "$r"

COUNT=$($MYSQL -N -e "SELECT COUNT(*) FROM blogPost WHERE title = 'Autosaved draft';" 2>/dev/null)
[ "$COUNT" = "1" ] && r=yes || r=no
check "still exactly one row (got $COUNT)" "$r"

echo
echo "7. Publishing"

TOKEN=$(token_from "$BASE/posts/$DRAFT_ID/edit")
curl -s -o /dev/null -b "$JAR" -c "$JAR" -X POST "$BASE/posts/$DRAFT_ID" \
    --data-urlencode "_token=$TOKEN" \
    --data-urlencode "action=publish" \
    --data-urlencode "id=$DRAFT_ID" \
    --data-urlencode "title=Published from the editor" \
    --data-urlencode "subtitle=A subtitle for the listing" \
    --data-urlencode "category=Systems" \
    --data-urlencode "tags=testing, php, testing" \
    --data-urlencode "description=Short summary of the article." \
    --data-urlencode "visibility=public" \
    --data-urlencode "comments_enabled=1" \
    --data-urlencode "content=<h2>Heading</h2><p>Some real writing.</p><pre data-language=\"php\"><code>echo 'hi';</code></pre><blockquote><p>Quoted.</p></blockquote><figure data-align=\"center\"><img src=\"https://example.com/a.jpg\" alt=\"Alt text\" width=\"80\"><figcaption>The caption</figcaption></figure>"

read -r STATUS SLUG PUBAT WORDS <<<"$($MYSQL -N -e "SELECT status, slug, published_at IS NOT NULL, word_count FROM blogPost WHERE id = $DRAFT_ID;" 2>/dev/null)"

[ "$STATUS" = "published" ] && r=yes || r=no
check "status is published" "$r"

[ "$SLUG" = "published-from-the-editor" ] && r=yes || r=no
check "slug follows the title (got $SLUG)" "$r"

[ "$PUBAT" = "1" ] && r=yes || r=no
check "published_at stamped" "$r"

[ "$WORDS" -gt 0 ] && r=yes || r=no
check "word count stored (got $WORDS)" "$r"

TAGS=$($MYSQL -N -e "SELECT tags FROM blogPost WHERE id = $DRAFT_ID;" 2>/dev/null)
[ "$TAGS" = "testing, php" ] && r=yes || r=no
check "duplicate tag dropped (got '$TAGS')" "$r"

echo
echo "8. The published article"

ART=$(curl -s "$BASE/posts/$DRAFT_ID")

echo "$ART" | grep -q '<h2>Heading</h2>' && r=yes || r=no
check "heading rendered as markup" "$r"

echo "$ART" | grep -q 'data-language="php"' && r=yes || r=no
check "code block keeps its language" "$r"

echo "$ART" | grep -q '<blockquote>' && r=yes || r=no
check "quote rendered" "$r"

echo "$ART" | grep -q 'The caption' && r=yes || r=no
check "image caption rendered" "$r"

echo "$ART" | grep -q 'A subtitle for the listing' && r=yes || r=no
check "subtitle rendered" "$r"

echo "$ART" | grep -q 'tag-chip' && r=yes || r=no
check "tags rendered" "$r"

echo
echo "9. Listings and slug routing"

curl -s "$BASE/" | grep -q 'Published from the editor' && r=yes || r=no
check "appears on the homepage" "$r"

HOME=$(curl -s "$BASE/")
echo "$HOME" | grep -q 'Short summary of the article' && r=yes || r=no
check "listing uses the author's summary" "$r"

echo "$HOME" | grep -q '&lt;h2&gt;\|&lt;p&gt;' && r=no || r=yes
check "no raw tags leak into listing excerpts" "$r"

[ "$(code_for "$BASE/posts/$SLUG")" = "200" ] && r=yes || r=no
check "reachable by slug" "$r"

[ "$(code_for "$BASE/posts/$DRAFT_ID")" = "200" ] && r=yes || r=no
check "still reachable by id" "$r"

echo
echo "10. Editing a published article"

EDIT=$(curl -s -b "$JAR" -c "$JAR" "$BASE/posts/$DRAFT_ID/edit")

echo "$EDIT" | grep -q 'Published from the editor' && r=yes || r=no
check "editor loads the stored title" "$r"

echo "$EDIT" | grep -q 'A subtitle for the listing' && r=yes || r=no
check "editor loads the subtitle" "$r"

echo "$EDIT" | grep -q 'data-status="published"' && r=yes || r=no
check "editor knows the article is live" "$r"

echo "$EDIT" | tr '\n' ' ' | grep -q 'value="testing, php"' && r=yes || r=no
check "tags retained in settings" "$r"

# Autosave must refuse to rewrite a live article behind the author's back.
TOKEN=$(token_from "$BASE/posts/$DRAFT_ID/edit")
SAVE=$(curl -s -b "$JAR" -c "$JAR" -X POST "$BASE/posts/autosave" \
    --data-urlencode "_token=$TOKEN" \
    --data-urlencode "id=$DRAFT_ID" \
    --data-urlencode "title=Sneaky overwrite" \
    --data-urlencode "content=<p>nope</p>")

echo "$SAVE" | grep -q '"reason":"published"' && r=yes || r=no
check "autosave declines to touch a live article" "$r"

TITLE=$($MYSQL -N -e "SELECT title FROM blogPost WHERE id = $DRAFT_ID;" 2>/dev/null)
[ "$TITLE" = "Published from the editor" ] && r=yes || r=no
check "live article unchanged by the attempt" "$r"

echo
echo "11. Preview"

[ "$(code_for "$BASE/posts/$DRAFT_ID/preview")" = "200" ] && r=yes || r=no
check "author can open the preview" "$r"

curl -s -b "$JAR" -c "$JAR" "$BASE/posts/$DRAFT_ID/preview" | grep -q '<h2>Heading</h2>' && r=yes || r=no
check "preview renders the same markup" "$r"

echo
echo "12. Unpublishing"

TOKEN=$(token_from "$BASE/posts/$DRAFT_ID/edit")
curl -s -o /dev/null -b "$JAR" -c "$JAR" -X POST "$BASE/posts/$DRAFT_ID/unpublish" \
    --data-urlencode "_token=$TOKEN"

read -r STATUS PUBAT <<<"$($MYSQL -N -e "SELECT status, published_at IS NOT NULL FROM blogPost WHERE id = $DRAFT_ID;" 2>/dev/null)"

[ "$STATUS" = "draft" ] && r=yes || r=no
check "back to draft" "$r"

[ "$PUBAT" = "1" ] && r=yes || r=no
check "original publication date kept" "$r"

curl -s "$BASE/" | grep -q 'Published from the editor' && r=no || r=yes
check "removed from the homepage" "$r"

echo
echo "13. Unlisted articles"

TOKEN=$(token_from "$BASE/posts/$DRAFT_ID/edit")
curl -s -o /dev/null -b "$JAR" -c "$JAR" -X POST "$BASE/posts/$DRAFT_ID" \
    --data-urlencode "_token=$TOKEN" \
    --data-urlencode "action=publish" \
    --data-urlencode "id=$DRAFT_ID" \
    --data-urlencode "title=Published from the editor" \
    --data-urlencode "category=Systems" \
    --data-urlencode "visibility=unlisted" \
    --data-urlencode "content=<p>Still readable by link.</p>"

[ "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/posts/$DRAFT_ID")" = "200" ] && r=yes || r=no
check "readable by anyone with the link" "$r"

curl -s "$BASE/" | grep -q 'Published from the editor' && r=no || r=yes
check "kept off the homepage" "$r"

echo
echo "14. Ownership and CSRF"

OTHER=$(mktemp)
OTHER_CODE=$(curl -s -o /dev/null -w '%{http_code}' -c "$OTHER" "$BASE/posts/$DRAFT_ID/edit")
[ "$OTHER_CODE" = "302" ] || [ "$OTHER_CODE" = "403" ] && r=yes || r=no
check "signed-out visitor cannot open the editor (got $OTHER_CODE)" "$r"

AUTO_CODE=$(curl -s -o /dev/null -w '%{http_code}' -c "$OTHER" -X POST "$BASE/posts/autosave" --data "title=x")
rm -f "$OTHER"
[ "$AUTO_CODE" = "401" ] && r=yes || r=no
check "autosave rejects a signed-out request (got $AUTO_CODE)" "$r"

BAD=$(curl -s -b "$JAR" -c "$JAR" -o /dev/null -w '%{http_code}' -X POST "$BASE/posts/autosave" \
    --data-urlencode "_token=wrong" --data-urlencode "title=x")
[ "$BAD" = "403" ] && r=yes || r=no
check "autosave rejects a bad token (got $BAD)" "$r"

LEGACY_EDIT=$(curl -s -b "$JAR" -c "$JAR" -o /dev/null -w '%{http_code}' "$BASE/posts/$LEGACY_ID/edit")
[ "$LEGACY_EDIT" = "403" ] && r=yes || r=no
check "cannot edit another author's article (got $LEGACY_EDIT)" "$r"

echo
echo "15. Other pages still work"

for path in "" "radar" "search?q=php" "about" "topics/systems" "login" "register"; do
    CODE=$(curl -s -o /dev/null -w '%{http_code}' "$BASE/$path")
    [ "$CODE" = "200" ] && r=yes || r=no
    check "GET /$path -> $CODE" "$r"
done

echo
printf '%s passed, %s failed\n\n' "$pass" "$fail"
[ "$fail" -eq 0 ]

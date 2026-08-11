<?php

declare(strict_types=1);

namespace App\Core;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Allowlist sanitiser for article bodies.
 *
 * The editor posts markup, so the body can never be trusted: it is rebuilt
 * here from a fixed list of tags and attributes rather than filtered by looking
 * for things that seem dangerous. Anything not named below is dropped, which
 * means a tag nobody thought of fails closed instead of open.
 */
final class Html
{
    /**
     * Tag => allowed attributes.
     *
     * Body headings start at h2: the article title is the page's only h1, so
     * the editor's "H1" button produces an h2 to keep the outline valid. h1 is
     * still accepted (pasted content) and demoted on the way in.
     *
     * @var array<string, list<string>>
     */
    private const ALLOWED = [
        'p'          => [],
        'br'         => [],
        'strong'     => [],
        'em'         => [],
        'u'          => [],
        's'          => [],
        'code'       => [],
        'pre'        => ['data-language'],
        'blockquote' => [],
        'h2'         => [],
        'h3'         => [],
        'h4'         => [],
        'ul'         => [],
        'ol'         => [],
        'li'         => [],
        'hr'         => [],
        'a'          => ['href', 'title'],
        'figure'     => ['class', 'data-align', 'data-embed'],
        'figcaption' => [],
        'img'        => ['src', 'alt', 'width'],
        'iframe'     => ['src', 'title', 'allow', 'allowfullscreen', 'loading'],
    ];

    /** Tags that carry no meaning when empty and are removed if they end up so. */
    private const DROP_IF_EMPTY = ['p', 'strong', 'em', 'u', 's', 'code', 'blockquote', 'h2', 'h3', 'h4', 'li', 'figcaption'];

    /** Elements allowed to sit at the top level of an article. */
    private const BLOCKS = ['p', 'h2', 'h3', 'h4', 'ul', 'ol', 'blockquote', 'pre', 'hr', 'figure'];

    /**
     * Hosts allowed in an <iframe src>. An embed is the one place markup can
     * pull in a third party document, so the list is deliberately tiny.
     *
     * @var list<string>
     */
    private const EMBED_HOSTS = [
        'www.youtube.com',
        'youtube.com',
        'www.youtube-nocookie.com',
        'player.vimeo.com',
        'codepen.io',
        'codesandbox.io',
        'w.soundcloud.com',
        'open.spotify.com',
    ];

    public static function sanitize(string $html): string
    {
        $html = trim($html);

        if ($html === '') {
            return '';
        }

        $document = self::parse($html);
        $body     = $document->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return '';
        }

        self::cleanChildren($body);
        self::promoteStrays($document, $body);

        $out = '';

        foreach ($body->childNodes as $child) {
            $out .= $document->saveHTML($child);
        }

        return trim($out);
    }

    /**
     * Loads a fragment without letting libxml reach the network or complain
     * about HTML5 tags it does not know.
     */
    private static function parse(string $html): DOMDocument
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);

        // The meta charset keeps multi-byte characters intact; without it
        // libxml assumes ISO-8859-1 and mangles anything non-ASCII.
        $document->loadHTML(
            '<?xml encoding="UTF-8"><html><body>' . $html . '</body></html>',
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $document;
    }

    /**
     * Walks a snapshot of the child list, because cleaning a node can remove
     * or replace it and iterating a live DOMNodeList would then skip siblings.
     */
    private static function cleanChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $child) {
            self::cleanNode($child);
        }
    }

    private static function cleanNode(DOMNode $node): void
    {
        if ($node instanceof DOMText) {
            return;
        }

        if (!$node instanceof DOMElement) {
            // Comments, processing instructions and anything else exotic
            $node->parentNode?->removeChild($node);

            return;
        }

        $name = strtolower($node->nodeName);
        $name = match ($name) {
            'b'      => 'strong',
            'i'      => 'em',
            'strike', 'del' => 's',
            'h1'     => 'h2',
            'h5', 'h6' => 'h4',
            'div', 'section', 'article' => 'p',
            default  => $name,
        };

        if (!isset(self::ALLOWED[$name])) {
            self::unwrap($node);

            return;
        }

        if ($name !== strtolower($node->nodeName)) {
            $node = self::rename($node, $name);
        }

        self::stripAttributes($node, self::ALLOWED[$name]);

        if (!self::attributesValid($node, $name)) {
            $node->parentNode?->removeChild($node);

            return;
        }

        self::cleanChildren($node);

        if (self::isDroppableEmpty($node, $name)) {
            $node->parentNode?->removeChild($node);
        }
    }

    /**
     * Replaces a disallowed element with its children, so unwrapping a stray
     * <span> keeps the words the author typed inside it.
     */
    private static function unwrap(DOMElement $node): void
    {
        $parent = $node->parentNode;

        if ($parent === null) {
            return;
        }

        // script/style content is markup, not prose, so it goes with the tag
        if (in_array(strtolower($node->nodeName), ['script', 'style', 'noscript', 'template'], true)) {
            $parent->removeChild($node);

            return;
        }

        while ($node->firstChild !== null) {
            $child = $node->firstChild;
            $node->removeChild($child);
            $parent->insertBefore($child, $node);
            self::cleanNode($child);
        }

        $parent->removeChild($node);
    }

    private static function rename(DOMElement $node, string $name): DOMElement
    {
        $replacement = $node->ownerDocument->createElement($name);

        foreach (iterator_to_array($node->attributes) as $attribute) {
            $replacement->setAttribute($attribute->nodeName, $attribute->nodeValue ?? '');
        }

        while ($node->firstChild !== null) {
            $replacement->appendChild($node->firstChild);
        }

        $node->parentNode?->replaceChild($replacement, $node);

        return $replacement;
    }

    /**
     * @param list<string> $keep
     */
    private static function stripAttributes(DOMElement $node, array $keep): void
    {
        foreach (iterator_to_array($node->attributes) as $attribute) {
            if (!in_array(strtolower($attribute->nodeName), $keep, true)) {
                $node->removeAttribute($attribute->nodeName);
            }
        }
    }

    /**
     * Per-tag attribute rules. Returning false deletes the element, which is
     * the right outcome for an image or embed whose source cannot be trusted.
     */
    private static function attributesValid(DOMElement $node, string $name): bool
    {
        if ($name === 'a') {
            $href = trim($node->getAttribute('href'));

            if (!self::isSafeLink($href)) {
                self::unwrap($node);

                return false;
            }

            $node->setAttribute('href', $href);
            $node->setAttribute('rel', 'noopener nofollow');
            $node->setAttribute('target', '_blank');

            return true;
        }

        if ($name === 'img') {
            if (!self::isHttpUrl(trim($node->getAttribute('src')))) {
                return false;
            }

            // Width is a percentage of the text column, clamped to sane bounds
            $width = (int) $node->getAttribute('width');

            if ($width < 25 || $width > 100) {
                $node->removeAttribute('width');
            }

            return true;
        }

        if ($name === 'iframe') {
            if (!self::isEmbeddableUrl(trim($node->getAttribute('src')))) {
                return false;
            }

            $node->setAttribute('loading', 'lazy');
            $node->setAttribute('allowfullscreen', 'true');

            return true;
        }

        if ($name === 'figure') {
            $align = $node->getAttribute('data-align');

            if (!in_array($align, ['left', 'center', 'wide'], true)) {
                $node->removeAttribute('data-align');
            }

            // class is only allowed so the article page can style embeds
            $class = $node->getAttribute('class');

            if (!in_array($class, ['embed', 'embed-link'], true)) {
                $node->removeAttribute('class');
            }

            return true;
        }

        if ($name === 'pre') {
            $language = strtolower(trim($node->getAttribute('data-language')));

            if (preg_match('/^[a-z0-9+#-]{1,20}$/', $language) !== 1) {
                $node->removeAttribute('data-language');
            } else {
                $node->setAttribute('data-language', $language);
            }

            return true;
        }

        return true;
    }

    private static function isDroppableEmpty(DOMElement $node, string $name): bool
    {
        if (!in_array($name, self::DROP_IF_EMPTY, true)) {
            return false;
        }

        // A paragraph holding only an image or a break is not empty
        if ($node->getElementsByTagName('img')->length > 0
            || $node->getElementsByTagName('br')->length > 0
            || $node->getElementsByTagName('iframe')->length > 0) {
            return false;
        }

        return trim(preg_replace('/\x{00A0}/u', ' ', $node->textContent) ?? '') === '';
    }

    /**
     * Wraps loose text left at the top level in a paragraph. Pasted content
     * often arrives as bare text nodes, and without this it would render
     * outside any block and ignore the article's paragraph spacing.
     */
    private static function promoteStrays(DOMDocument $document, DOMNode $body): void
    {
        $paragraph = null;

        foreach (iterator_to_array($body->childNodes) as $child) {
            $isBlock = $child instanceof DOMElement
                && in_array(strtolower($child->nodeName), self::BLOCKS, true);

            if ($isBlock) {
                $paragraph = null;

                continue;
            }

            if ($child instanceof DOMText && trim($child->textContent) === '') {
                // Whitespace between two inline tags is a real word gap, so it
                // is only dropped when no paragraph is open to hold it.
                if ($paragraph === null) {
                    $body->removeChild($child);

                    continue;
                }
            }

            if ($paragraph === null) {
                $paragraph = $document->createElement('p');
                $body->insertBefore($paragraph, $child);
            }

            $body->removeChild($child);
            $paragraph->appendChild($child);
        }
    }

    private static function isSafeLink(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        // Fragment and root-relative links stay inside the site
        if (str_starts_with($url, '#') || str_starts_with($url, '/')) {
            return !str_starts_with($url, '//');
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https', 'mailto'], true);
    }

    private static function isHttpUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    private static function isEmbeddableUrl(string $url): bool
    {
        if (!self::isHttpUrl($url)) {
            return false;
        }

        return in_array(strtolower((string) parse_url($url, PHP_URL_HOST)), self::EMBED_HOSTS, true);
    }

    /**
     * Readable text of an article body, whatever format it is stored in. Used
     * for excerpts, search snippets and word counts.
     */
    public static function toText(string $content, string $format = 'html'): string
    {
        if ($format !== 'html') {
            return trim(preg_replace('/\s+/', ' ', $content) ?? '');
        }

        // Block boundaries become spaces so "one</p><p>two" is not "onetwo"
        $spaced = preg_replace('#<(/?)(p|div|br|li|h[1-6]|blockquote|pre|figure|figcaption|tr)\b[^>]*>#i', ' ', $content) ?? $content;

        return trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($spaced), ENT_QUOTES, 'UTF-8')) ?? '');
    }

    public static function wordCount(string $content, string $format = 'html'): int
    {
        $text = self::toText($content, $format);

        if ($text === '') {
            return 0;
        }

        return count(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }

    public static function readingMinutes(int $words): int
    {
        return max(1, (int) round($words / 200));
    }

    /**
     * Converts a legacy plain-text body into paragraphs so an article written
     * before the rich editor existed can be opened and edited in it. Blank
     * lines separate paragraphs; single newlines become line breaks, which is
     * how the old pre-wrap rendering displayed them.
     */
    public static function fromPlainText(string $text): string
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", $text));

        if ($text === '') {
            return '';
        }

        $out = '';

        foreach (preg_split('/\n{2,}/', $text) ?: [] as $block) {
            $block = trim($block, "\n");

            if (trim($block) === '') {
                continue;
            }

            $lines = array_map(
                static fn (string $line): string => htmlspecialchars($line, ENT_QUOTES, 'UTF-8'),
                explode("\n", $block)
            );

            $out .= '<p>' . implode('<br>', $lines) . '</p>';
        }

        return $out;
    }
}

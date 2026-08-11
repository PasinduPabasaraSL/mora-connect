<?php

declare(strict_types=1);

/**
 * Checks the article sanitiser against the markup the editor produces and the
 * markup an attacker would try. Run with: php _verify_sanitizer.php
 */

require __DIR__ . '/app/Core/helpers.php';
require __DIR__ . '/app/Core/Html.php';

use App\Core\Html;

$pass = 0;
$fail = 0;

/**
 * @param callable(string): bool $expect
 */
function check(string $label, string $input, callable $expect): void
{
    global $pass, $fail;

    $out = Html::sanitize($input);

    if ($expect($out)) {
        $pass++;
        echo "  \033[32mok\033[0m   {$label}\n";

        return;
    }

    $fail++;
    echo "  \033[31mFAIL\033[0m {$label}\n";
    echo "       got: {$out}\n";
}

function keeps(string $needle): callable
{
    return static fn (string $out): bool => str_contains($out, $needle);
}

function drops(string $needle): callable
{
    return static fn (string $out): bool => !str_contains($out, $needle);
}

echo "\nFormatting the editor produces\n";

check('paragraph kept', '<p>Hello there.</p>', keeps('<p>Hello there.</p>'));
check('b becomes strong', '<p><b>bold</b></p>', keeps('<strong>bold</strong>'));
check('i becomes em', '<p><i>italic</i></p>', keeps('<em>italic</em>'));
check('underline kept', '<p><u>under</u></p>', keeps('<u>under</u>'));
check('strike normalised', '<p><strike>gone</strike></p>', keeps('<s>gone</s>'));
check('inline code kept', '<p>run <code>ls -la</code> now</p>', keeps('<code>ls -la</code>'));
check('word gap between tags survives', '<p><b>one</b> <i>two</i></p>', keeps('</strong> <em>'));
check('h1 demoted to h2', '<h1>Title</h1>', keeps('<h2>Title</h2>'));
check('h5 folded into h4', '<h5>Small</h5>', keeps('<h4>Small</h4>'));
check('lists kept', '<ul><li>one</li><li>two</li></ul>', keeps('<li>two</li>'));
check('quote kept', '<blockquote><p>Said it.</p></blockquote>', keeps('<blockquote>'));
check('divider kept', '<p>a</p><hr><p>b</p>', keeps('<hr>'));
check('code block keeps language', '<pre data-language="php"><code>echo 1;</code></pre>', keeps('data-language="php"'));
check('bogus language dropped', '<pre data-language="../../etc"><code>x</code></pre>', drops('data-language'));

echo "\nImages, figures and embeds\n";

check(
    'figure with caption kept',
    '<figure data-align="center"><img src="https://a.test/i.png" alt="Alt"><figcaption>Cap</figcaption></figure>',
    keeps('<figcaption>Cap</figcaption>')
);
check('alignment kept', '<figure data-align="wide"><img src="https://a.test/i.png" alt=""></figure>', keeps('data-align="wide"'));
check('unknown alignment dropped', '<figure data-align="wherever"><img src="https://a.test/i.png" alt=""></figure>', drops('data-align'));
check('width kept in range', '<img src="https://a.test/i.png" width="60">', keeps('width="60"'));
check('width out of range dropped', '<img src="https://a.test/i.png" width="900">', drops('width'));
check('javascript image src removed', '<img src="javascript:alert(1)">', drops('<img'));
check('data image src removed', '<img src="data:image/svg+xml,<svg onload=alert(1)>">', drops('<img'));
check('onerror attribute stripped', '<img src="https://a.test/i.png" onerror="alert(1)">', drops('onerror'));
check(
    'allowlisted embed kept',
    '<figure class="embed"><iframe src="https://www.youtube.com/embed/abc123"></iframe></figure>',
    keeps('youtube.com/embed/abc123')
);
check('other host embed removed', '<iframe src="https://evil.test/x"></iframe>', drops('<iframe'));
check('embed class limited', '<figure class="site-header"><img src="https://a.test/i.png"></figure>', drops('site-header'));

echo "\nLinks\n";

check('http link kept', '<p><a href="https://a.test/x">x</a></p>', keeps('href="https://a.test/x"'));
check('link hardened', '<p><a href="https://a.test/x">x</a></p>', keeps('rel="noopener nofollow"'));
check('internal link kept', '<p><a href="/posts/12">x</a></p>', keeps('href="/posts/12"'));
check('mailto kept', '<p><a href="mailto:a@b.test">mail</a></p>', keeps('mailto:a@b.test'));
check('javascript link unwrapped, text kept', '<p><a href="javascript:alert(1)">click</a></p>', drops('javascript'));
check('javascript link keeps its words', '<p><a href="javascript:alert(1)">click</a></p>', keeps('click'));
check('protocol-relative link rejected', '<p><a href="//evil.test">x</a></p>', drops('evil.test'));

echo "\nThings that must not survive\n";

check('script removed with its contents', '<p>a</p><script>alert(1)</script>', drops('alert'));
check('style removed', '<style>body{display:none}</style><p>a</p>', drops('display'));
check('event handlers stripped', '<p onclick="alert(1)">text</p>', drops('onclick'));
check('inline styles stripped', '<p style="position:fixed;top:0">text</p>', drops('style'));
check('class on a paragraph stripped', '<p class="btn btn-primary">text</p>', drops('class'));
check('id stripped', '<p id="editorBody">text</p>', drops('id='));
check('form removed', '<form action="/x"><input name="a"></form>', drops('<form'));
check('svg removed', '<svg><use href="#x"></use></svg>', drops('<svg'));
check('comment removed', '<p>a</p><!-- sneaky -->', drops('sneaky'));
check('unknown tag unwrapped but text kept', '<p>see <marquee>this</marquee></p>', keeps('see this'));
check('span unwrapped', '<p><span style="color:red">red</span></p>', keeps('<p>red</p>'));
check('div becomes a paragraph', '<div>Block</div>', keeps('<p>Block</p>'));
check('nested div collapses', '<div><div>Deep</div></div>', drops('<div'));

echo "\nTidying up\n";

check('empty paragraph dropped', '<p>real</p><p></p>', drops('<p></p>'));
check('paragraph with only a break kept', '<p><br></p>', keeps('<br>'));
check('stray text wrapped in a paragraph', 'Loose words', keeps('<p>Loose words</p>'));
check('empty body returns nothing', '   ', static fn (string $out): bool => $out === '');
check('zero-width space left over from a code block is harmless', "<pre><code>\u{200b}x</code></pre>", keeps('x'));

echo "\nText, counts and legacy conversion\n";

$text = Html::toText('<h2>Title</h2><p>Two words</p>');
$sep  = $text === 'Title Two words';
echo $sep
    ? "  \033[32mok\033[0m   blocks separated when flattened to text\n"
    : "  \033[31mFAIL\033[0m blocks separated when flattened to text (got '{$text}')\n";
$sep ? $pass++ : $fail++;

$words = Html::wordCount('<p>one two three</p><p>four</p>');
$ok = $words === 4;
echo $ok
    ? "  \033[32mok\033[0m   word count ignores markup\n"
    : "  \033[31mFAIL\033[0m word count ignores markup (got {$words})\n";
$ok ? $pass++ : $fail++;

$legacy = Html::fromPlainText("First line\nsecond line\n\nNew paragraph");
$ok = str_contains($legacy, '<p>First line<br>second line</p>') && str_contains($legacy, '<p>New paragraph</p>');
echo $ok
    ? "  \033[32mok\033[0m   legacy text becomes paragraphs and breaks\n"
    : "  \033[31mFAIL\033[0m legacy text becomes paragraphs and breaks (got {$legacy})\n";
$ok ? $pass++ : $fail++;

$legacy = Html::fromPlainText('<script>alert(1)</script>');
$ok = !str_contains($legacy, '<script');
echo $ok
    ? "  \033[32mok\033[0m   legacy text is escaped, not trusted\n"
    : "  \033[31mFAIL\033[0m legacy text is escaped, not trusted (got {$legacy})\n";
$ok ? $pass++ : $fail++;

printf("\n%d passed, %d failed\n\n", $pass, $fail);

exit($fail === 0 ? 0 : 1);

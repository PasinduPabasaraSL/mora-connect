<?php

/**
 * A loose constellation of technology marks, used as a background band behind
 * the top of a prose page. Pure decoration.
 *
 * The marks are drawn here rather than fetched, because the project ships no
 * third-party assets — and they are generic tool glyphs (a terminal, a database,
 * a container, a git graph) rather than company logos, which keeps somebody
 * else's trademark out of the page furniture.
 *
 * Everything is stroked in theme tokens, so it follows light and dark mode
 * without a second copy.
 */
?>
<div class="tech-field" aria-hidden="true">
    <?php /* "meet" rather than "slice": the marks are placed deliberately at the
             left and right edges, and slicing to cover would crop exactly those
             off on a wide window. */ ?>
    <svg viewBox="0 0 1440 520" preserveAspectRatio="xMidYMin meet" focusable="false">
        <defs>
            <?php /* Each glyph is drawn once on a 24x24 grid and placed by <use>. */ ?>
            <symbol id="tf-code" viewBox="0 0 24 24">
                <path d="M9.5 8 6 11.5 9.5 15M14.5 8 18 11.5 14.5 15"/>
            </symbol>

            <symbol id="tf-braces" viewBox="0 0 24 24">
                <path d="M10 4.5C8 4.5 8 7 8 8s-1 3-2 4c1 1 2 3 2 4s0 2.5 2 2.5"/>
                <path d="M14 4.5c2 0 2 2.5 2 3.5s1 3 2 4c-1 1-2 3-2 4s0 2.5-2 2.5"/>
            </symbol>

            <symbol id="tf-database" viewBox="0 0 24 24">
                <ellipse cx="12" cy="6.5" rx="7" ry="2.8"/>
                <path d="M5 6.5v11c0 1.55 3.13 2.8 7 2.8s7-1.25 7-2.8v-11"/>
                <path d="M5 12c0 1.55 3.13 2.8 7 2.8s7-1.25 7-2.8"/>
            </symbol>

            <symbol id="tf-terminal" viewBox="0 0 24 24">
                <rect x="3" y="4" width="18" height="16" rx="2.5"/>
                <path d="M7 9.5 9.5 12 7 14.5M12.5 15H17"/>
            </symbol>

            <symbol id="tf-containers" viewBox="0 0 24 24">
                <rect x="8.5" y="3.5" width="7" height="7" rx="1.2"/>
                <rect x="3.5" y="13" width="7" height="7" rx="1.2"/>
                <rect x="13.5" y="13" width="7" height="7" rx="1.2"/>
            </symbol>

            <symbol id="tf-chip" viewBox="0 0 24 24">
                <rect x="7.5" y="7.5" width="9" height="9" rx="1.5"/>
                <path d="M10 4v3.5M14 4v3.5M10 16.5V20M14 16.5V20M4 10h3.5M4 14h3.5M16.5 10H20M16.5 14H20"/>
            </symbol>

            <symbol id="tf-cloud" viewBox="0 0 24 24">
                <path d="M7 18a4 4 0 0 1 .4-8 5.2 5.2 0 0 1 9.8-1.1A3.9 3.9 0 0 1 17.5 18z"/>
            </symbol>

            <symbol id="tf-branch" viewBox="0 0 24 24">
                <circle cx="7" cy="6" r="2.3"/>
                <circle cx="7" cy="18" r="2.3"/>
                <circle cx="17" cy="10" r="2.3"/>
                <path d="M7 8.3v7.4M7 13h4.5A5 5 0 0 0 15.3 11"/>
            </symbol>

            <symbol id="tf-lock" viewBox="0 0 24 24">
                <rect x="5" y="10.5" width="14" height="9.5" rx="2.2"/>
                <path d="M8.5 10.5V8a3.5 3.5 0 0 1 7 0v2.5"/>
            </symbol>

            <symbol id="tf-network" viewBox="0 0 24 24">
                <circle cx="5.5" cy="7" r="2"/>
                <circle cx="5.5" cy="17" r="2"/>
                <circle cx="18.5" cy="12" r="2"/>
                <path d="M7.4 8 16.7 11.3M7.4 16l9.3-3.3"/>
            </symbol>

            <symbol id="tf-mobile" viewBox="0 0 24 24">
                <rect x="7" y="3" width="10" height="18" rx="2.2"/>
                <path d="M10.5 18h3"/>
            </symbol>

            <symbol id="tf-package" viewBox="0 0 24 24">
                <path d="M12 3.2 20 7.6v8.8L12 20.8 4 16.4V7.6z"/>
                <path d="M4 7.6 12 12l8-4.4M12 12v8.8"/>
            </symbol>

            <symbol id="tf-brackets" viewBox="0 0 24 24">
                <path d="M10.5 4.5H7v15h3.5M13.5 4.5H17v15h-3.5"/>
            </symbol>

            <symbol id="tf-gear" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="3"/>
                <path d="M12 4v2.4M12 17.6V20M4 12h2.4M17.6 12H20M6.3 6.3l1.7 1.7M16 16l1.7 1.7M17.7 6.3 16 8M8 16l-1.7 1.7"/>
            </symbol>
        </defs>

        <?php /* Threads between neighbouring marks, so the field reads as a
                 connected system rather than scattered stickers. Same idea as
                 the homepage diagram, which is what ties the two pages
                 together. */ ?>
        <g class="tf-threads">
            <path d="M88 116 L178 178 L104 278"/>
            <path d="M178 178 L326 258"/>
            <path d="M298 118 L326 258"/>
            <path d="M1258 188 L1368 98"/>
            <path d="M1258 188 L1158 328 L1278 408"/>
            <path d="M1388 288 L1258 188"/>
            <path d="M728 458 L1008 428"/>
        </g>

        <g class="tf-marks">
            <?php /* Left cluster */ ?>
            <g transform="translate(60 88) rotate(-5 28 28)">
                <rect width="56" height="56" rx="16"/>
                <use href="#tf-code" x="15" y="15" width="26" height="26"/>
            </g>
            <g transform="translate(150 150) rotate(4 28 28)">
                <rect width="56" height="56" rx="16"/>
                <use href="#tf-braces" x="15" y="15" width="26" height="26"/>
            </g>
            <g transform="translate(76 250) rotate(6 28 28)">
                <rect width="56" height="56" rx="16"/>
                <use href="#tf-database" x="15" y="15" width="26" height="26"/>
            </g>
            <g transform="translate(186 330) rotate(-3 28 28)">
                <rect width="56" height="56" rx="16"/>
                <use href="#tf-terminal" x="15" y="15" width="26" height="26"/>
            </g>
            <g class="tf-accent" transform="translate(270 90) rotate(-7 28 28)">
                <rect width="56" height="56" rx="16"/>
                <text x="28" y="34">PHP</text>
            </g>
            <g transform="translate(298 230) rotate(5 28 28)">
                <rect width="56" height="56" rx="16"/>
                <use href="#tf-branch" x="15" y="15" width="26" height="26"/>
            </g>

            <?php /* Right cluster */ ?>
            <g transform="translate(1340 70) rotate(6 28 28)">
                <rect width="56" height="56" rx="16"/>
                <use href="#tf-containers" x="15" y="15" width="26" height="26"/>
            </g>
            <g transform="translate(1230 160) rotate(-4 28 28)">
                <rect width="56" height="56" rx="16"/>
                <use href="#tf-chip" x="15" y="15" width="26" height="26"/>
            </g>
            <g transform="translate(1360 260) rotate(3 28 28)">
                <rect width="56" height="56" rx="16"/>
                <use href="#tf-cloud" x="15" y="15" width="26" height="26"/>
            </g>
            <g transform="translate(1130 300) rotate(-6 28 28)">
                <rect width="56" height="56" rx="16"/>
                <use href="#tf-lock" x="15" y="15" width="26" height="26"/>
            </g>
            <g class="tf-accent" transform="translate(1250 380) rotate(5 28 28)">
                <rect width="56" height="56" rx="16"/>
                <text x="28" y="34">SQL</text>
            </g>
            <g transform="translate(1076 34) rotate(-3 28 28)">
                <rect width="56" height="56" rx="16"/>
                <use href="#tf-network" x="15" y="15" width="26" height="26"/>
            </g>

            <?php /* A thin scatter across the middle, kept clear of the band the
                     headline occupies so it never sits behind the type. */ ?>
            <g transform="translate(600 18) rotate(4 28 28)">
                <rect width="56" height="56" rx="16"/>
                <text x="28" y="34">JS</text>
            </g>
            <g transform="translate(852 26) rotate(-5 28 28)">
                <rect width="56" height="56" rx="16"/>
                <use href="#tf-brackets" x="15" y="15" width="26" height="26"/>
            </g>
            <g transform="translate(700 430) rotate(-4 28 28)">
                <rect width="56" height="56" rx="16"/>
                <use href="#tf-mobile" x="15" y="15" width="26" height="26"/>
            </g>
            <g transform="translate(980 400) rotate(6 28 28)">
                <rect width="56" height="56" rx="16"/>
                <use href="#tf-package" x="15" y="15" width="26" height="26"/>
            </g>
            <g transform="translate(468 448) rotate(3 28 28)">
                <rect width="56" height="56" rx="16"/>
                <use href="#tf-gear" x="15" y="15" width="26" height="26"/>
            </g>
            <g class="tf-accent" transform="translate(388 372) rotate(-6 28 28)">
                <rect width="56" height="56" rx="16"/>
                <text x="28" y="34">CSS</text>
            </g>
        </g>
    </svg>
</div>

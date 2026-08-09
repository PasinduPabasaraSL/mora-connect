<?php

/**
 * Soft network diagram for the homepage hero. Pure decoration — no interaction,
 * no data. Topic colours match the category tokens so it feels part of the site
 * rather than a random illustration.
 */
?>
<svg class="hero-diagram" viewBox="0 0 480 400" xmlns="http://www.w3.org/2000/svg"
     role="img" aria-hidden="true" focusable="false">
    <?php /* Edges first so nodes sit on top */ ?>
    <g class="edges" fill="none" stroke-linecap="round">
        <path d="M96 88 L180 140 L280 96 L360 150"/>
        <path d="M180 140 L200 230 L300 260"/>
        <path d="M280 96 L300 260 L390 210"/>
        <path d="M96 88 L120 210 L200 230"/>
        <path d="M120 210 L80 300 L200 320"/>
        <path d="M200 230 L200 320 L320 340"/>
        <path d="M300 260 L320 340"/>
        <path d="M360 150 L420 100"/>
        <path d="M390 210 L420 100"/>
        <path d="M80 300 L40 220 L96 88" opacity="0.55"/>
    </g>

    <?php /* Topic-coloured nodes — Web Dev, DevOps, ML, Databases, Security, Systems, Mobile */ ?>
    <g class="nodes">
        <circle class="node n-web"    cx="96"  cy="88"  r="11"/>
        <circle class="node n-devops" cx="180" cy="140" r="9"/>
        <circle class="node n-ml"     cx="280" cy="96"  r="12"/>
        <circle class="node n-db"     cx="360" cy="150" r="9"/>
        <circle class="node n-sec"    cx="200" cy="230" r="10"/>
        <circle class="node n-sys"    cx="300" cy="260" r="11"/>
        <circle class="node n-mobile" cx="120" cy="210" r="8"/>
        <circle class="node n-web"    cx="390" cy="210" r="8"/>
        <circle class="node n-db"     cx="80"  cy="300" r="9"/>
        <circle class="node n-ml"     cx="200" cy="320" r="8"/>
        <circle class="node n-devops" cx="320" cy="340" r="10"/>
        <circle class="node n-sec"    cx="420" cy="100" r="7"/>
        <circle class="node n-sys"    cx="40"  cy="220" r="6"/>

        <?php /* Soft rings on a couple of hubs so the graph has hierarchy.
                 The pulsing one is wrapped in a group because CSS scale on a
                 bare <circle> is unreliable across browsers. */ ?>
        <circle class="ring" cx="280" cy="96"  r="22"/>
        <circle class="ring" cx="200" cy="230" r="20"/>
        <g class="pulse-wrap">
            <circle class="ring pulse" cx="300" cy="260" r="24"/>
        </g>
    </g>

    <?php /* Tiny topic labels — kept short so they read as legend, not clutter */ ?>
    <g class="labels">
        <text x="96"  y="68">web</text>
        <text x="280" y="74">ml</text>
        <text x="300" y="288">systems</text>
        <text x="200" y="256">security</text>
        <text x="360" y="176">data</text>
    </g>
</svg>

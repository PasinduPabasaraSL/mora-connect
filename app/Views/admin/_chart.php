<?php

/**
 * A monthly column chart, drawn as inline SVG.
 *
 * Hand-drawn rather than pulled from a charting library: twelve columns is a
 * loop and some arithmetic, and the panel is not worth a dependency the rest of
 * the project does not have.
 *
 * @var array<string, int> $series label (YYYY-MM) => count
 * @var string             $label  described to screen readers
 */

$series = $series ?? [];
$label  = $label ?? 'Monthly totals';

$count = count($series);
$peak  = $count === 0 ? 0 : max($series);

// A flat run of zeroes still needs a scale, or every bar divides by nothing
$scale = max(1, $peak);

$width   = 760;
$height  = 200;
$footer  = 30;   // room for the month labels
$ceiling = 26;   // room for the value above the tallest column

$plot = $height - $footer - $ceiling;
$slot = $count === 0 ? $width : $width / $count;
$barWidth = min(44.0, $slot * 0.5);
?>
<div class="admin-chart">
    <?php if ($peak === 0): ?>
        <p class="admin-chart-empty">Nothing recorded in the last <?= (int) $count ?> months.</p>
    <?php else: ?>
        <?php /* Scales uniformly, so the labels are never stretched sideways */ ?>
        <svg viewBox="0 0 <?= $width ?> <?= $height ?>" role="img" aria-label="<?= e($label) ?>">
            <line class="admin-chart-base" x1="0" y1="<?= $height - $footer ?>"
                  x2="<?= $width ?>" y2="<?= $height - $footer ?>"></line>

            <?php $index = 0; ?>
            <?php foreach ($series as $month => $value): ?>
                <?php
                $barHeight = $value === 0 ? 0 : max(3.0, ($value / $scale) * $plot);
                $x = ($index * $slot) + (($slot - $barWidth) / 2);
                $y = $height - $footer - $barHeight;

                // Crowded at twelve columns, so only every other month is named
                $showMonth = $count <= 6 || $index % 2 === 1;
                $stamp = (int) strtotime($month . '-01');
                ?>

                <?php if ($barHeight > 0): ?>
                    <rect class="admin-chart-bar" x="<?= round($x, 2) ?>" y="<?= round($y, 2) ?>"
                          width="<?= round($barWidth, 2) ?>" height="<?= round($barHeight, 2) ?>"
                          rx="4"></rect>

                    <text class="admin-chart-value" x="<?= round($x + ($barWidth / 2), 2) ?>"
                          y="<?= round($y - 8, 2) ?>" text-anchor="middle"><?= (int) $value ?></text>
                <?php endif; ?>

                <?php if ($showMonth): ?>
                    <text class="admin-chart-month" x="<?= round(($index * $slot) + ($slot / 2), 2) ?>"
                          y="<?= $height - 10 ?>" text-anchor="middle"><?= e(date('M', $stamp)) ?></text>
                <?php endif; ?>

                <?php $index++; ?>
            <?php endforeach; ?>
        </svg>
    <?php endif; ?>
</div>

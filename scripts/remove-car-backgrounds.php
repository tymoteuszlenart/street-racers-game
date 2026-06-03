<?php

declare(strict_types=1);

/**
 * Remove studio backgrounds from car PNGs.
 *
 * Uses edge-connected flood fill against the median neutral border color.
 * Only low-saturation pixels close to the sampled background are removed,
 * so dark or brightly colored car body pixels are preserved.
 *
 * Usage: php scripts/remove-car-backgrounds.php [directory]
 */
$directory = $argv[1] ?? 'public/cars';
$threshold = 45.0;
$saturationLimit = 55;
$padding = 8;

$files = glob(rtrim($directory, '/').'/*.png') ?: [];

if ($files === []) {
    fwrite(STDERR, "No PNG files found in {$directory}\n");
    exit(1);
}

foreach ($files as $file) {
    if (str_contains(basename($file), '.preview-')) {
        continue;
    }

    $result = processImage($file, $file, $threshold, $saturationLimit, $padding);
    printf(
        "%s: bg=[%d,%d,%d] removed=%d kept=%d size=%dx%d\n",
        basename($file),
        $result['bg'][0],
        $result['bg'][1],
        $result['bg'][2],
        $result['removed'],
        $result['kept'],
        $result['width'],
        $result['height'],
    );
}

function sampleBackgroundColor(GdImage $src, int $width, int $height): array
{
    $reds = $greens = $blues = [];

    for ($x = 0; $x < $width; $x++) {
        foreach ([0, $height - 1] as $y) {
            collectNeutralSample($src, $x, $y, $reds, $greens, $blues);
        }
    }

    for ($y = 0; $y < $height; $y++) {
        foreach ([0, $width - 1] as $x) {
            collectNeutralSample($src, $x, $y, $reds, $greens, $blues);
        }
    }

    sort($reds);
    sort($greens);
    sort($blues);

    $mid = (int) floor(count($reds) / 2);

    return [
        $reds[$mid] ?? 190,
        $greens[$mid] ?? 190,
        $blues[$mid] ?? 190,
    ];
}

function collectNeutralSample(GdImage $src, int $x, int $y, array &$reds, array &$greens, array &$blues): void
{
    $rgb = imagecolorat($src, $x, $y);
    $r = ($rgb >> 16) & 0xFF;
    $g = ($rgb >> 8) & 0xFF;
    $b = $rgb & 0xFF;

    if (max($r, $g, $b) - min($r, $g, $b) <= 45) {
        $reds[] = $r;
        $greens[] = $g;
        $blues[] = $b;
    }
}

function colorDistance(int $r, int $g, int $b, array $bg): float
{
    return sqrt(
        ($r - $bg[0]) ** 2 +
        ($g - $bg[1]) ** 2 +
        ($b - $bg[2]) ** 2
    );
}

function isBackgroundCandidate(int $rgb, array $bg, float $threshold, int $saturationLimit): bool
{
    $r = ($rgb >> 16) & 0xFF;
    $g = ($rgb >> 8) & 0xFF;
    $b = $rgb & 0xFF;

    if (max($r, $g, $b) - min($r, $g, $b) > $saturationLimit) {
        return false;
    }

    return colorDistance($r, $g, $b, $bg) <= $threshold;
}

function processImage(
    string $input,
    string $output,
    float $threshold,
    int $saturationLimit,
    int $padding,
): array {
    $src = imagecreatefrompng($input);
    $width = imagesx($src);
    $height = imagesy($src);
    $bg = sampleBackgroundColor($src, $width, $height);

    $queue = new SplQueue;
    $visited = array_fill(0, $width * $height, false);
    $background = array_fill(0, $width * $height, false);

    $enqueue = function (int $x, int $y) use (&$queue, &$visited, $width, $height): void {
        if ($x < 0 || $y < 0 || $x >= $width || $y >= $height) {
            return;
        }

        $idx = $y * $width + $x;
        if ($visited[$idx]) {
            return;
        }

        $visited[$idx] = true;
        $queue->enqueue([$x, $y]);
    };

    for ($x = 0; $x < $width; $x++) {
        $enqueue($x, 0);
        $enqueue($x, $height - 1);
    }
    for ($y = 0; $y < $height; $y++) {
        $enqueue(0, $y);
        $enqueue($width - 1, $y);
    }

    while (! $queue->isEmpty()) {
        [$x, $y] = $queue->dequeue();
        $idx = $y * $width + $x;
        $rgb = imagecolorat($src, $x, $y);

        if (! isBackgroundCandidate($rgb, $bg, $threshold, $saturationLimit)) {
            continue;
        }

        $background[$idx] = true;

        foreach ([[1, 0], [-1, 0], [0, 1], [0, -1]] as [$dx, $dy]) {
            $enqueue($x + $dx, $y + $dy);
        }
    }

    $out = imagecreatetruecolor($width, $height);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    $transparent = imagecolorallocatealpha($out, 0, 0, 0, 127);
    imagefill($out, 0, 0, $transparent);

    $minX = $width;
    $minY = $height;
    $maxX = -1;
    $maxY = -1;
    $kept = 0;
    $removed = 0;

    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $idx = $y * $width + $x;

            if ($background[$idx]) {
                $removed++;

                continue;
            }

            $rgb = imagecolorat($src, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            $color = imagecolorallocatealpha($out, $r, $g, $b, 0);
            imagesetpixel($out, $x, $y, $color);
            $kept++;
            $minX = min($minX, $x);
            $minY = min($minY, $y);
            $maxX = max($maxX, $x);
            $maxY = max($maxY, $y);
        }
    }

    $cropX = max(0, $minX - $padding);
    $cropY = max(0, $minY - $padding);
    $cropW = min($width, $maxX + $padding + 1) - $cropX;
    $cropH = min($height, $maxY + $padding + 1) - $cropY;
    $cropped = imagecrop($out, ['x' => $cropX, 'y' => $cropY, 'width' => $cropW, 'height' => $cropH]);
    imagealphablending($cropped, false);
    imagesavealpha($cropped, true);
    imagepng($cropped, $output);

    return [
        'bg' => $bg,
        'removed' => $removed,
        'kept' => $kept,
        'width' => $cropW,
        'height' => $cropH,
    ];
}

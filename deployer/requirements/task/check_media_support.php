<?php

declare(strict_types=1);

namespace Deployer;

use Deployer\Exception\RunException;

task('requirements:check:media_support', function (): void {
    if (!get('requirements_check_media_support_enabled')) {
        return;
    }

    checkGdFormatSupport();
    checkImageToolFormatSupport();
    checkBrotliExtension();
})->hidden();

function checkGdFormatSupport(): void
{
    $formats = ['AVIF' => 'AVIF Support', 'WebP' => 'WebP Support'];

    try {
        $gdJson = run('php -r "echo function_exists(\'gd_info\') ? json_encode(gd_info()) : \'null\';" 2>/dev/null');
    } catch (RunException) {
        foreach ($formats as $label => $key) {
            addRequirementRow("GD: $label Support", REQUIREMENT_SKIP, 'Could not query GD');
        }

        return;
    }

    $gdInfo = json_decode($gdJson, true);

    if (!is_array($gdInfo)) {
        foreach ($formats as $label => $key) {
            addRequirementRow("GD: $label Support", REQUIREMENT_SKIP, 'GD not available');
        }

        return;
    }

    foreach ($formats as $label => $key) {
        $supported = !empty($gdInfo[$key]);
        addRequirementRow(
            "GD: $label Support",
            $supported ? REQUIREMENT_OK : REQUIREMENT_WARN,
            $supported ? 'Supported' : 'Not compiled into GD'
        );
    }
}

function checkImageToolFormatSupport(): void
{
    $formats = ['AVIF' => 'AVIF', 'WEBP' => 'WebP'];
    $formatList = null;
    $toolLabel = null;

    // Try GraphicsMagick first
    try {
        $output = run('gm convert -list format 2>/dev/null');

        if ('' !== trim($output)) {
            $formatList = $output;
            $toolLabel = 'GraphicsMagick';
        }
    } catch (RunException) {
        // not available
    }

    // Fallback to ImageMagick
    if (null === $formatList) {
        foreach (['magick', 'convert'] as $imCommand) {
            try {
                $output = run("$imCommand -list format 2>/dev/null");

                if ('' !== trim($output)) {
                    $formatList = $output;
                    $toolLabel = 'ImageMagick';

                    break;
                }
            } catch (RunException) {
                continue;
            }
        }
    }

    if (null === $formatList || null === $toolLabel) {
        foreach ($formats as $label) {
            addRequirementRow("IM/GM: $label", REQUIREMENT_SKIP, 'No image processing tool found');
        }

        return;
    }

    foreach ($formats as $formatKey => $label) {
        $supported = (bool) preg_match('/^\s*' . $formatKey . '\b/mi', $formatList);
        addRequirementRow(
            "IM/GM: $label",
            $supported ? REQUIREMENT_OK : REQUIREMENT_WARN,
            $supported ? "Supported ($toolLabel)" : "Format not supported ($toolLabel)"
        );
    }
}

function checkBrotliExtension(): void
{
    try {
        $modules = strtolower(run('php -m 2>/dev/null'));
    } catch (RunException) {
        addRequirementRow('PHP ext: brotli', REQUIREMENT_SKIP, 'Could not retrieve PHP modules');

        return;
    }

    $loaded = in_array('brotli', array_map('trim', explode("\n", $modules)), true);
    addRequirementRow(
        'PHP ext: brotli',
        $loaded ? REQUIREMENT_OK : REQUIREMENT_WARN,
        $loaded ? 'Loaded' : 'Not loaded'
    );
}

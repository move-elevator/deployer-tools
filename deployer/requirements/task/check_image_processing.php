<?php

declare(strict_types=1);

namespace Deployer;

use Deployer\Exception\RunException;

task('requirements:check:image_processing', function (): void {
    if (!get('requirements_check_image_processing_enabled')) {
        return;
    }

    $gmMinVersion = get('requirements_graphicsmagick_min_version');
    $imMinVersion = get('requirements_imagemagick_min_version');
    $gmVersionFail = false;

    // Try GraphicsMagick first (recommended)
    try {
        $gmOutput = trim(run('gm version 2>/dev/null | head -1'));

        if ($gmOutput !== '' && preg_match('/GraphicsMagick\s+([\d.]+)/', $gmOutput, $matches)) {
            $actualVersion = $matches[1];
            $meets = version_compare($actualVersion, $gmMinVersion, '>=');

            if ($meets) {
                addRequirementRow('Image processing', REQUIREMENT_OK, "GraphicsMagick $actualVersion");

                return;
            }

            $gmVersionFail = "GraphicsMagick $actualVersion (required: >= $gmMinVersion)";
        }
    } catch (RunException) {
        // GraphicsMagick not available, try ImageMagick
    }

    // Fallback to ImageMagick (magick for IM7+, convert for legacy)
    foreach (['magick', 'convert'] as $imCommand) {
        try {
            $imOutput = trim(run("$imCommand -version 2>/dev/null | head -1"));

            if ($imOutput !== '' && preg_match('/ImageMagick\s+([\d.]+)/', $imOutput, $matches)) {
                $actualVersion = $matches[1];
                $meets = version_compare($actualVersion, $imMinVersion, '>=');
                $info = $meets
                    ? "ImageMagick $actualVersion"
                    : "ImageMagick $actualVersion (required: >= $imMinVersion)";
                addRequirementRow('Image processing', $meets ? REQUIREMENT_OK : REQUIREMENT_FAIL, $info);

                return;
            }
        } catch (RunException) {
            continue;
        }
    }

    addRequirementRow(
        'Image processing',
        REQUIREMENT_FAIL,
        $gmVersionFail !== false
            ? "$gmVersionFail — ImageMagick (>= $imMinVersion) not found"
            : "Neither GraphicsMagick (>= $gmMinVersion) nor ImageMagick (>= $imMinVersion) found"
    );
})->hidden();

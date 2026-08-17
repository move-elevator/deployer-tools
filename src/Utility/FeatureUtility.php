<?php

declare(strict_types=1);

namespace MoveElevator\DeployerTools\Utility;

/**
 * Normalizes feature identifiers into a name that is safe to use as a directory,
 * URL segment, symlink and database name suffix.
 */
final class FeatureUtility
{
    /**
     * Turn a feature identifier (typically a git branch name) into a flat instance name.
     *
     * Path separators become hyphens instead of being dropped, so "feature/ABC-12" and
     * "bugfix/ABC-12" stay distinct instances. Names that already consist of allowed
     * characters only are returned unchanged, which keeps existing instances and their
     * databases reachable.
     *
     * @throws \InvalidArgumentException if a non-blank identifier normalizes to an empty
     *                                   name or to a relative path segment, either of which
     *                                   would silently address the base instance
     */
    public static function normalize(?string $feature): string
    {
        $feature = trim((string) $feature);

        if ('' === $feature) {
            return '';
        }

        $normalized = str_replace(['/', '\\'], '-', $feature);
        $normalized = (string) preg_replace('/[^A-Za-z0-9_\-.]/', '', $normalized);

        // "" would resolve to the base instance, "." and ".." to it or its parent
        if ('' === trim($normalized, '.')) {
            throw new \InvalidArgumentException(
                sprintf('The feature name "%s" does not yield a usable instance name.', $feature)
            );
        }

        return $normalized;
    }
}

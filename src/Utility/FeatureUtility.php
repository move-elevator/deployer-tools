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
     * @param bool $hostnameSafe additionally tighten the result into a single valid DNS
     *                           hostname label, for feature_url_pattern (subdomain mode).
     *                           Only ever pass true for the feature part of a name, never
     *                           for a database/project prefix that predates subdomain mode.
     * @throws \InvalidArgumentException if a non-blank identifier normalizes to an empty
     *                                   name or to a relative path segment, either of which
     *                                   would silently address the base instance
     */
    public static function normalize(?string $feature, bool $hostnameSafe = false): string
    {
        $feature = trim((string) $feature);

        if ('' === $feature) {
            return '';
        }

        $normalized = str_replace(['/', '\\'], '-', $feature);
        $normalized = (string) preg_replace('/[^A-Za-z0-9_\-.]/', '', $normalized);

        if ($hostnameSafe) {
            $normalized = self::toHostnameLabel($normalized, $feature);
        }

        // "" would resolve to the base instance, "." and ".." to it or its parent
        if ('' === trim($normalized, '.')) {
            throw new \InvalidArgumentException(
                sprintf('The feature name "%s" does not yield a usable instance name.', $feature)
            );
        }

        return $normalized;
    }

    /**
     * Tighten an already flattened name into a single DNS hostname label: lowercase,
     * "a-z0-9-" only, no leading, trailing or duplicate hyphens, at most 63 characters
     * (the DNS label limit). A name that would exceed the limit is truncated and given a
     * short hash suffix derived from the original identifier, so it stays both valid and
     * stable across repeated deploys of the same (long) branch name.
     */
    private static function toHostnameLabel(string $normalized, string $original): string
    {
        $normalized = strtolower($normalized);
        $normalized = str_replace(['.', '_'], '-', $normalized);
        $normalized = (string) preg_replace('/[^a-z0-9-]/', '', $normalized);
        $normalized = (string) preg_replace('/-+/', '-', $normalized);
        $normalized = trim($normalized, '-');

        if (strlen($normalized) > 63) {
            $hash = substr(md5($original), 0, 8);
            $normalized = rtrim(substr($normalized, 0, 63 - 1 - strlen($hash)), '-') . '-' . $hash;
        }

        return $normalized;
    }
}

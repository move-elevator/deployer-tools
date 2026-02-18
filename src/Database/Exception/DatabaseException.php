<?php

declare(strict_types=1);

namespace MoveElevator\DeployerTools\Database\Exception;

class DatabaseException extends \RuntimeException
{
    public static function connectionFailed(string $message, ?\Throwable $previous = null): self
    {
        return new self("Database connection failed: {$message}", 0, $previous);
    }

    public static function queryFailed(string $query, string $message, ?\Throwable $previous = null): self
    {
        return new self("Database query failed [{$query}]: {$message}", 0, $previous);
    }

    public static function configurationMissing(string $parameter): self
    {
        return new self("Database configuration parameter missing: {$parameter}");
    }

    public static function managerNotFound(string $type): self
    {
        return new self("Database manager type '{$type}' is not supported");
    }

    public static function poolNotConfigured(): self
    {
        return new self("Database pool is not defined. Set 'database_pool' in your configuration");
    }

    public static function noFreeDatabases(): self
    {
        return new self("No free databases available. Check your pool or cleanup unused assignments");
    }
}

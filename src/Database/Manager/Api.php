<?php

namespace MoveElevator\DeployerTools\Database\Manager;

/**
 * Database Management "API"
 *
 * ToDo
 */
class Api extends AbstractManager implements ManagerInterface
{
    public function create(): void
    {
        throw new \RuntimeException('Not implemented yet.');
    }

    public function delete(string $feature): void
    {
        throw new \RuntimeException('Not implemented yet.');
    }

    public function exists(?string $feature = null): bool
    {
        throw new \RuntimeException('Not implemented yet.');
    }
}

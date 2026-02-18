<?php

declare(strict_types=1);

namespace MoveElevator\DeployerTools\Database\Manager;

interface ManagerInterface
{
    public function run(string $command): string;
    public function create(): void;
    public function delete(string $feature): void;
    public function exists(?string $feature = null): bool;
}

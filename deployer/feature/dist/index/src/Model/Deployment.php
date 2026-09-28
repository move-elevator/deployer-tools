<?php

namespace MoveElevator\FeatureIndex\Model;

final class Deployment
{
    public function __construct(
        public readonly int $timestamp,
        public readonly string $user = '',
        public readonly string $release = '',
        // user from .dep/deploy.lock, null while no deployment is running (or aborted)
        public readonly ?string $lockedBy = null,
    ) {
    }
}

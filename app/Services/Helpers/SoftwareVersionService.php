<?php

namespace App\Services\Helpers;

class SoftwareVersionService
{
    public function latestPanelVersionChangelog(): string
    {
        return '';
    }

    public function latestPanelVersion(): string
    {
        return '1.0.0';
    }

    public function latestWingsVersion(): string
    {
        return '1.0.0';
    }

    public function isLatestPanel(): bool
    {
        return true;
    }

    public function isLatestWings(string $version): bool
    {
        return true;
    }

    public function currentPanelVersion(): string
    {
        return '1.0.0';
    }
}

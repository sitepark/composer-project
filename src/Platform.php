<?php

declare(strict_types=1);

namespace SP\Composer\Project;

use Composer\InstalledVersions;

class Platform
{
    /**
     * @return array<string>
     */
    public function getInstalledPackages(): array
    {
        return InstalledVersions::getInstalledPackages();
    }

    public function getInstalledPackageVersion(string $packageName): string
    {
        return InstalledVersions::getVersion($packageName);
    }
}

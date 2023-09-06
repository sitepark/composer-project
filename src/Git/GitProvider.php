<?php

declare(strict_types=1);

namespace SP\Composer\Project\Git;

interface GitProvider
{
    public function getCurrentBranch(): string;

    public function updateTags(): void;

    /**
     * @return string[]
     */
    public function getBranches(): array;

    /**
     * @return string[]
     */
    public function getVersions(): array;

    /**
     * @return string[]
     */
    public function getVersionsFromMajor(int $major): array;

    public function isDev(): bool;

    public function isRelease(): bool;
}

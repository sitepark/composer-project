<?php

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

    public function isDev(): bool;
}

<?php

declare(strict_types=1);

namespace SP\Composer\Project\Git;

class StandardGitProvider implements GitProvider
{
    private Executor $executor;

    public function __construct(Executor $executor)
    {
        $this->executor = $executor;
    }

    /**
     * @return string
     */
    public function getCurrentBranch(): string
    {
        $branch = $this->executor->exec('git rev-parse --abbrev-ref HEAD');
        if (empty($branch)) {
            return '';
        }
        return $branch[0];
    }

    /**
     * @return string[]
     */
    public function getBranches(): array
    {
        return $this->executor->exec("git for-each-ref --format='%(refname:short)' refs/heads/**");
    }

    /**
     * @return void
     */
    public function updateTags(): void
    {
        /**
         * When loading the project, the latest tags are now loaded from the Git remote.
         * This ensures that when determining the next hotfix version
         * all necessary information is available in the local repository.
         */
        $this->executor->exec('git fetch --tags');
    }

    /**
     * @return string[]
     */
    public function getVersions(): array
    {
        $this->updateTags();
        return $this->executor->exec('git tag -l --sort=v:refname \'[0-9]*.[0-9]*.[0-9]*\'');
    }

    /**
     * @return string[]
     */
    public function getVersionsFromMajor(int $major): array
    {
        $this->updateTags();
        return $this->executor->exec('git tag -l --sort=v:refname \'' . $major . '.[0-9]*.[0-9]*\'');
    }

    /**
     * @return string[]
     */
    public function getVersionsFromMinor(int $major, int $minor): array
    {
        $this->updateTags();
        return $this->executor->exec('git tag -l --sort=v:refname \'' . $major . '.' . $minor . '.[0-9]*\'');
    }

    public function isDev(): bool
    {
        $version = $this->executor->exec('git tag -l --points-at HEAD');
        return empty($version);
    }

    public function isRelease(): bool
    {
        $version = $this->executor->exec('git tag -l --points-at HEAD');
        return !empty($version);
    }
}

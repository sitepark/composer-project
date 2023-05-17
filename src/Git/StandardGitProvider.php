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
         * Beim Laden des Projektes werden nun die neusten Tags vom Git Remote geladen.
         * Damit wird sichergestellt, dass beim ermitteln der nächsten Hotfix-Version
         * alle nötigen Informationen im lokalen Repository vorhanden sind.
         */
        $this->executor->exec('git fetch --tags');
    }

    /**
     * @return string[]
     */
    public function getVersions(): array
    {
        $this->updateTags();
        // Wenn HEAD auf einen TAG zeigt
        $versions = $this->executor->exec('git tag -l --points-at HEAD');
        if (empty($versions)) {
            // Wenn HEAD develop ist
            $versions = $this->executor->exec('git --no-pager tag --sort=v:refname');
        }
        return $versions;
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

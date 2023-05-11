<?php

namespace SP\Composer\Project\Git;

class StandardGitProvider implements GitProvider
{
    /**
     * @return string
     */
    public function getCurrentBranch(): string
    {
        return (string) exec('git rev-parse --abbrev-ref HEAD');
    }

    /**
     * @return string[]
     */
    public function getBranches(): array
    {
        $branches = [];
        exec("git for-each-ref --format='%(refname:short)' refs/heads/*", $branches);
        return $branches;
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
        exec('git fetch --tags');
    }

    /**
     * @return string[]
     */
    public function getVersions(): array
    {
        $this->updateTags();
        $versions = [];
        // Wenn HEAD auf einen TAG zeigt
        exec('git tag -l --points-at HEAD', $versions);
        if (empty($versions)) {
            // Wenn HEAD develop ist
            exec('git --no-pager tag --sort=v:refname', $versions);
        }
        return $versions;
    }

    public function isDev(): bool
    {
        $version = exec('git tag -l --points-at HEAD');
        return empty($version);
    }
}

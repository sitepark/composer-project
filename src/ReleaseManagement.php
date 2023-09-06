<?php

declare(strict_types=1);

namespace SP\Composer\Project;

use SP\Composer\Project\Git\Executor;

class ReleaseManagement
{
    private Project $project;

    private Executor $executor;

    public function __construct(Project $project, Executor $executor)
    {
        $this->project = $project;
        $this->executor = $executor;
    }

    public function verifyRelease(): void
    {
        $unstable = $this->project->getUnstableDependencies([
            // See: https://github.com/Roave/SecurityAdvisories#stability
            'roave/security-advisories'
        ]);
        if (count($unstable) > 0) {
            throw new \RuntimeException('There are unstable dependencies:' . "\n\n" . implode("\n", $unstable));
        }
    }

    public function hasUncommittedChanges(): bool
    {
        $result = $this->executor->exec('git status --short --untracked-files=no');
        return !empty($result);
    }

    /**
     * Checks whether the current working tree contains
     * uncommited changes. If it does, an Exception is thrown
     *
     *  @param string $msg Message of the Exception that may be thrown
     */
    public function assertNoUncommittedChanges(string $msg): void
    {
        if ($this->hasUncommittedChanges()) {
            $this->executor->exec('git status --porcelain --untracked-files=no');
            throw new \RuntimeException($msg);
        }
    }

    public function startHotfix(): string
    {
        if (!$this->project->isRelease()) {
            throw new \RuntimeException(
                "A hotfix can only be created on the basis of a release. " .
                "The current Git state is not a checked out tag. Current Branch: " .
                $this->project->getBranch()
            );
        }
        $releaseVersion = $this->project->getLatestMainRelease();
        if ($releaseVersion === null) {
            throw new \RuntimeException('There is no release yet for which a hotfix can be created.');
        }
        $hotfixVersion = $this->project->getNextReleaseVersion();
        [$major, $minor] = explode('.', $hotfixVersion);
        $hotfixBranch = 'hotfix/' . $major . '.' . $minor . '.x';
        $this->executor->exec('git checkout -b ' . $hotfixBranch . ' ' . $releaseVersion);
        $this->executor->exec('git push origin ' . $hotfixBranch);

        return $hotfixVersion;
    }

    public function release(): string
    {

        if (
            !$this->project->isMainBranch()
            && !$this->project->isSupportBranch()
            && !$this->project->isHotfixBranch()
        ) {
            throw new \RuntimeException("No release can be created with branch '" . $this->project->getBranch() . "'.");
        }

        $releaseVersion = $this->project->getNextReleaseVersion();

        $this->assertNoUncommittedChanges('The release can only be created when all changes are committed.');

        $this->executor->exec('git tag -a ' . $releaseVersion . " -m 'Release Version " . $releaseVersion . "'");
        $this->executor->exec('git push origin ' . $releaseVersion);

        return $releaseVersion;
    }
}

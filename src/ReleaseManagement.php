<?php

namespace SP\Composer\Project;

class ReleaseManagement
{
    /**
     * @var Project
     */
    private $project;

    public function __construct(Project $project)
    {
        $this->project = $project;
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

    public function hasUncommitedChanges(): bool
    {
        $result = exec('git status --short --untracked-files no');
        return !empty($result);
    }

    /**
     * Checks whether the current working tree contains
     * uncommited changes. If it does, an Exception is thrown
     *
     *  @param string $msg Message of the Exception that may be thrown
     */
    public function assertNoUncommitedChanges(string $msg): void
    {
        if ($this->hasUncommitedChanges()) {
            $this->exec('git status --porcelain --untracked-files no');
            throw new \RuntimeException($msg);
        }
    }

    public function startHotfix(): string
    {
        if (
            !$this->project->isMainBranch()
            && !$this->project->isSupportBranch()
        ) {
            throw new \RuntimeException("Can't start hotfix or branch: " . $this->project->getBranch());
        }
        $releaseVersion = $this->project->getLatestRelease();
        if ($releaseVersion === null) {
            throw new \RuntimeException('There is no release yet for which a hotfix can be created.');
        }
        $hotfixVersion = $this->project->getNextHotfixVersion();
        if ($hotfixVersion === null) {
            throw new \RuntimeException('Hotfix version could not be determined.');
        }
        $this->exec('git checkout -b hotfix/' . $hotfixVersion . ' ' . $releaseVersion);
        $this->exec('git push origin hotfix/' . $hotfixVersion);

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

        if ($this->project->isHotfixBranch()) {
            $releaseVersion = $this->project->getNextHotfixVersion();
        } else {
            $releaseVersion = $this->project->getNextReleaseVersion();
        }

        $this->assertNoUncommitedChanges('The release can only be created when all changes are committed.');

        $this->exec('git tag -a ' . $releaseVersion . " -m 'Release Version " . $releaseVersion . "'");
        $this->exec('git push origin ' . $releaseVersion);

        return $releaseVersion;
    }

    private function exec(string $cmd): void
    {
        $output = [ ];
        $exitCode = 0;
        echo $cmd . "\n";

        exec($cmd, $output, $exitCode);
        if ($exitCode !== 0) {
            throw new \RuntimeException('exec failed with exit-code: ' . $exitCode . "\n" . implode("\n", $output));
        }
        echo implode("\n", $output) . "\n";
    }
}

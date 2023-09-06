<?php

namespace SP\Composer\Project;

use FilesystemIterator;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SP\Composer\Project\Git\Executor;

/**
 * @covers \SP\Composer\Project\ReleaseManagement
 */
class ReleaseManagementTest extends TestCase
{
    private const GIT_BASE = "var/test/ReleaseManagementTest/gitrepo";

    private const GIT_ORIGIN_BASE = "var/test/ReleaseManagementTest/gitrepo.origin";

    private const TEST_FILE = "var/test/ReleaseManagementTest/gitrepo/file.txt";

    /**
     * @beforeClass
     */
    public static function initGitRepoDir(): void
    {
        self::rmdir(self::GIT_BASE);
        mkdir(directory: self::GIT_BASE, recursive: true);

        self::rmdir(self::GIT_ORIGIN_BASE);
        mkdir(directory: self::GIT_ORIGIN_BASE, recursive: true);

        $executor = new Executor(self::GIT_BASE);
        $executor->exec('git init --initial-branch=main');
        file_put_contents(self::TEST_FILE, 'a');
        $executor->exec('git add file.txt');
        $executor->exec('git commit -m "feat: add file.txt"');
        $executor->exec('git tag 1.0.0');
        file_put_contents(self::TEST_FILE, 'b');
        $executor->exec('git commit -a -m "feat: add file.txt"');
        $executor->exec('git tag 1.1.0');
        file_put_contents(self::TEST_FILE, 'c');
        $executor->exec('git commit -a -m "feat: add file.txt"');
        $executor->exec('git tag 2.0.0');
        file_put_contents(self::TEST_FILE, 'd');
        $executor->exec('git commit -a -m "feat: add file.txt"');
        $executor->exec('git tag 2.0.1');
        file_put_contents(self::TEST_FILE, 'e');
        $executor->exec('git commit -a -m "feat: add file.txt"');

        $executor->exec('git checkout -b z1');
        file_put_contents(self::TEST_FILE, 'f');
        $executor->exec('git commit -a -m "feat: add file.txt"');
        $executor->exec('git checkout main');
        $executor->exec('git checkout -b z2');
        file_put_contents(self::TEST_FILE, 'f');
        $executor->exec('git commit -a -m "feat: add file.txt"');
        $executor->exec('git checkout main');

        $executor->exec('git init --bare ' . self::GIT_ORIGIN_BASE);
        $executor->exec('git remote add origin ' . self::GIT_ORIGIN_BASE);
        $executor->exec('git push origin');
    }

    /**
     * @before
     */
    public static function restoreGitRepository(): void
    {
        $executor = new Executor(self::GIT_BASE);
        $executor->exec('git restore .');
    }
        /**
     * @throws Exception
     */
    public function testConstruct(): void
    {

        $executor = new Executor(self::GIT_BASE);
        $project = $this->createStub(Project::class);
        $releaseManagement = new ReleaseManagement($project, $executor);

        $executor->exec('git checkout main');

        file_put_contents(self::TEST_FILE, 'a-z');

        $uncommitedChanges = $releaseManagement->hasUncommittedChanges();

        $this->assertTrue($uncommitedChanges);
    }

    /**
     * @throws Exception
     */
    public function testVerifyRelease(): void
    {

        $executor = new Executor(self::GIT_BASE);
        $project = $this->createStub(Project::class);
        $project->method('getUnstableDependencies')->willReturn([]);
        $releaseManagement = new ReleaseManagement($project, $executor);

        $releaseManagement->verifyRelease();

        // Exception not thrown
        $this->assertTrue(true);
    }

    /**
     * @throws Exception
     */
    public function testVerifyReleaseWithUnstableDependencies(): void
    {

        $executor = new Executor(self::GIT_BASE);
        $project = $this->createStub(Project::class);
        $project->method('getUnstableDependencies')->willReturn(["sitepark/foo:dev-develop"]);
        $releaseManagement = new ReleaseManagement($project, $executor);

        $this->expectException(RuntimeException::class);
        $releaseManagement->verifyRelease();
    }

    /**
     * @throws Exception
     */
    public function testStartHotfix(): void
    {

        $executor = new Executor(self::GIT_BASE);
        $project = $this->createStub(Project::class);
        $project->method('isRelease')->willReturn(true);
        $project->method('getLatestMainRelease')->willReturn('1.1.0');
        $project->method('getNextReleaseVersion')->willReturn('1.1.1');
        $releaseManagement = new ReleaseManagement($project, $executor);

        $executor->exec('git checkout 1.1.0');

        $releaseManagement->startHotfix();

        $branches = $executor->exec("git for-each-ref --format='%(refname:short)' refs/heads/**");

        $this->assertContains('hotfix/1.1.x', $branches);
    }

    /**
     * @throws Exception
     */
    public function testRelease(): void
    {

        $executor = new Executor(self::GIT_BASE);
        $project = $this->createStub(Project::class);
        $project->method('isMainBranch')->willReturn(true);
        $project->method('isSupportBranch')->willReturn(false);
        $project->method('getNextReleaseVersion')->willReturn('1.2.0');
        $releaseManagement = new ReleaseManagement($project, $executor);

        $executor->exec('git checkout main');

        $releaseManagement->release();

        $tags = $executor->exec("git tag");

        $this->assertContains('1.2.0', $tags);
    }

    private static function rmdir(string $dirPath): void
    {
        if (!empty($dirPath) && is_dir($dirPath)) {
            $dirObj = new RecursiveDirectoryIterator(
                $dirPath,
                FilesystemIterator::SKIP_DOTS
            );
            $files = new RecursiveIteratorIterator($dirObj, RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $path) {
                $path->isDir() && !$path->isLink() ? rmdir($path->getPathname()) : unlink($path->getPathname());
            }
            rmdir($dirPath);
        }
    }
}

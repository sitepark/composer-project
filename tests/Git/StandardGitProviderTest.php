<?php

namespace SP\Composer\Project\Git;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * @covers \SP\Composer\Project\Git\StandardGitProvider
 */
class StandardGitProviderTest extends TestCase
{
    private const GIT_BASE = "var/test/StandardGitProviderTest/gitrepo";

    private const TEST_FILE = "var/test/StandardGitProviderTest/gitrepo/file.txt";

    /**
     * @beforeClass
     */
    public static function initGitRepoDir(): void
    {
        self::rmdir(self::GIT_BASE);
        mkdir(directory: self::GIT_BASE, recursive: true);

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
    }

    public function testGetCurrentBranch(): void
    {
        $executor = new Executor(self::GIT_BASE);
        $git = new StandardGitProvider($executor);

        $executor->exec('git checkout main');

        $branch = $git->getCurrentBranch();

        $this->assertEquals('main', $branch);
    }

    public function testGetBranches(): void
    {
        $executor = new Executor(self::GIT_BASE);
        $git = new StandardGitProvider($executor);

        $executor->exec('git checkout main');

        $branches = $git->getBranches();

        $this->assertEquals(['main', 'z1', 'z2'], $branches);
    }

    public function testGetVersions(): void
    {
        $executor = new Executor(self::GIT_BASE);
        $git = new StandardGitProvider($executor);

        $executor->exec('git checkout main');

        $versions = $git->getVersions();

        $this->assertEquals(['1.0.0', '1.1.0', '2.0.0', '2.0.1'], $versions);
    }

    public function testGetVersionsFromMajor(): void
    {
        $executor = new Executor(self::GIT_BASE);
        $git = new StandardGitProvider($executor);

        $executor->exec('git checkout main');

        $versions = $git->getVersionsFromMajor(1);

        $this->assertEquals(['1.0.0', '1.1.0'], $versions);
    }

    public function testIsDev(): void
    {
        $executor = new Executor(self::GIT_BASE);
        $git = new StandardGitProvider($executor);
        $executor->exec('git checkout main');

        $isDev = $git->isDev();

        $this->assertTrue($isDev);
    }

    public function testNotIsNDev(): void
    {
        $executor = new Executor(self::GIT_BASE);
        $git = new StandardGitProvider($executor);
        $executor->exec('git checkout 1.0.0');

        $isDev = $git->isDev();

        $this->assertFalse($isDev);
    }

    public function testNotIsRelease(): void
    {
        $executor = new Executor(self::GIT_BASE);
        $git = new StandardGitProvider($executor);
        $executor->exec('git checkout main');

        $isRelease = $git->isRelease();

        $this->assertFalse($isRelease);
    }

    public function testIsRelease(): void
    {
        $executor = new Executor(self::GIT_BASE);
        $git = new StandardGitProvider($executor);
        $executor->exec('git checkout 1.0.0');

        $isRelease = $git->isRelease();

        $this->assertTrue($isRelease);
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

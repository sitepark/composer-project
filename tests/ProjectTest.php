<?php

namespace SP\Composer\Project;

use Composer\Package\RootPackageInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SP\Composer\Project\Git\GitProvider;

/**
 * @covers \SP\Composer\Project\Project
 */
class ProjectTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testName(): void
    {
        $rootPackage = $this->createStub(RootPackageInterface::class);
        $rootPackage->method('getName')->willReturn('sitepark/foo');
        /** @var RootPackageInterface $rootPackage */

        $git = $this->createStub(GitProvider::class);
        /** @var GitProvider $git */

        $project = new Project($rootPackage, $git);
        $this->assertEquals("foo", $project->getName());
        $this->assertEquals("sitepark", $project->getVendor());
    }

    public function testGetVersionHotfix(): void
    {
        $rootPackage = $this->getMockBuilder(RootPackageInterface::class)->getMock();
        $rootPackage->method('getName')->willReturn('sitepark/foo');
        /** @var RootPackageInterface $rootPackage */

        $git = $this->getMockBuilder(GitProvider::class)->getMock();
        $git->method('getCurrentBranch')->willReturn('hotfix/1.1.1');
        $git->method('getVersions')->willReturn([
            '1.0',
            '1.1'
        ]);
        /** @var GitProvider $git */

        $project = new Project($rootPackage, $git);
        $this->assertEquals("1.1.1", $project->getVersion());
    }

    public function testGetVersionSupport(): void
    {
        $rootPackage = $this->getMockBuilder(RootPackageInterface::class)->getMock();
        $rootPackage->method('getName')->willReturn('sitepark/foo');
        /** @var RootPackageInterface $rootPackage */

        $git = $this->getMockBuilder(GitProvider::class)->getMock();
        $git->method('isDev')->willReturn(true);
        $git->method('getCurrentBranch')->willReturn('support/1.1.x');
        $git->method('getVersions')->willReturn([
            '1.0',
            '1.1',
            '1.2'
        ]);
        /** @var GitProvider $git */
        $project = new Project($rootPackage, $git);
        $this->assertEquals("1.1.1", $project->getVersion());
    }

    public function testGetVersionDevelop(): void
    {
        $rootPackage = $this->getMockBuilder(RootPackageInterface::class)->getMock();
        $rootPackage->method('getName')->willReturn('sitepark/foo');
        /** @var RootPackageInterface $rootPackage */

        $git = $this->getMockBuilder(GitProvider::class)->getMock();
        $git->method('getCurrentBranch')->willReturn('develop');
        $git->method('isDev')->willReturn(true);
        $git->method('getVersions')->willReturn([
            '1.0',
            '1.1'
        ]);
        /** @var GitProvider $git */

        $project = new Project($rootPackage, $git);
        $this->assertEquals("1.2.0", $project->getVersion());
    }

    public function testGetVersionMajorBranchAlias(): void
    {
        $rootPackage = $this->getMockBuilder(RootPackageInterface::class)->getMock();
        $rootPackage->method('getName')->willReturn('sitepark/foo');
        $rootPackage->method('getExtra')->willReturn([
            'branch-alias' => [
                'dev-develop' => '2.x-dev'
            ]
        ]);
        /** @var RootPackageInterface $rootPackage */

        $git = $this->getMockBuilder(GitProvider::class)->getMock();
        $git->method('getCurrentBranch')->willReturn('develop');
        $git->method('getVersions')->willReturn([
            '1.0',
            '1.1'
        ]);
        /** @var GitProvider $git */
        $project = new Project($rootPackage, $git);
        $this->assertEquals("2.0.0", $project->getNextReleaseVersion());
    }

    public function testGetVersionWithMultipleMajorVersions(): void
    {
        // Arrange
        /** @var RootPackageInterface&MockObject $rootPackage */
        $rootPackage = $this->getMockBuilder(RootPackageInterface::class)->getMock();
        $rootPackage->method('getName')->willReturn('sitepark/foo');
        $rootPackage->method('getExtra')->willReturn([
            'branch-alias' => [
                'dev-develop' => '2.x-dev'
            ]
        ]);

        /** @var GitProvider&MockObject $git */
        $git = $this->getMockBuilder(GitProvider::class)->getMock();
        $git->method('getCurrentBranch')->willReturn('develop');
        $git->method('getVersions')->willReturn([
            '1.0',
            '1.1',
            '2.0',
            '2.1',
        ]);

        $project = new Project($rootPackage, $git);

        // Act
        $versionList = $project->getNextReleaseVersion();

        // Assert
        $this->assertEquals('2.2.0', $versionList);
    }
}

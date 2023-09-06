<?php

namespace SP\Composer\Project;

use Composer\Package\Link;
use Composer\Package\RootPackageInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
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
    public function testGetName(): void
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

    /**
     * @throws Exception
     */
    public function testBaseFilename(): void
    {
        $rootPackage = $this->createStub(RootPackageInterface::class);
        $rootPackage->method('getName')->willReturn('sitepark/foo');
        /** @var RootPackageInterface $rootPackage */

        $git = $this->createStub(GitProvider::class);
        /** @var GitProvider $git */

        $project = new Project($rootPackage, $git);
        $this->assertEquals("sitepark-foo", $project->getBaseFilename());
    }

    /**
     * @throws Exception
     */
    public function testGetFeatureBranchName(): void
    {
        $rootPackage = $this->createStub(RootPackageInterface::class);
        $rootPackage->method('getName')->willReturn('sitepark/foo');
        /** @var RootPackageInterface $rootPackage */

        $git = $this->createStub(GitProvider::class);
        $git->method('getCurrentBranch')->willReturn("feature/my-feature");
        /** @var GitProvider $git */

        $project = new Project($rootPackage, $git);
        $this->assertEquals("my-feature", $project->getFeatureBranchName());
    }

    /**
     * @throws Exception
     */
    public function testGetVersionQualifier(): void
    {
        $rootPackage = $this->createStub(RootPackageInterface::class);
        $rootPackage->method('getName')->willReturn('sitepark/foo');
        /** @var RootPackageInterface $rootPackage */

        $git = $this->createStub(GitProvider::class);
        $git->method('isDev')->willReturn(true);
        $git->method('getCurrentBranch')->willReturn("feature/my-feature");
        /** @var GitProvider $git */

        $project = new Project($rootPackage, $git);
        $this->assertEquals("my-feature-SNAPSHOT", $project->getVersionQualifier());
    }

    /**
     * @throws Exception
     */
    public function testGetBranch(): void
    {
        $rootPackage = $this->createStub(RootPackageInterface::class);
        $rootPackage->method('getName')->willReturn('sitepark/foo');
        /** @var RootPackageInterface $rootPackage */

        $git = $this->createStub(GitProvider::class);
        $git->method('getCurrentBranch')->willReturn("main");
        /** @var GitProvider $git */

        $project = new Project($rootPackage, $git);
        $this->assertEquals("main", $project->getBranch());
    }

    /**
     * @throws Exception
     */
    public function testGetBranches(): void
    {
        $rootPackage = $this->createStub(RootPackageInterface::class);
        $rootPackage->method('getName')->willReturn('sitepark/foo');
        /** @var RootPackageInterface $rootPackage */

        $git = $this->createStub(GitProvider::class);
        $git->method('getBranches')->willReturn(["main", "support/1.x"]);
        /** @var GitProvider $git */

        $project = new Project($rootPackage, $git);
        $this->assertEquals(["main", "support/1.x"], $project->getBranches());
    }

    /**
     * @throws Exception
     */
    public function testGetUnstableDependencies(): void
    {
        $package = $this->createStub(Link::class);
        $package->method('getPrettyConstraint')->willReturn('dev-develop');
        $package->method('getTarget')->willReturn('sitepark/bar');
        /** @var Link $package */
        $rootPackage = $this->createStub(RootPackageInterface::class);
        $rootPackage->method('getName')->willReturn('sitepark/foo');
        $rootPackage->method('getRequires')->willReturn([$package]);
        /** @var RootPackageInterface $rootPackage */

        /** @var GitProvider $git */
        $git = $this->createStub(GitProvider::class);

        $project = new Project($rootPackage, $git);
        $this->assertEquals(['sitepark/bar:dev-develop'], $project->getUnstableDependencies());
    }

    /**
     * @throws Exception
     */
    public function testHasBranche(): void
    {
        $rootPackage = $this->createStub(RootPackageInterface::class);
        $rootPackage->method('getName')->willReturn('sitepark/foo');
        /** @var RootPackageInterface $rootPackage */

        $git = $this->createStub(GitProvider::class);
        $git->method('getBranches')->willReturn(["main", "support/1.x"]);
        /** @var GitProvider $git */

        $project = new Project($rootPackage, $git);
        $this->assertTrue($project->hasBranch('support/1.x'));
    }

    public function testGetNextReleaseVersionInMainBranch(): void
    {
        $rootPackage = $this->getMockBuilder(RootPackageInterface::class)->getMock();
        $rootPackage->method('getName')->willReturn('sitepark/foo');
        /** @var RootPackageInterface $rootPackage */

        $git = $this->getMockBuilder(GitProvider::class)->getMock();
        $git->method('getCurrentBranch')->willReturn('main');
        $git->method('getVersions')->willReturn([
            '1.0.0',
            '1.1.0'
        ]);
        /** @var GitProvider $git */

        $project = new Project($rootPackage, $git);
        $this->assertEquals("1.2.0", $project->getNextReleaseVersion());
    }

    public function testGetNextReleaseVersionInSupportBranch(): void
    {
        $rootPackage = $this->getMockBuilder(RootPackageInterface::class)->getMock();
        $rootPackage->method('getName')->willReturn('sitepark/foo');
        /** @var RootPackageInterface $rootPackage */

        $git = $this->getMockBuilder(GitProvider::class)->getMock();
        $git->method('getCurrentBranch')->willReturn('support/2.x');
        $git->method('getVersionsFromMajor')->willReturn([
            '2.0.0',
            '2.0.1',
            '2.1.0',
        ]);
        /** @var GitProvider $git */

        $project = new Project($rootPackage, $git);
        $this->assertEquals("2.2.0", $project->getNextReleaseVersion());
    }

    public function testGetNextReleaseVersionInHotfixBranch(): void
    {
        $rootPackage = $this->getMockBuilder(RootPackageInterface::class)->getMock();
        $rootPackage->method('getName')->willReturn('sitepark/foo');
        /** @var RootPackageInterface $rootPackage */

        $git = $this->getMockBuilder(GitProvider::class)->getMock();
        $git->method('getCurrentBranch')->willReturn('hotfix/1.1.x');
        $git->method('getVersionsFromMinor')->willReturn([
            '1.1.0',
            '1.1.1',
            '1.1.2'
        ]);
        /** @var GitProvider $git */

        $project = new Project($rootPackage, $git);
        $this->assertEquals("1.1.3", $project->getNextReleaseVersion());
    }

    public function testGetVersionHotfix(): void
    {
        $rootPackage = $this->getMockBuilder(RootPackageInterface::class)->getMock();
        $rootPackage->method('getName')->willReturn('sitepark/foo');
        /** @var RootPackageInterface $rootPackage */

        $git = $this->getMockBuilder(GitProvider::class)->getMock();
        $git->method('getCurrentBranch')->willReturn('hotfix/1.1.x');
        $git->method('getVersions')->willReturn([
            '1.1.0',
            '1.1.1'
        ]);
        /** @var GitProvider $git */

        $project = new Project($rootPackage, $git);
        $this->assertEquals("1.1.1", $project->getVersion());
    }

    public function testGetVersionSupportIsDev(): void
    {
        $rootPackage = $this->getMockBuilder(RootPackageInterface::class)->getMock();
        $rootPackage->method('getName')->willReturn('sitepark/foo');
        /** @var RootPackageInterface $rootPackage */

        $git = $this->getMockBuilder(GitProvider::class)->getMock();
        $git->method('isDev')->willReturn(true);
        $git->method('getCurrentBranch')->willReturn('support/1.x');
        $git->method('getVersionsFromMajor')->willReturn([
            '1.0.0',
            '1.1.0',
            '1.2.0'
        ]);
        /** @var GitProvider $git */
        $project = new Project($rootPackage, $git);
        $this->assertEquals("1.3.0", $project->getVersion());
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
            '1.0.0',
            '1.1.0'
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
            '1.0.0',
            '1.1.0'
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
            '1.0.0',
            '1.1.0',
            '2.0.0',
            '2.1.0',
        ]);

        $project = new Project($rootPackage, $git);

        // Act
        $versionList = $project->getNextReleaseVersion();

        // Assert
        $this->assertEquals('2.2.0', $versionList);
    }
}

<?php

namespace SP\Composer\Project;

use Composer\Package\Link;
use Composer\Package\RootPackageInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SP\Composer\Project\Git\GitProvider;

#[CoversClass(Project::class)]
class ProjectTest extends TestCase
{
    public function testGetName(): void
    {
        $rootPackage = $this->createStub(RootPackageInterface::class);
        $rootPackage->method('getName')->willReturn('sitepark/foo');
        $git = $this->createStub(GitProvider::class);

        $project = new Project($rootPackage, $git);
        $this->assertEquals("foo", $project->getName());
        $this->assertEquals("sitepark", $project->getVendor());
    }

    public function testBaseFilename(): void
    {
        $rootPackage = $this->createStub(RootPackageInterface::class);
        $rootPackage->method('getName')->willReturn('sitepark/foo');

        $git = $this->createStub(GitProvider::class);

        $project = new Project($rootPackage, $git);
        $this->assertEquals("sitepark-foo", $project->getBaseFilename());
    }

    public function testGetFeatureBranchName(): void
    {
        $rootPackage = $this->createStub(RootPackageInterface::class);
        $rootPackage->method('getName')->willReturn('sitepark/foo');

        $git = $this->createStub(GitProvider::class);
        $git->method('getCurrentBranch')->willReturn("feature/my-feature");

        $project = new Project($rootPackage, $git);
        $this->assertEquals("my-feature", $project->getFeatureBranchName());
    }


    public function testGetVersionQualifier(): void
    {
        $rootPackage = $this->createStub(RootPackageInterface::class);
        $rootPackage->method('getName')->willReturn('sitepark/foo');

        $git = $this->createStub(GitProvider::class);
        $git->method('isDev')->willReturn(true);
        $git->method('getCurrentBranch')->willReturn("feature/my-feature");

        $project = new Project($rootPackage, $git);
        $this->assertEquals("my-feature-SNAPSHOT", $project->getVersionQualifier());
    }

    public function testGetBranch(): void
    {
        $rootPackage = $this->createStub(RootPackageInterface::class);
        $rootPackage->method('getName')->willReturn('sitepark/foo');

        $git = $this->createStub(GitProvider::class);
        $git->method('getCurrentBranch')->willReturn("main");

        $project = new Project($rootPackage, $git);
        $this->assertEquals("main", $project->getBranch());
    }

    public function testGetBranches(): void
    {
        $rootPackage = $this->createStub(RootPackageInterface::class);
        $rootPackage->method('getName')->willReturn('sitepark/foo');

        $git = $this->createStub(GitProvider::class);
        $git->method('getBranches')->willReturn(["main", "support/1.x"]);

        $project = new Project($rootPackage, $git);
        $this->assertEquals(["main", "support/1.x"], $project->getBranches());
    }

    public function testGetUnstableDependencies(): void
    {
        $platform = $this->createStub(Platform::class);
        $platform->method('getInstalledPackages')->willReturn(['sitepark/bar']);
        $platform->method('getInstalledPackageVersion')->willReturn('dev-develop');

        $package = $this->createStub(Link::class);
        $package->method('getPrettyConstraint')->willReturn('dev-develop');
        $package->method('getTarget')->willReturn('sitepark/bar');

        $rootPackage = $this->createStub(RootPackageInterface::class);
        $rootPackage->method('getName')->willReturn('sitepark/foo');
        $rootPackage->method('getRequires')->willReturn([$package]);

        $git = $this->createStub(GitProvider::class);

        $project = new Project($rootPackage, $git, $platform);
        $this->assertEquals(['sitepark/bar:dev-develop'], $project->getUnstableDependencies());
    }

    public function testHasBranche(): void
    {
        $rootPackage = $this->createStub(RootPackageInterface::class);
        $rootPackage->method('getName')->willReturn('sitepark/foo');

        $git = $this->createStub(GitProvider::class);
        $git->method('getBranches')->willReturn(["main", "support/1.x"]);

        $project = new Project($rootPackage, $git);
        $this->assertTrue($project->hasBranch('support/1.x'));
    }

    public function testGetNextReleaseVersionInMainBranch(): void
    {
        $rootPackage = $this->getMockBuilder(RootPackageInterface::class)->getMock();
        $rootPackage->method('getName')->willReturn('sitepark/foo');

        $git = $this->getMockBuilder(GitProvider::class)->getMock();
        $git->method('getCurrentBranch')->willReturn('main');
        $git->method('getVersions')->willReturn([
            '1.0.0',
            '1.1.0'
        ]);

        $project = new Project($rootPackage, $git);
        $this->assertEquals("1.2.0", $project->getNextReleaseVersion());
    }

    public function testGetNextReleaseVersionInSupportBranch(): void
    {
        $rootPackage = $this->getMockBuilder(RootPackageInterface::class)->getMock();
        $rootPackage->method('getName')->willReturn('sitepark/foo');

        $git = $this->getMockBuilder(GitProvider::class)->getMock();
        $git->method('getCurrentBranch')->willReturn('support/2.x');
        $git->method('getVersionsFromMajor')->willReturn([
            '2.0.0',
            '2.0.1',
            '2.1.0',
        ]);

        $project = new Project($rootPackage, $git);
        $this->assertEquals("2.2.0", $project->getNextReleaseVersion());
    }

    public function testGetNextReleaseVersionInHotfixBranch(): void
    {
        $rootPackage = $this->getMockBuilder(RootPackageInterface::class)->getMock();
        $rootPackage->method('getName')->willReturn('sitepark/foo');

        $git = $this->getMockBuilder(GitProvider::class)->getMock();
        $git->method('getCurrentBranch')->willReturn('hotfix/1.1.x');
        $git->method('getVersionsFromMinor')->willReturn([
            '1.1.0',
            '1.1.1',
            '1.1.2'
        ]);

        $project = new Project($rootPackage, $git);
        $this->assertEquals("1.1.3", $project->getNextReleaseVersion());
    }

    public function testGetVersionHotfix(): void
    {
        $rootPackage = $this->getMockBuilder(RootPackageInterface::class)->getMock();
        $rootPackage->method('getName')->willReturn('sitepark/foo');

        $git = $this->getMockBuilder(GitProvider::class)->getMock();
        $git->method('getCurrentBranch')->willReturn('hotfix/1.1.x');
        $git->method('getVersionsFromMinor')->willReturn([
            '1.1.0',
            '1.1.1'
        ]);

        $project = new Project($rootPackage, $git);
        $this->assertEquals("1.1.1", $project->getVersion());
    }

    public function testGetVersionSupportIsDev(): void
    {
        $rootPackage = $this->getMockBuilder(RootPackageInterface::class)->getMock();
        $rootPackage->method('getName')->willReturn('sitepark/foo');


        $git = $this->getMockBuilder(GitProvider::class)->getMock();
        $git->method('isDev')->willReturn(true);
        $git->method('getCurrentBranch')->willReturn('support/1.x');
        $git->method('getVersionsFromMajor')->willReturn([
            '1.0.0',
            '1.1.0',
            '1.2.0'
        ]);

        $project = new Project($rootPackage, $git);
        $this->assertEquals("1.3.0", $project->getVersion());
    }

    public function testGetVersionDevelop(): void
    {
        $rootPackage = $this->getMockBuilder(RootPackageInterface::class)->getMock();
        $rootPackage->method('getName')->willReturn('sitepark/foo');

        $git = $this->getMockBuilder(GitProvider::class)->getMock();
        $git->method('getCurrentBranch')->willReturn('develop');
        $git->method('isDev')->willReturn(true);
        $git->method('getVersions')->willReturn([
            '1.0.0',
            '1.1.0'
        ]);

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

        $git = $this->getMockBuilder(GitProvider::class)->getMock();
        $git->method('getCurrentBranch')->willReturn('develop');
        $git->method('getVersions')->willReturn([
            '1.0.0',
            '1.1.0'
        ]);

        $project = new Project($rootPackage, $git);
        $this->assertEquals("2.0.0", $project->getNextReleaseVersion());
    }

    public function testGetVersionWithMultipleMajorVersions(): void
    {
        // Arrange
        $rootPackage = $this->getMockBuilder(RootPackageInterface::class)->getMock();
        $rootPackage->method('getName')->willReturn('sitepark/foo');
        $rootPackage->method('getExtra')->willReturn([
            'branch-alias' => [
                'dev-develop' => '2.x-dev'
            ]
        ]);

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

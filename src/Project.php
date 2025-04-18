<?php

declare(strict_types=1);

namespace SP\Composer\Project;

use Composer\InstalledVersions;
use Composer\Package\RootPackageInterface;
use Composer\Semver\Comparator;
use Composer\Semver\VersionParser;
use SP\Composer\Project\Git\GitProvider;

class Project
{
    private string $branch;

    private string $vendor;

    private string $name;

    private RootPackageInterface $package;

    private GitProvider $gitProvider;

    private Platform $platform;

    public function __construct(RootPackageInterface $package, GitProvider $gitProvider, ?Platform $platform = null)
    {
        $this->gitProvider = $gitProvider;
        $this->package = $package;
        $this->platform = $platform ?? new Platform();
        $this->load();
    }

    private function load(): void
    {
        $nameParts = explode('/', $this->package->getName());
        if (count($nameParts) < 2) {
            throw new \RuntimeException('Unsupported package name: ' . $this->package->getName());
        }
        $this->vendor = $nameParts[0];
        $this->name = $nameParts[1];
        $this->branch = $this->gitProvider->getCurrentBranch();
    }

    public function getPackage(): RootPackageInterface
    {
        return $this->package;
    }

    public function getBranch(): string
    {
        return $this->branch;
    }

    /**
     * @return string[]
     */
    public function getBranches(): array
    {
        return $this->gitProvider->getBranches();
    }

    public function hasBranch(string $name): bool
    {
        return in_array($name, $this->getBranches(), true);
    }

    public function getVendor(): string
    {
        return $this->vendor;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): ?string
    {
        if ($this->isDev()) {
            return $this->getNextReleaseVersion();
        }

        return $this->getLatestReleaseVersion();
    }

    public function isMainBranch(): bool
    {
        return $this->branch === 'main';
    }

    public function isSupportBranch(): bool
    {
        return strpos($this->branch, 'support/') !== false;
    }

    public function isHotfixBranch(): bool
    {
        return strpos($this->branch, 'hotfix/') !== false;
    }

    /**
     * Qualifier can be Feature-Branch-Name and/or SNAPSHOT.
     */
    public function getVersionQualifier(): ?string
    {
        $qualifier = [];
        $featureBranchName = $this->getFeatureBranchName();
        if (!empty($featureBranchName)) {
            $qualifier[] = $featureBranchName;
        }
        if ($this->isDev()) {
            $qualifier[] = 'SNAPSHOT';
        }

        if (empty($qualifier)) {
            return null;
        }

        return implode('-', $qualifier);
    }

    public function getBaseFilename(): string
    {
        return $this->getVendor() . '-' . $this->getName();
    }

    public function getFeatureBranchName(): ?string
    {
        $featureBranchPrefix = 'feature/';

        if (strpos($this->branch, $featureBranchPrefix) !== false) {
            return substr($this->branch, strlen($featureBranchPrefix));
        }
        return null;
    }

    /**
     * @return string[]
     */
    public function getVersions(): array
    {
        return $this->gitProvider->getVersions();
    }

    /**
     * @return string[]
     */
    public function getVersionsFromMinor(int $major, int $minor): array
    {
        return $this->gitProvider->getVersionsFromMinor($major, $minor);
    }

    /**
     * @return string[]
     */
    public function getVersionsFromMajor(int $major): array
    {
        return $this->gitProvider->getVersionsFromMajor($major);
    }

    public function getLatestMainRelease(): ?string
    {
        $versions = $this->getVersions();
        if (empty($versions)) {
            return null;
        }
        return $versions[count($versions) - 1];
    }

    public function getLatestReleaseFromMajor(int $major): ?string
    {
        $versions = $this->getVersionsFromMajor($major);
        if (empty($versions)) {
            return null;
        }
        return $versions[count($versions) - 1];
    }

    public function getLatestReleaseFromMinor(int $major, int $minor): ?string
    {
        $versions = $this->getVersionsFromMinor($major, $minor);
        if (empty($versions)) {
            return null;
        }
        return $versions[count($versions) - 1];
    }

    public function isDev(): bool
    {
        return $this->gitProvider->isDev();
    }

    public function isRelease(): bool
    {
        return $this->gitProvider->isRelease();
    }

    /**
     * @param string[] $excludes
     * @return string[]
     */
    public function getUnstableDependencies(array $excludes = []): array
    {

        $unstable = [];
        foreach ($this->platform->getInstalledPackages() as $package) {
            if ($package === $this->package->getName()) {
                continue;
            }
            $version = $this->platform->getInstalledPackageVersion($package);
            if ($version === null) {
                continue;
            }
            $stability = VersionParser::parseStability($version);
            if ($stability !== 'stable' && !in_array($package, $excludes, true)) {
                $unstable[] = $package . ':' . $version;
            }
        }

        return $unstable;
    }

    public function getLatestReleaseVersion(): ?string
    {
        if ($this->isSupportBranch()) {
            [$major] = $this->parseVersionFromBranch($this->getBranch());
            return $this->getLatestReleaseFromMajor((int)$major);
        }
        if ($this->isHotfixBranch()) {
            [$major, $minor] = $this->parseVersionFromBranch($this->getBranch());
            return $this->getLatestReleaseFromMinor((int)$major, (int)$minor);
        }

        return $this->getLatestMainRelease();
    }
    public function getNextReleaseVersion(): ?string
    {
        if ($this->isSupportBranch()) {
            [$major] = $this->parseVersionFromBranch($this->getBranch());
            $version = $this->getLatestReleaseFromMajor((int)$major);
            return $this->incrementMinorLevel($version);
        }
        if ($this->isHotfixBranch()) {
            [$major, $minor] = $this->parseVersionFromBranch($this->getBranch());
            $version = $this->getLatestReleaseFromMinor((int)$major, (int)$minor);
            return $this->incrementPatchLevel($version);
        }

        $version = $this->getLatestMainRelease();
        if ($version === null) {
            return $this->getBranchAliasVersion('dev-' . $this->branch) ?? '1.0.0';
        }
        $version = $this->incrementMinorLevel($version);

        // If the branch alias version is greater than the version that was
        // determined based on Git tags, it will be taken.
        // This is necessary e.g. when releasing major versions.
        $branchAliasVersion = $this->getBranchAliasVersion('dev-' . $this->branch);
        if ($branchAliasVersion !== null && $version !== null && Comparator::greaterThan($branchAliasVersion, $version)) {
            $version = $branchAliasVersion;
        }
        return $version;
    }

    /**
     * @param string $branch
     * @return string[]
     */
    private function parseVersionFromBranch(string $branch): array
    {
        $slashPos = strpos($branch, '/');
        $version = substr($branch, $slashPos + 1);
        return explode('.', $version);
    }

    /**
     * Determines the base version of the branch alias passed.
     * e.g. dev-1.x => 1.0
     * If no branch alias exists null is returned
     */
    private function getBranchAliasVersion(string $alias): ?string
    {
        $branchAlias = $this->getBranchAlias($alias);
        if ($branchAlias === null) {
            return null;
        }
        $parser = new VersionParser();
        $numericBranchAliasVersion = $parser->parseNumericAliasPrefix($branchAlias);
        $version = explode('.', $numericBranchAliasVersion . '0');
        // semversion x.x.x
        while (count($version) < 3) {
            $version[] = '0';
        }
        return implode('.', $version);
    }

    /**
     * Get the alias version of the given alias name.
     * Returns zero if the alias is not known.
     */
    private function getBranchAlias(string $aliasName): ?string
    {
        $extra = $this->getPackage()->getExtra();
        $branchAliases = $extra['branch-alias'] ?? null;
        if (!is_array($branchAliases)) {
            return null;
        }

        $alias = $branchAliases[$aliasName] ?? null;
        if (is_string($alias)) {
            return $alias;
        }
        return null;
    }

    private function incrementMinorLevel(?string $version): ?string
    {
        if ($version === null) {
            return null;
        }

        $s = explode('.', $version);
        if (count($s) < 2) {
            $s[] = '0';
        }
        return $s[0] . '.' . ((int)$s[1] + 1) . '.0';
    }

    private function incrementPatchLevel(?string $version): ?string
    {

        if ($version === null) {
            return null;
        }

        $s = explode('.', $version);
        if (count($s) < 2) {
            $s[] = '0';
        }
        if (count($s) < 3) {
            $s[] = '0';
        }
        $s[2] = (int)$s[2] + 1;
        return implode('.', $s);
    }
}

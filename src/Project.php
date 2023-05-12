<?php

namespace SP\Composer\Project;

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

    public function __construct(RootPackageInterface $package, GitProvider $gitProvider)
    {
        $this->gitProvider = $gitProvider;
        $this->package = $package;
        $this->load();
    }

    public function setGitProvider(GitProvider $provider): void
    {
        $this->gitProvider = $provider;
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

    public function switchBranch(string $name): void
    {
        exec('git checkout ' . $name);
        $this->load();
    }

    public function getVendor(): string
    {
        return $this->vendor;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getReleaseVersion(): string
    {

        $supportBranchPrefix = 'support/';
        $hotfixBranchPrefix = 'hotfix/';

        if (strpos($this->branch, $supportBranchPrefix) !== false) {
            $baseVersion = substr($this->branch, strlen($supportBranchPrefix));
            $baseVersion = substr($baseVersion, 0, strlen($baseVersion) - 2);
            return $this->getNextSupportVersion($baseVersion);
        }
        if (strpos($this->branch, $hotfixBranchPrefix) !== false) {
            return substr($this->branch, strlen($hotfixBranchPrefix));
        }

        return $this->getNextReleaseVersion();
    }

    public function getVersion(): string
    {
        $version = null;
        if ($this->isSupportBranch()) {
            $baseVersion = substr($this->branch, strlen('support/'));
            $baseVersion = substr($baseVersion, 0, strlen($baseVersion) - 2);
            if ($this->isDev()) {
                $version = $this->getNextSupportVersion($baseVersion);
            } else {
                $version = $this->getLatestSupport($baseVersion);
            }
        } elseif ($this->isHotfixBranch()) {
            $version = substr($this->branch, strlen('hotfix/'));
        } else {
            if ($this->isDev()) {
                $version = $this->getNextReleaseVersion();
            } else {
                $version = $this->getLatestRelease();
            }
        }

        return $version;
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
     * Qualifier kann Feature-Branch-Name und/oder SNAPSHOT sein.
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

    public function getLatestRelease(): ?string
    {
        $versions = $this->getVersions();
        if (empty($versions)) {
            return null;
        }
        return $versions[count($versions) - 1];
    }

    /**
     * Liefert die letzte Support-Version für die
     * übergebene Basis-Version (z.B. 1.3.x)
     *
     * Die entsprechende Version wird anhand von Git-Tags
     * ermittelt.
     */
    public function getLatestSupport(string $baseVersion): ?string
    {
        return $this->getLatestHotfix($baseVersion);
    }

    public function getLatestHotfix(string $baseVersion): ?string
    {
        $versions = $this->getVersions();
        if (empty($versions)) {
            return null;
        }

        $hotfixVersion = null;
        foreach ($versions as $v) {
            if ($v === $baseVersion || strpos($v, $baseVersion . '.') === 0) {
                $hotfixVersion = $v;
            }
        }

        return $hotfixVersion;
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

        // TODO: Auch Einträge aus der Lock-Datei überprüfen
        // composer show --locked -D -f json

        /* @var $allRequires \Composer\Package\Link[] */
        $allRequires = array_merge(
            $this->package->getRequires(),
            $this->package->getDevRequires()
        );

        $unstable = [];
        foreach ($allRequires as $req) {
            $stability = VersionParser::parseStability($req->getPrettyConstraint());
            if ($stability !== 'stable' && !in_array($req->getTarget(), $excludes, true)) {
                $unstable[] = $req->getTarget() . ':' . $req->getPrettyConstraint();
            }
        }

        return $unstable;
    }

    public function getNextReleaseVersion(): string
    {
        $version = $this->getLatestRelease();
        if ($version === null) {
            return $this->getBranchAliasVersion('dev-' . $this->branch) ?? '1.0.0';
        }
        $version = $this->incrementMinorLevel($version);

        // Sollte die Branch-Alias-Version größer als die Version die
        // basierend auf Git-Tags ermittelt wurde, wird diese genommen.
        // Dies ist z.B. bei Release von Major-Versionen nötig.
        $branchAliasVersion = $this->getBranchAliasVersion('dev-' . $this->branch);
        if ($branchAliasVersion !== null && Comparator::greaterThan($branchAliasVersion, $version)) {
            $version = $branchAliasVersion;
        }
        return $version;
    }

    /**
     * Ermittelt die Basis-Version des übergebenen Branch-Aliases
     * z.B. dev-1.x => 1.0
     * Sollte kein Branch Alias existrieren wird null zurückgeliefert
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
     * Ermittelt die Alias-Version des übergebenen Alias-Namen.
     * Liefert null zurück falls der Alias nicht bekannt ist.
     */
    private function getBranchAlias(string $aliasName): ?string
    {
        return $this->getPackage()->getExtra()['branch-alias'][$aliasName] ?? null;
    }

    public function getNextHotfixVersion(): ?string
    {
        $version = $this->getLatestRelease();
        if ($version === null) {
            return null;
        }
        return $this->incrementPatchLevel($version);
    }

    public function getNextSupportVersion(string $baseVersion): ?string
    {
        $isSupportForMinor = !empty(preg_match("/^\d$/", $baseVersion));
        $isSupportForPatch = !empty(preg_match("/^\d\.\d$/", $baseVersion));

        $version = $this->getLatestSupport($baseVersion);
        if ($isSupportForPatch) {
            return $this->incrementPatchLevel($version);
        } elseif ($isSupportForMinor) {
            return $this->incrementMinorLevel($version);
        }
        throw new \RuntimeException("Unsupported Support Version $baseVersion");
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
        return $s[0] . '.' . (intval($s[1]) + 1) . '.0';
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
        $s[2] = intval($s[2]) + 1;
        return implode('.', $s);
    }
}

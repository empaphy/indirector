<?php

declare(strict_types=1);

namespace Empaphy\Indirector\Composer\Autoload;

use Composer\Package\Link;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Semver\Constraint\Bound;
use Composer\Semver\Constraint\Constraint;

/**
 * Add the autoloader to Doctrine Annotation Registry.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
class AutoloadGenerator extends \Composer\Autoload\AutoloadGenerator
{

    /**
     * Compiles an ordered list of namespace => path mappings
     *
     * @param array<int, array{0: \Composer\Package\PackageInterface, 1: string|null}> $packageMap
     * @param RootPackageInterface                                   $rootPackage
     * @param bool|string[]                                          $filteredDevPackages
     * @return array{
     *             'psr-0':                 array<string, array<string>>,
     *             'psr-4':                 array<string, array<string>>,
     *             'classmap':              array<int, string>,
     *             'files':                 array<string, string>,
     *             'exclude-from-classmap': array<int, string>,
     *         }
     */
    public function parseAutoloads(array $packageMap, PackageInterface $rootPackage, $filteredDevPackages = false): array
    {
        $autoloads = parent::parseAutoloads($packageMap, $rootPackage, $filteredDevPackages);

        $autoloads = $this->bumpFileToTopOfAutoloads($autoloads, $packageMap, 'empaphy/indirector', 'bootstrap.php');
        $autoloads = $this->bumpFileToTopOfAutoloads($autoloads, $packageMap, 'rector/rector',      'bootstrap.php');
        $autoloads = $this->bumpFileToTopOfAutoloads($autoloads, $packageMap, 'phpstan/phpstan',    'bootstrap.php');

        return $autoloads;
    }

    /**
     * @param  array   $autoloads
     * @param  array   $packageMap
     * @param  string  $packageName
     * @param  string  $path
     * @return array
     */
    private function bumpFileToTopOfAutoloads(
        array $autoloads,
        array $packageMap,
        string $packageName,
        string $path
    ): array {
        // Ensure the Indirector bootstrap is loaded first at all times.
        $item = $this->pickPackageFromMap($packageMap, $packageName);

        if (null !== $item) {
            [$package, $installPath] = $item;
            $fileIdentifier          = $this->getFileIdentifier($package, $path);
            $relativePath            = $autoloads['files'][$fileIdentifier]
                ?? (empty($installPath) ? ($path ?: '.') : "{$installPath}/{$path}");
            $autoloads['files'] = [$fileIdentifier => $relativePath] + $autoloads['files'];
        }

        return $autoloads;
    }

    /**
     * @param  array<int, array{0: \Composer\Package\PackageInterface, 1: string|null}>  $packageMap
     * @param  bool|'php-only'                                                           $checkPlatform
     * @param  string[]                                                                  $devPackageNames
     * @return ?string
     */
    protected function getPlatformCheck(array $packageMap, $checkPlatform, array $devPackageNames): ?string
    {
        $bound = new Bound('7.3.0', true);

        // Lower the version constraints for all packages to 7.2.
        // TODO: move this to Plugin.
        foreach ($packageMap as $item) {
            $package  = $item[0];
            $requires = $package->getRequires();
            $link     = $requires['php'] ?? null;

            if (null !== $link && $link->getConstraint()->getLowerBound()->compareTo($bound, '>')) {
                 $requires['php'] = new Link(
                    $link->getSource(),
                    $link->getTarget(),
                    new Constraint('>=', '7.2.0'),
                    $link->getDescription(),
                    $link->getPrettyConstraint()
                );
                $package->setRequires($requires);
            }
        }

        return parent::getPlatformCheck($packageMap, $checkPlatform, $devPackageNames);
    }

    /**
     * @param  array<int, array{0: \Composer\Package\PackageInterface, 1: string|null}>  $packageMap
     * @param  string                                                                    $packageName
     * @return array{0: \Composer\Package\PackageInterface, 1: string|null}|null
     */
    private function pickPackageFromMap(array $packageMap, string $packageName): ?array
    {
        foreach ($packageMap as $item) {
            if ($item[0]->getName() === $packageName) {
                return $item;
            }
        }

        return null;
    }
}

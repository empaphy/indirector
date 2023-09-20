<?php

/**
 * @noinspection PhpMultipleClassDeclarationsInspection
 * @noinspection UnknownInspectionInspection
 */

declare(strict_types=1);

namespace Empaphy\Indirector\Php;

/**
 * Represents a PHP version.
 */
class PhpVersion extends \PHPStan\Php\PhpVersion
{
    /**
     * Major version.
     *
     * Should be incremented when incompatible changes are made.
     *
     * @var int
     * @readonly
     */
    public $major;

    /**
     * Minor version.
     *
     * Should be incremented when new functionality is added in a backward compatible manner.
     *
     * @var int
     * @readonly
     */
    public $minor;

    /**
     * Patch version.
     *
     * Should be incremented when backward compatible bug fixes are made.
     *
     * @var int
     * @readonly
     */
    public $patch;

    /**
     * Feature level.
     *
     * @var int
     */
    private $featureVersionId;

    /**
     * @param  int  $versionId  A PHP version ID, e.g. `70429` for PHP 7.4.29.
     */
    public function __construct(int $versionId)
    {
        parent::__construct($versionId);

        $this->major            = self::getMajorFromVersionId($versionId);
        $this->minor            = self::getMinorFromVersionId($versionId);
        $this->patch            = self::getPatchFromVersionId($versionId);
        $this->featureVersionId = self::getFeatureVersionId($versionId);
    }

    /**
     * Return the major version component from the provided version ID.
     *
     * @param  int  $versionId  A PHP version ID, e.g. `70429` for PHP 7.4.29.
     * @return int The major version component.
     */
    public static function getMajorFromVersionId(int $versionId): int
    {
        return (int) floor($versionId / 10000);
    }

    /**
     * Return the minor version component from the provided version ID.
     *
     * @param  int  $versionId  A PHP version ID, e.g. `70429` for PHP 7.4.29.
     * @return int The minor version component.
     */
    public static function getMinorFromVersionId(int $versionId): int
    {
        return (int) floor($versionId % 10000 / 100);
    }

    /**
     * Return the patch version component from the provided version ID.
     *
     * @param  int  $versionId  A PHP version ID, e.g. `70429` for PHP 7.4.29.
     * @return int The patch version component.
     */
    public static function getPatchFromVersionId(int $versionId): int
    {
        return (int) floor($versionId % 100);
    }

    /**
     * Return the feature level from the provided version ID.
     *
     * @param  int  $versionId  A PHP version ID, e.g. `70429` for PHP 7.4.29.
     * @return int The feature level, e.g. `70400` for PHP 7.4.29.
     */
    public static function getFeatureVersionId(int $versionId): int
    {
        return 100 * (int) floor($versionId % 100000 / 100);
    }

    /**
     * Return a human-readable version string.
     *
     * @return string A version string in `x.y.z` format, e.g. "7.4.29".
     */
    public function __toString(): string
    {
        return $this->getVersionString();
    }

    /**
     * Compare the major version component of this version with another.
     *
     * @param  \PHPStan\Php\PhpVersion  $other  The other version to compare with.
     * @return int `-1` if this version is less than `$other`,
     *             `0` if they are equal,
     *             `1` if this version is greater than `$other`.
     */
    public function compareMajorVersion(\PHPStan\Php\PhpVersion $other): int
    {
        return $this->major <=> self::getMajorFromVersionId($other->getVersionId());
    }

    /**
     * Compare the major and minor (`major.minor`) version component of this version with those of another.
     *
     * @param  \PHPStan\Php\PhpVersion  $other  The other version to compare with.
     * @return int `-1` if this `major.minor` version is less than `$other`,
     *              `0` if `major.minor` is equal between both,
     *              `1` if this `major.minor` version is greater than `$other`.
     */
    public function compareMinorVersion(\PHPStan\Php\PhpVersion $other): int
    {
        $majorComparison = $this->compareMajorVersion($other);
        if ($majorComparison !== 0) {
            return $majorComparison;
        }

        return $this->minor <=> self::getMinorFromVersionId($other->getVersionId());
    }

    /**
     * Compare this version with another.
     *
     * @param  \PHPStan\Php\PhpVersion  $other  The other version to compare with.
     * @return int `-1` if this version is less than `$other`,
     *             `0` if they are equal,
     *             `1` if this version is greater than `$other`.
     */
    public function compare(\PHPStan\Php\PhpVersion $other): int
    {
       return $this->getVersionId() <=> $other->getVersionId();
    }

    /**
     * Check if this version is bidirectionally (both backward and forward) compatible with another.
     *
     * @param  \PHPStan\Php\PhpVersion  $other  The other version to check against.
     * @return bool `true` if this version is bidirectionally compatible with `$other`, `false` otherwise.
     */
    public function isBidirectionallyCompatibleWith(\PHPStan\Php\PhpVersion $other): bool
    {
        return $this->featureVersionId === self::getFeatureVersionId($other->getVersionId());
    }

    /**
     * Check if this version is backwards compatible with another.
     *
     * @todo This should probably be updated with special cases of incompatible changes, since PHP doesn't follow SemVer
     *       very strictly.
     *
     * @param  \PHPStan\Php\PhpVersion  $other  The other version to check against.
     * @return bool `true` if this version is backwards compatible with `$other`, `false` otherwise.
     */
    public function isBackwardsCompatibleWith(\PHPStan\Php\PhpVersion $other): bool
    {
        return 0 === $this->compareMajorVersion($other)
            && $this->patch >= self::getPatchFromVersionId($other->getVersionId());
    }

    /**
     * Check if this version is forward compatible with another.
     *
     * @todo This should probably be updated with special cases of incompatible changes, since PHP doesn't follow SemVer
     *       very strictly.
     *
     * @param  \PHPStan\Php\PhpVersion  $other  The other version to check against.
     * @return bool `true` if this version is forward compatible with `$other`, `false` otherwise.
     */
    public function isForwardCompatibleWith(\PHPStan\Php\PhpVersion $other): bool
    {
        return 0 === $this->compareMajorVersion($other)
            && $this->patch <= self::getPatchFromVersionId($other->getVersionId());
    }

    /**
     * Return version in `xy` format, e.g. `74` for PHP 7.4.29.
     *
     * @return string A version string in `xy` format, e.g. "74".
     */
    public function getMajorMinor(): string
    {
        return $this->major . $this->minor;
    }
}

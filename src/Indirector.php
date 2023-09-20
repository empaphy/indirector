<?php

/**
 * @noinspection PhpUnused
 * @noinspection UnknownInspectionInspection
 */

declare(strict_types=1);

namespace Empaphy\Indirector;

use Empaphy\Indirector\Php\PhpVersion;
use Empaphy\Indirector\Processor\IncludeFileProcessor;
use Empaphy\Indirector\Processor\RectorProcessor;
use Rector\Config\RectorConfig;
use Rector\Core\DependencyInjection\RectorContainerFactory;
use Rector\Core\ValueObject\Bootstrap\BootstrapConfigs;
use Rector\Core\ValueObject\PhpVersion as PhpVersionId;
use Rector\Set\ValueObject\DowngradeLevelSetList;

final class Indirector
{
    /**
     * @var \Empaphy\Indirector\Php\PhpVersion $sourcePhpVersion
     * @readonly
     */
    public $sourcePhpVersion;

    /**
     * @var \Empaphy\Indirector\Php\PhpVersion $sourcePhpVersion
     * @readonly
     */
    public $targetPhpVersion;

    /**
     * @var \Rector\Config\RectorConfig
     */
    private $rectorConfig;

    public function __construct()
    {
        // TODO: this must be resolved from composer.json.
        $this->sourcePhpVersion = new PhpVersion(PhpVersionId::PHP_82);
        $this->targetPhpVersion = new PhpVersion(PHP_VERSION_ID);
    }

    /**
     * Enable Indirector.
     *
     * @return void
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function enable(): void
    {
        if ($this->isIndirectorNeeded()) {
            $bootstrapConfigs       = new BootstrapConfigs(null, []);
            $rectorContainerFactory = new RectorContainerFactory();
            $this->rectorConfig = $rectorContainerFactory->createFromBootstrapConfigs($bootstrapConfigs);

            $this->rectorConfig->phpstanConfig(dirname(__DIR__) . '/phpstan.neon');
            $this->rectorConfig->alias(RectorProcessor::class, IncludeFileProcessor::class);
            $this->rectorConfig->singleton(__CLASS__, function () { return $this; });
            $this->rectorConfig->sets([
                constant(DowngradeLevelSetList::class . "::DOWN_TO_PHP_{$this->targetPhpVersion->getMajorMinor()}")
            ]);

            $processor = $this->rectorConfig->make(IncludeFileProcessor::class);

            IncludeFileStreamWrapper::register(static function ($path, $mode, $options, &$opened_path) use ($processor) {
                return $processor->processFile($path);
            });
        }
    }

    /**
     * @return bool
     */
    public function isIndirectorNeeded(): bool
    {
        return ! $this->sourcePhpVersion->isBidirectionallyCompatibleWith($this->targetPhpVersion);
    }

    /**
     * @param  callable $callable
     *
     * @return $this
     */
    public function configureRector(callable $callable): self
    {
        $callable($this->rectorConfig);

        return $this;
    }

    /**
     * @return \Rector\Config\RectorConfig
     * @internal
     */
    public function getRectorConfig(): RectorConfig
    {
        return $this->rectorConfig;
    }

    /**
     * @param  string $cacheDirectory
     * @return $this
     */
    public function setCacheDirectory(string $cacheDirectory): self
    {
        $this->rectorConfig->cacheDirectory($cacheDirectory);

        return $this;
    }
}

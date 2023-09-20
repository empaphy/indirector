<?php

/**
 * @noinspection PhpUnused
 * @noinspection UnknownInspectionInspection
 */

declare(strict_types=1);

namespace Empaphy\Indirector\Config;

use Empaphy\Indirector\Processor\IncludeFileProcessor;
use Empaphy\Indirector\Processor\RectorProcessor;
use Empaphy\Indirector\StreamWrapper\Config\IncludeFileStreamWrapperConfig;
use PHPStan\Php\PhpVersion;
use Rector\Config\RectorConfig;
use Rector\Core\DependencyInjection\RectorContainerFactory;
use Rector\Core\ValueObject\Bootstrap\BootstrapConfigs;
use Rector\Core\ValueObject\PhpVersion as PhpVersionId;
use Rector\Set\ValueObject\DowngradeLevelSetList;

final class RectorStreamWrapperConfig implements IncludeFileStreamWrapperConfig
{
    /**
     * @var null|string
     */
    private $cacheDirectory;

    /**
     * @var \Rector\Config\RectorConfig
     */
    private $rectorConfig;

    public function __construct()
    {
        $levelSetList = DowngradeLevelSetList::class . '::DOWN_TO_PHP_' . PHP_MAJOR_VERSION . PHP_MINOR_VERSION;

        $bootstrapConfigs       = new BootstrapConfigs(null, []);
        $rectorContainerFactory = new RectorContainerFactory();

        $this->rectorConfig = $rectorContainerFactory->createFromBootstrapConfigs($bootstrapConfigs);
        $this->rectorConfig->phpstanConfig(dirname(__DIR__, 2) . '/phpstan.neon');
        $this->rectorConfig->alias(RectorProcessor::class, IncludeFileProcessor::class);
        $this->rectorConfig->singleton(__CLASS__, function () {
            return $this;
        });
        $this->rectorConfig->sets([constant($levelSetList)]);
    }

    /**
     * @return \Empaphy\Indirector\Processor\IncludeFileProcessor
     */
    public function getIncludeFileProcessor(): IncludeFileProcessor
    {
        return $this->rectorConfig->make(IncludeFileProcessor::class);
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
     * @return null|string
     */
    public function getCacheDirectory(): ?string
    {
        return $this->cacheDirectory;
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
     *
     * @return $this
     */
    public function setCacheDirectory(string $cacheDirectory): self
    {
        $this->cacheDirectory = $cacheDirectory;
        $this->rectorConfig->cacheDirectory($cacheDirectory);

        return $this;
    }
}

<?php

/**
 * @noinspection PhpUnused
 * @noinspection UnknownInspectionInspection
 */

declare(strict_types=1);

namespace Empaphy\StreamWrapper\Config;

use Empaphy\StreamWrapper\Processor\IncludeFileProcessor;
use Empaphy\StreamWrapper\Processor\RectorProcessor;
use Rector\Config\RectorConfig;
use Rector\Core\DependencyInjection\RectorContainerFactory;
use Rector\Core\ValueObject\Bootstrap\BootstrapConfigs;
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

        /** @var \Rector\Config\RectorConfig $rectorConfig */
        $rectorConfig = $rectorContainerFactory->createFromBootstrapConfigs($bootstrapConfigs);

        $rectorConfig->singleton(IncludeFileProcessor::class, function () {
            return $this->rectorConfig->make(RectorProcessor::class);
        });
        $rectorConfig->singleton(__CLASS__, function () {
            return $this;
        });
        $rectorConfig->sets([constant($levelSetList)]);

        $this->rectorConfig = $rectorConfig;
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

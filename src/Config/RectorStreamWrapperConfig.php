<?php
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace Empaphy\StreamWrapper\Config;

use Empaphy\StreamWrapper\Processor\ForkedRectorProcessor;
use Empaphy\StreamWrapper\Processor\RectorProcessor;
use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;
use Rector\Core\DependencyInjection\LazyContainerFactory;
use Rector\Set\ValueObject\DowngradeLevelSetList;

final class RectorStreamWrapperConfig
{
    /**
     * @var \Rector\Config\RectorConfig
     */
    private $container;

    /**
     * @var string
     */
    private $rootDirectory;

    public function __construct()
    {
        $factory = new LazyContainerFactory();

        $this->container = $factory->create();

        $levelSetList = 'DOWN_TO_PHP_' . PHP_MAJOR_VERSION . PHP_MINOR_VERSION;

        $this->processorClass(ForkedRectorProcessor::class);

        $this->container->cacheClass(FileCacheStorage::class);
        $this->container->sets([
            constant(DowngradeLevelSetList::class . '::' . $levelSetList),
        ]);
    }

    /**
     * @param  callable $callable
     *
     * @return $this
     */
    public function configureRector(callable $callable): self
    {
        $callable($this->container);

        return $this;
    }

    /**
     * @return \Rector\Config\RectorConfig
     * @internal
     */
    public function getContainer(): RectorConfig
    {
        return $this->container;
    }

    /**
     * @return string
     */
    public function getRootDirectory(): string
    {
        return $this->rootDirectory;
    }

    /**
     * @param  string $class
     *
     * @return $this
     */
    public function processorClass(string $class): self
    {
        $this->container->singleton(RectorProcessor::class, function () use ($class) {
            return $this->container->make($class);
        });

        return $this;
    }

    /**
     * @param  string $cacheDirectory
     *
     * @return $this
     */
    public function setCacheDirectory(string $cacheDirectory): self
    {
        $this->container->cacheDirectory($cacheDirectory);

        return $this;
    }

    /**
     * @param  string $rootDirectory
     *
     * @return $this
     */
    public function setRootDirectory(string $rootDirectory): self
    {
        $this->rootDirectory = $rootDirectory;

        return $this;
    }
}

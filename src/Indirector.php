<?php /**  */

/**
 * @noinspection PhpMultipleClassDeclarationsInspection
 * @noinspection PhpUnused
 * @noinspection UnknownInspectionInspection
 */

declare(strict_types=1);

namespace Empaphy\Indirector;

use Empaphy\Indirector\Php\PhpVersion;
use Empaphy\Indirector\Processor\IncludeFileProcessor;
use Empaphy\Indirector\Processor\RectorProcessor;
use Rector\Config\RectorConfig;
use Rector\Core\DependencyInjection\LazyContainerFactory;
use Rector\Core\ValueObject\Bootstrap\BootstrapConfigs;
use Rector\Set\ValueObject\DowngradeLevelSetList;

final class Indirector
{
    /**
     * @var \Empaphy\Indirector\Php\PhpVersion $sourcePhpVersion
     */
    private $sourcePhpVersion;

    /**
     * @var \Empaphy\Indirector\Php\PhpVersion $sourcePhpVersion
     * @readonly
     */
    public $targetPhpVersion;

    /**
     * @var \Rector\Config\RectorConfig|null
     */
    private $rectorConfig;

    private static $instance = null;

    private $bootstrapConfigs = null;

    private $lazyContainerFactory = null;

    private function __construct(int $targetPhpVersionId = PHP_VERSION_ID)
    {
        $this->targetPhpVersion = new PhpVersion($targetPhpVersionId);
    }

    public static function get(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param  class-string  $dependency
     * @return void
     *
     * @throws \Empaphy\Indirector\DependencyNotYetAvailableException
     */
    public static function ensureDependencyAvailable(string $dependency): void
    {
        if (! class_exists($dependency)) {
            throw new DependencyNotYetAvailableException($dependency);
        }
    }

    /**
     * Get the Rector configuration.
     *
     * This function will gracefully return null if the Rector classes are not yet available.
     *
     * @return \Rector\Config\RectorConfig
     *
     * @noinspection PhpMissingReturnTypeInspection
     * @noinspection ReturnTypeCanBeDeclaredInspection
     * @throws \Empaphy\Indirector\DependencyNotYetAvailableException
     */
    public function getRectorConfig(): RectorConfig
    {
        if (null === $this->rectorConfig) {
            $bootstrapConfigs     = $this->getBoostrapConfigs();
            $lazyContainerFactory = $this->getLazyContainerFactory();

            $this->rectorConfig = $lazyContainerFactory->create();
            foreach ($bootstrapConfigs->getConfigFiles() as $configFile) {
                $this->rectorConfig->import($configFile);
            }
            $this->rectorConfig->sets([
                constant(DowngradeLevelSetList::class . "::DOWN_TO_PHP_{$this->targetPhpVersion->getMajorMinor()}")
            ]);
            $this->rectorConfig->boot();

            $this->rectorConfig->phpstanConfig(dirname(__DIR__) . '/phpstan.neon');
            $this->rectorConfig->alias(RectorProcessor::class, IncludeFileProcessor::class);
            $this->rectorConfig->singleton(__CLASS__, function () { return $this; });
        }

        return $this->rectorConfig;
    }

    /**
     * Enable Indirector.
     *
     * @param  int  $sourcePhpVersion  The (highest) PHP version that the source code is expected to be compatible with.
     * @return void
     *
     * @noinspection PhpUnusedParameterInspection
     * @throws \Empaphy\Indirector\DependencyNotYetAvailableException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function enable(int $sourcePhpVersion = 80208): void
    {
        // TODO: support multiple instances for multiple sourcePhpVersions.
        if (null !== $this->sourcePhpVersion) {
            return;
        }

        $this->sourcePhpVersion = new PhpVersion($sourcePhpVersion);

        if ($this->isIndirectorNeeded()) {
            IncludeFileStreamWrapper::register(
                function ($path, $mode, $options, &$opened_path) {
                    $rectorConfig = $this->getRectorConfig();

                    /** @noinspection OneTimeUseVariablesInspection */
                    $processor = $rectorConfig->make(IncludeFileProcessor::class);

                    return $processor->processFile($path);
                }
            );
        }
    }

    /**
     * Disable Indirector.
     *
     * @return void
     */
    public function disable(): void
    {
        if (null === $this->sourcePhpVersion) {
            return;
        }

        IncludeFileStreamWrapper::unregister();

        $this->rectorConfig     = null;
        $this->sourcePhpVersion = null;
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
        $callable($this->getRectorConfig());

        return $this;
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

    /**
     * This function will gracefully return null if the Rector classes are not yet available.
     *
     * @return \Rector\Core\ValueObject\Bootstrap\BootstrapConfigs
     *
     * @noinspection PhpMissingReturnTypeInspection
     * @noinspection ReturnTypeCanBeDeclaredInspection
     * @throws \Empaphy\Indirector\DependencyNotYetAvailableException
     */
    private function getBoostrapConfigs()
    {
        if (null === $this->bootstrapConfigs) {
            self::ensureDependencyAvailable(BootstrapConfigs::class);
            $this->bootstrapConfigs = new BootstrapConfigs(null, []);
        }

        return $this->bootstrapConfigs;
    }

    /**
     * This function will gracefully return null if the Rector classes are not yet available.
     *
     * @return \Rector\Core\DependencyInjection\LazyContainerFactory
     *
     * @noinspection PhpMissingReturnTypeInspection
     * @noinspection ReturnTypeCanBeDeclaredInspection
     * @throws \Empaphy\Indirector\DependencyNotYetAvailableException
     */
    private function getLazyContainerFactory()
    {
        if (null === $this->lazyContainerFactory) {
            self::ensureDependencyAvailable(LazyContainerFactory::class);
            $this->lazyContainerFactory = new LazyContainerFactory();
        }

        return $this->lazyContainerFactory;
    }
}

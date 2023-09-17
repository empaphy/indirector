<?php

/**
 * @noinspection UnknownInspectionInspection
 */

declare(strict_types=1);

namespace Empaphy\StreamWrapper;

use Rector\ChangesReporting\Output\ConsoleOutputFormatter;
use Rector\Core\Application\FileProcessor;
use Rector\Core\Bootstrap\RectorConfigsResolver;
use Rector\Core\DependencyInjection\RectorContainerFactory;
use Rector\Core\Provider\CurrentFileProvider;
use Rector\Core\ValueObject\Application\File;
use Rector\Core\ValueObject\Bootstrap\BootstrapConfigs;
use Rector\Core\ValueObject\Configuration;
use RuntimeException;

/**
 * This PHP stream wrapper implements a `rectorfile://` protocol handler that allows one to use
 * [Rector](https://github.com/rectorphp/rector) to downgrade included PHP code on the fly.
 */
class RectorStreamWrapper implements SeekableResourceWrapper
{
    use WrapsFileStream {
        stream_open as _stream_open;
    }

    private const STREAM_OPEN_FOR_INCLUDE = 0x00000080;

    /**
     * @var int
     */
    private static $registered = 0;

    /**
     * @var \Rector\Core\Application\FileProcessor
     */
    private $rectorFileProcessor;

    /**
     * @var \Rector\Core\ValueObject\Configuration
     */
    private $rectorConfiguration;

    /**
     * @var \Rector\Core\Provider\CurrentFileProvider
     */
    private $rectorCurrentFileProvider;

    /**
     * @return \Rector\Core\ValueObject\Bootstrap\BootstrapConfigs
     * @see RectorConfigsResolver::provide()
     */
    private function provideRectorConfigs(): BootstrapConfigs
    {
        // TODO: implement something here
        $mainConfigFile = null;
        $configFiles    = [dirname(__DIR__) . DIRECTORY_SEPARATOR . 'rector.php'];

        return new BootstrapConfigs($mainConfigFile, $configFiles);
    }

    /**
     * @return bool
     */
    public static function register(): bool
    {
        if (self::$registered > 0) {
            return true;
        }

        stream_wrapper_unregister('file');
        self::$registered++;
        $result = stream_wrapper_register('file', __CLASS__);

        if (! $result) {
            throw new RuntimeException('Failed to register stream wrapper.');
        }

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        return $result;
    }

    public static function unregister(): bool
    {
        $result = true;

        if (self::$registered > 0) {
            $result           = stream_wrapper_restore('file');
            self::$registered = 0;
        }

        return $result;
    }

    /**
     * Opens an included PHP file and downgrades it to the currently running PHP version using Rector.
     * This method is called immediately after the wrapper is initialized (i.e. by `include`).
     *
     * @param  string      $path          Specifies the path that was passed to the original function.
     * @param  string      $mode          The mode used to open the file, as detailed for {@see fopen()}.
     * @param  int         $options       Holds additional flags set by the streams API.
     * @param  null|string $opened_path   If the path is opened successfully, and {@see STREAM_USE_PATH} is set in
     *                                    options, `opened_path` should be set to the full path of the file/resource
     *                                    that was actually opened.
     *
     * @return bool `true` on success or `false` on failure.
     * @throws \RectorPrefix202309\Psr\Container\ContainerExceptionInterface
     * @throws \RectorPrefix202309\Psr\Container\NotFoundExceptionInterface
     */
    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        self::unregister();

        $bootstrapConfigs       = $this->provideRectorConfigs();
        $rectorContainerFactory = new RectorContainerFactory();

        $this->rectorConfiguration = new Configuration(
            true,
            false,
            false,
            ConsoleOutputFormatter::NAME,
            ['php'],
            [],
            false
        );

        $rectorContainer                 = $rectorContainerFactory->createFromBootstrapConfigs($bootstrapConfigs);
        $this->rectorCurrentFileProvider = $rectorContainer->get(CurrentFileProvider::class);
        $this->rectorFileProcessor       = $rectorContainer->get(FileProcessor::class);

        $content   = null;
        $including = (bool) ($options & self::STREAM_OPEN_FOR_INCLUDE);

        try {
            if ($including) {
                $fork = new Fork(function () use ($path) {
                    include $path;

                    $realpath = stream_resolve_include_path($path);

                    if (false === $realpath) {
                        return false;
                    }

                    $rectorFile = new File($realpath, file_get_contents($realpath, true));
                    $this->rectorCurrentFileProvider->setFile($rectorFile);
                    $fileProcessResult = $this->rectorFileProcessor->processFile(
                        $rectorFile,
                        $this->rectorConfiguration
                    );
                    $systemErrors      = $fileProcessResult->getSystemErrors();

                    if (! empty($systemErrors)) {
                        return false;
                    }

                    if ($rectorFile->hasChanged()) {
                        return $rectorFile->getFileContent();
                    }

                    return null;
                });

                $content = $fork->wait();
            }

            if (null === $content) {
                return $this->_stream_open($path, $mode, $options, $opened_path);
            }

            $this->handle = fopen('php://memory', 'rb+');

            fwrite($this->handle, $content);
            rewind($this->handle);
        } finally {
            self::register();
        }

        return $this->handle !== false;
    }
}

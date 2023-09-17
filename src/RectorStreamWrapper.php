<?php

/**
 * @noinspection UnknownInspectionInspection
 */

declare(strict_types=1);

namespace Empaphy\StreamWrapper;

use Empaphy\StreamWrapper\Config\RectorStreamWrapperConfig;
use Empaphy\StreamWrapper\Processor\RectorProcessor;
use Rector\ChangesReporting\Output\ConsoleOutputFormatter;
use Rector\Core\Application\FileProcessor;
use Rector\Core\Provider\CurrentFileProvider;
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
     * @var \Empaphy\StreamWrapper\Config\RectorStreamWrapperConfig
     */
    private static $config;

    /**
     * @var int
     */
    private static $registered = 0;

    /**
     * @var \RectorPrefix202309\Psr\Container\ContainerInterface
     */
    private $rectorContainer;

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
     * @param  null|\Empaphy\StreamWrapper\Config\RectorStreamWrapperConfig $config
     *
     * @return bool
     */
    public static function register(?RectorStreamWrapperConfig $config = null): bool
    {
        if (self::$registered > 0) {
            return true;
        }

        self::$config = $config ?? self::$config ?? new RectorStreamWrapperConfig();

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

        if (! $this->rectorContainer) {
            $this->setupRector();
        }

        $content   = null;
        $including = $options & self::STREAM_OPEN_FOR_INCLUDE;

        try {
            if ($including) {
                /** @var \Empaphy\StreamWrapper\Processor\RectorProcessor $processor */
                $processor = $this->rectorContainer->get(RectorProcessor::class);
                $content   = $processor->process($path);
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

    /**
     * @return void
     * @throws \RectorPrefix202309\Psr\Container\ContainerExceptionInterface
     * @throws \RectorPrefix202309\Psr\Container\NotFoundExceptionInterface
     */
    protected function setupRector(): void
    {
        $this->rectorContainer = self::$config->getContainer();
        $this->rectorContainer->boot();

        $this->rectorCurrentFileProvider = $this->rectorContainer->get(CurrentFileProvider::class);
        $this->rectorFileProcessor       = $this->rectorContainer->get(FileProcessor::class);

        $this->rectorConfiguration = new Configuration(
            true,
            false,
            false,
            ConsoleOutputFormatter::NAME,
            ['php'],
            [],
            false
        );
    }
}

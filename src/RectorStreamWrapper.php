<?php

/**
 * @noinspection UnknownInspectionInspection
 */

declare(strict_types=1);

namespace Empaphy\StreamWrapper;

use Empaphy\StreamWrapper\Config\RectorStreamWrapperConfig;
use Empaphy\StreamWrapper\Processor\IncludeFileProcessor;
use RuntimeException;
use Throwable;

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
     * @var bool
     */
    private static $initialized = false;

    /**
     * @var int
     */
    private static $registered = 0;

    public static function initialize(?RectorStreamWrapperConfig $config = null): void
    {
        if (false === self::$initialized) {
            self::$config = $config ?? self::$config ?? new RectorStreamWrapperConfig();
            self::$initialized = true;
        }
    }

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
        if (self::$registered <= 0) {
            return true;
        }

        self::$registered = 0;

        return stream_wrapper_restore('file');
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
     */
    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        self::unregister();

        $container = self::$config->getRectorConfig();

        // Wrap all the code in this function in a try block, so we can
        // re-register the stream wrapper even if an exception is thrown.
        try {
            $content = null;

            if ($options & self::STREAM_OPEN_FOR_INCLUDE) {
                try {
                    /** @var \Empaphy\StreamWrapper\Processor\IncludeFileProcessor $processor */
                    $processor = $container->get(IncludeFileProcessor::class);
                    $content   = $processor->processFile($path);
                } catch (Throwable $t) {
                    trigger_error($t->getMessage(), E_USER_WARNING);

                    // Fall back to regular stream wrapper.
                    return $this->_stream_open($path, $mode, $options, $opened_path);
                }
            }

            $this->handle = fopen('php://memory', 'rb+');
            fwrite($this->handle, $content);
            rewind($this->handle);

            return $this->handle !== false;
        } finally {
            self::register();
        }
    }
}

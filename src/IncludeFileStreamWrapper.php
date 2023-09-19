<?php

/**
 * @noinspection UnknownInspectionInspection
 */

declare(strict_types=1);

namespace Empaphy\Indirector;

use Empaphy\Indirector\StreamWrapper\Config\IncludeFileStreamWrapperConfig;
use Empaphy\Indirector\StreamWrapper\FileStreamWrapper;
use RuntimeException;
use Throwable;

/**
 * This file stream wrapper can be used to modify the PHP include files at runtime.
 */
class IncludeFileStreamWrapper extends FileStreamWrapper
{
    private const STREAM_OPEN_FOR_INCLUDE = 0x00000080;

    /**
     * @var \Empaphy\Indirector\StreamWrapper\Config\IncludeFileStreamWrapperConfig
     */
    private static $config;

    /**
     * @var bool
     */
    private static $initialized = false;

    /**
     * @param  \Empaphy\Indirector\StreamWrapper\Config\IncludeFileStreamWrapperConfig  $config
     * @return void
     */
    public static function initialize(IncludeFileStreamWrapperConfig $config): void
    {
        if (false === self::$initialized) {
            self::$config      = $config;
            self::$initialized = true;
        }

        if (! self::register()) {
            throw new RuntimeException('Failed to register stream wrapper.');
        }
    }

    /**
     * Opens an included PHP file and processes it using the configured include file processor.
     *
     * This method is called immediately after the wrapper is initialized (i.e. by `include`).
     *
     * @param  string       $path         Specifies the path that was passed to the original function.
     * @param  string       $mode         The mode used to open the file, as detailed for {@see fopen()}.
     * @param  int          $options      Holds additional flags set by the streams API.
     * @param  null|string  $opened_path  If the path is opened successfully, and {@see STREAM_USE_PATH} is set in
     *                                    options, `opened_path` should be set to the full path of the file/resource
     *                                    that was actually opened.
     * @return bool `true` on success or `false` on failure.
     */
    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        self::unregister();

        // Wrap all the code in this function in a try block, so we can
        // re-register the stream wrapper even if an exception is thrown.
        try {
            $content = null;

            if ($options & self::STREAM_OPEN_FOR_INCLUDE) {
                try {
                    $processor = self::$config->getIncludeFileProcessor();
                    $content   = $processor->processFile($path);
                } catch (Throwable $t) {
                    trigger_error("{$t->getMessage()}\n{$t->getTraceAsString()}", E_USER_WARNING);

                    // Fall back to regular stream wrapper.
                    return parent::stream_open($path, $mode, $options, $opened_path);
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

<?php

/**
 * @noinspection UnknownInspectionInspection
 */

declare(strict_types=1);

namespace Empaphy\Indirector;

use Empaphy\Indirector\StreamWrapper\FileStreamWrapperOverride;
use Empaphy\Indirector\StreamWrapper\StreamWrapperOverride;
use RuntimeException;
use Throwable;

/**
 * This file stream wrapper can be used to modify the PHP include files at runtime.
 */
final class IncludeFileStreamWrapper extends FileStreamWrapperOverride implements StreamWrapperOverride
{
    /**
     * Flag indicating that the stream was opened for inclusion.
     */
    private const STREAM_OPEN_FOR_INCLUDE = 128;

    /**
     * The callback function to be called when a file is included.
     *
     * @var callable
     */
    private static $onInclude;

    /**
     * Registers this stream wrapper.
     *
     * @param  callable  $onInclude
     * @return void
     */
    public static function register(callable $onInclude): void
    {
        self::$onInclude = $onInclude;

        if (! self::overrideStreamWrapper()) {
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
     * @param  int          $options      Holds additional flags set by the streams API:
     *                                      - {@see STREAM_USE_PATH}:      Path should be opened using the include path.
     *                                      - {@see STREAM_REPORT_ERRORS}: Wrapper is responsible for raising errors
     *                                        using {@see trigger_error()} during opening of the stream. If this flag is
     *                                        not set, you should not raise any errors.
     * @param  null|string  $opened_path  If the path is opened successfully, and {@see STREAM_USE_PATH} is set in
     *                                    options, `opened_path` should be set to the full path of the file/resource
     *                                    that was actually opened.
     * @return bool `true` on success or `false` on failure.
     */
    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        $this->disable();

        // Wrap all the code in this function in a try block, so we can
        // re-register the stream wrapper even if an exception is thrown.
        try {
            if ($options & self::STREAM_OPEN_FOR_INCLUDE) {
                return $this->stream_open_for_include($path, $mode, $options, $opened_path);
            }

            return parent::stream_open($path, $mode, $options, $opened_path);
        } finally {
            $this->enable();
        }
    }

    /**
     * Opens a file for inclusion and presents it to the {@see self::$onInclude} callback function.
     *
     * @param  string       $path         Specifies the path that was passed to the original function.
     * @param  string       $mode         The mode used to open the file, as detailed for {@see fopen()}.
     * @param  int          $options      Holds additional flags set by the streams API:
     *                                      - {@see STREAM_USE_PATH}:      Path should be opened using the include path.
     *                                      - {@see STREAM_REPORT_ERRORS}: Wrapper is responsible for raising errors
     *                                        using {@see trigger_error()} during opening of the stream. If this flag is
     *                                        not set, you should not raise any errors.
     * @param  null|string  $opened_path  If the path is opened successfully, and {@see STREAM_USE_PATH} is set in
     *                                    options, `opened_path` should be set to the full path of the file/resource
     *                                    that was actually opened.
     * @return bool `true` on success or `false` on failure.
     */
    protected function stream_open_for_include(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        $use_include_path = (bool) ($options & STREAM_USE_PATH);
        $report_errors    = (bool) ($options & STREAM_REPORT_ERRORS);

        if ($use_include_path) {
            $path = stream_resolve_include_path($path);
        }

        if (false === $path || ! file_exists($path)) {
            if ($report_errors) {
                trigger_error("Couldn't find '{$path}' for inclusion", E_USER_WARNING);
            }

            return false;
        }

        if ($use_include_path) {
            $opened_path = $path;
        }

        try {
            $content = call_user_func(self::$onInclude, $path, $mode, $options, $opened_path);
        } catch (Throwable $e) {
            if ($report_errors) {
                trigger_error(sprintf(
                    "%s: %s (%s)\n in %s:%s\n%s\nwhile including '%s'",
                    get_class($e),
                    $e->getMessage(),
                    $e->getCode(),
                    $e->getFile(),
                    $e->getLine(),
                    $e->getTraceAsString(),
                    $path
                ), E_USER_WARNING);
            }

            return false;
        }

        $this->handle = fopen('php://memory', 'rb+');
        fwrite($this->handle, $content);
        rewind($this->handle);

        return $this->handle !== false;
    }
}

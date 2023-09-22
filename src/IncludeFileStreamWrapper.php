<?php

/**
 * @noinspection UnknownInspectionInspection
 */

declare(strict_types=1);

namespace Empaphy\Indirector;

use Empaphy\Indirector\StreamWrapper\FileStreamWrapperOverride;
use RuntimeException;
use Throwable;

/**
 * This file stream wrapper can be used to modify the PHP include files at runtime.
 */
final class IncludeFileStreamWrapper extends FileStreamWrapperOverride
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
        $this->path = $path;

        if (self::INTERNAL_INCLUDE_FILE_NAME === $path) {
            return $this->open_internal_include();
        }

        if ($options & self::STREAM_OPEN_FOR_INCLUDE) {
            try {
                return $this->override($path, function () use ($path, $mode, $options, $opened_path) {
                    return $this->stream_open_for_include($path, $mode, $options, $opened_path);
                });
            } catch (UseFallbackThrowable $uft) {
                // Warn and then fall back to the default behaviour below.
                trigger_error("Fallback to regular include for `{$path}`:\n{$uft->getMessage()}", E_USER_NOTICE);
            }
        }

        return parent::stream_open($path, $mode, $options, $opened_path);
    }

    /**
     * Opens a file for inclusion and presents it to the {@see self::$onInclude} callback function.
     *
     * @param  string       $path             Specifies the path that was passed to the original function.
     * @param  string       $mode             The mode used to open the file, as detailed for {@see fopen()}.
     * @param  int          $options          Holds additional flags set by the streams API:
     *                                        - {@see STREAM_USE_PATH}:      Path should be opened using the include
     *                                        path.
     *                                        - {@see STREAM_REPORT_ERRORS}: Wrapper is responsible for raising errors
     *                                        using {@see trigger_error()} during opening of the stream. If this flag
     *                                        is
     *                                        not set, you should not raise any errors.
     * @param  null|string  $opened_path      If the path is opened successfully, and {@see STREAM_USE_PATH} is set in
     *                                        options, `opened_path` should be set to the full path of the
     *                                        file/resource
     *                                        that was actually opened.
     * @return bool `true` on success or `false` on failure.
     *
     * @throws \Empaphy\Indirector\UseFallbackThrowable
     */
    protected function stream_open_for_include(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        // First attempt to open the actual file.
        $this->file = $this->open($path, $mode, $options, $opened_path);

        if (false === $this->file) {
            return false;
        }

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
            $callback = self::$onInclude;
            $content  = $callback($path, $mode, $options, $opened_path);
        } catch (UseFallbackThrowable $uft) {
            throw $uft;
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

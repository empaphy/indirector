<?php

declare(strict_types=1);

namespace Empaphy\Indirector\StreamWrapper;

use RuntimeException;

/**
 * @implements \Empaphy\Indirector\StreamWrapper\ResourceWrapper
 */
trait WrapsStream
{
    /**
     * The current context, or null if no context was passed to the caller function.
     *
     * Use the stream_context_get_options() to parse the context.
     *
     * > **Note:**
     * > This property must be public so PHP can populate it with the actual context resource.
     *
     * @var resource|null
     */
    public $context;

    /**
     * The underlying resource.
     *
     * @var resource|false
     */
    private $handle = false;

    /**
     * Retrieve the underlying resource.
     *
     * This method is called in response to {@see stream_select()}.
     *
     * @param  int  $cast_as  Can be {@see STREAM_CAST_FOR_SELECT} when {@see stream_select()} is calling
     *                        {@see stream_cast()} or {@see STREAM_CAST_AS_STREAM} when {@see stream_cast()} is called
     *                        for other uses.
     * @return resource|false Should return the underlying stream resource used by the wrapper, or `false`.
     */
    public function stream_cast(int $cast_as)
    {
        return $this->handle;
    }

    /**
     * Close a resource.
     *
     * This method is called in response to {@see fclose()}.
     *
     * All resources that were locked, or allocated, by the wrapper should be released.
     *
     * @return bool `true` on success or `false` on failure
     */
    public function stream_close(): bool
    {
        return fclose($this->handle);
    }

    /**
     * Tests for end-of-file on a file pointer.
     *
     * @return bool `true` if the read/write position is at the end of the stream and if no more data is available to be
     *              read, or `false` otherwise.
     */
    public function stream_eof(): bool
    {
        return feof($this->handle);
    }

    /**
     * Change stream metadata.
     *
     * This method is called to set metadata on the stream. It is called when
     * one of the following functions is called on a stream URL:
     *
     *   - {@see touch()}
     *   - {@see chmod()}
     *   - {@see chown()}
     *   - {@see chgrp()}
     *
     * Please note that some of these operations may not be available on your
     * system.
     *
     * @param  string  $path    The file path or URL to set metadata. Note that in the case of a URL, it must be a ://
     *                          delimited URL. Other URL forms are not supported.
     * @param  int     $option  One of:
     *                            - {@see STREAM_META_TOUCH} (The method was called in response to {@see touch()})
     *                            - {@see STREAM_META_OWNER_NAME} (The method was called in response to {@see chown()}
     *                              with string parameter)
     *                            - {@see STREAM_META_OWNER} (The method was called in response to {@see chown()})
     *                            - {@see STREAM_META_GROUP_NAME} (The method was called in response to {@see chgrp()})
     *                            - {@see STREAM_META_GROUP} (The method was called in response to {@see chgrp()})
     *                            - {@see STREAM_META_ACCESS} (The method was called in response to {@see chmod()})
     * @param  mixed   $value   If option is
     *                            - {@see STREAM_META_TOUCH}: Array consisting of two arguments of the {@see touch()}
     *                              function.
     *                            - {@see STREAM_META_OWNER_NAME} or {@see STREAM_META_GROUP_NAME}: The name of the owner
     *                              user/group as string.
     *                            - {@see STREAM_META_OWNER} or {@see STREAM_META_GROUP}: The value owner user/group
     *                              argument as int.
     *                            - {@see STREAM_META_ACCESS}: The argument of the {@see chmod()} as int.
     * @return bool `true` on success or `false` on failure. If $option is not implemented, `false` should be returned.
     */
    public function stream_metadata(string $path, int $option, $value): bool
    {
        self::unregister();

        $return = false;

        try {
            switch ($option) {
                case STREAM_META_TOUCH:
                    if (empty($value)) {
                        $return = touch($path);
                    } else {
                        $return = touch($path, ...$value);
                    }
                    break;

                case STREAM_META_OWNER_NAME:
                case STREAM_META_OWNER:
                    $return = chown($path, $value);
                    break;

                case STREAM_META_GROUP_NAME:
                case STREAM_META_GROUP:
                    $return = chgrp($path, $value);
                    break;

                case STREAM_META_ACCESS:
                    $return = chmod($path, $value);
                    break;

                default:
                    throw new RuntimeException('Unknown stream_metadata option');
            }
        } finally {
            self::register();
        }

        return $return;
    }

    /**
     * Opens file.
     *
     * This method is called immediately after the wrapper is initialized (e.g. by {@see fopen()} and
     * {@see file_get_contents()}).
     *
     * @param  string       $path         Specifies the path that was passed to the original function.
     * @param  string       $mode         The mode used to open the file, as detailed for {@see fopen()}.
     * @param  int          $options      Holds additional flags set by the streams API.
     * @param  string|null  $opened_path  If the path is opened successfully, and {@see STREAM_USE_PATH} is set in
     *                                    options, `opened_path` should be set to the full path of the file/resource
     *                                    that was actually opened.
     * @return bool `true` on success or `false` on failure.
     */
    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        self::unregister();

        try {
            $use_include_path = (bool) ($options & STREAM_USE_PATH);
            $report_errors    = (bool) ($options & STREAM_REPORT_ERRORS);

            $this->handle = self::fopen($path, $mode, $use_include_path, $report_errors, $this->context);

            if (false === $this->handle) {
                return false;
            }

            if ($use_include_path) {
                $opened_path = stream_resolve_include_path($path);
            }

            return true;
        } finally {
            self::register();
        }
    }

    /**
     * @param  string         $filename
     * @param  string         $mode
     * @param  bool           $use_include_path
     * @param  bool           $report_errors
     * @param  resource|null  $context
     * @return false|resource
     */
    private static function fopen(
        string $filename,
        string $mode,
        bool $use_include_path,
        bool $report_errors,
        $context = null
    ) {
        if (! $report_errors) {
            if (null === $context) {
                return @fopen($filename, $mode, $use_include_path);
            }

            return @fopen($filename, $mode, $use_include_path, $context);
        }

        if (null === $context) {
            return fopen($filename, $mode, $use_include_path);
        }

        return fopen($filename, $mode, $use_include_path, $context);
    }

    /**
     * Read from stream.
     *
     * This method is called in response to {@see fread()} and {@see fgets()}.
     *
     * Emits {@see E_WARNING} if call to this method fails (i.e. not implemented).
     *
     * > **Note:**
     * > {@see static::stream_eof()} is called directly after calling {@see static::stream_read()} to check if EOF has
     * > been reached. If not implemented, EOF is assumed.
     *
     * > **Warning:**
     * > When reading the whole file (for example, with {@see file_get_contents()}), PHP will call
     * > {@see static::stream_read()} followed by {@see self::stream_eof()} in a loop but as long as
     * > {@see static::stream_read()} returns a non-empty string, the return value of {@see static::stream_eof()} is
     * > ignored.
     *
     * @param  int  $count  How many bytes of data from the current position should be returned.
     * @return string|false If there are less than $count bytes available, as many as are available should be returned.
     *                      If no more data is available, an empty string should be returned.
     *                      To signal that reading failed, false should be returned.
     */
    public function stream_read(int $count)
    {
        return fread($this->handle, $count);
    }

    /**
     * Change stream options.
     *
     * This method is called to set options on the stream.
     *
     * @param  int  $option
     * @param  int  $arg1
     * @param  int  $arg2
     * @return bool `true` on success or `false` on failure. If $option is not implemented, `false` should be returned.
     */
    public function stream_set_option(int $option, int $arg1, int $arg2): bool
    {
        switch ($option) {
            case STREAM_OPTION_BLOCKING:
                return stream_set_blocking($this->handle, (bool) $arg1);

            case STREAM_OPTION_READ_BUFFER:
                return stream_set_read_buffer($this->handle, $arg1) === 0;

            case STREAM_OPTION_WRITE_BUFFER:
                return stream_set_write_buffer($this->handle, $arg1) === 0;

            case STREAM_OPTION_READ_TIMEOUT:
                return stream_set_timeout($this->handle, $arg1, $arg2);

            default:
                return false;
        }
    }

    /**
     * Retrieve the current position of the file handle.
     *
     * @return int returns the current position of the file handle.
     */
    public function stream_tell(): int
    {
        return ftell($this->handle);
    }

    /**
     * Truncate stream.
     *
     * Will respond to truncation, e.g., through {@see ftruncate()}.
     *
     * @param  int  $new_size  The new size.
     * @return bool `true` on success or `false` on failure.
     */
    public function stream_truncate(int $new_size): bool
    {
        return ftruncate($this->handle, $new_size);
    }
}

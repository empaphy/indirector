<?php

declare(strict_types=1);

namespace Empaphy\StreamWrapper;

trait WrapsFileStream
{
    public $context;

    /**
     * @var resource|false
     */
    private $handle = false;

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
            if (isset($this->context)) {
                $this->handle = fopen($path, $mode, (bool) $options, $this->context);
            } else {
                $this->handle = fopen($path, $mode, (bool) $options);
            }
        } finally {
            self::register();
        }

        return false !== $this->handle;
    }

    /**
     * Read the included PHP file.
     *
     * @param  int  $count  How many bytes of data from the current position should be returned.
     * @return string|false If there are less than $count bytes available, as many as are available should be returned.
     *                      If no more data is available, an empty string should be returned.
     *                      To signal that reading failed, false should be returned.
     */
    public function stream_read(int $count): string|false
    {
        /** @noinspection OneTimeUseVariablesInspection
         *  @noinspection PhpUnnecessaryLocalVariableInspection */
        $contents = fread($this->handle, $count);

        return $contents;
    }

    /**
     * Retrieve information about a file resource.
     *
     * This method is called in response to {@see fstat()}.
     *
     * @return array|false See {@see stat()}.
     */
    public function stream_stat(): array|false
    {
        return fstat($this->handle);
    }

    /**
     * Change stream options.
     *
     * This method is called to set options on the stream.
     *
     * @param  int  $option
     * @param  int  $arg1
     * @param  int  $arg2
     * @return bool `true` on success or `false` on failure. If $option is not implemented, false should be returned.
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
        }

        return false;
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
     * Write to stream.
     *
     * This method is called in response to fwrite().
     *
     * > **Note:**
     * > Remember to update the current position of the stream by number of bytes that were successfully written.
     *
     * @param  string  $data  Should be stored into the underlying stream.
     *                        > **Note:**
     *                        > If there is not enough room in the underlying stream, store as much as possible.
     * @return int Should return the number of bytes that were successfully stored, or 0 if none could be stored.
     *             > **Note:**
     *             > If the return value is greater than the length of $data, {@see E_WARNING} will be emitted and the
     *             > return value will be truncated to its length.
     */
    public function stream_write(string $data): int
    {
        return fwrite($this->handle, $data) ?: 0;
    }

    /**
     * Tests for end-of-file on a file pointer.
     *
     * @return bool `true` if the read/write position is at the end of the stream and if no more data is available to be
     *              read, or false otherwise.
     */
    public function stream_eof(): bool
    {
        return feof($this->handle);
    }

    /**
     * Seeks to specific location in a stream.
     *
     * @param  int  $offset  The stream offset to seek to.
     * @param  int  $whence  Possible values:
     *                         - {@see SEEK_SET} - Set position equal to $offset bytes.
     *                         - {@see SEEK_CUR} - Set position to current location plus $offset.
     *                         - {@see SEEK_END} - Set position to end-of-file plus $offset.
     * @return bool `true` if the position was updated, `false` otherwise.
     */
    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        return 0 === fseek($this->handle, $offset, $whence);
    }

    /**
     * Change stream metadata.
     *
     * This method is called to set metadata on the stream. It is called when one of the following functions is called
     * on a stream URL:
     *
     *   - {@see touch()}
     *   - {@see chmod()}
     *   - {@see chown()}
     *   - {@see chgrp()}
     *
     * Please note that some of these operations may not be available on your system.
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
     * @param  mixed   $value  If option is
     *                           - {@see STREAM_META_TOUCH}: Array consisting of two arguments of the {@see touch()}
     *                             function.
     *                           - {@see STREAM_META_OWNER_NAME} or {@see STREAM_META_GROUP_NAME}: The name of the owner
     *                             user/group as string.
     *                           - {@see STREAM_META_OWNER} or {@see STREAM_META_GROUP}: The value owner user/group
     *                             argument as int.
     *                           - {@see STREAM_META_ACCESS}: The argument of the {@see chmod()} as int.
     * @return bool `true` on success or `false` on failure. If $option is not implemented, `false` should be returned.
     */
    public function stream_metadata(string $path, int $option, mixed $value): bool
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
}

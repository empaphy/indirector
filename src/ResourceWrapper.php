<?php

declare(strict_types=1);

namespace Empaphy\StreamWrapper;

/**
 * @property resource $context
 */
interface ResourceWrapper
{
//    /**
//     * Close directory handle.
//     *
//     * This method is called in response to {@see closedir()}.
//     *
//     * Any resources which were locked, or allocated, during opening and use of
//     * the directory stream should be released.
//     */
//    public function dir_closedir(): bool;
//
//    public function dir_opendir(string $path, int $options): bool;
//
//    public function dir_readdir(): string;
//
//    public function dir_rewinddir(): bool;
//
//    public function mkdir(string $path, int $mode, int $options): bool;
//
//    public function rename(string $path_from, string $path_to): bool;
//
//    public function rmdir(string $path, int $options): bool;
//
//    public function stream_cast(int $cast_as);
//
//    public function stream_close(): void;

    /**
     * Tests for end-of-file on a file pointer.
     *
     * This method is called in response to {@see feof()}.
     *
     * > **Warning:**
     * > When reading the whole file (for example, with {@see file_get_contents()}), PHP will call
     * > {@see static::stream_read()} followed by {@see static::stream_eof()} in a loop but as long as
     * > {@see static::stream_read()} returns a non-empty string, the return value of {@see static::stream_eof()} is
     * > ignored.
     *
     * @return bool `true` if the read/write position is at the end of the stream and if no more data is available to be
     *              read, or false otherwise.
     */
    public function stream_eof(): bool;

//    public function stream_flush(): bool;
//
//    public function stream_lock(int $operation): bool;

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
    public function stream_metadata(string $path, int $option, mixed $value): bool;

    /**
     * Opens file or URL.
     *
     * This method is called immediately after the wrapper is initialized (e.g. by {@see fopen()} and
     * {@see file_get_contents()}).
     *
     * @param  string       $path         Specifies the URL that was passed to the original function.
     * @param  string       $mode         The mode used to open the file, as detailed for {@see fopen()}.
     * @param  int          $options      Holds additional flags set by the streams API.
     * @param  string|null  $opened_path  If the path is opened successfully, and {@see STREAM_USE_PATH} is set in
     *                                    options, `opened_path` should be set to the full path of the file/resource
     *                                    that was actually opened.
     * @return bool `true` on success or `false` on failure.
     */
    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool;

    /**
     * Read from stream.
     *
     * This method is called in response to {@see fread()} and {@see fgets()}.
     *
     * > **Note:**
     * > Remember to update the read/write position of the stream (by the number of bytes that were successfully read).
     *
     * > **Note:**
     * > {@see static::stream_eof()} is called directly after calling {@see static::stream_read()} to check if EOF has
     * > been reached. If not implemented, EOF is assumed.
     *
     * > **Warning:**
     * > When reading the whole file (for example, with {@see file_get_contents()}), PHP will call
     * > {@see static::stream_read()} followed by {@see static::stream_eof()} in a loop but as long as
     * > {@see static::stream_read()} returns a non-empty string, the return value of {@see static::stream_eof()} is
     * > ignored.
     *
     * @param  int  $count  How many bytes of data from the current position should be returned.
     * @return string|false If there are less than $count bytes available, as many as are available should be returned.
     *                      If no more data is available, an empty string should be returned.
     *                      To signal that reading failed, false should be returned.
     */
    public function stream_read(int $count): string|false;

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
    public function stream_set_option(int $option, int $arg1, int $arg2): bool;

    /**
     * Retrieve information about a file resource.
     *
     * This method is called in response to {@see fstat()}.
     *
     * @return array|false See {@see stat()}.
     */
    public function stream_stat(): array|false;

    /**
     * Retrieve the current position of a stream.
     *
     * This method is called in response to {@see ftell()} to determine the current position.
     *
     * @return int Should return the current position of the stream.
     */
    public function stream_tell(): int;

//    public function stream_truncate(int $new_size): bool;

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
    public function stream_write(string $data): int;

//    public function unlink(string $path): bool;
//
//    public function url_stat(string $path, int $flags): array|false;
}

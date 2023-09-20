<?php

declare(strict_types=1);

namespace Empaphy\Indirector\StreamWrapper;

/**
 * @implements \Empaphy\Indirector\StreamWrapper\SeekableResourceWrapper
 */
trait WrapsSeekableStream
{
    use WrapsStream;

    /**
     * Seeks to specific location in a stream.
     *
     * > **Note:**
     * > If not implemented, false is assumed as the return value.
     *
     * > **Note:**
     * > Upon success, {@see static::stream_tell()} is called directly after calling {@see static::stream_seek()}. If
     * > {@see static::stream_tell()} fails, the return value to the caller function will be set to `false`.
     *
     * > **Note:**
     * > Not all seeks operations on the stream will result in this function being called. PHP streams have read
     * > buffering enabled by default (see also {@see stream_set_read_buffer()}) and seeking may be done by merely
     * > moving the buffer pointer.
     *
     * @param  int  $offset  The stream offset to seek to.
     * @param  int  $whence  Possible values:
     *                         - {@see SEEK_SET} - Set position equal to `$offset` bytes.
     *                         - {@see SEEK_CUR} - Set position to current location plus `$offset`.
     *                         - {@see SEEK_END} - Set position to end-of-file plus `$offset`.
     * @return bool `true` if the position was updated, `false` otherwise.
     */
    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        return 0 === fseek($this->handle, $offset, $whence);
    }
}

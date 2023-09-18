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
}

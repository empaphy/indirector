<?php

declare(strict_types=1);

namespace Empaphy\Indirector\StreamWrapper;

/**
 * @property-read resource $context is updated if a valid context is passed to the caller function.
 */
interface SeekableResourceWrapper extends ResourceWrapper
{
    /**
     * Seeks to specific location in a stream.
     * This method is called in response to {@see fseek()}.
     * The read/write position of the stream should be updated according to the $offset and $whence.
     *
     * @param  int $offset     The stream offset to seek to.
     * @param  int $whence     Possible values:
     *                         - {@see SEEK_SET} - Set position equal to $offset bytes.
     *                         - {@see SEEK_CUR} - Set position to current location plus $offset.
     *                         - {@see SEEK_END} - Set position to end-of-file plus $offset.
     *
     * @return bool `true` if the position was updated, `false` otherwise.
     */
    public function stream_seek(int $offset, int $whence = SEEK_SET): bool;
}

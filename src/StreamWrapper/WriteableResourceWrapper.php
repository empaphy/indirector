<?php

declare(strict_types=1);

namespace Empaphy\Indirector\StreamWrapper;

/**
 * @property-read resource $context is updated if a valid context is passed to the caller function.
 */
interface WriteableResourceWrapper extends ResourceWrapper
{
    /**
     * Flushes the output.
     *
     * This method is called in response to {@see fflush()} and when the stream is being closed while any unflushed data
     * has been written to it before.
     *
     * If you have cached data in your stream but not yet stored it into the underlying storage, you should do so now.
     *
     * @return bool Should return `true` if the cached data was successfully stored (or if there was no data to store),
     *              or `false` if the data could not be stored.
     */
    public function stream_flush(): bool;

    /**
     * Write to stream.
     *
     * This method is called in response to fwrite().
     * > **Note:**
     * > Remember to update the current position of the stream by number of bytes that were successfully written.
     *
     * @param  string  $data  Should be stored into the underlying stream.
     *                        > **Note:**
     *                        > If there is not enough room in the underlying stream, store as much as possible.
     *
     * @return int Should return the number of bytes that were successfully stored, or 0 if none could be stored.
     *             > **Note:**
     *             > If the return value is greater than the length of $data, {@see E_WARNING} will be emitted and the
     *             > return value will be truncated to its length.
     */
    public function stream_write(string $data): int;
}

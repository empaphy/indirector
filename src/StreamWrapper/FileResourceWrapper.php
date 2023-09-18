<?php

declare(strict_types=1);

namespace Empaphy\Indirector\StreamWrapper;

/**
 * @property-read resource $context is updated if a valid context is passed to the caller function.
 */
interface FileResourceWrapper extends SeekableResourceWrapper, WriteableResourceWrapper
{
    /**
     * Renames a file or directory.
     *
     * This method is called in response to {@see rename()}.
     *
     * Should attempt to rename `$path_from` to `$path_to`.
     *
     * > Note:
     * > In order for the appropriate error message to be returned this method should not be defined if the wrapper does
     * > not support renaming files.
     *
     * Emits {@see E_WARNING} if call to this method fails (i.e. not implemented).
     *
     * @param  string  $path_from  The URL to the current file.
     * @param  string  $path_to    The URL which the `$path_from` should be renamed to.
     * @return bool `true` on success or `false` on failure.
     */
    public function rename(string $path_from, string $path_to): bool;

    /**
     * Advisory file locking.
     *
     * This method is called in response to {@see flock()}, when {@see file_put_contents()} (when flags contains
     * {@see LOCK_EX}), {@see stream_set_blocking()} and when closing the stream ({@see LOCK_UN}).
     *
     * Emits {@see E_WARNING} if call to this method fails (i.e. not implemented).
     *
     * @param  int  $operation  is one of the following:
     *                            - {@see LOCK_SH} to acquire a shared lock (reader).
     *                            - {@see LOCK_EX} to acquire an exclusive lock (writer).
     *                            - {@see LOCK_UN} to release a lock (shared or exclusive).
     *                            - {@see LOCK_NB} if you don't want {@see flock()} to block while locking.
     * @return bool `true` on success or `false` on failure.
     */
    public function stream_lock(int $operation): bool;

    /**
     * Retrieve information about a file resource.
     *
     * This method is called in response to {@see fstat()}.
     *
     * Emits {@see E_WARNING} if call to this method fails (i.e. not implemented).
     *
     * @return array|false See {@see stat()}.
     */
    public function stream_stat();

    /**
     * Delete a file.
     *
     * This method is called in response to {@see unlink()}.
     *
     * > **Note:**
     * > In order for the appropriate error message to be returned this method should not be defined if the wrapper does
     * > not support removing files.
     *
     * Emits {@see E_WARNING} if call to this method fails (i.e. not implemented).
     *
     * @param  string  $path  The file URL which should be deleted.
     * @return bool `true` on success or `false` on failure.
     */
    public function unlink(string $path): bool;

    /**
     * Retrieve information about a file.
     *
     * This method is called in response to all {@see stat()} related functions.
     *
     * @param  string  $path   The file path or URL to stat. Note that in the case of a URL, it must be a :// delimited
     *                         URL. Other URL forms are not supported.
     * @param  int     $flags  Holds additional flags set by the streams API. It can hold one or more of the following
     *                         values OR'd together.
     *                         {@see STREAM_URL_STAT_LINK}
     *                         : For resources with the ability to link to other resource (such as an HTTP Location:
     *                         forward, or a filesystem symlink). This flag specified that only information about the
     *                         link itself should be returned, not the resource pointed to by the link. This flag is set
     *                         in response to calls to {@see lstat()}, {@see is_link()}, or {@see filetype()}.
     * @return array|false
     */
    public function url_stat(string $path, int $flags);
}

<?php

declare(strict_types=1);

namespace Empaphy\Indirector\StreamWrapper;

/**
 * @implements \Empaphy\Indirector\StreamWrapper\FileResourceWrapper
 */
trait WrapsFileStream
{
    use WrapsDirectoryStream;
    use WrapsSeekableStream;
    use WrapsWriteableStream;

    /**
     * @var resource|bool
     */
    protected $file = false;

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
    public function rename(string $path_from, string $path_to): bool
    {
        if (null === $this->context) {
            return rename($path_from, $path_to);
        }

        return rename($path_from, $path_to, $this->context);
    }

    /**
     * Close a resource.
     *
     * This method is called in response to {@see fclose()}.
     *
     * All resources that were locked, or allocated, by the wrapper should be released.
     *
     * @return void
     */
    public function stream_close(): void
    {
        parent::stream_close();

        if (is_resource($this->file)) {
            fclose($this->file);
        }
        $this->file = false;
    }

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
    public function stream_lock(int $operation): bool
    {
        return flock($this->file, $operation);
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
        $this->path = $path;
        $this->file = $this->handle = $this->open($path, $mode, $options, $opened_path);

        return false !== $this->file;
    }

    /**
     * Retrieve information about a file resource.
     *
     * This method is called in response to {@see fstat()}.
     *
     * Emits {@see E_WARNING} if call to this method fails (i.e. not implemented).
     *
     * @return array|false See {@see stat()}.
     */
    public function stream_stat()
    {
        return fstat($this->file);
    }

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
    public function unlink(string $path): bool
    {
        if (null === $this->context) {
            return unlink($path);
        }

        return unlink($path, $this->context);
    }

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
    public function url_stat(string $path, int $flags)
    {
        if ($flags & STREAM_URL_STAT_LINK) {
            if ($flags & STREAM_URL_STAT_QUIET) {
                return @lstat($path);
            }

            return lstat($path);
        }

        if ($flags & STREAM_URL_STAT_QUIET) {
            return @stat($path);
        }

        return stat($path);
    }
}

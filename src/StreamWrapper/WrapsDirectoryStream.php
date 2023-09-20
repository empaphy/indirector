<?php

declare(strict_types=1);

namespace Empaphy\Indirector\StreamWrapper;

/**
 * @extends    \Empaphy\Indirector\StreamWrapper\WrapsStream
 * @implements \Empaphy\Indirector\StreamWrapper\DirectoryResourceWrapper
 */
trait WrapsDirectoryStream
{
    /**
     * Open directory handle.
     *
     * This method is called in response to {@see opendir()}.
     *
     * @param  string  $path     Specifies the URL that was passed to {@see opendir()}.
     *                           > **Note:**
     *                           > The URL can be broken apart with {@see parse_url()}.
     * @param  int     $options
     * @return bool `true` on success or `false` on failure.
     */
    public function dir_opendir(string $path, int $options): bool
    {
        if (null === $this->context) {
            $this->handle = opendir($path);
        } else {
            $this->handle = opendir($path, $this->context);
        }

        return false !== $this->handle;
    }

    /**
     * Close directory handle.
     *
     * This method is called in response to {@see closedir()}.
     *
     * Any resources which were locked, or allocated, during opening and use of the directory stream should be released.
     *
     * @return bool `true` on success or `false` on failure.
     */
    public function dir_closedir(): bool
    {
        closedir($this->handle);
        $this->handle = false;

        return true;
    }

    /**
     * Read entry from directory handle.
     *
     * This method is called in response to {@see readdir()}.
     *
     * Emits {@see E_WARNING} if call to this method fails (i.e. not implemented).
     *
     * @return string|false Should return string representing the next filename, or `false` if there is no next file.
     */
    public function dir_readdir()
    {
        return readdir($this->handle);
    }

    /**
     * Rewind directory handle.
     *
     * This method is called in response to {@see rewinddir()}.
     *
     * Should reset the output generated by {@see static::dir_readdir()}. i.e.: The next call to
     * {@see static::dir_readdir()} should return the first entry in the location returned by
     * {@see static::dir_opendir()}.
     *
     * @return bool `true` on success or `false` on failure.
     */
    public function dir_rewinddir(): bool
    {
        rewinddir($this->handle);

        return true;
    }

    /**
     * Create a directory.
     *
     * This method is called in response to {@see mkdir()}.
     *
     * > Note:
     * > In order for the appropriate error message to be returned this method should not be defined if the wrapper does
     * > not support creating directories.
     *
     * Emits {@see E_WARNING} if call to this method fails (i.e. not implemented).
     *
     * @param  string  $path     Directory which should be created.
     * @param  int     $mode     The value passed to {@see mkdir()}.
     * @param  int     $options  A bitwise mask of values, such as {@see STREAM_MKDIR_RECURSIVE}.
     * @return bool `true` on success or `false` on failure.
     */
    public function mkdir(string $path, int $mode, int $options): bool
    {
        $recursive = (bool) ($options & STREAM_MKDIR_RECURSIVE);

        if (null === $this->context) {
            return mkdir($path, $mode, $recursive);
        }

        return mkdir($path, $mode, $recursive, $this->context);
    }

    /**
     * Removes a directory.
     *
     * This method is called in response to {@see rmdir()}.
     *
     * > Note:
     * > In order for the appropriate error message to be returned this method should not be defined if the wrapper does
     * > not support removing directories.
     *
     * Emits {@see E_WARNING} if call to this method fails (i.e. not implemented).
     *
     * @param  string    $path     The directory URL which should be removed.
     * @param  int|null  $options  A bitwise mask of values.
     * @return bool `true` on success or `false` on failure.
     */
    public function rmdir(string $path, ?int $options): bool
    {
        if (null === $this->context) {
            return rmdir($path);
        }

        return rmdir($path, $this->context);
    }
}

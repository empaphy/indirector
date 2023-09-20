<?php

namespace Empaphy\Indirector\StreamWrapper;

/**
 * This trait implements methods that allow a stream wrapper to override an already defined stream wrapper.
 *
 * @implements \Empaphy\Indirector\StreamWrapper\StreamWrapperOverride
 */
trait OverridesStreamWrapper
{
    /**
     * The protocol that this stream wrapper overrides.
     *
     * @var string
     */
    protected static $protocol = 'file';

    /**
     * Keeps track of whether this stream wrapper has been registered.
     *
     * @var int
     */
    private static $registered = 0;

    /**
     * Register this stream wrapper.
     *
     * @return bool `true` on success or `false` on failure.
     */
    public static function register(): bool
    {
        return static::overrideStreamWrapper();
    }

    /**
     * Unregister this stream wrapper by restoring the previously configure file stream wrapper.
     *
     * @return bool `true` on success or `false` on failure.
     */
    public static function unregister(): bool
    {
        return static::restoreStreamWrapper();
    }

    /**
     * Overriding the previously configured file stream wrapper.
     *
     * @return bool `true` on success or `false` on failure.
     */
    final protected static function overrideStreamWrapper(): bool
    {
        if (self::$registered > 0) {
            return true;
        }

        stream_wrapper_unregister(static::$protocol);
        self::$registered++;

        return stream_wrapper_register(static::$protocol, static::class);
    }

    /**
     * Restores the previously configured file stream wrapper.
     *
     * @return bool `true` on success or `false` on failure.
     */
    final protected static function restoreStreamWrapper(): bool
    {
        if (self::$registered <= 0) {
            return true;
        }

        self::$registered--;

        return stream_wrapper_restore(static::$protocol);
    }

    /**
     * Enables this stream wrapper by registering it.
     *
     * @return bool
     */
    final public function enable(): bool
    {
        return static::overrideStreamWrapper();
    }

    /**
     * Disables this stream wrapper by restoring the previously configured file stream wrapper.
     *
     * @return bool
     */
    final public function disable(): bool
    {
        return static::restoreStreamWrapper();
    }

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
        $this->disable();

        try {
            return parent::dir_opendir($path, $options);
        } finally {
            $this->enable();
        }
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
    public function stream_metadata($path, $option, $value): bool
    {
        $this->disable();

        try {
            return parent::stream_metadata($path, $option, $value);
        } finally {
            $this->enable();
        }
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
        $this->disable();

        try {
            return parent::stream_open($path, $mode, $options, $opened_path);
        } finally {
            $this->enable();
        }
    }
}

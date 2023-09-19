<?php

declare(strict_types=1);

namespace Empaphy\Indirector\StreamWrapper;

/**
 * This trait implements methods that allow a stream wrapper to override an already defined stream wrapper.
 */
trait OverridesStreamWrapper
{
    /**
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
        if (self::$registered > 0) {
            return true;
        }

        stream_wrapper_unregister('file');
        self::$registered++;

        return stream_wrapper_register('file', static::class);
    }

    /**
     * Unregister this stream wrapper by restoring the previously configure file stream wrapper.
     *
     * @return bool `true` on success or `false` on failure.
     */
    public static function unregister(): bool
    {
        if (self::$registered <= 0) {
            return true;
        }

        self::$registered--;

        return stream_wrapper_restore('file');
    }

    /**
     * Enables this stream wrapper by registering it.
     *
     * @return bool
     */
    public function enable(): bool
    {
        return self::register();
    }

    /**
     * Disables this stream wrapper by restoring the previously configured file stream wrapper.
     *
     * @return bool
     */
    public function disable(): bool
    {
        return self::unregister();
    }
}

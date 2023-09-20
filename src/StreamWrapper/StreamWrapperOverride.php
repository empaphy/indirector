<?php

declare(strict_types=1);

namespace Empaphy\Indirector\StreamWrapper;

/**
 * Represents a stream wrapper that overrides a default PHP stream wrapper.
 *
 * It provides functions to enable and disable the stream wrapper, which are needed to temporarily unregister the stream
 * to be able to utilize the native PHP file functions.
 */
interface StreamWrapperOverride
{
    /**
     * Enables this stream wrapper by registering it.
     *
     * @return bool
     */
    public function enable(): bool;

    /**
     * Disables this stream wrapper by restoring the previously configured file stream wrapper.
     *
     * @return bool
     */
    public function disable(): bool;
}

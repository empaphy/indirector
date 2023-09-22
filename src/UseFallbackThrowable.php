<?php

declare(strict_types=1);

namespace Empaphy\Indirector;

/**
 * Indicates that upon catching the throwable, the fallback method should be used.
 */
interface UseFallbackThrowable extends IndirectorThrowable
{
    // Marker interface.
}

<?php

declare(strict_types=1);

namespace Empaphy\Indirector\StreamWrapper;

/**
 * Minimal implementation of a PHP stream wrapper.
 */
abstract class StreamWrapper implements ResourceWrapper
{
    use WrapsStream;
}

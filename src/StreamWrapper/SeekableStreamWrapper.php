<?php

declare(strict_types=1);

namespace Empaphy\Indirector\StreamWrapper;

class SeekableStreamWrapper extends StreamWrapper implements SeekableResourceWrapper
{
    use WrapsSeekableStream;
}

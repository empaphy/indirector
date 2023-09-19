<?php

declare(strict_types=1);

namespace Empaphy\Indirector\StreamWrapper;

class FileStreamWrapper extends SeekableStreamWrapper implements FileResourceWrapper
{
    use WrapsFileStream;
}

<?php

declare(strict_types=1);

namespace Empaphy\Indirector\StreamWrapper;

class FileStreamWrapperOverride extends FileStreamWrapper implements StreamWrapperOverride
{
    use OverridesStreamWrapper;
}

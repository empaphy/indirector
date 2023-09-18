<?php

declare(strict_types=1);

namespace Empaphy\Indirector\StreamWrapper\Config;

use Empaphy\Indirector\Processor\IncludeFileProcessor;

interface IncludeFileStreamWrapperConfig
{
    public function getIncludeFileProcessor(): IncludeFileProcessor;
}

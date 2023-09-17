<?php

declare(strict_types=1);

namespace Empaphy\StreamWrapper\Processor;

interface RectorProcessor
{
    /**
     * @param  string $path
     *
     * @return null|string
     */
    public function process(string $path): ?string;
}

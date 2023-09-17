<?php

declare(strict_types=1);

namespace Empaphy\StreamWrapper\Processor;

final class DirectRectorProcessor extends AbstractRectorProcessor
{
    /**
     * @param  string $path
     *
     * @return null|string
     */
    public function process(string $path): ?string
    {
        return $this->processFile($path);
    }
}

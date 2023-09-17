<?php

declare(strict_types=1);

namespace Empaphy\StreamWrapper\Processor;

use Empaphy\StreamWrapper\Fork;

final class ForkedRectorProcessor extends AbstractRectorProcessor
{
    /**
     * @param  string $path
     *
     * @return null|string
     */
    public function process(string $path): ?string
    {
        $fork = new Fork(function () use ($path) {
            include $path;

            return $this->processFile($path);
        });

        return $fork->wait();
    }
}

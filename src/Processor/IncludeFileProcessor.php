<?php

declare(strict_types=1);

namespace Empaphy\StreamWrapper\Processor;

use Exception;

/**
 * Represents a processor that processes a PHP include file, returning the
 * processed PHP file contents.
 */
interface IncludeFileProcessor
{
    /**
     * @param  string  $path  The path to the file to process.
     * @return string The processed file contents.
     *
     * @throws Exception if the file could not be processed.
     */
    public function processFile(string $path): string;
}

<?php

declare(strict_types=1);

namespace Empaphy\StreamWrapper\Processor;

use Empaphy\StreamWrapper\Fork;
use Rector\Core\Application\FileProcessor;
use Rector\Core\Provider\CurrentFileProvider;
use Rector\Core\ValueObject\Application\File;
use Rector\Core\ValueObject\Configuration;

final class ForkedRectorProcessor implements RectorProcessor
{
    /**
     * @var \Rector\Core\ValueObject\Configuration
     */
    private $configuration;

    /**
     * @var \Rector\Core\Provider\CurrentFileProvider
     */
    private $currentFileProvider;

    /**
     * @var \Rector\Core\Application\FileProcessor
     */
    private $fileProcessor;

    /**
     * @param  \Rector\Core\Provider\CurrentFileProvider $currentFileProvider
     * @param  \Rector\Core\Application\FileProcessor    $fileProcessor
     * @param  \Rector\Core\ValueObject\Configuration    $configuration
     */
    public function __construct(
        CurrentFileProvider $currentFileProvider,
        FileProcessor       $fileProcessor,
        Configuration       $configuration
    ) {
        $this->currentFileProvider = $currentFileProvider;
        $this->fileProcessor       = $fileProcessor;
        $this->configuration       = $configuration;
    }

    public function process(string $path): ?string
    {
        $fork = new Fork(function () use ($path) {
            include $path;

            $realpath = stream_resolve_include_path($path);

            if (false === $realpath) {
                return false;
            }

            $rectorFile = new File($realpath, file_get_contents($realpath, true));
            $this->currentFileProvider->setFile($rectorFile);

            $fileProcessResult = $this->fileProcessor->processFile($rectorFile, $this->configuration);

            $systemErrors = $fileProcessResult->getSystemErrors();

            if (! empty($systemErrors)) {
                return false;
            }

            if ($rectorFile->hasChanged()) {
                return $rectorFile->getFileContent();
            }

            return null;
        });

        return $fork->wait();
    }
}

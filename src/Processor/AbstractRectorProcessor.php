<?php

declare(strict_types=1);

namespace Empaphy\StreamWrapper\Processor;

use Empaphy\StreamWrapper\Config\RectorStreamWrapperConfig;
use Rector\Core\Application\FileProcessor;
use Rector\Core\Provider\CurrentFileProvider;
use Rector\Core\ValueObject\Application\File;
use Rector\Core\ValueObject\Configuration;

abstract class AbstractRectorProcessor implements RectorProcessor
{
    /**
     * @var \Rector\Core\ValueObject\Configuration
     */
    protected $configuration;

    /**
     * @var \Rector\Core\Provider\CurrentFileProvider
     */
    protected $currentFileProvider;

    /**
     * @var \Rector\Core\Application\FileProcessor
     */
    protected $fileProcessor;

    /**
     * @var \Empaphy\StreamWrapper\Config\RectorStreamWrapperConfig
     */
    private $streamWrapperConfig;

    /**
     * @param  \Empaphy\StreamWrapper\Config\RectorStreamWrapperConfig $config
     * @param  \Rector\Core\Provider\CurrentFileProvider               $currentFileProvider
     * @param  \Rector\Core\Application\FileProcessor                  $fileProcessor
     * @param  \Rector\Core\ValueObject\Configuration                  $configuration
     */
    public function __construct(
        RectorStreamWrapperConfig $config,
        CurrentFileProvider       $currentFileProvider,
        FileProcessor             $fileProcessor,
        Configuration             $configuration
    ) {
        $this->streamWrapperConfig = $config;
        $this->configuration       = $configuration;
        $this->currentFileProvider = $currentFileProvider;
        $this->fileProcessor       = $fileProcessor;
    }

    /**
     * @param  string $path
     *
     * @return null|string
     */
    protected function processFile(string $path): ?string
    {
        if ($this->hasCachedFile($path)) {
            return $this->getCachedFile($path);
        }

        $realpath = stream_resolve_include_path($path);

        if (false === $realpath) {
            return null;
        }

        $rectorFile = new File($realpath, file_get_contents($realpath, true));
        $this->currentFileProvider->setFile($rectorFile);

        $fileProcessResult = $this->fileProcessor->processFile($rectorFile, $this->configuration);

        $systemErrors = $fileProcessResult->getSystemErrors();

        if (! empty($systemErrors)) {
            return null;
        }

        if ($rectorFile->hasChanged()) {
            $this->setCachedFile($path, $rectorFile->getFileContent());

            return $rectorFile->getFileContent();
        }

        $this->setCachedFile($path, '');

        return null;
    }

    /**
     * @param  string $path
     *
     * @return null|string
     */
    private function getCacheFilePath(string $path): ?string
    {
        $cacheDirectory = $this->streamWrapperConfig->getCacheDirectory();

        if (! $cacheDirectory) {
            return null;
        }

        return $cacheDirectory . DIRECTORY_SEPARATOR . md5($path);
    }

    /**
     * @param  string $path
     *
     * @return string
     */
    private function getCachedFile(string $path): string
    {
        return file_get_contents($this->getCacheFilePath($path));
    }

    /**
     * @param  string $path
     *
     * @return bool
     */
    private function hasCachedFile(string $path): bool
    {
        $cachedFile = $this->getCacheFilePath($path);

        return file_exists($cachedFile);
    }

    /**
     * @param  string $path
     * @param  string $getFileContent
     *
     * @return void
     */
    private function setFileCache(string $path, string $getFileContent): void
    {
        $cachedFile = $this->getCacheFilePath($path);

        file_put_contents($cachedFile, $getFileContent);
    }
}

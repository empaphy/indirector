<?php

declare(strict_types=1);

namespace Empaphy\Indirector\Processor;

use Empaphy\Indirector\Indirector;
use Rector\Caching\Detector\ChangedFilesDetector;
use Rector\ChangesReporting\Output\ConsoleOutputFormatter;
use Rector\Core\Application\FileProcessor;
use Rector\Core\Configuration\Option;
use Rector\Core\Configuration\Parameter\SimpleParameterProvider;
use Rector\Core\Provider\CurrentFileProvider;
use Rector\Core\ValueObject\Application\File;
use Rector\Core\ValueObject\Configuration;
use Rector\Core\ValueObject\Error\SystemError;
use Rector\Core\ValueObject\Reporting\FileDiff;
use RuntimeException;

class RectorProcessor implements IncludeFileProcessor
{
    /**
     * @var \Rector\Caching\Detector\ChangedFilesDetector
     */
    protected $changedFilesDetector;

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
     * @var \Empaphy\Indirector\Indirector
     */
    private $indirector;

    /**
     * @param  \Empaphy\Indirector\Indirector                 $indirector
     * @param  \Rector\Caching\Detector\ChangedFilesDetector  $changedFilesDetector
     * @param  \Rector\Core\Provider\CurrentFileProvider      $currentFileProvider
     * @param  \Rector\Core\Application\FileProcessor         $fileProcessor
     */
    public function __construct(
        Indirector           $indirector,
        ChangedFilesDetector $changedFilesDetector,
        CurrentFileProvider  $currentFileProvider,
        FileProcessor        $fileProcessor
    ) {
        $this->indirector           = $indirector;
        $this->changedFilesDetector = $changedFilesDetector;
        $this->currentFileProvider  = $currentFileProvider;
        $this->fileProcessor        = $fileProcessor;

        $this->configuration = new Configuration(true, false, false, ConsoleOutputFormatter::NAME, ['php'], [], false);
    }

    /**
     * @param  string $path
     *
     * @return null|string
     */
    public function processFile(string $path): string
    {
        if ($this->hasCachedFile($path)) {
            return $this->getCachedFile($path);
        }

        $file = new File($path, file_get_contents($path, true));
        $this->currentFileProvider->setFile($file);
        $fileProcessResult = $this->fileProcessor->processFile($file, $this->configuration);

        $systemErrors = $fileProcessResult->getSystemErrors();
        if ($systemErrors !== []) {
            $systemErrorMessages = array_map(static function (SystemError $systemError) {
                return $systemError->getMessage();
            }, $systemErrors);

            $this->changedFilesDetector->invalidateFile($file->getFilePath());

            throw new RuntimeException(
                sprintf('File `%s` could not be processed:\n%s', $path, implode("\n", $systemErrorMessages))
            );
        }

        if (! $this->configuration->isDryRun() || ! $fileProcessResult->getFileDiff() instanceof FileDiff) {
            $this->changedFilesDetector->cacheFile($file->getFilePath());
        }

        if ($file->hasChanged()) {
            $this->setFileCache($path, $file->getFileContent());

            return $file->getFileContent();
        }

        $this->setFileCache($path, '');

        return $file->getFileContent();
    }

    /**
     * @param  string $path
     *
     * @return null|string
     */
    private function getCacheFilePath(string $path): ?string
    {
        $cacheDirectory = SimpleParameterProvider::provideStringParameter(Option::CACHE_DIR);

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

        if (! $cachedFile) {
            return false;
        }

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

        if (! $cachedFile) {
            return;
        }

        file_put_contents($cachedFile, $getFileContent);
    }
}

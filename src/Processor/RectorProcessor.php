<?php

declare(strict_types=1);

namespace Empaphy\StreamWrapper\Processor;

use Empaphy\StreamWrapper\Config\RectorStreamWrapperConfig;
use Rector\Caching\Detector\ChangedFilesDetector;
use Rector\ChangesReporting\Output\ConsoleOutputFormatter;
use Rector\Core\Application\FileProcessor;
use Rector\Core\Provider\CurrentFileProvider;
use Rector\Core\ValueObject\Application\File;
use Rector\Core\ValueObject\Configuration;
use Rector\Core\ValueObject\Error\SystemError;
use Rector\Core\ValueObject\Reporting\FileDiff;
use RuntimeException;

class RectorProcessor implements IncludeFileProcessor
{
    /**
     * @var \Rector\Core\ValueObject\Configuration
     */
    protected $configuration;

    /**
     * @var \Rector\Caching\Detector\ChangedFilesDetector
     */
    protected $changedFilesDetector;

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
     * @param  \Empaphy\StreamWrapper\Config\RectorStreamWrapperConfig  $config
     * @param  \Rector\Caching\Detector\ChangedFilesDetector            $changedFilesDetector
     * @param  \Rector\Core\Provider\CurrentFileProvider                $currentFileProvider
     * @param  \Rector\Core\Application\FileProcessor                   $fileProcessor
     */
    public function __construct(
        RectorStreamWrapperConfig $config,
        ChangedFilesDetector      $changedFilesDetector,
        CurrentFileProvider       $currentFileProvider,
        FileProcessor             $fileProcessor
    ) {
        $this->streamWrapperConfig  = $config;
        $this->changedFilesDetector = $changedFilesDetector;
        $this->currentFileProvider  = $currentFileProvider;
        $this->fileProcessor        = $fileProcessor;
    }

    /**
     * @param  string $path
     * @return null|string
     */
    public function processFile(string $path): string
    {
//        if ($this->hasCachedFile($path)) {
//            return $this->getCachedFile($path);
//        }

        $realpath = stream_resolve_include_path($path);

        if (false === $realpath) {
            throw new RuntimeException(sprintf('File `%s` not found in include path.', $path));
        }

        $file = new File($realpath, file_get_contents($realpath, true));

        $this->currentFileProvider->setFile($file);
        $fileProcessResult = $this->fileProcessor->processFile(
            $file,
            new Configuration(true, false, false, ConsoleOutputFormatter::NAME, ['php'], [], false)
        );

        $systemErrors = $fileProcessResult->getSystemErrors();
        if ($systemErrors !== []) {
            $systemErrorMessages = array_map(static function(SystemError $systemError) {
                return $systemError->getMessage();
            }, $systemErrors);
            $this->changedFilesDetector->invalidateFile($file->getFilePath());
            throw new RuntimeException(sprintf('File `%s` could not be processed:\n%s', $path, implode("\n", $systemErrorMessages)));
        }

        if (!$this->configuration->isDryRun() || !$fileProcessResult->getFileDiff() instanceof FileDiff) {
            $this->changedFilesDetector->cacheFile($file->getFilePath());
        }

        // TODO: use Rector's own caching mechanism?
        if ($file->hasChanged()) {
//            $this->setFileCache($path, $file->getFileContent());
            return $file->getFileContent();
        }

//        $this->setFileCache($path, '');

        return $file->getFileContent();
    }

//    /**
//     * @param  string $path
//     *
//     * @return null|string
//     */
//    private function getCacheFilePath(string $path): ?string
//    {
//        $cacheDirectory = $this->streamWrapperConfig->getCacheDirectory();
//
//        if (! $cacheDirectory) {
//            return null;
//        }
//
//        return $cacheDirectory . DIRECTORY_SEPARATOR . md5($path);
//    }
//
//    /**
//     * @param  string $path
//     *
//     * @return string
//     */
//    private function getCachedFile(string $path): string
//    {
//        return file_get_contents($this->getCacheFilePath($path));
//    }
//
//    /**
//     * @param  string $path
//     *
//     * @return bool
//     */
//    private function hasCachedFile(string $path): bool
//    {
//        $cachedFile = $this->getCacheFilePath($path);
//
//        if (! $cachedFile) {
//            return false;
//        }
//
//        return file_exists($cachedFile);
//    }
//
//    /**
//     * @param  string $path
//     * @param  string $getFileContent
//     *
//     * @return void
//     */
//    private function setFileCache(string $path, string $getFileContent): void
//    {
//        $cachedFile = $this->getCacheFilePath($path);
//
//        if (! $cachedFile) {
//            return;
//        }
//
//        file_put_contents($cachedFile, $getFileContent);
//    }
}

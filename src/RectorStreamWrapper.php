<?php

/**
 * @noinspection UnknownInspectionInspection
 */

declare(strict_types=1);

namespace Empaphy\StreamWrapper;

use Rector\ChangesReporting\Output\JsonOutputFormatter;
use Rector\Core\Bootstrap\RectorConfigsResolver;
use Rector\Core\Configuration\Option;
use Rector\Core\Console\Style\SymfonyStyleFactory;
use Rector\Core\DependencyInjection\RectorContainerFactory;
use Rector\Core\Util\Reflection\PrivatesAccessor;
use Rector\Core\ValueObject\Bootstrap\BootstrapConfigs;
use RectorPrefix202309\Nette\Utils\Json;
use RectorPrefix202309\Symfony\Component\Console\Application;
use RectorPrefix202309\Symfony\Component\Console\Command\Command;
use RectorPrefix202309\Symfony\Component\Console\Input\ArgvInput;
use RuntimeException;

/**
 * This PHP stream wrapper implements a `rectorfile://` protocol handler that allows one to use
 * [Rector](https://github.com/rectorphp/rector) to downgrade included PHP code on the fly.
 */
class RectorStreamWrapper implements SeekableResourceWrapper
{
    public const PROTOCOL = 'rectorfile';

    /**
     * @var resource
     */
    public $context;

    public function __construct()
    {
        $bootstrapConfigs = $this->provideRectorConfigs();
        $rectorContainerFactory = new RectorContainerFactory();
        $container = $rectorContainerFactory->createFromBootstrapConfigs($bootstrapConfigs);
    }

    /**
     * @see RectorConfigsResolver::provide();
     *
     * @return \Rector\Core\ValueObject\Bootstrap\BootstrapConfigs
     */
    private function provideRectorConfigs(): BootstrapConfigs
    {
        // TODO: implement something here
        $mainConfigFile         = null;
        $rectorRecipeConfigFile = null;

        $configFiles = [];
        if ($rectorRecipeConfigFile !== null) {
            $configFiles[] = $rectorRecipeConfigFile;
        }

        return new BootstrapConfigs($mainConfigFile, $configFiles);
    }

    /**
     * @return bool
     */
    public static function register(): bool
    {
        $result = stream_wrapper_register(self::PROTOCOL, self::class);
        if (! $result) {
            throw new RuntimeException('Failed to register stream wrapper.');
        }

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        return $result;
    }

    /**
     * Opens an included PHP file and downgrades it to the currently running PHP version using Rector.
     *
     * This method is called immediately after the wrapper is initialized (i.e. by `include`).
     *
     * @param  string       $path         Specifies the path that was passed to the original function.
     * @param  string       $mode         The mode used to open the file, as detailed for {@see fopen()}.
     * @param  int          $options      Holds additional flags set by the streams API.
     * @param  string|null  $opened_path  If the path is opened successfully, and {@see STREAM_USE_PATH} is set in
     *                                    options, `opened_path` should be set to the full path of the file/resource
     *                                    that was actually opened.
     * @return bool `true` on success or `false` on failure.
     */
    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        // Remove the protocol from the path.
        $path = substr($path, strlen(self::PROTOCOL . '://'));

        $realpath = realpath($path);
        if (false !== $realpath) {
            $this->context = fopen($realpath, $mode, true);
            $opened_path   = $realpath;
        }

        return true;
    }

    /**
     * Read the included PHP file.
     *
     * @param  int  $count  How many bytes of data from the current position should be returned.
     * @return string|false If there are less than $count bytes available, as many as are available should be returned.
     *                      If no more data is available, an empty string should be returned.
     *                      To signal that reading failed, false should be returned.
     */
    public function stream_read(int $count): string|false
    {
        /** @noinspection OneTimeUseVariablesInspection
         *  @noinspection PhpUnnecessaryLocalVariableInspection */
        $contents = fread($this->context, $count);
//        if (false !== $contents) {
//            $contents = $this->downgrade($contents);
//        }

        return $contents;
    }

    /**
     * Change stream options.
     *
     * This method is called to set options on the stream.
     *
     * @param  int  $option
     * @param  int  $arg1
     * @param  int  $arg2
     * @return bool `true` on success or `false` on failure. If $option is not implemented, false should be returned.
     */
    public function stream_set_option(int $option, int $arg1, int $arg2): bool
    {
        return false;
    }

    /**
     * Retrieve information about a file resource.
     *
     * This method is called in response to {@see fstat()}.
     *
     * @return array|false See {@see stat()}.
     */
    public function stream_stat(): array|false
    {
        return fstat($this->context);
    }

    /**
     * This method is not supported for this stream wrapper.
     *
     * @param  string  $data
     * @return int
     */
    public function stream_write(string $data): int
    {
        throw new RuntimeException('Writing to this stream is not supported.');
    }


    /**
     * Retrieve the current position of the file handle.
     *
     * @return int returns the current position of the file handle.
     */
    public function stream_tell(): int
    {
        return ftell($this->context);
    }

    /**
     * Tests for end-of-file on a file pointer.
     *
     * @return bool `true` if the read/write position is at the end of the stream and if no more data is available to be
     *              read, or false otherwise.
     */
    public function stream_eof(): bool
    {
        return feof($this->context);
    }

    /**
     * Seeks to specific location in a stream.
     *
     * @param  int  $offset  The stream offset to seek to.
     * @param  int  $whence  Possible values:
     *                         - {@see SEEK_SET} - Set position equal to $offset bytes.
     *                         - {@see SEEK_CUR} - Set position to current location plus $offset.
     *                         - {@see SEEK_END} - Set position to end-of-file plus $offset.
     * @return bool `true` if the position was updated, `false` otherwise.
     */
    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        return 0 === fseek($this->context, $offset, $whence);
    }

    /**
     * Change stream metadata.
     *
     * This method is not supported for this stream wrapper.
     *
     * @param  string  $path    The file path or URL to set metadata.
     * @param  int     $option
     * @param  mixed   $value
     * @return bool If $option is not implemented, `false` should be returned.
     */
    public function stream_metadata(string $path, int $option, mixed $value): bool
    {
        return false;
    }
}

<?php

declare(strict_types=1);

namespace Empaphy\StreamWrapper;

use RuntimeException;
use Throwable;

class Fork
{
    public const ACTION_RETURN = 'return';
    public const ACTION_THROW  = 'throw';
    public const ACTION_VOID   = 'void';
    public const DELIMITER     = "\n__CUTLERY_FORK_END_DELIMITER__\n";

    public const SOCKET_BUFFER_SIZE = 16777216;
    public const BUFFER_SIZE = 1024;

    protected int $pid;

    /**
     * @var resource[]
     */
    protected array $socketPair;

    public function __construct($callable)
    {
        socket_create_pair(AF_UNIX, SOCK_STREAM, 0, $fd);

        $this->socketPair = $fd;
        $this->pid        = pcntl_fork();

        switch ($this->pid) {
            case -1:
                throw new RuntimeException('Failed to fork process');

            case 0:  // We're in the child process, run the $callable.
                $exitStatus = 0;

                try {
                    $result = $callable();

                    if (null === $result) {
                        $data = self::ACTION_VOID . ',null';
                    } elseif (is_string($result)) {
                        $data = self::ACTION_RETURN . ',' . base64_encode($result);
                    } else {
                        throw new RuntimeException('Result must be a string');
                    }
                } catch (Throwable $t) {
                    $exitStatus = 1;
                    try {
                        $data = self::ACTION_THROW . ',' . base64_encode($t->getMessage());
                    } catch (Throwable) {
                        $data = self::ACTION_THROW . ',' . base64_encode('Unknown Error');
                    }
                } finally {
                    $data .= self::DELIMITER;
                    socket_set_option($this->socketPair[0], SOL_SOCKET, SO_SNDBUF, strlen($data));

                    $read   = [];
                    $write  = [$this->socketPair[0]];
                    $except = [];

                    $socketCount = socket_select($read, $write, $except, 1);
                    if ($socketCount) {
                        if (false === socket_write($this->socketPair[0], $data)) {
                            throw new RuntimeException(
                                'Failed to write to socket: ' . socket_strerror(socket_last_error())
                            );
                        }
                    } else {
                        throw new RuntimeException(
                            'Parent stopped listening on socket: ' . socket_strerror(socket_last_error())
                        );
                    }

                    socket_close($this->socketPair[0]);
                    $this->socketPair[0] = null;
                }

                exit($exitStatus);

            default:
                break;
        }
    }

    public function __destruct()
    {
        try {
            foreach ($this->socketPair as $socket) {
                if (null !== $socket) {
                    socket_close($socket);
                }
            }
        } catch (Throwable $t) {
            // Ignore.
        }
    }

    /**
     * @return string|null
     *
     * @throws \RuntimeException
     */
    public function wait(): ?string
    {
        $delimiterPosition = false;
        $buffer = '';

        socket_set_nonblock($this->socketPair[1]);

        do {
            $read   = [$this->socketPair[1]];
            $write  = [];
            $except = [];

            $socketCount = socket_select($read, $write, $except, 1);
            if ($socketCount) {
                while(($data = socket_read($this->socketPair[1], self::BUFFER_SIZE))) {
                    $buffer .= $data;
                }

                $delimiterPosition = strpos($buffer, self::DELIMITER);
                if (false !== $delimiterPosition) {
                    $buffer = substr($buffer, 0, $delimiterPosition);
                }
            } elseif (false === $socketCount) {
                throw new RuntimeException(
                    'socket_select() failed: ' . socket_strerror(socket_last_error($this->socketPair[1]))
                );
            } else {
                $pid = pcntl_waitpid($this->pid, $status, WNOHANG);
                if (-1 === $pid) {
                    throw new RuntimeException("Child process (pid {$this->pid}) exited too early: {$pid}");
                }
            }
        } while (false === $delimiterPosition);

        // Wait for child process to finish.
        pcntl_waitpid($this->pid, $status);

        socket_close($this->socketPair[1]);
        $this->socketPair[1] = null;

        [$action, $result] = explode(',', $buffer, 2);

        switch ($action) {
            case self::ACTION_RETURN:
                return base64_decode($result);

            case self::ACTION_VOID:
                return null;

            case self::ACTION_THROW:
                throw new RuntimeException(base64_decode($result));

            default:
                throw new RuntimeException('Invalid action');
        }
    }
}

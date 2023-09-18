<?php

declare(strict_types=1);

class IncludeMe
{
    public function __construct(
        public readonly string $greeting = 'Hello',
        public readonly string $subject = 'World',
    ) {

    }

    public function helloWorld(): void
    {
        $closure = fn() => "{$this->greeting} {$this->subject}!";

        echo $closure();
    }
}

<?php

declare(strict_types=1);

class IncludeMe
{
    public string $greeting;
    public string $subject;

    public function __construct() {
        $this->greeting = 'Hello';
        $this->subject = 'World';
    }

    public function helloWorld(): void
    {
        $closure = function() { return "{$this->greeting} {$this->subject}!"; };
//        $closure = fn() => "{$this->greeting} {$this->subject}!";

        echo $closure();
    }
}

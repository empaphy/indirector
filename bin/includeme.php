<?php

declare(strict_types=1);

class IncludeMe
{
    public function helloWorld(): void
    {
        $closure = static function () {
            return 'Hello World!';
        };

        echo $closure();
    }
}

#!/usr/bin/env php
<?php

declare(strict_types=1);

use Empaphy\StreamWrapper\RectorStreamWrapper;

require_once dirname(__DIR__) . '/vendor/autoload.php';

RectorStreamWrapper::register();

include __DIR__ . '/includeme.php';

$includeMe = new IncludeMe();
$includeMe->helloWorld();

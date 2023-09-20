#!/usr/bin/env php
<?php

declare(strict_types=1);

use Empaphy\Indirector\Indirector;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$indirector = new Indirector();
$indirector->enable();

include __DIR__ . '/includeme.php';

$includeMe = new IncludeMe();
$includeMe->helloWorld();

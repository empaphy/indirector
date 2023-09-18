#!/usr/bin/env php
<?php

declare(strict_types=1);

use Empaphy\Indirector\Config\RectorStreamWrapperConfig;
use Empaphy\Indirector\IncludeFileStreamWrapper;

require_once dirname(__DIR__) . '/vendor/autoload.php';

IncludeFileStreamWrapper::initialize(new RectorStreamWrapperConfig());

include __DIR__ . '/includeme.php';

$includeMe = new IncludeMe();
$includeMe->helloWorld();

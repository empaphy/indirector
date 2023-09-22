<?php

declare(strict_types=1);

use Empaphy\Indirector\Indirector;

require_once 'phar://vendor/phpstan/phpstan/phpstan.phar/stubs/runtime/ReflectionUnionType.php';
require_once 'phar://vendor/phpstan/phpstan/phpstan.phar/stubs/runtime/ReflectionAttribute.php';
require_once 'phar://vendor/phpstan/phpstan/phpstan.phar/stubs/runtime/Attribute.php';
require_once 'phar://vendor/phpstan/phpstan/phpstan.phar/stubs/runtime/ReflectionIntersectionType.php';

Indirector::get()->enable();

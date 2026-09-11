<?php

use Allgorithm\FilamentActionGuard\Tests\TestCase;

// Silence PHP 8.5 deprecation notices originating from vendor packages (e.g., orchestra/testbench PDO::MYSQL_ATTR_SSL_CA)
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

uses(TestCase::class)->in(__DIR__);

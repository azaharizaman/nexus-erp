<?php

declare(strict_types=1);

// Package-level test bootstrap for packages/nexus-crm

if (! defined('LARAVEL_START')) {
    define('LARAVEL_START', microtime(true));
}

// Composer autoload
require_once __DIR__.'/../vendor/autoload.php';

// Default environment for package tests
$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_CONNECTION'] = 'testing';
$_ENV['CACHE_DRIVER'] = 'array';
$_ENV['SESSION_DRIVER'] = 'array';
$_ENV['QUEUE_CONNECTION'] = 'sync';
$_ENV['MAIL_MAILER'] = 'array';

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('memory_limit', '512M');

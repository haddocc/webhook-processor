<?php

define("APPROOT", dirname(__DIR__));

require APPROOT . '/vendor/autoload.php';

use App\Application;
use Dotenv\Dotenv;

// load .env
$dotenv = Dotenv::createImmutable(APPROOT);
$dotenv->load();

// boot application
Application::boot();

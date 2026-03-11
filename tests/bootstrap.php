<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

if (file_exists(dirname(__DIR__) . '/.env.test.local')) {
    (new Dotenv())->load(dirname(__DIR__) . '/.env.test.local');
}

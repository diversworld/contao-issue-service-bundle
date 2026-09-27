<?php

declare(strict_types=1);

$loader = require dirname(__DIR__).'/vendor/autoload.php';
$loader->addPsr4('Diversworld\\ContaoIssueServiceBundle\\', dirname(__DIR__).'/src/');
$loader->addPsr4('Diversworld\\ContaoIssueServiceBundle\\Tests\\', __DIR__.'/');

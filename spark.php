#!/usr/bin/env php
<?php

/**
 * @file
 * Spark cli executable.
 */

require __DIR__.'/vendor/autoload.php';
use Symfony\Component\Console\Application;
use Sparkfabrik\Tools\Spark\SparkConfigurationWrapper;
try {
  $config = new SparkConfigurationWrapper();
  $config->loadConfig();
  $application = new Application('Spark', '0.1.0');
  $application->run();
}
catch (Exception $e) {
  print $e->getMessage() . PHP_EOL;
}


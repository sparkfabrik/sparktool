#!/usr/bin/env php
<?php

/**
 * @file
 * Spark cli executable.
 */

require __DIR__.'/vendor/autoload.php';
use Symfony\Component\Console\Application;
use Sparkfabrik\Tools\Spark\SparkConfigurationWrapper;
use Sparkfabrik\Tools\Spark\Command\Redmine\RedmineIssueCommand;
try {
  $application = new Application('Spark', '0.1.0');
  $config = new SparkConfigurationWrapper();
  $config->loadConfig();

  // Load commands.
  $application->add(new RedmineIssueCommand);
  $application->run();
}
catch (Exception $e) {
  print $e->getMessage() . PHP_EOL;
}


#!/usr/bin/env php
<?php

/**
 * @file
 * Spark cli executable.
 */
if (strpos(basename(__FILE__), 'phar')) {
    require_once 'phar://spark.phar/vendor/autoload.php';
}
else{
    if (file_exists(__DIR__.'/vendor/autoload.php')) {
        require_once __DIR__.'/vendor/autoload.php';
    } elseif (file_exists(__DIR__.'/../../autoload.php')) {
        require_once __DIR__ . '/../../autoload.php';
    } else {
        require_once 'phar://spark.phar/vendor/autoload.php';
    }
}
if (file_exists(__DIR__.'/vendor/adrianocori/filedb/filedb.inc')) {
  require_once __DIR__.'/vendor/adrianocori/filedb/filedb.inc';
}
use Symfony\Component\Console\Application;
use Sparkfabrik\Tools\Spark\SparkConfigurationWrapper;
use Sparkfabrik\Tools\Spark\Command\Redmine\RedmineSearchCommand;
use Sparkfabrik\Tools\Spark\Command\Redmine\RedmineSearchPresetsCommand;
use Sparkfabrik\Tools\Spark\Command\Redmine\RedmineShowCommand;
use Sparkfabrik\Tools\Spark\Command\Redmine\RedmineGitBranchCommand;
use Robo\Task\Development\SemVer;
try {
  $semver_file = '.semver';
  if (!file_exists($semver_file)) {
    $semver_file = 'phar://spark.phar/.semver';
  }
  $semver = new SemVer($semver_file);
  $application = new Application('Spark', (string) $semver);
  $config = new SparkConfigurationWrapper();
  $config->loadConfig();

  // Load commands.
  $application->add(new RedmineSearchCommand);
  $application->add(new RedmineSearchPresetsCommand);
  $application->add(new RedmineShowCommand);
  $application->add(new RedmineGitBranchCommand);
  $application->run();
}
catch (Exception $e) {
  print $e->getMessage() . PHP_EOL;
}


<?php

/*
 * This file is part of the Spark tool package.
 *
 * (c) Paolo Mainardi <paolo.mainardi@sparkfabrik.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sparkfabrik\Tools\Spark\Command\Redmine;

use Sparkfabrik\Tools\Spark\Command\SparkCommand;
use Sparkfabrik\Tools\Spark\SparkConfigurationWrapper;
use Symfony\Component\Console\Command\Command;
use Redmine\Client as Redmine;

/**
 * Base class for all redmine commands.
 *
 * @author Paolo Mainardi <paolo.mainardi@sparkfabrik.com>
 *
 * @api
 */
class RedmineCommand extends SparkCommand
{
  private $redmineConfig;
  private $redmineClient;

  /**
   * Constructor.
   *
   * @param string|null $name The name of the command; passing null means it must be set in configure()
   *
   * @throws \LogicException When the command name is empty
   *
   * @api
   */
  public function __construct($name = null) {
    parent::__construct($name);
    $this->initConfig();
  }

  /**
   * Return redmine configuration.
   */
  public function getRedmineConfig() {
    return $this->redmineConfig;
  }

  /**
   * Return redmine client.
   */
  public function getRedmineClient() {
    $client = $this->redmineClient;
    if (!$client) {
      throw new \Exception('Redmine client not defined');
    }
    return $client;
  }

  /**
   * Create redmine client.
   */
  private function createRedmineClient() {
    $this->redmineClient = new Redmine(
      $this->redmineConfig['redmine_url'],
      $this->redmineConfig['redmine_api_key']
    );
  }

  /**
   * Initialize configurations and client.
   */
  protected function initConfig() {
    $configManager = new SparkConfigurationWrapper();
    $this->redmineConfig = $configManager->getValueFromConfig('services', 'redmine_credentials');
    $this->redmineConfig['project_id'] = $configManager->getValueFromConfig('projects', 'redmine_project_id');
    $this->redmineConfig['git_pattern'] = $configManager->getValueFromConfig('git', 'branch_pattern');
    $this->createRedmineClient();
  }
}

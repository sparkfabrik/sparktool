<?php

/*
 * This file is part of the Spark tool package.
 *
 * (c) Paolo Mainardi <paolo.mainardi@sparkfabrik.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sparkfabrik\Tools\Spark\Command\Gitlab;

use Sparkfabrik\Tools\Spark\Command\SparkCommand;
use Sparkfabrik\Tools\Spark\SparkConfigurationWrapper;
use Symfony\Component\Console\Command\Command;

/**
 * Base class for all gitlab commands.
 *
 * @author Paolo Mainardi <paolo.mainardi@sparkfabrik.com>
 *
 * @api
 */
class GitlabCommand extends SparkCommand
{
  private $gitlabConfig;
  private $gitlabClient;

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
   * Return gitlab configuration.
   */
  public function getGitlabConfig() {
    return $this->gitlabConfig;
  }

  /**
   * Return gitlab client.
   */
  public function getGitlabClient() {
    $client = $this->gitlabClient;
    if (!$client) {
      throw new \Exception('Gitlab client not defined');
    }
    return $client;
  }

  /**
   * Create gitlab client.
   */
  private function createGitlabClient() {
    $this->gitlabClient = new \Gitlab\Client($this->gitlabConfig['gitlab_url']);
    $this->gitlabClient->authenticate($this->gitlabConfig['gitlab_token'], \Gitlab\Client::AUTH_URL_TOKEN);
  }

  /**
   * Initialize configurations and client.
   */
  protected function initConfig() {
    $configManager = new SparkConfigurationWrapper();
    $this->gitlabConfig = $configManager->getValueFromConfig('services', 'gitlab_credentials');
    $this->gitlabConfig['project_id'] = $configManager->getValueFromConfig('projects', 'gitlab_project_id');
    $this->createGitlabClient();
  }
}

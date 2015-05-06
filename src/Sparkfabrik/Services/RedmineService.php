<?php

/*
 * This file is part of the Spark tool package.
 *
 * (c) Paolo Mainardi <paolo.mainardi@sparkfabrik.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sparkfabrik\Tools\Spark\Services;
use Sparkfabrik\Tools\Spark\Services\AbstractService;
use Sparkfabrik\Tools\Spark\Services\ServiceInterface;
use Sparkfabrik\Tools\Spark\SparkConfigurationWrapper;
use Sparkfabrik\Tools\Spark\SparkConfigurationWrapperInterface;
use Redmine\Client;

/**
 * Base class for all redmine commands.
 *
 * @author Paolo Mainardi <paolo.mainardi@sparkfabrik.com>
 *
 * @api
 */
class RedmineService extends AbstractService
{
  protected function initConfig() {
    $config = new SparkConfigurationWrapper();
    $this->config = $config->getValueFromConfig('services', 'redmine_credentials');
    $this->config['project_id'] = $config->getValueFromConfig('projects', 'redmine_project_id');
    $this->config['git_pattern'] = $config->getValueFromConfig('git', 'branch_pattern');
  }

  protected function initClient() {
    $client = new \Redmine\Client(
      $this->config['redmine_url'],
      $this->config['redmine_api_key']
    );
    if (empty($client)) {
      throw new Exception('Cannot connect to redmine client, check your configurations.');
    }
    $this->setClient($client);
  }
}

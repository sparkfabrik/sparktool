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
use Gitlab\Client;

/**
 * Base class for all redmine commands.
 *
 * @author Paolo Mainardi <paolo.mainardi@sparkfabrik.com>
 *
 * @api
 */
class GitlabService extends AbstractService
{
    protected function initConfig(SparkConfigurationWrapperInterface $config = null)
    {
        if (empty($config)) {
            $config = new SparkConfigurationWrapper();
        }
        $this->config = $config->getValueFromConfig('services', 'gitlab_credentials');
        $this->config['project_id'] = $config->getValueFromConfig('projects', 'gitlab_project_id');
    }

    protected function initClient()
    {
        if (empty($this->client)) {
            $gitlab_url = $this->config['gitlab_url'];
            if (substr($gitlab_url, -1) !== '/') {
                $gitlab_url .= '/';
            }
            $gitlab_url .= 'api/v3/';
            $this->client = new \Gitlab\Client(
                $gitlab_url
            );
            $this->client->authenticate($this->config['gitlab_token'], \Gitlab\Client::AUTH_URL_TOKEN);
            if (empty($this->client)) {
                throw new \Exception('Cannot connect to redmine client, check your configurations.');
            }
        }
    }
}

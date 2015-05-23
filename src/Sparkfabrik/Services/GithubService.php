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
use Github\Client;

/**
 * Base class for all github commands.
 *
 * @author Edoardo Dusi <edoardo.dusi@sparkfabrik.com>
 *
 * @api
 */
class GithubService extends AbstractService
{
    protected function initConfig(SparkConfigurationWrapperInterface $config = null)
    {
        if (empty($config)) {
            $config = new SparkConfigurationWrapper();
        }
        $this->config = $config->getValueFromConfig('services', 'github');
        $this->config['git_pattern'] = $config->getValueFromConfig('git', 'branch_pattern');
    }

    protected function initClient()
    {
        if (empty($this->client)) {
            $this->client = new \Github\Client();
            if (empty($this->client)) {
                throw new \Exception('Cannot connect to github client, check your configurations.');
            }
        }
    }
}

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
use Sparkfabrik\Tools\Spark\Services\GitlabService;
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
    /**
     * Constructor.
     *
     * @param string|null $name The name of the command; passing null means it must be set in configure()
     *
     * @throws \LogicException When the command name is empty
     *
     * @api
     */
    public function __construct($name = null)
    {
        parent::__construct($name);
    }

    /**
     * Initialize configurations and client.
     */
    protected function initService()
    {
        $this->service = new GitlabService();
        $this->service->run();
    }
}

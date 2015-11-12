<?php

/*
 * This file is part of the Spark tool package.
 *
 * (c) Paolo Mainardi <paolo.mainardi@sparkfabrik.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sparkfabrik\Tools\Spark\Command\Github;

use Sparkfabrik\Tools\Spark\Command\SparkCommand;
use Sparkfabrik\Tools\Spark\Services\GithubService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Github\Client;

/**
 * Base class for all GitHub commands.
 *
 * @author Edoardo Dusi <edoardo.dusi@sparkfabrik.com>
 *
 * @api
 */
class GithubCommand extends SparkCommand
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
        $this->service = new GithubService();
        $this->service->run();
    }

    /**
    * Generate output table.
    */
    protected function tableGithubOutput($output, $fields, $res, $key)
    {
        $table = new Table($output);
        $table->setHeaders(array_values($fields));
        $rows = array();
        foreach ($res as $val) {
            $row = array();
            foreach ($fields as $field => $key) {
                $field_val = $val[$field];
                if (isset($val[$field])) {
                    $row[] = $field_val;
                } else {
                    $row[] = '';
                }
            }
            $rows[] = $row;
        }
        $table->setRows($rows)->render();
    }
}

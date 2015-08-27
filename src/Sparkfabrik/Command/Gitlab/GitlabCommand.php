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
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

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

    /**
    * Generate output table.
    */
    protected function tableGitlabOutput($output, $fields, $res)
    {
        $table = new Table($output);
        $table->setHeaders(array_values($fields));
        $rows = array();
        if (function_exists('mb_substr')) {
            $truncate_func = 'mb_substr';
        } else {
            $truncate_func = 'substr';
        }
        // Pretty print created/updated.
        $dates_fields = array(
            'created_at' => array('format' => 'd-m-Y'),
            'updated_at' => array('format' => 'd-m-Y H:i:s')
        );
        foreach ($res as $key => $val) {
            $row = array();
            foreach ($fields as $field => $key) {
                if (isset($val[$field])) {
                    if (array_key_exists($field, $dates_fields)) {
                        $format = $dates_fields[$field]['format'];
                        $date = new \DateTime($val[$field]);
                        $field_val = $date->format($format);
                    } else {
                        $field_val = $val[$field];
                    }
                    $row[] = $truncate_func($field_val, 0, 72);
                } else {
                    $row[] = '';
                }
            }
            $rows[] = $row;
        }
        $table->setRows($rows)->render();
    }

    /**
     * Make the results array a plain array.
     * @param  mixed[] &$results The
     * @return [type]           [description]
     */
    protected function makePlainArray(&$results)
    {
        foreach ($results as $key => $value) {
            foreach ($value['author'] as $a_key => $a_value) {
                $results[$key]['author_' . $a_key] = $a_value;
            }

            $results[$key]['assignee_name'] = '';
            if ($value['assignee'] != null) {
                foreach ($value['assignee'] as $as_key => $as_value) {
                    $results[$key]['assignee_' . $as_key] = $as_value;
                }
            }
        }
    }

    /**
     * Help to find a project ID with a search by name.
     * @param  string $project_name
     *         A case-sensitive string contained in the project name.
     * @return ConsoleOutput output and then exit.
     */
    protected function findProjectId($project_name)
    {
        $output = new ConsoleOutput;
        $client = $this->getService()->getClient();
        $res = $client->api('projects')->search($project_name);

        $output->writeln('<info>Projects by name founds:</info>');
        foreach ($res as $key => $project) {
            $output->writeln('* ID: ' . $project['id'] . ' - ' . 'Name: ' . $project['name'] . ' - ' . $project['name_with_namespace']);
        }
        $output->writeln('<info>Select a project ID and put it into the config file such the value of "gitlab_project_id" or use it such the "project_id" option</info>');
        exit;
    }
}

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
use Sparkfabrik\Tools\Spark\Services\RedmineService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Redmine\Client;

/**
 * Base class for all redmine commands.
 *
 * @author Paolo Mainardi <paolo.mainardi@sparkfabrik.com>
 *
 * @api
 */
class RedmineCommand extends SparkCommand
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
        $this->service = new RedmineService();
        $this->service->run();
    }

    /**
    * Generate output table.
    */
    protected function tableRedmineOutput($output, $fields, $res, $key)
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
        'created_on' => array('format' => 'd-m-Y'),
        'updated_on' => array('format' => 'd-m-Y H:m:s')
        );
        foreach ($res[$key] as $val) {
            $row = array();
            foreach ($fields as $field => $key) {
                if (isset($val[$field])) {
                    if (array_key_exists($field, $dates_fields)) {
                        $format = $dates_fields[$field]['format'];
                        $field_val = date($format, strtotime($val[$field]));
                    } elseif (isset($val[$field]['name'])) {
                        $field_val = $val[$field]['name'];
                    } else {
                        $field_val = $val[$field];
                    }
                    $row[] = $truncate_func($field_val, 0, 50);
                } else {
                    $row[] = '';
                }
            }
            $rows[] = $row;
        }
        $table->setRows($rows)->render();

        // Warns the user about limit and total_count.
        $limit = $res['limit'];
        $total_count = $res['total_count'];
        if (is_array($res['limit'])) {
            $limit = array_sum($res['limit']);
        }
        if (is_array($res['total_count'])) {
            $total_count = reset($res['total_count']);
        }
        if ($limit < $total_count) {
            $text = "<info>Showing \"%d\" of \"%d\" issues</info>";
            $text .= "<comment>(you can adjust the limit using --limit argument)</comment>";
            $info = sprintf(
                $text,
                $res['limit'],
                $res['total_count']
            );
            $output->writeln("");
            $output->writeln($info);
            $output->writeln("");
        }
    }

    /**
   * Generate a mini report based on results.
   */
    protected function tableRedmineReportOutput($output, $res, $key)
    {
        $table = new Table($output);
        $table->setHeaders(
            array(
            'Issues',
            'Estimated hours',
            'Estimated days',
            'Number of developers'
            )
        );
        $rows = array();
        $estimated_time = 0;
        $developers = array();
        foreach ($res[$key] as $val) {
            if (isset($val['estimated_hours'])) {
                $estimated_time += $val['estimated_hours'];
            }
            if (isset($val['assigned_to']['name'])) {
                $developer = $val['assigned_to']['name'];
                if (!isset($developers[$developer])) {
                    $developers[$developer] = $developer;
                }
            }
        }
        $table->setRows(
            array(
            array(count($res[$key]), $estimated_time, ceil($estimated_time / 8), count($developers)))
        );

        return $table->render();
    }
}

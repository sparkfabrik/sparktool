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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Yaml\Yaml;
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
    $configManager = $this->getConfigurationManager();
    $this->redmineConfig = $configManager->getValueFromConfig('services', 'redmine_credentials');
    $this->redmineConfig['project_id'] = $configManager->getValueFromConfig('projects', 'redmine_project_id');
    $this->createRedmineClient();
  }

  /**
   * Generate output table.
   */
  protected function tableRedmineOutput($output, $fields, $res, $key) {
    $table = new Table($output);
    $table->setHeaders(array_values($fields));
    $rows = array();
    if (function_exists('mb_substr')) {
      $truncate_func = 'mb_substr';
    }
    else {
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
          }
          elseif (isset($val[$field]['name'])) {
            $field_val = $val[$field]['name'];
          }
          else {
            $field_val = $val[$field];
          }
          $row[] = $truncate_func($field_val, 0, 50);
        }
        else {
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
      $info = sprintf("<info>Showing \"%d\" of \"%d\" issues</info> <comment>(you can adjust the limit using --limit argument)</comment>",
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
  protected function tableRedmineReportOutput($output, $res, $key) {
    $table = new Table($output);
    $table->setHeaders(array(
      'Issues',
      'Estimated hours',
      'Estimated days',
      'Number of developers'
    ));
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
    $table->setRows(array(
      array(count($res[$key]), $estimated_time, ceil($estimated_time / 8), count($developers)))
    );
    return $table->render();
  }

}

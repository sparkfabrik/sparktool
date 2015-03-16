<?php

namespace Sparkfabrik\Tools\Spark\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Yaml\Yaml;

abstract class BaseCommand extends Command
{
   protected function getOptions($path = __DIR__) {
      $reflection = new \ReflectionClass($this);
      $base_path = $path . '/config/';
      $options_file = strtolower($reflection->getShortName()) . '_options.yml';
      if (file_exists($base_path . $options_file)) {
        try {
          $yaml = file_get_contents($base_path . $options_file);
          $config = Yaml::parse($yaml);
          foreach ($config['options'] as $key => $option) {
            if ($config['options'][$key]['optional'] === true) {
              $config['options'][$key]['optional'] = InputOption::VALUE_OPTIONAL;
            }
            elseif ($config['options'][$key]['optional'] === 'none') {
              $config['options'][$key]['optional'] = InputOption::VALUE_NONE;
            }
            else {
              $config['options'][$key]['optional'] = InputOption::VALUE_REQUIRED;
            }
          }
          return $config['options'];
        }
        catch (Exception $e) {
          return $output->writeln('<error>'. $e->getMessage() . '</error>');
        }
      }
  }

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
    foreach ($res[$key] as $val) {
      $row = array();
      foreach ($fields as $field => $key) {
        if (isset($val[$field])) {
          if ($field == 'created_on' || $field == 'updated_on') {
            $field_val = date('d/m/Y', strtotime($val[$field]));
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
    $table->setRows($rows);
    return $table;
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
    return $table;
  }

}

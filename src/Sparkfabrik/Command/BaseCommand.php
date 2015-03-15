<?php

namespace Sparkfabrik\Tools\Spark\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
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
              $config['options'][$key]['optional'] = InputArgument::OPTIONAL;
            }
            else {
              $config['options'][$key]['optional'] = InputArgument::REQUIRED;
            }
          }
          return $config['options'];
        }
        catch (Exception $e) {
          return $output->writeln('<error>'. $e->getMessage() . '</error>');
        }
      }
  }

  protected function tableOutput($output, $fields, $res, $key) {
    $table = new Table($output);
    $table->setHeaders(array_values($fields));
    $rows = array();
    foreach ($res[$key] as $val) {
      $row = array();
      foreach ($fields as $field => $key) {
        if (isset($val[$field])) {
          if ($field == 'created_on' || $field == 'updated_on') {
            $row[] = date('d/m/Y', strtotime($val[$field]));
          }
          elseif (isset($val[$field]['name'])) {
            $row[] = $val[$field]['name'];
          }
          else {
            $row[] = $val[$field];
          }
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

}

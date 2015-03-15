<?php

namespace Sparkfabrik\Tools\Spark\Command\Redmine;

use Sparkfabrik\Tools\Spark\SparkConfigurationWrapper;
use Sparkfabrik\Tools\Spark\Command\BaseCommand;
use Redmine\Client as Redmine;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RedmineIssueCommand extends BaseCommand
{
    protected $redmineConfig;
    protected $redmineClient;

    private function createRedmineClient() {
      try {
        $this->redmineClient = new Redmine(
          $this->redmineConfig['redmine_url'],
          $this->redmineConfig['redmine_api_key']
        );
      }
      catch (Exception $e) {
        return $output->writeln('<error>'. $e->getMessage() . '</error>');
      }
    }

    protected function configure() {
      $configManager = new SparkConfigurationWrapper();
      $this->redmineConfig = $configManager->getValueFromConfig('services', 'redmine_credentials');
      $this->redmineConfig['project_id'] = $configManager->getValueFromConfig('projects', 'redmine_project_id');
      $this->createRedmineClient();
      $this->setName('redmine:list')
           ->setDescription('List redmine issues');
      $options = $this->getOptions(__DIR__);
      foreach ($options as $option) {
        call_user_func_array(array($this, "addOption"), $option);
      }
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
      try {
        $client = $this->redmineClient;
        // Get project id, arguments overrides project.
        $project_id = $input->getOption('project_id')
          ? $input->getOption('project_id')
          : $this->redmineConfig['project_id'];

        $assigned_to_id = $input->getOption('assigned_to_id');
        if ($assigned_to_id === 'me') {
          $assigned_to_id = $client->api('user')->getCurrentUser()['user']['id'];
        }
        elseif ($assigned_to_id === 'all') {
          $assigned_to_id = '';
        }

        $issues = $client->api('issue')->all(array(
          'limit' => $input->getOption('limit'),
          'sort' => $input->getOption('sort'),
          'project_id' => $project_id,
          'tracker_id' => $input->getOption('tracker_id'),
          'status_id' => $input->getOption('status_id'),
          'assigned_to_id' => $assigned_to_id,
        ));

        // This is how redmine library return empty results :(.
        if (count($issues) == 1 && ($issues[0] === 1)) {
          return $output->writeln('<info>No results</info>');
        }
        // Fields to print.
        $fields = array(
          'id' => 'ID',
          'project' => 'Project',
          'created_on' => 'Created',
          'updated_on' => 'Updated',
          'tracker' => 'Tracker',
          'fixed_version' => 'Version',
          'author' => 'Author',
          'assigned_to' => 'Assigned',
          'status' => 'Status',
          'estimated_hours' => 'Estimated',
          'subject' => 'Subject',
        );

        // Hide project if it is already configured.
        if ($project_id) {
          unset($fields['project']);
        }
        $this->tableOutput($output, $fields, $issues, 'issues')
             ->render();
      }
      catch (Exception $e) {
        return $output->writeln('<error>'. $e->getMessage() . '</error>');
      }
    }
}

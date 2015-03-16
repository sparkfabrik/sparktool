<?php

namespace Sparkfabrik\Tools\Spark\Command\Redmine;

use Sparkfabrik\Tools\Spark\SparkConfigurationWrapper;
use Sparkfabrik\Tools\Spark\Command\BaseCommand;
use Sparkfabrik\Tools\Spark\RedmineApi\User as RedmineApiUser;
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
      // Add options.
      $this->addOption(
        'report',
        null,
        InputOption::VALUE_NONE,
        'Print a summary report'
      );
      $this->addOption(
        'limit',
        'l',
        InputOption::VALUE_OPTIONAL,
        'How many issues should be printed ?',
        50
      );
      $this->addOption(
        'sort',
        's',
        InputOption::VALUE_OPTIONAL,
        'Issues sorting',
        'updated_on:desc'
      );
      $this->addOption(
        'project_id',
        'p',
        InputOption::VALUE_OPTIONAL,
        'Filter by project machine name'
      );
      $this->addOption(
        'status',
        false,
        InputOption::VALUE_OPTIONAL,
        'Filter by project status name or id',
        'open'
      );
      $this->addOption(
        'assigned',
        false,
        InputOption::VALUE_OPTIONAL,
        'Filter by assigned to user id or by user name',
        'me'
      );
      $this->addOption(
        'sprint',
        false,
        InputOption::VALUE_OPTIONAL,
        'Filter by version (sprint name). You can specify name (ex: SPRINT-XX) or numeric-id.'
      );
    }

    /**
     * Read status argument and translate to a redmine status_id.
     *
     * @param string|integer $status
     *
     * @return integer|boolean
     */
    private function handleArgumentStatusId($status) {
      if (is_numeric($status)) {
        return $status;
      }
      // Handle status (ex: Open, Closed, Feedback ecc..).
      $status = strtolower($status);
      $default_statues = array('*', 'open', 'close');
      if (in_array($status, $default_statues)) {
        return $status;
      }
      else {
        $custom_statuses = $this->redmineClient->api('issue_status')->all()['issue_statuses'];
        foreach ($custom_statuses as $custom_status) {
          if (strtolower($custom_status['name']) === $status) {
            $status_id = $custom_status['id'];
            return $status_id;
          }
        }
      }
      throw new \Exception('Status not found.');
    }

    /**
     * Read project_id argument and translate to a redmine project_id.
     *
     * @param string|integer $project_id
     *
     * @return integer|boolean
     */
    private function handleAgumentProjectId($project_id = null) {
      return ($project_id ? $project_id : $this->redmineConfig['project_id']);
    }

    /**
     * Read assigned argument and translate to a redmine assigned_to_id.
     *
     * @return integer|boolean
     */
    private function handleArgumentAssignedToId($assigned) {
      if (is_numeric($assigned)) {
        return $assigned;
      }
      if ($assigned !== 'all') {
        // Instantiate proxy redmine user class, we need more power.
        $redmineUserClient = new RedmineApiUser($this->redmineClient);

        // Handle "me" alias.
        if ($assigned === 'me') {
          $assigned_id = $this->redmineClient->api('user')->getCurrentUser()['user']['id'];
          return $assigned_id;
        }
        else {
          // Translate string to id.
          $user_id = $redmineUserClient->getIdByFirstLastName($assigned);
          if ($user_id === false) {
            throw new \Exception('No user found.');
          }
          return $user_id;
        }
      }
    }

    /**
     * Read version argument and translate to a redmine fixed_version_id.
     *
     * @return integer|boolean
     */
    private function handleArgumentFixedVersionId($sprint, $project_id) {
      if (is_numeric($sprint)) {
        return $sprint;
      }
      else {
        // @todo check if getIdByName() suffers of autolimit of 25 records
        // as it happens for users.
        // @todo make this search case unsensitive.
        $fixed_version_id = $this->redmineClient->api('version')->getIdByName($project_id, $sprint);
        if ($fixed_version_id === false) {
           throw new \Exception('No sprint version found.');
        }
        return $fixed_version_id;
      }
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
      try {
        $client = $this->redmineClient;
        $api_options = array();
        $api_options['limit'] = $input->getOption('limit');
        $api_options['sort'] = $input->getOption('sort');

        // Arguments handling.
        try {
          $project_id = $this->handleAgumentProjectId($input->getOption('project_id'));
          if ($project_id) {
            $api_options['project_id'] = $project_id;
            // Versions depends on project_id.
            if ($input->getOption('sprint')) {
              $api_options['fixed_version_id'] = $this->handleArgumentFixedVersionId($input->getOption('sprint'), $project_id);
            }
          }
          if ($input->getOption('status')) {
            $api_options['status_id'] = $this->handleArgumentStatusId($input->getOption('status'));
          }
          if ($input->getOption('assigned')) {
            $api_options['assigned_to_id'] = $this->handleArgumentAssignedToId($input->getOption('assigned'));
          }
        }
        catch (\Exception $e) {
          return $output->writeln('<error>' . $e->getMessage() . '</error>');
        }

        // Print debug informations if required.
        if ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
          if (function_exists('dump')) {
            dump($api_options);
          }
          else {
            var_dump($api_options);
          }
        }

        // Run query.
        $res = $client->api('issue')->all($api_options);
        // This is how redmine library return empty results.
        if (count($res) == 1 && ($res[0] === 1)
          || (isset($res['total_count']) && $res['total_count'] === 0)) {
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
        // Render issue table.
        $this->tableRedmineOutput($output, $fields, $res, 'issues')
             ->render();
        if ($input->getOption('report')) {
          $this->tableRedmineReportOutput($output, $res, 'issues')
               ->render();
        }
      }
      catch (Exception $e) {
        return $output->writeln('<error>'. $e->getMessage() . '</error>');
      }
    }
}

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

use Sparkfabrik\Tools\Spark\Command\Redmine\RedmineCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Redmine\Api\Tracker;

class RedmineSearchCommand extends RedmineCommand
{
    // Include helper trait.
    use \Sparkfabrik\Tools\Spark\Helpers\Traits\Command\Redmine\RedmineSearchTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('redmine:search')
            ->setDescription('Search redmine issues')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command displays help for a given command:

  <info>php %command.full_name% list</info>

You can also output the help in other formats by using the <comment>--format</comment> option:

  <info>php %command.full_name% --format=xml list</info>

To display the list of available commands, please use the <info>list</info> command.
EOF
            );
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
            'Filter by project status name or id. Possible values: open, closed, *',
            'open'
        );
        // @codingStandardsIgnoreStart
        $this->addOption(
            'assigned',
            false,
            InputOption::VALUE_OPTIONAL,
            <<<EOF
Filter by assigned to user-id or by username. Magic tokens: "me", "not me", "all", "anyone" and "none".
Where:
 - "me" issues assigned to the calling user
 - "not me" issues not assigned to the calling user
 - "all" issues assigned and not assigned
 - "anyone" issues assigned
 - "none" issues not assigned
EOF
            ,
            'all'
        );
        // @codingStandardsIgnoreEnd
        $this->addOption(
            'sprint',
            false,
            InputOption::VALUE_OPTIONAL,
            'Filter by version (sprint name). You can specify name (ex: SPRINT-XX) or numeric-id.'
        );
        $this->addOption(
            'created',
            false,
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            <<<EOF
Single date format: "<=|=> [date]"
Multiple date range format: "[date]"
Where [date] can be any expression supported by strtotime.
EOF
        );
        $this->addOption(
            'updated',
            false,
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Filter by updated date. Supported format: (<=|=>) [any english textual datetime]'
        );
        $this->addOption(
            'not-estimated',
            false,
            InputOption::VALUE_NONE,
            'Filter by not estimated issues.'
        );
        $this->addOption(
            'subject',
            false,
            InputOption::VALUE_OPTIONAL,
            'Filter by subject, it filters contained text in the subject.'
        );
        $this->addOption(
            'tracker',
            false,
            InputOption::VALUE_OPTIONAL,
            'Filter by tracker.'
        );
        $this->addOption(
            'fields',
            false,
            InputOption::VALUE_OPTIONAL,
            <<<EOF
Filter by field. Available fields are:
- id
- project
- created_on
- updated_on
- tracker
- fixed_version
- author
- assigned_to
- status
- estimated_hours
- subject
EOF
        );
        $this->addOption(
            'preset',
            false,
            InputOption::VALUE_OPTIONAL,
            'Load arguments from a saved preset.'
        );
        $this->addOption(
            'save-preset',
            false,
            InputOption::VALUE_OPTIONAL,
            'Save current search with a preset name.'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            if ($preset_name = $input->getOption('preset')) {
                $preset = $this->getSearchPreset($preset_name);
                if ($preset) {
                    $preset_options = unserialize($preset['query']);
                    foreach ($preset_options as $opt_name => $opt_value) {
                        $input->setOption($opt_name, $opt_value);
                    }
                }
            }

            $client = $this->getService()->getClient();
            $api_options = array();
            $api_options['limit'] = $input->getOption('limit');
            $api_options['sort'] = $input->getOption('sort');
            try {
                $this->handleArguments($input, $output, $api_options);
            } catch (\Exception $e) {
                return $output->writeln('<error>' . $e->getMessage() . '</error>');
            }

            // Print debug informations if required.
            if ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
                $output->writeln('<info>' . var_export($api_options, true) . '</info>');
            }

            // Run query.
            $res = $client->api('issue')->all($api_options);

            // JSON Syntax error or just false result.
            if ((isset($res[0]) && ($res[0] === 'Syntax error'))
                || $res === false
            ) {
                throw new \Exception('Failed to parse response.');
            }

            // Handle errors.
            if (isset($res['errors'])) {
                $errors = implode("\n", $res['errors']);
                throw new \Exception($errors);
            }

            // This is how redmine library return empty results.
            if (!count($res)
                || (count($res) == 1 && ($res[0] === 1))
                || (isset($res['total_count']) && $res['total_count'] === 0)
                || (empty($res))
            ) {
                return $output->writeln('<info>No issues found.</info>');
            }

            // Reduce results, filter out estimated issues.
            if ($input->getOption('not-estimated')) {
                foreach ($res['issues'] as $key => $issue) {
                    if (isset($issue['estimated_hours'])) {
                        unset($res['issues'][$key]);
                        if (!is_array($res['total_count'])) {
                            --$res['total_count'];
                        }
                    }
                }
            }

            // Reduce results, filter by subject content.
            if ($input->getOption('subject')) {
                $subject = $input->getOption('subject');
                foreach ($res['issues'] as $key => $issue) {
                    if (stripos($issue['subject'], $subject) === false) {
                        unset($res['issues'][$key]);
                        if (!is_array($res['total_count'])) {
                            --$res['total_count'];
                        }
                    }
                }
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

            if ($input->getOption('fields')) {
                $filters = explode(',', strtolower($input->getOption('fields')));
                $fields = array_intersect_key($fields, array_flip($filters));
                $incorrect_filters = implode(', ', array_keys(array_diff_key(array_flip($filters), $fields)));
                if (!empty($incorrect_filters)) {
                    $output->writeln('<error>Incorrect filters inserted: ' . $incorrect_filters . '</error>');
                }
            }

            // Hide project if it is already configured.
            if (isset($api_options['project_id'])) {
                unset($fields['project']);
            }

            if ($preset = $input->getOption('save-preset')) {
                $can_save = false;
                $this->handleArgumentPresetName($input, $output, $preset, $can_save);
                $options = $input->getOptions();
                unset($options['preset']);
                unset($options['save-preset']);

                if ($can_save) {
                    $query = $options;
                    $this->saveSearchPreset($preset, $query);
                }
            }

            // Render issue table.
            $this->tableRedmineOutput($output, $fields, $res, 'issues');
            if ($input->getOption('report')) {
                $this->tableRedmineReportOutput($output, $res, 'issues');
            }
        } catch (Exception $e) {
            return $output->writeln('<error>'. $e->getMessage() . '</error>');
        }
    }

    /**
     * Handles preset name allowing users to overwrite change or abort the save.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @param  null            $preset
     * @param  bool            $can_save
     * @throws \Exception
     */
    private function handleArgumentPresetName(InputInterface $input, OutputInterface $output, &$preset = null, &$can_save = false)
    {
        $helper = $this->getHelper('question');

        if (empty($preset)) {
            $preset = $input->getOption('save-preset');
        }

        if ($this->getSearchPreset($preset)) {
            $actions = array('overwrite', 'change', 'abort');
            $question = new ChoiceQuestion(
                sprintf('Preset %s already exists. Tell me how to proceed (default: change):', $preset),
                $actions,
                1
            );
            $question->setErrorMessage('You have selected an invalid option.');
            $preset_action = $helper->ask($input, $output, $question);

            if (!in_array($preset_action, $actions)) {
                throw new \Exception('You have selected an invalid option.');
            }

            switch ($preset_action) {
            case 'overwrite':
                $can_save = true;
                break;

            case 'change':
                $question = new Question('Preset name:');
                $preset = $helper->ask($input, $output, $question);
                $this->handleArgumentPresetName($input, $output, $preset, $can_save);
                break;

            case 'abort':
                $can_save = false;
            }
        }
        else {
            $can_save = true;
        }
    }

    /**
     * Get a saved search preset.
     *
     * @param  string $preset
     * @return mixed
     */
    public function getSearchPreset($preset)
    {
        return \FileDB::select('redmine_search_presets', 'rsp')
        ->fields('rsp')
        ->condition('rsp.preset', $preset)
        ->execute()
        ->fetch();
    }

    /**
     * Save a search preset.
     *
     * @param string $preset
     * @param string $query
     */
    private function saveSearchPreset($preset, $query)
    {
        $orig = $this->getSearchPreset($preset);
        $fields = array(
        'preset' => $preset,
        'query' => serialize(array_filter($query)),
        );

        if (!empty($orig)) {
            \FileDB::update('redmine_search_presets')
            ->fields($fields)
            ->condition('preset', $orig['preset'])
            ->execute();
        }
        else {
            \FileDB::insert('redmine_search_presets', $fields);
        }
    }

    /**
     * Read status argument and translate to a redmine status_id.
     *
     * @param string|integer $status
     *
     * @return integer|boolean
     */
    private function handleArgumentStatusId($status)
    {
        if (is_numeric($status)) {
            return $status;
        }

        // Handle status (ex: Open, Closed, Feedback ecc..).
        $status = strtolower($status);
        if (strpos($status, ',') !== false) {
            $statuses = explode(',', $status);
        } else {
            $statuses = array($status);
        }
        $default_statues = array('*', 'open', 'close');
        $status_params = array();
        foreach ($statuses as &$requested_status) {
            $requested_status = trim($requested_status);
            if (in_array($requested_status, $default_statues)) {
                array_push($status_params, $requested_status);
            } else {
                $custom_statuses = $this->getService()->getClient()->api('issue_status')->all()['issue_statuses'];
                foreach ($custom_statuses as $custom_status) {
                    if (strtolower($custom_status['name']) === $requested_status) {
                        array_push($status_params, $custom_status['id']);
                    }
                }
            }
        }
        $status_params = implode('|', $status_params);
        if (strlen($status_params) != 0) {
            return $status_params;
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
    private function handleAgumentProjectId($project_id = null)
    {
        return ($project_id ? $project_id : $this->getService()->getConfig()['project_id']);
    }

    /**
     * Read assigned argument and translate to a redmine assigned_to_id.
     *
     * @return integer|boolean
     */
    private function handleArgumentAssignedToId($assigned)
    {
        if (is_numeric($assigned)) {
            return $assigned;
        }

        // @link http://www.redmine.org/issues/8918#note-3
        $magic_tokens = array(
            'me' => 'me',
            'not me' => '!me',
            'anyone' => '*',
            'none' => '!*',
            'all' => '',
        );
        if (array_key_exists($assigned, $magic_tokens)) {
            return $magic_tokens[$assigned];
        }

        // Translate object to first+last name.
        $name = strtolower($assigned);
        $users = $this->getService()->getClient()->api('user')->all(array('limit' => 200));
        $usernames = $this->redmineUsersObjectToFirstLastname($users);
        if (!isset($name, $usernames)) {
            throw new \Exception('No user found.');
        }
        return $usernames[$name];
    }

    /**
   * Read version argument and translate to a redmine fixed_version_id.
   *
   * @return integer|boolean
   */
    private function handleArgumentFixedVersionId($sprint, $project_id)
    {
        if (is_numeric($sprint)) {
            return $sprint;
        } else {
            // Set a very high limit.
            $version_client = $this->getService()->getClient()->api('version');
            $fixed_version_id = $version_client->getIdByName($project_id, $sprint, array('limit' => 500));
            if ($fixed_version_id === false) {
                throw new \Exception('No sprint version found.');
            }
            return $fixed_version_id;
        }
    }

    /**
     * Handle standard date and date with search operators: "<=" and ">="
     *
     * @link http://www.redmine.org/projects/redmine/wiki/Rest_Issues:
     *
     * @return integer|boolean
     */
    private function handleArgumentDateSingle($date, $use_operators = true)
    {
        $date = strtolower($date);
        if ($use_operators) {
            $op = false;
            $operators = array('<=', '>=');
            foreach ($operators as $operator) {
                if (strpos($date, $operator) !== false) {
                    $op = $operator;
                    $date = trim(str_replace($operator, '', $date));
                    break;
                }
            }
        }
        $timestamp = strtotime($date);
        if (!$timestamp) {
            throw new \Exception(sprintf('Date handler - invalid date format: "%s"', $date));
        }
        $date_option = date('Y-m-d', $timestamp);
        if (isset($op) && $op !== false) {
            $date_option = $op.$date_option;
        }
        return $date_option;
    }

    /**
     * Handle date ranges.
     *
     * @link http://www.redmine.org/projects/redmine/wiki/Rest_Issues:
     *
     * @return integer|boolean
     */
    private function handleArgumentDateRange($args)
    {
        $range = array();
        foreach ($args as $date) {
            $range[] = $this->handleArgumentDateSingle($date, false);
        }
        $range_date = '><' . implode('|', $range);
        return $range_date;
    }

    /**
     * Handle date arguments to parse single or ranges dates.
     *
     * @link http://www.redmine.org/projects/redmine/wiki/Rest_Issues:
     *
     * @return integer|boolean
     */
    private function handleArgumentDate($args)
    {
        if (count($args) > 2) {
            throw new Exception('Date handler: Too much dates dude!');
        }
        if (count($args) == 2) {
            return $this->handleArgumentDateRange($args);
        } else {
            return $this->handleArgumentDateSingle(reset($args));
        }
    }

    /**
     * Handle tracker argument.
     *
     * @todo verify multiple values against this request:
     *  https://github.com/kbsali/php-redmine-api/issues/127 .
     *
     * @param $tracker integer|string
     *
     * @return integer
     */
    private function handleArgumentTracker($tracker)
    {
        $trackerId = 0;

        $trackers = $this->getService()->getClient()->api('tracker')->listing();
        if (is_numeric($tracker)) {
            if (in_array($tracker, $trackers)) {
                $trackerId = $tracker;
            }
        } else {
            $tracker = strtolower($tracker);
            foreach ($trackers as $tname => $tid) {
                if ($tracker === strtolower($tname)) {
                    $trackerId = $tid;
                    break;
                }
            }
        }

        if (empty($trackerId)) {
            throw new \Exception("Unrecognized tracker given by argument.");
        }

        return $trackerId;
    }

    /**
     * Handle standard arguments.
     *
     * @link http://www.redmine.org/projects/redmine/wiki/Rest_Issues:
     *
     * @return integer|boolean
     */
    private function handleArguments(InputInterface $input, OutputInterface $output, &$api_options)
    {
        $project_id = $this->handleAgumentProjectId($input->getOption('project_id'));
        if ($project_id) {
            $api_options['project_id'] = $project_id;

            // Versions depends on project_id.
            if ($input->getOption('sprint')) {
                $api_options['fixed_version_id'] = $this->handleArgumentFixedVersionId(
                    $input->getOption('sprint'),
                    $project_id
                );
            }
        }
        if ($input->getOption('status')) {
            $api_options['status_id'] = $this->handleArgumentStatusId($input->getOption('status'));
        }
        if ($input->getOption('assigned')) {
            $assignment = ($input->getOption('assigned') ? $input->getOption('assigned') : 'me');
            $api_options['assigned_to_id'] = $this->handleArgumentAssignedToId($input->getOption('assigned'));
        }
        if ($input->getOption('created')) {
            $created_args = $input->getOption('created');
            $api_options['created_on'] = $this->handleArgumentDate($created_args);
        }
        if ($input->getOption('updated')) {
            $updated_args = $input->getOption('updated');
            $api_options['updated_on'] = $this->handleArgumentDate($updated_args);
        }
        if ($input->getOption('tracker')) {
            $tracker_args = $input->getOption('tracker');
            $api_options['tracker_id'] = $this->handleArgumentTracker($tracker_args);
        }
    }
}

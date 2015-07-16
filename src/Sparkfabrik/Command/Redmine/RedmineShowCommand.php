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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;

class RedmineShowCommand extends RedmineCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('redmine:show')
            ->setDescription('Show issue.');
        $this->addArgument(
            'issue',
            InputArgument::REQUIRED,
            'Issue id'
        );
        $this->addOption(
            'mr',
            'm',
            InputOption::VALUE_NONE,
            'Extract and print the merge requests links.'
        );
        $this->addOption(
            'open',
            'o',
            InputOption::VALUE_NONE,
            'Open issue or merge requests.'
        );
        $this->addOption(
            'description',
            'd',
            InputOption::VALUE_NONE,
            'Show the issue description.'
        );
        $this->addOption(
            'info',
            'i',
            InputOption::VALUE_NONE,
            'Show the issue\'s not essential fields. Such as author and creation date.'
        );
        $this->addOption(
            'complete',
            'c',
            InputOption::VALUE_NONE,
            'Show all the issue\'s details.'
        );
    }

    /**
     * Extract merge requests.
     */
    private function extractMergeRequests($comments)
    {
        $regex = '/https?:\/\/[^\s()<>]+merge_requests+\/[0-9]+/';
        $output = [];
        foreach ($comments as $comment) {
            if (isset($comment['notes']) && (!empty($comment['notes']))) {
                $note = trim($comment['notes']);
                $created = date('d-m-Y', strtotime($comment['created_on']));
                if (preg_match_all($regex, $comment['notes'], $matches)) {
                    foreach ($matches[0] as $match) {
                        $output[$created][] = $match;
                    }
                }
            }
        }
        return $output;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client = $this->getService()->getClient();

        // Arguments and options.
        $issue_id = $input->getArgument('issue');
        $redmine_url = $this->service->getConfig()['redmine_url'];
        $show_mr = $input->getOption('mr');
        $show_info = $input->getOption('info');
        $show_me_everything = $input->getOption('complete');
        $open = $input->getOption('open');
        $open_command = (PHP_OS === 'Darwin' ? 'open' : 'xdg-open');
        $description = $input->getOption('description');

        $params = array('include' => array('journals'));
        $res = $client->api('issue')->show($issue_id, $params);

        // Handle errors.
        if (isset($res['errors'])) {
            $errors = implode("\n", $res['errors']);
            throw new \Exception($errors);
        }
        if ($res === 1) {
            return $output->writeln('<info>No issues found.</info>');
        }

        // Issue subject and description.
        $issue = $res['issue'];
        $redmine_issue_url = $redmine_url . '/issues/' . $issue_id;
        $output->writeln('');
        $output->writeln($issue['subject']);
        if ($description || $show_me_everything) {
            $output->writeln('<comment>' . trim($issue['description']) . '</comment>');
        }
        $output->writeln('');

        // Issue details list.
        $details = array(
            'tracker' => 'Type (Tracker)',
            'assigned_to' => 'Assigned to',
            'status' => 'Status',
            'priority' => 'Priority',
            'category' => 'Category',
            'fixed_version' => 'Fixed version',
        );

        $table = new Table($output);
        foreach ($details as $key => $label) {
            if (!empty($issue[$key])) {
                $table ->addRow(array(
                    '<info>' . $label . ': </info>',
                    $issue[$key]['name']
                ));
            }
        }

        // Separate custom fields form the above.
        if (!empty($issue['custom_fields'])) {
            $additional_rows = array();
            foreach ($issue['custom_fields'] as $key => $field) {
                if (!empty($field['value'])) {
                    $value = (string) $field['value'];
                    $name = (string) $field['name'];
                    $additional_rows[] = array('<info>' . $name . ':</info>', $value);
                }
            }
            if (!empty($additional_rows)) {
                $table->addRow(new TableSeparator());
                $table->addRows($additional_rows);
            }
        }

        // Include the following details only when requested.
        if ($show_info || $show_me_everything) {
            $date = new \DateTime($issue['created_on']);
            $created_at = $date->format('d-m-Y');
            $table->addRows(array(
                new TableSeparator(),
                array('<info>Author: </info>', $issue['author']['name']),
                array('<info>ID: </info>', $issue['id']),
                array('<info>Creation date: </info>', $created_at),
                array('<info>Done ratio: </info>', $issue['done_ratio'] . '%'),
                array('<info>Spent hours: </info>', $issue['spent_hours']),
            ));
        }

        // Finally render the details table.
        $table->render();
        $output->writeln('');

        // Extract merge requests.
        if ($show_mr || $show_me_everything) {
            $table = new Table($output);
            if (count($issue['journals'])) {
                $mrs = $this->extractMergeRequests($issue['journals']);
                if (!empty($mrs)) {
                    $table->setHeaders(array('Merge requests URL', 'Posted on'));
                    foreach ($mrs as $date => $mr) {
                        foreach ($mr as $mr_single) {
                            $table->addRow(array($mr_single, $date));
                            $mrs_urls[] = $mr;
                        }
                    }
                    if ($open) {
                        // This is needed just to open the browser.
                        $command = $open_command . ' -Wn http://example.com';
                        $process = new Process($command);
                        $process->run();

                        // Open merge requests.
                        $command = $open_command . ' ' . implode(' ', $mrs_urls);
                        $process = new Process($command);
                        $process->run();
                    }
                }
            }
            if (empty($mrs) || !count($issue['journals'])) {
                $table->addRow(array('No Merge Requests have been opened yet.'));
            }
            $table->render();
            $output->writeln('');
        }

        if ($open && !$show_mr) {
            $command = $open_command . ' ' . $redmine_issue_url;
            $process = new Process($command);
            $process->run();
        }
        $output->writeln('<info>URL: </info>'. $redmine_issue_url);
        $output->writeln('');

        // Prompt help message.
        if (!$show_me_everything) {
            $output->writeln('Use [-c|--complete] to show full list of details and MR.');
            $output->writeln('');
        }
    }
}

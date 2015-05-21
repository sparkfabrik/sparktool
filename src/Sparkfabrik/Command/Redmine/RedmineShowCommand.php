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
            false,
            InputOption::VALUE_NONE,
            'Extract and print the merge requests links.'
        );
        $this->addOption(
            'open',
            false,
            InputOption::VALUE_NONE,
            'Open issue or merge requests.'
        );
        $this->addOption(
            'description',
            false,
            InputOption::VALUE_NONE,
            'Show the issue description.'
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
                if (preg_match_all($regex, $comment['notes'], $matches)) {
                    foreach ($matches[0] as $match) {
                        $output[] = $match;
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
        $open = $input->getOption('open');
        $open_command = (PHP_OS === 'Darwin' ? 'open' : 'xdg-open');
        $description = $input->getOption('description');

        $params = array('include' => 'journals');
        $res = $client->api('issue')->show($issue_id, $params);
        $extra_output = array();

        // Handle errors.
        if (isset($res['errors'])) {
            $errors = implode("\n", $res['errors']);
            throw new \Exception($errors);
        }
        if ($res === 1) {
            return $output->writeln('<info>No issues found.</info>');
        }

        // Issue element.
        $issue = $res['issue'];
        $redmine_issue_url = $redmine_url . '/issues/' . $issue_id;
        $output->writeln('<info>Subject: </info>'. $issue['subject']);
        $output->writeln('<info>URL: </info>'. $redmine_issue_url);
        if ($description) {
            $output->writeln('<info>Description: </info>');
            $output->writeln(trim($issue['description']));
        }
        foreach ($extra_output as $name => $elements) {
            $output->writeln('<info>' . strtoupper($name) . ':</info>');
            foreach ($elements as $element) {
                $output->writeln($element);
            }
        }

        // Extract merge requests.
        if ($show_mr && count($issue['journals'])) {
            $mrs = $this->extractMergeRequests($issue['journals']);
            if (!empty($mrs)) {
                $table = new Table($output);
                $table->setHeaders(array('Merge requests'));
                foreach ($mrs as $mr) {
                    $table->addRow(array($mr));
                }
                $table->render();
                if ($open) {
                    // This is needed just to open the browser.
                    $command = $open_command . ' -Wn http://example.com';
                    $process = new Process($command);
                    $process->run();

                    // Open merge requests.
                    $command = $open_command . ' ' . implode(' ', $mrs);
                    $process = new Process($command);
                    $process->run();
                }
            }
        }

        if ($open && !$show_mr) {
            $command = $open_command . ' ' . $redmine_issue_url;
            $process = new Process($command);
            $process->run();
        }
        $output->writeln("");
    }
}

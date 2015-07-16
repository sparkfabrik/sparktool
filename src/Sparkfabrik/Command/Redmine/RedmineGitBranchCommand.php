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

class RedmineGitBranchCommand extends RedmineCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('redmine:git:branch')
            ->setDescription(<<<EOF
Generate git branch. Name is generated starting from issue subject
EOF
            );
        $this->addArgument(
            'issue',
            InputArgument::REQUIRED,
            'Issue id'
        );
        $this->addArgument(
            'origin-branch',
            InputArgument::OPTIONAL,
            'Branch to start from.',
            'develop'
        );
        $this->addOption(
            'dry-run',
            false,
            InputOption::VALUE_NONE,
            'Just print the branch name, not execute git flow.'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $branch = $this->getService()->getConfig()['git_pattern'];
        $client = $this->getService()->getClient();
        $issue = $input->getArgument('issue');
        $origin_branch = $input->getArgument('origin-branch');
        $dry_run = $input->getOption('dry-run');
        $res = $client->api('issue')->show($issue);

        // Handle errors.
        if (isset($res['errors'])) {
            $errors = implode("\n", $res['errors']);
            throw new \Exception($errors);
        }
        if ($res === 1) {
            return $output->writeln('<info>No issues found.</info>');
        }

        // Use Custom field to determine the story name.
        $story = null;
        $story_name = $res['issue']['subject'];
        foreach ($res['issue']['custom_fields'] as $field) {
            if ($field['name'] === 'Jira Story Code') {
                $story = $field['value'];
                $story_name = str_replace($field['value'], '', $story_name);
            }
        }

        // Clean up story name.
        if (mb_detect_encoding($story_name) === 'UTF-8') {
            $story_name_converted = @iconv('UTF-8', 'ASCII//TRANSLIT', $story_name);
            if ($story_name_converted) {
                $story_name = $story_name_converted;
            } else {
                $story_name = iconv('UTF-8', 'ASCII//IGNORE', $story_name);
            }
        }
        $story_name = preg_replace("/[^a-z0-9 -]/i", '', $story_name);
        $story_name = strtolower(preg_replace("/\W/", '_', $story_name));
        $story_name = implode((array_filter(explode(' ', str_replace('_', ' ', $story_name)))), '_');

        // Replace patterns from branch structure.
        $branch = str_replace('%(story)', $story, $branch);
        $branch = str_replace('%(issue_id)', (string) $issue, $branch);
        $branch = str_replace('%(story_name)', $story_name, $branch);

        // Cleanup branch name from leading undescores.
        $branch = trim($branch, '_');

        // Create branch using standard git commands.
        if (!$dry_run) {
            try {
                $git_process = new Process('git checkout ' . $origin_branch);
                $git_process = new Process('git checkout -b feature/' . $branch);
                $git_process->mustRun();
                $output->writeln("<info>Branch: \"{$branch}\" created</info>");

                // Auto track of branch.
                $git_process = new Process('git push --set-upstream origin feature/' . $branch);
                $git_process->mustRun();
                $output->writeln("<info>Branch: \"{$branch}\" tracked</info>");
            } catch (ProcessFailedException $e) {
                return $output->writeln("<comment>Error: " . $git_process->getErrorOutput() . "</comment>");
            }
        } else {
            $output->writeln('I will execute: <info>git checkout ' . $origin_branch . '</info>');
            $output->writeln('I will execute: <info>git checkout -b feature/' . $branch . '</info>');
            $output->writeln('I will execute: <info>git push --set-upstream origin feature/' . $branch . '</info>');
        }
    }
}

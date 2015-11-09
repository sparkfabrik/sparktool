<?php

/*
 * This file is part of the Spark tool package.
 *
 * (c) Marcello Gorla <marcello.gorla@sparkfabrik.com>
 * (c) Alessio Piazza <alessio.piazza@sparkfabrik.com>
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
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class RedmineGitCommitCommand extends RedmineCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('redmine:git:commit')
            ->setDescription(<<<EOF
Generate git commit message starting from issue subject
EOF
            );
        $this->addArgument(
            'issue',
            InputArgument::REQUIRED,
            'Issue id'
        );
        $this->addOption(
            'execute',
            'e',
            InputOption::VALUE_NONE,
            'Print the Commit message and execute the commit too.'
        );
        $this->addOption(
            'no-verify',
            false,
            InputOption::VALUE_NONE,
            'Escapes the CS check.'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $base_msg = $this->getService()->getConfig()['commit_pattern'];
        $client = $this->getService()->getClient();
        $issue = $input->getArgument('issue');
        $execute = $input->getOption('execute');
        $skipcs = $input->getOption('no-verify');
        $res = $client->api('issue')->show($issue);

        // Handle errors.
        if (isset($res['errors'])) {
            $errors = implode("\n", $res['errors']);
            throw new \Exception($errors);
        }
        if ($res === 1) {
            return $output->writeln('<info>No issues found.</info>');
        }

        // Get a clean story name.
        $story = $this->getStoryCode($res['issue']);
        $story_name = $this->getCleanStoryName($res['issue']['subject'], $story);
        $story_name = preg_replace('/[a-z0-9 ]{0,}[- ]{0,}/', '', $story_name, 1);
        $story_name = str_replace('"', '\'', $story_name);

        // Output to the user the current status of informations.
        $output->writeln('<info>Issue Number</info>: ' . $issue);
        $output->writeln('<info>Jira Story Code</info>: ' . $story);
        $output->writeln('<info>Story Name</info>: ' . $story_name);

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('<question>Are these informations correct? [y/N]</question> ', false);
        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('');
            $output->writeln('<comment>Go check the issue with:</comment> spark git:redmine:show '. $issue .' -o');
            $output->writeln('');
            return;
        }

        // Replace patterns from branch structure.
        $base_msg = str_replace('%(story)', $story, $base_msg);
        $base_msg = str_replace('%(issue_id)', (string) $issue, $base_msg);
        $base_msg = str_replace('%(story_name)', $story_name, $base_msg);

        // Cleanup branch name from leading undescores.
        $base_msg = trim($base_msg, '_');

        // Ask for the user commit message.
        $output->writeln('');
        $output->writeln('<info>Complete the commit message.</info>');
        $question = new Question($base_msg . ' ');
        $msg = $helper->ask($input, $output, $question);
        $commit_msg = $base_msg . ' ' . $msg;

        // Outputs the complete commit message.
        $output->writeln('<info>Here it is.</info>');
        $output->writeln($commit_msg);

        // Returns the commit message.
        if ($execute) {
            $command = 'git commit -m "' . $commit_msg . '"';
            if ($skipcs) {
                $command .= ' --no-verify';
            }
            $output->writeln('');
            $output->writeln('<info>' . $command . '</info>');
            $question = new ConfirmationQuestion('<question>Should I execute? [y/N]</question> ', false);
            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('');
                return $output->writeln("<info>Not committed, maybe next time.</info>");
            } else {
                $output->writeln('');
                $output->writeln('Committing...');
                try {
                    // Auto track of branch.
                    $git_process = new Process($command);
                    $git_process->mustRun();
                    return $output->writeln('<info>Committed!</info>');
                } catch (ProcessFailedException $e) {
                    return $output->writeln("<comment>Error: " . $git_process->getErrorOutput() . "</comment>");
                }

            }
        }
    }
}

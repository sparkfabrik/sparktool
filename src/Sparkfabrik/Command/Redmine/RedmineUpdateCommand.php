<?php

/*
 * This file is part of the Spark tool package.
 *
 * (c) Paolo Pustorino <paolo.pustorino@sparkfabrik.com>
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
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class RedmineUpdateCommand extends RedmineCommand
{
    /**
     * {@inheritdoc}
     * Note that not all issue attributes are exposed to the command.
     * This subset seems sensible for the scope of this tool, yet more
     * attributes can be added as needed in next iterations.
     *
     * @todo: add file attachment feature
     * @todo: add missing attributes, such as due-date, done-ratio,
     * parent-issue-id, etc.
     */
    protected function configure()
    {
        $this
            ->setName('redmine:update')
            ->setDescription('Update an issue.');
        $this->addArgument(
            'issue',
            InputArgument::REQUIRED,
            'Issue id'
        );
        $this->addOption(
            'project',
            'p',
            InputOption::VALUE_OPTIONAL,
            'The project the issue must belong to.'
        );
        $this->addOption(
            'tracker',
            null,
            InputOption::VALUE_OPTIONAL,
            'The new tracker to set for the issue.'
        );
        $this->addOption(
            'status',
            null,
            InputOption::VALUE_OPTIONAL,
            'The new status for the issue.'
        );
        $this->addOption(
            'priority',
            null,
            InputOption::VALUE_OPTIONAL,
            'The new priority for the issue.'
        );
        $this->addOption(
            'category',
            null,
            InputOption::VALUE_OPTIONAL,
            'The new category for the issue.'
        );
        $this->addOption(
            'subject',
            null,
            InputOption::VALUE_OPTIONAL,
            'The new subject to set for the issue.'
        );
        $this->addOption(
            'description',
            null,
            InputOption::VALUE_OPTIONAL,
            'The new description to set for the issue. *Use with care, odds are good what you really want is to add a comment!*'
        );
        $this->addOption(
            'target-version',
            null,
            InputOption::VALUE_OPTIONAL,
            'The new target version for the issue (id).'
        );
        $this->addOption(
            'assignee',
            null,
            InputOption::VALUE_OPTIONAL,
            'The new person in charge for the issue (id).'
        );
        $this->addOption(
            'notes',
            null,
            InputOption::VALUE_OPTIONAL,
            'A note to add to the issue (comments or description for the update).'
        );
        $this->addOption(
            'custom-fields',
            null,
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'A custom field in the "name:value" or "id:value" format.'
        );
        $this->addOption(
            'dump-issue',
            null,
            InputOption::VALUE_NONE,
            'Dump the issue information on successful update as for `redmine:show` command with `--complete` option.'
        );
    }
    
    protected function parseCustomFields($customFieldPairs) {
        foreach ($customFieldPairs as $customField) {
            $pair = explode(':',$customField,2);
            // 
        }
    }
    
    protected function showIssue($issue_id, $showOutput) {
        $command = $this->getApplication()->find('redmine:show');

        $arguments = array(
            'command' => 'redmine:show',
            'issue'    => $issue_id,
            '--complete'  => true,
        );
        
        $showInput = new ArrayInput($arguments);
        return $command->run($showInput, $showOutput);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Get Redmine client
        $client = $this->getService()->getClient();

        // Read settings, argument and options
        $redmine_url = $this->service->getConfig()['redmine_url'];
        $issue_id = $input->getArgument('issue');
        $params = array(
            'id' => $issue_id,
            'subject' => $input->getOption('subject'),
            'notes' => $input->getOption('notes'),
            'project' =>  $input->getOption('project'),
            'category_id' =>  $input->getOption('category'),
            'priority_id' =>  $input->getOption('priority'),
            'status_id' =>  $input->getOption('status'),
            'tracker_id' => $input->getOption('tracker'),
            'assigned_to_id' =>  $input->getOption('assignee'),
            'description' => $input->getOption('description'),
        );

        $res = $client->api('issue')->update($issue_id, $params);

        // Handle errors.
        if (isset($res['errors'])) {
            $errors = implode("\n", $res['errors']);
            throw new \Exception($errors);
        }
            
        $returnCode = $output->writeln('<info>Issue ' . $issue_id . ' updated.</info>');
        if ($input->getOption('dump-issue')) {
            $returnCode = $this->showIssue($issue_id,$output);    
        }
        
        return $returnCode;
    
    }
}

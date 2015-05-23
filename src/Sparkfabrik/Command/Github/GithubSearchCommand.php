<?php

/*
 * This file is part of the Spark tool package.
 *
 * (c) Paolo Mainardi <paolo.mainardi@sparkfabrik.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sparkfabrik\Tools\Spark\Command\Github;

use Sparkfabrik\Tools\Spark\Command\Github;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Github\Client;

class GithubSearchCommand extends GithubCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('github:search')
            ->setDescription('Search github issues')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command displays help for a given command:

  <info>php %command.full_name% list</info>

You can also output the help in other formats by using the <comment>--format</comment> option:

  <info>php %command.full_name% --format=xml list</info>

To display the list of available commands, please use the <info>list</info> command.
EOF
            );
    }
    
     /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $client = $this->getService()->getClient();
            $res = $client
                ->api('issue')
                ->all(
                    $this->getService()->getConfig()['github_user'],
                    $this->getService()->getConfig()['github_repo'],
                    array('state' => 'open')
                );

            // Github API returns empty array if it finds no issues.
            if (!count($res) || (empty($res))) {
                return $output->writeln('<info>No issues found.</info>');
            }

            // Fields to print.
            $fields = array(
            'number' => 'ID',
            'title' => 'Title',
            'url' => 'URL',
            'state' => 'State'
            );
            
            // Render issue table.
            $this->tableGithubOutput($output, $fields, $res, 'issues');
            
        } catch (Exception $e) {
            return $output->writeln('<error>'. $e->getMessage() . '</error>');
        }
    }
}

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
            ->setHelp('The <info>%command.name%</info> command searches issues on GitHub.');
        $this->addOption(
            'status',
            false,
            InputOption::VALUE_OPTIONAL,
            'Filter by project status name. Possible values: open, closed',
            'open'
        );
    }

     /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $client = $this->getService()->getClient();
            $api_options = array();
            $api_options['status'] = $input->getOption('status');
            try {
                $this->handleArguments($input, $output, $api_options);
            } catch (\Exception $e) {
                return $output->writeln('<error>' . $e->getMessage() . '</error>');
            }

            // If the user provided a token, authenticate him.
            $github_token = $this->getService()->getConfig()['github_token'];
            if (!empty($github_token)) {
                $client->authenticate(
                    $github_token,
                    null,
                    \Github\Client::AUTH_URL_TOKEN
                );
            }
            $res = $client
                ->api('issue')
                ->all(
                    $this->getService()->getConfig()['github_user'],
                    $this->getService()->getConfig()['github_repo'],
                    array('state' => $api_options['status'])
                );

            // Github API returns empty array if it finds no issues.
            if (!count($res) || (empty($res))) {
                return $output->writeln('<info>No issues found.</info>');
            }

            // Fields to print.
            $fields = array(
                'number' => 'ID',
                'title' => 'Title',
                'html_url' => 'URL',
                'state' => 'State',
                'user/login' => 'Author',
                'assignee/login' => 'Assigned to',
            );

            // Render issue table.
            $this->tableGithubOutput($output, $fields, $res, 'issues');

        } catch (Exception $e) {
            return $output->writeln('<error>'. $e->getMessage() . '</error>');
        }
    }

    /**
     * Read status argument.
     *
     * @param string $status
     *
     * @return string|boolean
     */
    private function handleArgumentStatus($status)
    {
        if (is_numeric($status) || ($status !== 'open' && $status !== 'closed')) {
            throw new \Exception('Invalid status "' . $status . '"');
        }
        return $status;
    }

    /**
     * Handle standard arguments.
     *
     * @return integer|boolean
     */
    private function handleArguments(InputInterface $input, OutputInterface $output, &$api_options)
    {
        $status = $input->getOption('status');
        if ($status) {
            $api_options['status'] = $this->handleArgumentStatus($status);
        }
    }
}

<?php

/*
 * This file is part of the Spark tool package.
 *
 * (c) Vincenzo Di Biaggio <vincenzo.dibiaggio@sparkfabrik.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Sparkfabrik\Tools\Spark\Command\Gitlab;

use Sparkfabrik\Tools\Spark\Command\Gitlab\GitlabCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Gitlab\Api\MergeRequests;

class GitlabSearchMergeRequestCommand extends GitlabCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('gitlab:mr:search')
            ->setDescription('Merge request search by parameters')
            ->setHelp('The <info>%command.name%</info> command searches Merge Requests on Gitlab.
                If you provide more than one parameter, all of them will be used with AND operator with the order:
                ISSUE - STORY');
        // Add options.
        $this->addOption(
            'issue',
            null,
            InputOption::VALUE_OPTIONAL,
            'Issue id',
            null
        );
        $this->addOption(
            'story',
            null,
            InputOption::VALUE_OPTIONAL,
            'Story id',
            null
        );
        $this->addOption(
            'limit',
            null,
            InputOption::VALUE_OPTIONAL,
            'MRs number limit to search',
            10
        );
        $this->addOption(
            'position',
            null,
            InputOption::VALUE_OPTIONAL,
            'Content where to search. Can be: "branch" or "title"',
            'branch'
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
            $res = array();
            $page = 1;
            while (count($res) < $api_options['limit']) {
                $r = $client->api('mr')->getList(
                    $api_options['project_id'],
                    MergeRequests::STATE_ALL,
                    $page,
                    // Use limit option to manage result per page to get the minimum results possible.
                    $api_options['limit'],
                    MergeRequests::ORDER_BY,
                    'desc'
                );
                $res = array_merge($res, $r);
                $page++;
            }

            if (!count($res)) {
                return $output->writeln('<info>No Merge Requests found.</info>');
            }

            // Make a plain array.
            $this->makePlainArray($res);

            // Reduce results, filter by content.
            foreach ($res as $key => $mr) {
                // Issue.
                if (!is_null($api_options['issue'])) {
                    if (stripos($mr[$api_options['position']], $api_options['issue']) === false) {
                        unset($res[$key]);
                    }
                }

                // Story.
                if (!is_null($api_options['story'])) {
                    if (stripos($mr[$api_options['position']], $api_options['story']) === false) {
                        unset($res[$key]);
                    }
                }
            }

            // Re-check how many results we have now.
            if (!count($res)) {
                return $output->writeln('<info>No Merge Requests found.</info>');
            }

            // Fields to print.
            $fields = array(
                'id'            => 'ID',
                'title'         => 'Title',
                // 'description'   => 'Description',
                'state'         => 'Status',
                'created_at'    => 'Created',
                'updated_at'    => 'Updated',
                'source_branch' => 'From Branch',
                'target_branch' => 'To Branch',
                'upvotes'       => 'Upvotes',
                'downvotes'     => 'Downvotes',
                'author_name'   => 'Author',
                'assignee_name' => 'Assignee',
            );

            // Render table.
            $this->tableGitlabOutput($output, $fields, $res);

        } catch (Exception $e) {
            return $output->writeln('<error>'. $e->getMessage() . '</error>');
        }
    }

    /**
     * Handle standard arguments.
     *
     * @link https://github.com/gitlabhq/gitlabhq/tree/master/doc/api:
     *
     * @return integer|boolean
     *
     * @throws Exception
     */
    private function handleArguments(InputInterface $input, OutputInterface $output, &$api_options)
    {
        $api_options['project_id'] = $this->handleAgumentProjectId();
        $api_options['issue'] = $this->handleAgumentIssue($input->getOption('issue'));
        $api_options['story'] = $this->handleAgumentStory($input->getOption('story'));
        $api_options['limit'] = $this->handleAgumentLimit($input->getOption('limit'));
        $api_options['position'] = $this->handleAgumentPosition($input->getOption('position'));

        if (is_null($api_options['issue']) && is_null($api_options['story'])) {
            throw new \Exception('You need to specify a story id or a issue id.');
        }
    }

    /**
     * Redmine issue number.
     *
     * @param string|integer $issue
     *
     * @return integer|boolean
     */
    private function handleAgumentIssue($issue = null)
    {
        return $issue;
    }

    /**
     * Story id.
     *
     * @param string|integer $story
     *
     * @return integer|boolean
     */
    private function handleAgumentStory($story = null)
    {
        return $story;
    }

    /**
     * Gitlab project id.
     *
     * @param string|integer $project_id
     *
     * @return integer| ConsoleOutput output.
     */
    private function handleAgumentProjectId()
    {
        $conf_project_id = $this->getService()->getConfig()['project_id'];
        if (isset($project_id) && !is_integer($project_id)) {
            $project_id = $this->findProjectId($project_id);
        } else if (isset($conf_project_id) &&
        !is_integer($conf_project_id)) {
            $project_id = $this->findProjectId($conf_project_id);
        } else {
            return ($project_id ? $project_id : $conf_project_id);
        }
    }

    /**
     * Gitlab project id.
     *
     * @param string|integer $project_id
     *
     * @return integer|boolean
     */
    private function handleAgumentLimit($limit)
    {
        return $limit;
    }

    /**
     * Position where perform the search.
     *
     * @param string $position
     *
     * @return sring
     *
     * @throws Exception
     */
    private function handleAgumentPosition($position)
    {
        // Allowed values.
        $positions = array(
            'branch',
            'title',
        );

        // Map options with result indexes.
        $map = array(
            'branch' => 'source_branch',
            'title' => 'title',
        );

        if (in_array($position, $positions)) {
            return $map[$position];
        } else {
            throw new \Exception('Option "Position" not valid');
        }
    }
}

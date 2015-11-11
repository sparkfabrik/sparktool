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

class GitlabMergeRequestCommand extends GitlabCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('gitlab:mr')
            ->setDescription('Merge request search')
            ->setHelp('The <info>%command.name%</info> command searches Merge Requests on Gitlab.');
        // Add options.
        $this->addOption(
            'project_id',
            null,
            InputOption::VALUE_OPTIONAL,
            'The project Id. Default value from the conf file.',
            null
        );
        $this->addOption(
            'state',
            null,
            InputOption::VALUE_OPTIONAL,
            'Merge Request status. Allowed values: all, opened, merged, closed',
            'all'
        );
        $this->addOption(
            'page',
            null,
            InputOption::VALUE_OPTIONAL,
            'Start page (like an offset)',
            1
        );
        $this->addOption(
            'results-per-page',
            null,
            InputOption::VALUE_OPTIONAL,
            'Number of results per page',
            20
        );
        $this->addOption(
            'order-by',
            null,
            InputOption::VALUE_OPTIONAL,
            'Order by',
            'updated_at'
        );
        $this->addOption(
            'sort',
            null,
            InputOption::VALUE_OPTIONAL,
            'Sort',
            'desc'
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

            // Manage output before run the query if it's necessary (contextual automatic searches for example).
            if ($this->manageServiceOutput($api_options, $output)) {
                // Run query.
                $res = $client->api('mr')->getList(
                    $api_options['project_id'],
                    $api_options['state'],
                    $api_options['page'],
                    $api_options['results_per_page'],
                    $api_options['order_by'],
                    $api_options['sort']
                );

                if (!count($res)) {
                    return $output->writeln('<info>No Merge Requests found.</info>');
                }

                // Make a plain array.
                $this->makePlainArray($res);


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
            }

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
     */
    private function handleArguments(InputInterface $input, OutputInterface $output, &$api_options)
    {
        $api_options['project_id'] = $this->handleAgumentProjectId($input->getOption('project_id'));
        $api_options['state'] = $this->handleAgumentState($input->getOption('state'));
        $api_options['page'] = $this->handleAgumentPage($input->getOption('page'));
        $api_options['results_per_page'] = $this->handleAgumentResultsPerPage($input->getOption('results-per-page'));
        $api_options['order_by'] = $this->handleAgumentOrderBy($input->getOption('order-by'));
        $api_options['sort'] = $this->handleAgumentSort($input->getOption('sort'));
    }

    /**
     * Gitlab Merge Request status.
     *
     * @param string $state
     *
     * @return string
     *
     * @throws Exception
     */
    private function handleAgumentState($state = null)
    {
        $states = array(
            MergeRequests::STATE_ALL,
            MergeRequests::STATE_MERGED,
            MergeRequests::STATE_OPENED,
            MergeRequests::STATE_CLOSED,
        );

        if (in_array($state, $states)) {
            return $state;
        } else {
            throw new \Exception('Option "State" not valid');
        }
    }

    /**
     * Listing start page.
     *
     * @param string|integer $page
     *
     * @return integer
     */
    private function handleAgumentPage($page = null)
    {
        return ($page ? $page : 1);
    }

    /**
     * Results per page.
     *
     * @param string|integer $page
     *
     * @return integer
     */
    private function handleAgumentResultsPerPage($rpp = null)
    {
        return ($rpp ? $rpp : 20);
    }

    /**
     * Order By.
     *
     * @param string $order_by
     *
     * @return string
     */
    private function handleAgumentOrderBy($order_by = null)
    {
        return ($order_by ? $order_by : 'updated_at');
    }

    /**
     * Sort.
     *
     * @param string $sort
     *
     * @return string
     *
     * @throws Exception
     */
    private function handleAgumentSort($sort = null)
    {
        $sorts = array('asc', 'desc');
        if (in_array($sort, $sorts)) {
            return $sort;
        } else {
            throw new \Exception('Option "Sort" not valid');
        }
    }
}

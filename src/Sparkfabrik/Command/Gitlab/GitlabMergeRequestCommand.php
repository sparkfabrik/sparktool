<?php

/*
 * This file is part of the Spark tool package.
 *
 * (c) Paolo Mainardi <paolo.mainardi@sparkfabrik.com>
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

class GitlabMergeRequestCommand extends GitlabCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('gitlab:mr')
            ->setDescription('WIP: Merge request search');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $client = $this->getService()->getClient();

            // Print debug informations if required.
            if ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
                $output->writeln('<info>' . var_export($api_options, true) . '</info>');
            }

            // Run query.
            $res = $client->api('mr')->getList(6, 'all', 1, 20, 'updated_at', 'desc');

            if (!count($res)) {
                return $output->writeln('<info>No Merge Requests found.</info>');
            }

            // Make a plain array.
            foreach ($res as $key => $value) {
                foreach ($value['author'] as $a_key => $a_value) {
                    $res[$key]['author_' . $a_key] = $a_value;
                }

                $res[$key]['assignee_name'] = '';
                if ($value['assignee'] != null) {
                    foreach ($value['assignee'] as $as_key => $as_value) {
                        $res[$key]['assignee_' . $as_key] = $as_value;
                    }
                }
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
}

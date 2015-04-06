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
    protected function configure() {
      $this->initConfig();
      $this
        ->setName('gitlab:mr:search')
        ->setDescription('WIP: Merge request search')
      ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
      $client = $this->getGitlabClient();
      $config = $this->getGitlabConfig();
      $projects = $client->api('projects')->search('spark-tool');
      dump($projects); die;
      //$project = new \Gitlab\Model\Project($config['project_name'], $client);
      $merge_requests = $client->api('mr')->all($config['project_id']);
      dump($merge_requests);
      die;
    }
}

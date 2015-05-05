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

class RedmineShowCommand extends RedmineCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure() {
      $this->initConfig();
      $this
        ->setName('redmine:show')
        ->setDescription('Show issue.')
      ;
      $this->addArgument(
        'issue',
        InputArgument::REQUIRED,
        'Issue id'
      );
      $this->addOption(
        'mr',
        false,
        InputOption::VALUE_NONE,
        'Dump the merge requests.'
      );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
      $client = $this->getRedmineClient();
      $issue = $input->getArgument('issue');
      $show_mr = $input->getOption('mr');
      $params = array('include' => 'changesets, journals, attachments');
      $res = $client->api('issue')->show($issue, $params);
      $extra_output = array();

      // Handle errors.
      if (isset($res['errors'])) {
        $errors = implode("\n", $res['errors']);
        throw new \Exception($errors);
      }
      if ($res === 1) {
        return $output->writeln('<info>No issues found.</info>');
      }
      if ($show_mr && count($res['issue']['journals'])) {
        $regex = '#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#';
        $comments = $res['issue']['journals'];
        foreach ($comments as $comment) {
          if (isset($comment['notes']) && (!empty($comment['notes']))) {
            if (preg_match($regex, $comment['notes'], $match)) {
              if (stripos($match[0], 'merge_request')) {
                $extra_output['mr'][] = $match[0];
              }
            }
          }
        }
      }
      dump($extra_output);
    }
}

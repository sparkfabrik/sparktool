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
      $this->addOption(
        'description',
        false,
        InputOption::VALUE_NONE,
        'Show the description.'
      );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
      $client = $this->getRedmineClient();
      $issue_id = $input->getArgument('issue');
      $redmine_url = $this->getRedmineConfig()['redmine_url'];
      $show_mr = $input->getOption('mr');
      $description = $input->getOption('description');
      $params = array('include' => 'journals');
      $res = $client->api('issue')->show($issue_id, $params);
      $extra_output = array();

      // Handle errors.
      if (isset($res['errors'])) {
        $errors = implode("\n", $res['errors']);
        throw new \Exception($errors);
      }
      if ($res === 1) {
        return $output->writeln('<info>No issues found.</info>');
      }

      $issue = $res['issue'];
      if ($show_mr && count($issue['journals'])) {
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
      $output->writeln('<info>Subject: </info>'. $issue['subject']);
      $output->writeln('<info>URL: </info>'. $redmine_url . '/issues/' . $issue_id);
      if ($description) {
        $output->writeln('<info>Description: </info>');
        $output->writeln(trim($issue['description']));
      }
      foreach ($extra_output as $name => $elements) {
        $output->writeln('<info>' . strtoupper($name) . ':</info>');
        foreach ($elements as $element) {
          $output->writeln($element);
        }
      }
      $output->writeln("");
    }
}

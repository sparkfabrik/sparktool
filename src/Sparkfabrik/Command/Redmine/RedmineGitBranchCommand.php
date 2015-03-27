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

class RedmineGitBranchCommand extends RedmineCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure() {
      $this->initConfig();
      $this
        ->setName('redmine:git:branch')
        ->setDescription('WIP: Generate git branch name using issue subject')
      ;
      $this->addArgument(
        'issue',
        InputArgument::REQUIRED,
        'Issue id'
      );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
      $client = $this->getRedmineClient();
      $issue = $input->getArgument('issue');
      $res = $client->api('issue')->show($issue);

      // Handle errors.
      if (isset($res['errors'])) {
        $errors = implode("\n", $res['errors']);
        throw new \Exception($errors);
      }
      if ($res === 1) {
        return $output->writeln('<info>No issues found.</info>');
      }

      // Login taken from: https://github.com/mavimo/git-redmine-utilities/blob/develop/git-redmine
      // Extract items from issue info.
      $subject = $res['issue']['subject'];
      $subject_items = explode('-', $subject, 3);
      // Punish not well named issues.
      if (count($subject_items) != 3) {
        return $output->writeln(PHP_EOL . '<error>Rename your issue please.</error>' .
          PHP_EOL . '<info>Well named issues are: STORY_PREFIX-STORY_CODE_ISSUE-ID_STORY_NAME</info>' . PHP_EOL .
          'Your issue instead is: "' . $subject . '"' . PHP_EOL);
      }
      $story_prefix = trim($subject_items[0]);
      $story_code = trim($subject_items[1]);
      $story_name = trim($subject_items[2]);

      // Clean story name.
      $story_name = str_replace(array('[', ']'), '', $story_name);
      $story_name = strtolower(preg_replace("/\W/", '_', $story_name));
      $story_name = str_replace('__', '_', $story_name);

      dump($story_prefix);
      dump($story_code);
      dump($story_name);
    }
}

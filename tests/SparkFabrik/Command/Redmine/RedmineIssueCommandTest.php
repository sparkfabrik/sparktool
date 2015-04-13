<?php

/*
 * This file is part of the Spark tool package.
 *
 * (c) Paolo Mainardi <paolo.mainardi@sparkfabrik.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sparkfabrik\Tools\Spark\Tests\Command\Redmine;

use Symfony\Component\Console\Application;
use Sparkfabrik\Tools\Spark\Command\SparkCommand;
use Sparkfabrik\Tools\Spark\Command\Redmine\RedmineCommand;
use Sparkfabrik\Tools\Spark\Command\Redmine\RedmineIssueCommand;
use Symfony\Component\Console\Tester\CommandTester;

class RedmineIssueCommandTest extends \PHPUnit_Framework_TestCase
{
  private $application;
  private $tester;

  protected function setUp()
  {
    $this->application = new Application();
    $this->application->add(new RedmineIssueCommand());
    $command = $this->application->find('redmine:search');
    $this->tester = new CommandTester($command);

  }

  public function testCommand() {
    $command = $this->application->find('redmine:search');
    //$this->tester->execute(array('command' => $command->getName()));
    //dump($this->tester->getDisplay());
  }
}

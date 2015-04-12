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
  public function testCommand()
  {
    // $application = new Application();
    // $command = new RedmineIssueCommand();
    // $commandTester = new CommandTester($command);
    // $application->add($command);
    // $res = $commandTester->execute(array('command' => $command->getName()));
  }
}

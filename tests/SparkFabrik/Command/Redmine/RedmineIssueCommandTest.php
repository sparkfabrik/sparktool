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

  private function getMockedRedmineClient() {
    $redmineClient = $this->getMockBuilder('\Redmine\Client')
      ->setConstructorArgs(array('mock_url', 'mock_key'))
      ->getMock();
    return $redmineClient;
  }

  private function getMockedRedmineApiIssue() {
    $redmineApiIssue = $this->getMockBuilder('\Redmine\Api\Issue')
      ->disableOriginalConstructor()
      ->getMock();
    return $redmineApiIssue;
  }

  public function testNoIssuesFound() {
    $command = $this->application->find('redmine:search');
    $redmineClient = $this->getMockedRedmineClient();
    $redmineApiIssue = $this->getMockedRedmineApiIssue();

    // Mock method all of redmine api.
    $redmineApiIssue->expects($this->once())
      ->method('all')
      ->will($this->returnValue(array()));

    // Mock method api of redmine client.
    $redmineClient->expects($this->once())
        ->method('api')
        ->will($this->returnValue($redmineApiIssue));

    // Set the mocked client.
    $command->setRedmineClient($redmineClient);

    // Execute.
    $this->tester->execute(
      array(
        'command' => $command->getName(),
        '--project_id' => 'test_project_id',
      )
    );
    $res = trim($this->tester->getDisplay());
    $this->assertEquals($res, 'No issues found.');
  }

  /**
   * @expectedException  Exception
   * @expectedExceptionMessage errors
   */
  public function testIssueErrorResponse() {
    $command = $this->application->find('redmine:search');
    $redmineClient = $this->getMockedRedmineClient();
    $redmineApiIssue = $this->getMockedRedmineApiIssue();

    // Mock method all of redmine api.
    $redmineApiIssue->expects($this->once())
      ->method('all')
      ->will($this->returnValue(array('errors' => array('errors'))));

    // Mock method api of redmine client.
    $redmineClient->expects($this->once())
        ->method('api')
        ->will($this->returnValue($redmineApiIssue));

    // Set the mocked client.
    $command->setRedmineClient($redmineClient);

    // Execute.
    $this->tester->execute(
      array(
        'command' => $command->getName(),
        '--project_id' => 'test_project_id',
      )
    );
  }
}

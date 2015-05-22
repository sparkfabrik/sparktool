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
use Sparkfabrik\Tools\Spark\Service\RedmineService;
use Sparkfabrik\Tools\Spark\Command\SparkCommand;
use Sparkfabrik\Tools\Spark\Command\Redmine\RedmineCommand;
use Sparkfabrik\Tools\Spark\Command\Redmine\RedmineGitBranchCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Output\OutputInterface;

class RedmineGitBranchCommandTest extends \PHPUnit_Framework_TestCase
{
    private $application;
    private $tester;
    private $command;

  // Mocks
    private $service;
    private $redmineClient;
    private $redmineApiIssue;

    private $issue_subject = 'SP-000 - Testing branch name with “quoted”-utf8 and àccènted wörds';
    private $issue_subject_wrong = 'BUG: testing_branch_name';

    protected function setUp()
    {
        $this->application = new Application();
        $this->application->add(new RedmineGitBranchCommand());
        $command = $this->application->find('redmine:git:branch');
        $this->tester = new CommandTester($command);

    }

    private function getMockedService()
    {
        $service = $this->getMockBuilder('\Sparkfabrik\Tools\Spark\Services\RedmineService')
        ->getMock();
        $service
        ->expects($this->once())
        ->method('getConfig')
        ->will($this->returnValue(array('git_pattern' => '%(story_prefix)-%(story_code)_%(issue_id)_%(story_name)')));
        return $service;
    }

    private function getMockedRedmineClient()
    {
        $redmineClient = $this->getMockBuilder('\Redmine\Client')
        ->setConstructorArgs(array('mock_url', 'mock_key'))
        ->getMock();
        return $redmineClient;
    }

    private function getMockedRedmineApiIssue()
    {
        $redmineApiIssue = $this->getMockBuilder('\Redmine\Api\Issue')
        ->disableOriginalConstructor()
        ->getMock();
        return $redmineApiIssue;
    }

    private function createCommand($name)
    {
        $this->command = $this->application->find($name);
    }

    private function createMocks($options = array())
    {
        $this->service = $this->getMockedService();
        $this->redmineClient = $this->getMockedRedmineClient();
        $this->redmineApiIssue = $this->getMockedRedmineApiIssue();

      // Default returns for mock objects.
        $default_options = array_replace(
            array('redmineApiIssueShow' => array('issue' => array('subject' => $this->issue_subject))),
            $options
        );

      // Mock methods.
        $this->redmineApiIssue->expects($this->any())
        ->method('show')
        ->will($this->returnValue($default_options['redmineApiIssueShow']));

      // Mock method api of redmine client.
        $this->redmineClient->expects($this->any())
        ->method('api')
        ->will($this->returnValue($this->redmineApiIssue));

      // Mock getClient on service object, just return mock redmine.
        $this->service->expects($this->any())
        ->method('getClient')
        ->will($this->returnValue($this->redmineClient));

      // Set the mocked client.
        $this->command->setService($this->service);
    }

    public function testCreateGitBranch()
    {
        $command = $this->createCommand('redmine:git:branch');
        $this->createMocks();

        // Execute with project_id
        $input = array(
        'command' => $this->command->getName(),
        'issue' => '1234',
        '--dry-run' => true,
        );
        $this->tester->execute($input);
        $res = trim($this->tester->getDisplay());
        $this->assertContains('SP-000_1234_testing_branch_name', $res);
        // Elimination of utf-8 punctuation.
        $this->assertContains('quoted_utf8', $res);
        // Transliteration of utf-8 accented characters.
        $this->assertContains('accented_words', $res);
    }

    public function testCreateGitBranchWithAwrongIssueFormat()
    {
        $command = $this->createCommand('redmine:git:branch');
        $options = array('redmineApiIssueShow' => array('issue' => array('subject' => $this->issue_subject_wrong)));
        $this->createMocks($options);

        // Execute with project_id
        $input = array(
        'command' => $this->command->getName(),
        'issue' => '1234',
        '--dry-run' => true,
        );
        $this->tester->execute($input);
        $res = trim($this->tester->getDisplay());
        $this->assertContains('Rename your issue please.', $res);
    }
}

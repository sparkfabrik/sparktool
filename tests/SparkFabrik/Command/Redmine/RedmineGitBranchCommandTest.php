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

    private $issue_subject_with_story_name = 'SP-000 - Testing branch name with “quoted”-utf8 and àccènted wörds';
    private $issue_subject_without_story_name = 'BUG: Test branch name';

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
            ->will($this->returnValue(array('git_pattern' => '%(story)_%(issue_id)_%(story_name)')));
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
            array('redmineApiIssueShow' => array('issue' => array('subject' => $this->issue_subject_with_story_name))),
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

    /**
     * Test git branch creation  --dry-run with custom field "Jira story name".
     */
    public function testCreateGitBranchDryRunWithStoryName()
    {
        $command = $this->createCommand('redmine:git:branch');
        $options = array(
            'redmineApiIssueShow' => array(
                  'issue' => array(
                      'subject' => $this->issue_subject_with_story_name,
                      'custom_fields' => array(
                          array(
                              'id' => 19,
                              'name' => 'Jira Story Code',
                              'value' => 'SP-000',
                          )
                      ),
                  ),
            )
        );
        $this->createMocks($options);

        $input = array(
            'command' => $this->command->getName(),
            'issue' => '1234',
            'origin-branch' => 'develop',
            '--dry-run' => true,
        );
        $this->tester->execute($input);
        $res = trim($this->tester->getDisplay());
        $res = explode(PHP_EOL, $res);
        $this->assertEquals('I will execute: git checkout develop', $res[0]);
        $this->assertContains('I will execute: git checkout -b feature/SP-000_1234_testing_branch_name', $res[1]);
        $this->assertContains('I will execute: git push --set-upstream origin feature/SP-000_1234_testing_branch_name', $res[2]);
        // Elimination of utf-8 punctuation.
        $this->assertContains('quoted_utf8', $res[1]);
        // Transliteration of utf-8 accented characters.
        $this->assertContains('accented_words', $res[1]);
    }

    /**
     * Test git branch creation  --dry-run without custom field "Jira story name".
     */
    public function testCreateGitBranchDryRunWithoutStoryName()
    {
        $command = $this->createCommand('redmine:git:branch');
        $options = array(
            'redmineApiIssueShow' => array(
                  'issue' => array(
                      'subject' => $this->issue_subject_without_story_name,
                      'custom_fields' => array(
                          array(
                              'id' => 19,
                              'name' => 'Another field',
                              'value' => 'test',
                          )
                      ),
                  ),
            )
        );
        $this->createMocks($options);

        $input = array(
            'command' => $this->command->getName(),
            'issue' => '1234',
            'origin-branch' => 'develop',
            '--dry-run' => true,
        );
        $this->tester->execute($input);
        $res = trim($this->tester->getDisplay());
        $res = explode(PHP_EOL, $res);
        $this->assertEquals('I will execute: git checkout develop', $res[0]);
        $this->assertContains('I will execute: git checkout -b feature/1234_bug_test_branch_name', $res[1]);
        $this->assertContains('I will execute: git push --set-upstream origin feature/1234_bug_test_branch_name', $res[2]);
    }

  /**
   * Test error response.
   *
   * @expectedException  Exception
   * @expectedExceptionMessage API show error
   *
   */
    public function testCreateGitBranchShowError()
    {
        $command = $this->createCommand('redmine:git:branch');
        $options = array(
            'redmineApiIssueShow' => array(
                  'issue' => array(
                      'subject' => $this->issue_subject_with_story_name
                  ),
                  'errors' => array('API show error'),
            )
        );
        $this->createMocks($options);

        $input = array(
            'command' => $this->command->getName(),
            'issue' => '1234',
            '--dry-run' => true,
        );
        $this->tester->execute($input);
        $res = trim($this->tester->getDisplay());
    }

    /**
     * Test missing issue.
     */
    public function testCreateGitBranchMissingIssue()
    {
        $command = $this->createCommand('redmine:git:branch');
        $options = array('redmineApiIssueShow' => 1);
        $this->createMocks($options);
        $input = array(
            'command' => $this->command->getName(),
            'issue' => '1234',
            '--dry-run' => true,
        );
        $this->tester->execute($input);
        $res = trim($this->tester->getDisplay());
        $this->assertEquals('No issues found.', $res);
    }
}

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
use Sparkfabrik\Tools\Spark\Command\Redmine\RedmineSearchCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Output\OutputInterface;

class RedmineSearchCommandTest extends \PHPUnit_Framework_TestCase
{
    private $application;
    private $tester;
    private $command;

    // Mocks
    private $service;
    private $redmineClient;
    private $redmineApiIssue;

    protected function setUp()
    {
        $this->application = new Application();
        $this->application->add(new RedmineSearchCommand());
        $command = $this->application->find('redmine:search');
        $this->tester = new CommandTester($command);

    }

    private function getMockedService()
    {
        $service = $this->getMockBuilder('\Sparkfabrik\Tools\Spark\Services\RedmineService')
            ->getMock();
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

    /**
   * Create mocks.
   */
    private function createMocks($options = array())
    {
        $this->service = $this->getMockedService();
        $this->redmineClient = $this->getMockedRedmineClient();
        $this->redmineApiIssue = $this->getMockedRedmineApiIssue();

        // Default returns for mock objects.
        $default_options = array_replace(
            array('redmineApiIssueAll' => array()),
            $options
        );

        // Mock methods.
        $this->redmineApiIssue->expects($this->any())
            ->method('all')
            ->will($this->returnValue($default_options['redmineApiIssueAll']));

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
   * Test no issues found.
   */
    public function testNoIssuesFound()
    {
        $command = $this->createCommand('redmine:search');
        $this->createMocks();

        // Execute with project_id
        $options = array(
        'command' => $this->command->getName(),
        '--project_id' => 'test_project_id',
        );
        $this->tester->execute($options);
        $res = trim($this->tester->getDisplay());
        $this->assertEquals('No issues found.', $res);

        // Execute without project_id.
        unset($options['--project_id']);
        $this->tester->execute($options);
        $res = trim($this->tester->getDisplay());
        $this->assertEquals('No issues found.', $res);
    }

    /**
   * Test syntax error.
   *
   * @expectedException        Exception
   * @expectedExceptionMessage Failed to parse response
   */
    public function testSyntaxError()
    {
        $command = $this->createCommand('redmine:search');
        $options = array('redmineApiIssueAll' => array('Syntax error'));
        $this->createMocks($options);

        // Execute with project_id
        $options = array(
        'command' => $this->command->getName(),
        '--project_id' => 'test_project_id',
        );
        $this->tester->execute($options);
    }

    /**
   * Test false return result set.
   *
   * @expectedException        Exception
   * @expectedExceptionMessage Failed to parse response
   */
    public function testFalseResult()
    {
        $command = $this->createCommand('redmine:search');
        $options = array('redmineApiIssueAll' => false);
        $this->createMocks($options);

        // Execute with project_id
        $options = array(
        'command' => $this->command->getName(),
        '--project_id' => 'test_project_id',
        );
        $this->tester->execute($options);
    }

    /**
   * Test verbosity.
   */
    public function testSearchWithDebugVerbosity()
    {
        $command = $this->createCommand('redmine:search');
        $this->createMocks();

        // Execute with project_id
        $input = array(
        'command' => $this->command->getName(),
        '--project_id' => 'test_project_id',
        );
        $options = array('verbosity' => OutputInterface::VERBOSITY_DEBUG);
        $this->tester->execute($input, $options);
        $res = trim($this->tester->getDisplay());
        $expected_string = var_export(
            array(
                'limit' => 50,
                'sort' => 'updated_on:desc',
                'project_id' => 'test_project_id',
                'status_id' => 'open',
                'assigned_to_id' => '',
            ),
            true
        );
        $this->assertContains('No issues found', $res);
        $this->assertContains($expected_string, $res);
    }

    /**
   * @expectedException  Exception
   * @expectedExceptionMessage errors
   */
    public function testIssueErrorResponse()
    {
        $command = $this->createCommand('redmine:search');
        $error_response = array('errors' => array('errors'));
        $this->createMocks(array('redmineApiIssueAll' => $error_response));

        // Execute.
        $this->tester->execute(
            array(
            'command' => $this->command->getName(),
            '--project_id' => 'test_project_id',
            )
        );
    }
}

<?php

/*
 * This file is part of the Spark tool package.
 *
 * (c) Paolo Mainardi <paolo.mainardi@sparkfabrik.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sparkfabrik\Tools\Spark\Tests\Command\Gitlab;

use Symfony\Component\Console\Application;
use Sparkfabrik\Tools\Spark\Service\GitlabService;
use Sparkfabrik\Tools\Spark\Command\Gitlab\GitlabMergeRequestCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Output\OutputInterface;

class GitlabCommandTest extends \PHPUnit_Framework_TestCase
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
        $this->application->add(new GitlabMergeRequestCommand());
        $command = $this->application->find('gitlab:mr');
        $this->tester = new CommandTester($command);
    }

    private function getMockedService()
    {
        $service = $this->getMockBuilder('\Sparkfabrik\Tools\Spark\Services\GitlabService')
            ->getMock();
        return $service;
    }

    private function getMockedGitlabClient()
    {
        $gitlabClient = $this->getMockBuilder('\Gitlab\Client')
            ->setConstructorArgs(array('mock_url'))
            ->getMock();
        return $gitlabClient;
    }

    private function getMockedGitlabApiMergeRequests()
    {
        $gitlabApiMergeRequests = $this->getMockBuilder('\Gitlab\Api\MergeRequests')
            ->disableOriginalConstructor()
            ->getMock();
        return $gitlabApiMergeRequests;
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
        $this->gitlabClient = $this->getMockedGitlabClient();
        $this->gitlabApiMergeRequests = $this->getMockedGitlabApiMergeRequests();

        // Default returns for mock objects.
        $default_options = array_replace(
            array('gitlabApiMergeRequestsAll' => array()),
            $options
        );

        // Mock methods.
        $this->gitlabApiMergeRequests->expects($this->any())
            ->method('getList')
            ->will($this->returnValue($default_options['gitlabApiMergeRequestsAll']));

        // Mock method api of gitlab client.
        $this->gitlabClient->expects($this->any())
            ->method('api')
            ->with(
                $this->logicalOr(
                    $this->equalTo('mr')
                )
            )
            ->will(
                $this->returnCallback(
                    function ($arg) {
                        switch ($arg) {
                            case 'mr':
                                return $this->gitlabApiMergeRequests;
                                    break;
                        }
                    }
                )
            );

        // Mock getClient on service object, just return mock gitlab.
        $this->service->expects($this->any())
            ->method('getClient')
            ->will($this->returnValue($this->gitlabClient));

        // Set the mocked client.
        $this->command->setService($this->service);
    }

   /**
     * Test no MRs found.
    */
    public function testNoMRsFound()
    {
        $command = $this->createCommand('gitlab:mr');
        $this->createMocks();

        $options = array(
            'command' => $this->command->getName(),
        );

        $this->tester->execute($options);
        $res = trim($this->tester->getDisplay());
        $this->assertEquals('No Merge Requests found.', $res);
    }
}

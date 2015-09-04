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

class GitlabMergeRequestCommandTest extends \PHPUnit_Framework_TestCase
{
    private $application;
    private $tester;
    private $command;

    // Mocks
    private $service;
    private $redmineClient;
    private $redmineApiIssue;

    private $searchProjectString = 'test';
    private $projectId = 9;

    private static $fixturesPath;

    public static function setUpBeforeClass()
    {
        self::$fixturesPath = __DIR__.'/../../Fixtures/';
    }

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

    private function getMockedGitlabApiProjectSearch()
    {
        $gitlabApiProjectSearch = $this->getMockBuilder('\Gitlab\Api\Projects')
            ->disableOriginalConstructor()
            ->getMock();
        return $gitlabApiProjectSearch;
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
        $this->gitlabApiProjectSearch = $this->getMockedGitlabApiProjectSearch();

        // Default returns for mock objects.
        $default_options = array_replace(
            array('gitlabApiMergeRequestsAll' => array()),
            array('gitlabApiProjectSearch' => array()),
            $options
        );

        // Mock methods.
        $this->gitlabApiMergeRequests->expects($this->any())
            ->method('getList')
            ->will($this->returnValue($default_options['gitlabApiMergeRequestsAll']));

        $this->gitlabApiProjectSearch->expects($this->any())
            ->method('search')
            ->will($this->returnValue($default_options['gitlabApiProjectSearch']));

        // Mock method api of gitlab client.
        $this->gitlabClient->expects($this->any())
            ->method('api')
            ->with(
                $this->logicalOr(
                    $this->equalTo('mr'),
                    $this->equalTo('projects')
                )
            )
            ->will(
                $this->returnCallback(
                    function ($arg) {
                        switch ($arg) {
                            case 'mr':
                                return $this->gitlabApiMergeRequests;
                                    break;
                            case 'projects':
                                return $this->gitlabApiProjectSearch;
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
     *
    */
    public function testNoMRsFound()
    {
        $command = $this->createCommand('gitlab:mr');
        $this->createMocks();

        $options = array(
            'command' => $this->command->getName(),
            '--project_id' => 543534534543,
        );

        $this->tester->execute($options);
        $res = trim($this->tester->getDisplay());
        $this->assertEquals('No Merge Requests found.', $res);
    }

    /**
     * Test no MRs found.
     *
     * @group ciccio
    */
    public function testProjectResult()
    {
        $search_projects = unserialize(file_get_contents(self::$fixturesPath . "gitlab_projects_search_multiple_results.serialized"));
        $search_mrs = unserialize(file_get_contents(self::$fixturesPath . "gitlab_search_mr_results.serialized"));

        $command = $this->createCommand('gitlab:mr');
        $this->createMocks(
            array(
                'gitlabApiMergeRequestsAll' => $search_mrs,
                'gitlabApiProjectSearch' => $search_projects,
            )
        );

        $options = array(
            'command' => $this->command->getName(),
            '--project_id' => 'Iperbole',
        );

        $this->tester->execute($options);
        $res = trim($this->tester->getDisplay());

        $this->assertContains('Select a project ID', $res);
    }
}

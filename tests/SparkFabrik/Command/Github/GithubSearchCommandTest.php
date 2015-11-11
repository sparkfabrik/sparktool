<?php

/*
 * This file is part of the Spark tool package.
 *
 * (c) Paolo Mainardi <paolo.mainardi@sparkfabrik.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sparkfabrik\Tools\Spark\Tests\Command\Github;

use Symfony\Component\Console\Application;
use Sparkfabrik\Tools\Spark\Service\GithubService;
use Sparkfabrik\Tools\Spark\Command\SparkCommand;
use Sparkfabrik\Tools\Spark\Command\Github\GithubCommand;
use Sparkfabrik\Tools\Spark\Command\Github\GithubSearchCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Output\OutputInterface;

class GithubSearchCommandTest extends \PHPUnit_Framework_TestCase
{
    private $application;
    private $tester;
    private $command;

    // Mocks
    private $service;
    private $githubClient;
    private $githubApiIssue;
    private static $fixturesPath;

    public static function setUpBeforeClass()
    {
        self::$fixturesPath = __DIR__.'/../../Fixtures/';
    }

    protected function setUp()
    {
        $this->application = new Application();
        $this->application->add(new GithubSearchCommand());
        $command = $this->application->find('github:search');
        $this->tester = new CommandTester($command);
    }

    private function getMockedService()
    {
        $service = $this->getMockBuilder('\Sparkfabrik\Tools\Spark\Services\GithubService')
            ->getMock();
        return $service;
    }

    private function getMockedGithubClient()
    {
        $githubClient = $this->getMockBuilder('\Github\Client')
            ->setConstructorArgs(array())
            ->getMock();
        return $githubClient;
    }

    private function getMockedGithubApiIssue()
    {
        $httpClient = $this->getMock('Guzzle\Http\Client', array('send'));
        $httpClient
            ->expects($this->any())
            ->method('send');
        $mock = $this->getMock('Github\HttpClient\HttpClient', array(), array(array(), $httpClient));

        $client = new \Github\Client($mock);
        $client->setHttpClient($mock);

        $githubApiIssue = $this->getMockBuilder('\Github\Api\Issue')
            ->setMethods(array('get', 'post', 'postRaw', 'patch', 'delete', 'put', 'head'))
            ->setConstructorArgs(array($client))
            ->getMock();
        return $githubApiIssue;
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
        $this->githubClient = $this->getMockedGithubClient();
        $this->githubApiIssue = $this->getMockedGithubApiIssue();

        // Default returns for mock objects.
        $default_options = array_replace(
            array('githubApiIssueAll' => array()),
            $options
        );

        // Mock methods.
        $this->githubApiIssue->expects($this->any())
            ->method('all')
            ->will($this->returnValue($default_options['githubApiIssueAll']));

        // Mock method api of github client.
        $this->githubClient->expects($this->any())
            ->method('api')
            ->will($this->returnValue($this->githubApiIssue));

        // Mock getClient on service object, just return mock github client.
        $this->service->expects($this->any())
            ->method('getClient')
            ->will($this->returnValue($this->githubClient));

        // Set the mocked client.
        $this->command->setService($this->service);
    }

    /**
     * Test no issues found.
     *
     * @group noissues
    */
    public function testNoIssuesFound()
    {
        $command = $this->createCommand('github:search');
        $this->createMocks();

        // Execute
        $options = array(
            'command' => $this->command->getName(),
        );
        $this->tester->execute($options);
        $res = trim($this->tester->getDisplay());
        $this->assertEquals('No issues found.', $res);
    }
}

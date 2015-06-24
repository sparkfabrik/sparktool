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
    private static $fixturesPath;

    public static function setUpBeforeClass()
    {
        self::$fixturesPath = __DIR__.'/../../Fixtures/';
    }

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
        $service
            ->method('getConfig')
            ->will(
                $this->returnValue(
                    array(
                        'redmine_output_fields' => 'id|ID,project|Project,created_on|Created,updated_on|Updated,tracker|Traker,fixed_version|Version,author|Author,assigned_to|Assigned,status|Status,estimated_hours|Estimated,subject|Subject',
                        'project_id' => null,
                    )
                )
            );
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

    private function getMockedRedmineApiIssueStatus()
    {
        $redmineApiIssue = $this->getMockBuilder('\Redmine\Api\IssueStatus')
            ->disableOriginalConstructor()
            ->getMock();
        return $redmineApiIssue;
    }

    private function getMockedRedmineApiUser()
    {
        $redmineApiUser = $this->getMockBuilder('\Redmine\Api\User')
            ->disableOriginalConstructor()
            ->getMock();
        return $redmineApiUser;
    }

    private function getMockedRedmineApiMembership()
    {
        $redmineApiMembership = $this->getMockBuilder('\Redmine\Api\Membership')
            ->disableOriginalConstructor()
            ->getMock();
        return $redmineApiMembership;
    }

    private function getMockedRedmineApiIssuePriorities()
    {
        $redmineApiIssuePriorities = $this->getMockBuilder('\Redmine\Api\IssuePriority')
            ->disableOriginalConstructor()
            ->getMock();
        return $redmineApiIssuePriorities;
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
        $this->redmineApiIssueStatus = $this->getMockedRedmineApiIssueStatus();
        $this->redmineApiUser = $this->getMockedRedmineApiUser();
        $this->redmineApiMembership = $this->getMockedRedmineApiMembership();
        $this->redmineApiIssuePriorities = $this->getMockedRedmineApiIssuePriorities();

        // Default returns for mock objects.
        $default_options = array_replace(
            array('redmineApiIssueAll' => array()),
            array('redmineApiIssueStatusAll' => array('issue_statuses' => array())),
            array('redmineApiUserGetCurrentUser' => array()),
            array('redmineApiUserAll' => array()),
            array('redmineApiMembershipAll' => array()),
            array('redmineApiUserShow' => array()),
            array('redmineApiIssuePriorities' => array('issue_priorities' => array())),
            $options
        );

        // Mock methods.
        $this->redmineApiIssue->expects($this->any())
            ->method('all')
            ->will($this->returnValue($default_options['redmineApiIssueAll']));

        $this->redmineApiIssueStatus->expects($this->any())
            ->method('all')
            ->will($this->returnValue($default_options['redmineApiIssueStatusAll']));

        $this->redmineApiUser->expects($this->any())
            ->method('getCurrentUser')
            ->will($this->returnValue($default_options['redmineApiUserGetCurrentUser']));

        $this->redmineApiUser->expects($this->any())
            ->method('all')
            ->will($this->returnValue($default_options['redmineApiUserAll']));

        $this->redmineApiUser->expects($this->any())
            ->method('show')
            ->will($this->returnValue($default_options['redmineApiUserShow']));

        $this->redmineApiMembership->expects($this->any())
            ->method('all')
            ->will($this->returnValue($default_options['redmineApiMembershipAll']));

        $this->redmineApiIssuePriorities->expects($this->any())
            ->method('all')
            ->will($this->returnValue($default_options['redmineApiIssuePriorities']));

        // Mock method api of redmine client.
        $this->redmineClient->expects($this->any())
            ->method('api')
            ->with(
                $this->logicalOr(
                    $this->equalTo('issue'),
                    $this->equalTo('issue_status'),
                    $this->equalTo('user'),
                    $this->equalTo('membership'),
                    $this->equalTo('issue_priority')
                )
            )
            ->will(
                $this->returnCallback(
                    function ($arg) {
                        switch ($arg) {
                            case 'issue':
                                return $this->redmineApiIssue;
                                    break;

                            case 'issue_status':
                                return $this->redmineApiIssueStatus;
                                    break;

                            case 'user':
                                return $this->redmineApiUser;
                                    break;

                            case 'membership':
                                return $this->redmineApiMembership;
                                    break;

                            case 'issue_priority':
                                return $this->redmineApiIssuePriorities;
                                    break;
                        }
                    }
                )
            );

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
    * Test not estimated issues.
    */
    public function testSearchNotEstimated()
    {
        $command = $this->createCommand('redmine:search');

        // Issues to mock.
        $issues = unserialize(file_get_contents(self::$fixturesPath . "redmine-search-not-estimated.serialized"));
        // Create mocks.
        $this->createMocks(array('redmineApiIssueAll' => $issues));

        // Execute with project_id
        $input = array(
            'command' => $this->command->getName(),
            '--project_id' => 'test_project_id',
            '--not-estimated' => true,
            '--fields' => 'id'
        );
        $this->tester->execute($input);
        $res = trim($this->tester->getDisplay());
        $expected = <<<EOF
+------+
| ID   |
+------+
| 8924 |
| 8925 |
| 8918 |
| 8916 |
| 8923 |
| 8922 |
| 8919 |
| 8917 |
| 8915 |
+------+
EOF
        ;
        $this->assertContains($expected, $res);
    }

        /**
        * Test search by subject.
        */
        public function testSearchSubject()
        {
            $command = $this->createCommand('redmine:search');

            // Issues to mock.
            $issues = unserialize(file_get_contents(self::$fixturesPath . "redmine-search-not-estimated.serialized"));

            // Create mocks.
            $this->createMocks(array('redmineApiIssueAll' => $issues));

            // Execute with project_id
            $input = array(
                'command' => $this->command->getName(),
                '--project_id' => 'test_project_id',
                '--subject' => 'Find',
                '--fields' => 'id'
            );
            $expected = <<<EOF
+------+
| ID   |
+------+
| 8921 |
| 8920 |
| 8916 |
+------+
EOF
            ;
            $this->tester->execute($input);
            $res = trim($this->tester->getDisplay());
            $this->assertEquals($expected, $res);
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

    /**
     * Test incorrect fields arguments.
     *
     * @group incorrectFields
     */
        public function testIncorrectFields()
        {
            $command = $this->createCommand('redmine:search');
            $data = file_get_contents(self::$fixturesPath . 'RedmineSearchResult');
            $this->createMocks(array('redmineApiIssueAll' => unserialize($data)));

            // Execute with project_id
            $input = array(
            'command' => $this->command->getName(),
            '--project_id' => 'test_project_id',
            );
            $options = array('--fields' => 'incorrect_field');
            $this->tester->execute($options);
            $res = trim($this->tester->getDisplay());
            $this->assertEquals('Incorrect filters inserted: incorrect_field', $res);
        }

    /**
     * Test incorrect fields arguments.
     *
     * @group fieldsSingleFilter
     */
        public function testFieldsSingleFilter()
        {
            $command = $this->createCommand('redmine:search');
            $data = file_get_contents(self::$fixturesPath . 'RedmineSearchResult');
            $this->createMocks(array('redmineApiIssueAll' => unserialize($data)));

            // Execute with project_id
            $input = array(
                'command' => $this->command->getName(),
                '--project_id' => 'test_project_id',
            );
            $options = array('--fields' => 'id');
            $this->tester->execute($options);
            $res = trim($this->tester->getDisplay());
            $this->assertEquals("+------+\n| ID   |\n+------+", substr($res, 0, 26));
        }

    /**
     * Test search by status.
     */
        public function testSearchByStatus()
        {
            $response_mock = file_get_contents(self::$fixturesPath . 'response_one_issue_new.serialized');
            // file_put_contents($path . 'response_one_issue_new.serialized', serialize($this->response_new_issue));die;
            $command = $this->createCommand('redmine:search');
            $this->createMocks(array('redmineApiIssueAll' => unserialize($response_mock)));

            $input = array(
                'command' => $this->command->getName(),
                '--project_id' => 'test_project_id',
            );

            $options = array('--status' => 'new');
            $this->tester->execute($input, $options);
            $res = trim($this->tester->getDisplay());
            $this->assertContains('New', $res);
        }

    /**
     * Test search by more than one status.
     *
     * @group fail
     */
        public function testSearchByMoreThanOneStatus()
        {
            $response_mock = file_get_contents(self::$fixturesPath . 'response_two_issue_new_and_in_progress.serialized');
            $response_mock_statues = file_get_contents(self::$fixturesPath . 'redmine-search-statuses.serialized');
            $command = $this->createCommand('redmine:search');
            $this->createMocks(
                array(
                'redmineApiIssueAll' => unserialize($response_mock),
                'redmineApiIssueStatusAll' => array('issue_statuses' => unserialize($response_mock_statues))
                )
            );
            $input = array(
                'command' => $this->command->getName(),
                '--project_id' => 'test_project_id',
                '--status' => 'new, in progress'
            );
            $this->tester->execute($input);
            $res = trim($this->tester->getDisplay());
            $this->assertContains('New', $res);
            $this->assertContains('In Progress', $res);
        }

    /**
     * Test search by assigned from not admin user.
     */
        public function testSearchByAssignedFromNotAdminUser()
        {
            $response_mock = file_get_contents(self::$fixturesPath . 'response_one_issue_assigned_user.serialized');
            $current_user_not_admin_mock = file_get_contents(self::$fixturesPath . 'response_current_user_not_admin.serialized');
            $user_show_mock = file_get_contents(self::$fixturesPath . 'response_user_show_user_not_admin.serialized');
            $membership_all = file_get_contents(self::$fixturesPath . 'response_membership_all_user_not_admin.serialized');

            $command = $this->createCommand('redmine:search');
            $this->createMocks(
                array(
                    'redmineApiUserGetCurrentUser' => unserialize($current_user_not_admin_mock),
                    'redmineApiIssueAll' => unserialize($response_mock),
                    'redmineApiUserShow' => unserialize($user_show_mock),
                    'redmineApiMembershipAll' => unserialize($membership_all),
                )
            );

            $input = array(
                'command' => $this->command->getName(),
                '--project_id' => 'test_project_id',
                '--assigned' => 'Paolo Pustorino',
                '--fields' => 'id'
            );

            $this->tester->execute($input);
            $res = trim($this->tester->getDisplay());
            $expected = <<<EOF
+------+
| ID   |
+------+
| 8921 |
+------+
EOF
            ;
            $this->assertContains($expected, $res);
        }

    /**
     * Test search by assigned from admin user.
     */
        public function testSearchByAssignedFromAdminUser()
        {
            $response_mock = file_get_contents(self::$fixturesPath . 'response_one_issue_assigned_user.serialized');
            $current_user_admin_mock = file_get_contents(self::$fixturesPath . 'response_current_user_admin.serialized');
            $users_mock = file_get_contents(self::$fixturesPath . 'response_users_user.serialized');

            $command = $this->createCommand('redmine:search');
            $this->createMocks(
                array(
                    'redmineApiUserGetCurrentUser' => unserialize($current_user_admin_mock),
                    'redmineApiIssueAll' => unserialize($response_mock),
                    'redmineApiUserAll' => unserialize($users_mock),
                )
            );

            $input = array(
                'command' => $this->command->getName(),
                '--project_id' => 'test_project_id',
                '--assigned' => 'Paolo Pustorino',
                '--fields' => 'id'
            );

            $this->tester->execute($input);
            $res = trim($this->tester->getDisplay());
            $expected = <<<EOF
+------+
| ID   |
+------+
| 8921 |
+------+
EOF
            ;
            $this->assertContains($expected, $res);
        }

    /**
     * Test search by assigned with user id.
     */
        public function testSearchByAssignedWithUserId()
        {
            $response_mock = file_get_contents(self::$fixturesPath . 'response_one_issue_assigned_user.serialized');

            $command = $this->createCommand('redmine:search');
            $this->createMocks(
                array(
                    'redmineApiIssueAll' => unserialize($response_mock),
                )
            );

            $input = array(
                'command' => $this->command->getName(),
                '--project_id' => 'test_project_id',
                '--assigned' => 1,
                '--fields' => 'id'
            );

            $this->tester->execute($input);
            $res = trim($this->tester->getDisplay());
            $expected = <<<EOF
+------+
| ID   |
+------+
| 8921 |
+------+
EOF
            ;
            $this->assertContains($expected, $res);
        }

    /**
     * Test search by wrong assigned user.
     */
        public function testSearchByAssignedUserNotFound()
        {
            $response_mock = file_get_contents(self::$fixturesPath . 'response_one_issue_assigned_user.serialized');
            $current_user_admin_mock = file_get_contents(self::$fixturesPath . 'response_current_user_admin.serialized');
            $users_mock = file_get_contents(self::$fixturesPath . 'response_users_user.serialized');
            $command = $this->createCommand('redmine:search');
            $this->createMocks(
                array(
                    'redmineApiUserGetCurrentUser' => unserialize($current_user_admin_mock),
                    'redmineApiIssueAll' => unserialize($response_mock),
                    'redmineApiUserAll' => unserialize($users_mock),
                )
            );

            $input = array(
                'command' => $this->command->getName(),
                '--project_id' => 'test_project_id',
                '--assigned' => 'WRONG NAME'
            );

            $this->tester->execute($input);
            $res = trim($this->tester->getDisplay());
            $expected = "No user found.";
            $this->assertContains($expected, $res);
        }

    /**
     * Test search by assigned user from not admin user without specify project.
     */
        public function testSearchByAssignedUserFromNotAdminUserWithoutProjectId()
        {
            $current_user_not_admin_mock = file_get_contents(self::$fixturesPath . 'response_current_user_not_admin.serialized');

            $command = $this->createCommand('redmine:search');
            $this->createMocks(
                array(
                    'redmineApiUserGetCurrentUser' => unserialize($current_user_not_admin_mock),
                )
            );

            $input = array(
                'command' => $this->command->getName(),
                '--project_id' => '',
                '--assigned' => 'Paolo Pustorino'
            );

            $this->tester->execute($input);
            $res = trim($this->tester->getDisplay());
            $expected = "To perform search by assigned user specify the project id.";
            $this->assertContains($expected, $res);
        }

    /**
     * Test search with priority order.
     */
        public function testSearchWithPriorityOrder()
        {
            $testRes = file_get_contents(self::$fixturesPath . 'redmine-search-with-normal-priority-order.serialized');
            $issuePriorities = file_get_contents(self::$fixturesPath . 'redmine-issue-priorities.serialized');

            $command = $this->createCommand('redmine:search');
            $this->createMocks(
                array(
                    'redmineApiIssuePriorities' => array('issue_priorities' => unserialize($issuePriorities)),
                    'redmineApiIssueAll' => unserialize($testRes),
                )
            );

            // var_dump(unserialize($testRes));die;

            $input = array(
                'command' => $this->command->getName(),
                '--priority-order' => 'Normal',
            );

            $expected = <<<EOF
+------+----------+-------------------------+------------+---------------------+-------------+-----------+---------------------+---------------------+-------------+-----------+----------------------------------------------------+
| ID   | Priority | Project                 | Created    | Updated             | Traker      | Version   | Author              | Assigned            | Status      | Estimated | Subject                                            |
+------+----------+-------------------------+------------+---------------------+-------------+-----------+---------------------+---------------------+-------------+-----------+----------------------------------------------------+
| 8738 | Normal   | Ecommerce               | 29-04-2015 | 18-06-2015 03:59:33 | Feature     | Backlog   | Marco Frattola      | Alessio Piazza      | In Progress |           | Update logos and static contents                   |
| 8852 | Normal   | Ecommerce               | 15-05-2015 | 18-06-2015 03:59:45 | Feature     | Backlog   | Marco Frattola      | Alessio Piazza      | In Progress |           | Modifiche sezione "Sostenibilità"                  |
| 8854 | Normal   | Ecommerce               | 15-05-2015 | 18-06-2015 04:55:22 | Feature     | Backlog   | Marco Frattola      | Alessio Piazza      | In Progress |           | Modifiche sezione "Qualità"                        |
| 9081 | Normal   | Elite                   | 16-06-2015 | 18-06-2015 05:21:30 | Feature     | SPRINT-35 | Marco Frattola      | Marcello Testi      | In Progress | 24        | EE-714 - Associate a video to a slide              |
| 9079 | Normal   | Elite                   | 16-06-2015 | 18-06-2015 05:21:30 | Epic        | SPRINT-35 | Marco Frattola      |                     | New         | 118       | EE-000 - Thron integration                         |
| 8853 | Normal   | Ecommerce               | 15-05-2015 | 18-06-2015 06:24:34 | Feature     | Backlog   | Marco Frattola      | Alessio Piazza      | In Progress |           | Modifiche sezione "Outlet"                         |
| 8855 | Normal   | Ecommerce               | 15-05-2015 | 18-06-2015 07:18:36 | Feature     | Backlog   | Marco Frattola      | Alessio Piazza      | In Progress |           | Modifiche sezione "Innovazione"                    |
| 8851 | Normal   | Ecommerce               | 15-05-2015 | 18-06-2015 08:58:41 | Feature     | Backlog   | Marco Frattola      | Alessio Piazza      | In Progress |           | Modifiche sezione "Scopri l'azienda"               |
| 9102 | Normal   | Ecommerce               | 18-06-2015 | 18-06-2015 09:29:20 | Task        |           | Alessio Piazza      | Alessio Piazza      | In Progress |           | Update perofil logo                                |
| 8942 | Normal   | Ecommerce               | 25-05-2015 | 19-06-2015 03:42:34 | Bug         |           | Paolo Pustorino     | Alessio Piazza      | In Progress |           | Taxonomy lineage displayed in the cart page        |
| 9069 | Normal   | Elite                   | 15-06-2015 | 21-06-2015 14:12:15 | Feature     | SPRINT-35 | Marco Frattola      | Marcello Gorla      | Resolved    | 40        | EE-705 - Viewing the user profile of a Broker      |
| 9042 | Normal   | Elite                   | 08-06-2015 | 22-06-2015 01:39:07 | Feature     | SPRINT-35 | Marco Frattola      | Paolo Mainardi      | Merged      | 24        | EE-695 - Allow meeting forward to invite other mem |
| 8905 | Normal   | Iperbole Comunità - EXT | 20-05-2015 | 22-06-2015 12:37:53 | Feature     |           | Olivia Pinto        | Vincenzo Di Biaggio | Validated   |           | Eliminare link ridondanti pagine interne           |
| 9104 | Normal   | Iperbole Comunità - EXT | 19-06-2015 | 22-06-2015 15:17:32 | Feature     |           | Michele Restuccia   | Vincenzo Di Biaggio | Feedback    |           | Nuovo commento per proprio contenuto / progetto /  |
| 9106 | Normal   | Iperbole Comunità - EXT | 19-06-2015 | 22-06-2015 15:17:33 | Feature     |           | Michele Restuccia   | Vincenzo Di Biaggio | Feedback    |           | Notifica per segui su profilo persona / profilo or |
| 9105 | Normal   | Iperbole Comunità - EXT | 19-06-2015 | 22-06-2015 15:17:33 | Feature     |           | Michele Restuccia   | Vincenzo Di Biaggio | Feedback    |           | Nuovo commento per contenuto seguito / commentato  |
| 9107 | Normal   | Iperbole Comunità - EXT | 19-06-2015 | 22-06-2015 15:17:34 | Feature     |           | Michele Restuccia   | Vincenzo Di Biaggio | Feedback    |           | Un utente è aggiunto come admin di un profilo org  |
| 9108 | Normal   | Iperbole Comunità - EXT | 19-06-2015 | 22-06-2015 15:17:35 | Feature     |           | Michele Restuccia   | Vincenzo Di Biaggio | Feedback    |           | L'organizzazione è aggiunta come partner di un pr  |
| 9112 | Normal   | Iperbole Comunità - EXT | 20-06-2015 | 22-06-2015 15:17:36 | Feature     |           | Michele Restuccia   | Vincenzo Di Biaggio | Feedback    |           | Notifica per approvazione informazioni inviate con |
| 9111 | Normal   | Iperbole Comunità - EXT | 20-06-2015 | 22-06-2015 15:17:36 | Feature     |           | Michele Restuccia   | Vincenzo Di Biaggio | Feedback    |           | Notifica per abilitazione all'invio modulo LFA     |
| 9113 | Normal   | Iperbole Comunità - EXT | 20-06-2015 | 22-06-2015 15:17:37 | Feature     |           | Michele Restuccia   | Vincenzo Di Biaggio | Feedback    |           | Notifiche per segnalazione (contenuto / profilo pe |
| 9115 | Normal   | Iperbole Comunità - EXT | 20-06-2015 | 22-06-2015 15:17:39 | Feature     |           | Michele Restuccia   | Vincenzo Di Biaggio | Feedback    |           | Notifiche per Community Manager: proposte di colla |
| 9103 | Normal   | Iperbole Comunità - EXT | 19-06-2015 | 22-06-2015 15:39:42 | Feature     |           | Vincenzo Di Biaggio | Vincenzo Di Biaggio | Feedback    |           | Contenuto Notifiche Comunità                       |
| 9054 | Normal   | Elite                   | 10-06-2015 | 22-06-2015 22:38:44 | Feature     | SPRINT-35 | Marco Frattola      | Adriano Cori        | Merged      | 16        | EG-456 - Notifications when an imported user his p |
| 9114 | Normal   | Iperbole Comunità - EXT | 20-06-2015 | 23-06-2015 08:48:32 | Feature     |           | Michele Restuccia   | Vincenzo Di Biaggio | Feedback    |           | Notifiche per Community Manager: proposta / commen |
| 9116 | Normal   | Iperbole Comunità - EXT | 22-06-2015 | 23-06-2015 10:07:19 | Feature     |           | Vincenzo Di Biaggio |                     | Validated   |           | Update nodi con indirizzo non geolocalizzato       |
| 9056 | Normal   | Elite                   | 10-06-2015 | 23-06-2015 14:21:41 | Feature     | SPRINT-35 | Marco Frattola      | Adriano Cori        | Merged      | 12        | EG-454 - Update Scheduler information when a user  |
| 9126 | Normal   | Elite                   | 23-06-2015 | 23-06-2015 14:59:44 | Improvement | Backlog   | Marco Frattola      |                     | New         |           | EE-721 - Remove reference to deleted page section  |
| 9118 | Normal   | Elite                   | 23-06-2015 | 23-06-2015 15:01:41 | Improvement | SPRINT-35 | Marco Frattola      | Marcello Testi      | New         | 1         | EE-720 - Remove "Register" link from homepage head |
| 9074 | Normal   | Elite                   | 15-06-2015 | 23-06-2015 15:09:50 | Feature     | SPRINT-35 | Marco Frattola      | Marcello Gorla      | In Progress | 24        | EE-709 - Broker viewing company agenda             |
| 9091 | Urgent   | Technical               | 18-06-2015 | 18-06-2015 10:50:12 | Task        |           | Marco Frattola      | Paolo Pustorino     | Feedback    |           | LAMP stack installation with php-fpm               |
| 9090 | Urgent   | Technical               | 18-06-2015 | 18-06-2015 09:44:41 | Task        |           | Marco Frattola      |                     | New         |           | Migrate VATTAN (DEADLINE: 24/06/2015)              |
| 9095 | Urgent   | Technical               | 18-06-2015 | 18-06-2015 05:57:36 | Task        |           | Marco Frattola      |                     | New         |           | Create /var/www symlinks                           |
| 9094 | Urgent   | Technical               | 18-06-2015 | 18-06-2015 05:57:32 | Task        |           | Marco Frattola      |                     | New         |           | Migrate directories: /var/lib/mongodb, /var/lib/my |
| 9089 | Urgent   | Technical               | 18-06-2015 | 18-06-2015 05:54:50 | Epic        |           | Marco Frattola      |                     | New         |           | Migrate old infrastructures (Twinbit, Agavee) to S |
| 9093 | Urgent   | Technical               | 18-06-2015 | 18-06-2015 05:57:31 | Task        |           | Marco Frattola      |                     | New         |           | Migrate service configurations                     |
| 9098 | High     | Technical               | 18-06-2015 | 18-06-2015 05:57:54 | Task        |           | Marco Frattola      |                     | New         |           | Send H-ART new IP Address to be enabled on its VPN |
| 9097 | High     | Technical               | 18-06-2015 | 18-06-2015 05:57:53 | Task        |           | Marco Frattola      |                     | New         |           | LAMP stack installation with mod_php               |
| 8932 | High     | Iperbole Comunità - EXT | 25-05-2015 | 19-06-2015 02:28:01 | Feature     |           | Michele Restuccia   | Vincenzo Di Biaggio | On hold     |           | Funzionamento Tile 'In evidenza' (HOMEPAGE)        |
| 9100 | High     | Technical               | 18-06-2015 | 18-06-2015 05:57:56 | Support     |           | Marco Frattola      |                     | New         |           | Verify Andrea Panisson new development environment |
| 9096 | High     | Technical               | 18-06-2015 | 18-06-2015 09:44:56 | Task        |           | Marco Frattola      |                     | New         |           | Migrate ZOTAN (DEADLINE: 05/07/2015)               |
+------+----------+-------------------------+------------+---------------------+-------------+-----------+---------------------+---------------------+-------------+-----------+----------------------------------------------------+

Showing "50" of "1529" issues(you can adjust the limit using --limit argument)
EOF
            ;

            $this->tester->execute($input);
            $res = trim($this->tester->getDisplay());
            $this->assertEquals($expected, $res);
        }
}

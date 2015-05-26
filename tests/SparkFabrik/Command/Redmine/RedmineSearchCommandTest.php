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

        // Default returns for mock objects.
        $default_options = array_replace(
            array('redmineApiIssueAll' => array()),
            array('redmineApiIssueStatusAll' => array('issue_statuses' => array())),
            array('redmineApiUserGetCurrentUser' => array()),
            array('redmineApiUserAll' => array()),
            array('redmineApiMembershipAll' => array()),
            array('redmineApiUserShow' => array()),
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

        // Mock method api of redmine client.
        $this->redmineClient->expects($this->any())
            ->method('api')
            ->with(
                $this->logicalOr(
                    $this->equalTo('issue'),
                    $this->equalTo('issue_status'),
                    $this->equalTo('user'),
                    $this->equalTo('membership')
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
}

<?php

/*
 * This file is part of the Spark tool package.
 *
 * (c) Paolo Pustorino <paolo.pustorino@sparkfabrik.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sparkfabrik\Tools\Spark\Tests\Command\Welcome;

use Symfony\Component\Console\Application;
use Sparkfabrik\Tools\Spark\Command\Welcome\WelcomeCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Output\OutputInterface;

class RedmineSearchCommandTest extends \PHPUnit_Framework_TestCase
{
    private $application;
    private $tester;
    private $command;

    // Mocks
    private $commandOutput;

    protected function setUp()
    {
        $this->application = new Application();
        $this->application->add(new WelcomeCommand());
        $command = $this->application->find('spark:welcome');
        $this->tester = new CommandTester($command);

    }

    private function getMockedCommandOutput()
    {
        $splashOutput = file_get_contents('.banner.txt');
        $splashOutput = strtr($splashOutput, ['\033' => "\033"]);
        return trim($splashOutput);
    }

    private function getMockedListCommandOutput()
    {
        // return a (hopefully) significative string
        return 'command [options] [arguments]';
    }

    private function createCommand($name)
    {
        $this->command = $this->application->find($name);
    }

    /**
     * Tests that the output of the welcome command contains the banner set in .banner.txt
     */
    public function testExplicitCallShowsBanner()
    {
        $command = $this->createCommand('spark:welcome');
        $expected  = $this->getMockedCommandOutput();

        // Execute with project_id
        $options = array(
            'command' => $this->command->getName(),
        );
        $this->tester->execute($options);
        $res = trim($this->tester->getDisplay());
        $this->assertStringStartsWith($expected, $res);
    }

     /**
     * Tests that the output of the welcome command spits the usage instructions
     */
    public function testExplicitCallShowsUsageInfo()
    {
        $command = $this->createCommand('spark:welcome');
        $expected  = $this->getMockedListCommandOutput();

        // Execute with project_id
        $options = array(
            'command' => $this->command->getName(),
        );
        $this->tester->execute($options);
        $res = trim($this->tester->getDisplay());
        $this->assertContains($expected, $res);
    }
}

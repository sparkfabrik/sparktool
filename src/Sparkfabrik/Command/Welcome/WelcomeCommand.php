<?php

/*
 * This file is part of the Spark tool package.
 *
 * (c) Paolo Pustorino <paolo.pustorino@sparkfabrik.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sparkfabrik\Tools\Spark\Command\Welcome;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Implements Welcome command
 *
 * @author Paolo Pustorino <paolo.pustorino@sparkfabrik.com>
 *
 * @api
 */
class WelcomeCommand extends Command
{
    /**
     * Give a name and description to the command
     */
    protected function configure()
    {
        $this->setName('spark:welcome')
            ->setDescription('Prints the welcome splash');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $welcome_file = '.banner.txt';
        if (!file_exists($welcome_file)) {
            $welcome_file = 'phar://spark.phar/' . $welcome_file;
        }
        $splash = file_get_contents($welcome_file);
        $splash = strtr($splash, ['\033' => "\033"]);
        $output->writeln($splash);

        $listCommand = $this->getApplication()->find('list');

        $arguments = array(
            'command' => '',
        );

        $input = new ArrayInput($arguments);
        $returnCode = $listCommand->run($input, $output);
    }
}

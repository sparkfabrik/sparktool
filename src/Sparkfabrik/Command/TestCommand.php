<?php

namespace Sparkfabrik\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output) {
      $output->writeln('Created the file test');
    }
}

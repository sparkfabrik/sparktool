<?php

/*
 * This file is part of the Spark tool package.
 *
 * (c) Paolo Mainardi <paolo.mainardi@sparkfabrik.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sparkfabrik\Tools\Spark\Command\Redmine;

use Sparkfabrik\Tools\Spark\Command\Redmine\RedmineCommand;
use Sparkfabrik\Tools\Spark\RedmineApi\User as RedmineApiUser;
use Sparkfabrik\Tools\Spark\RedmineApi\Version as RedmineApiVersion;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Redmine\Api\Tracker;

class RedmineSearchPresetsCommand extends RedmineCommand
{

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('redmine:search:presets')
            ->setDescription('Manage redmine issues search presets')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command displays help for a given command:

  <info>php %command.full_name% list</info>

You can also output the help in other formats by using the <comment>--format</comment> option:

  <info>php %command.full_name% --format=xml list</info>

To display the list of available commands, please use the <info>list</info> command.
EOF
            );
        $this->addOption(
            'delete',
            null,
            InputOption::VALUE_REQUIRED,
            'Delete a saved search preset.'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $options = $input->getOptions();
            if ($options['delete']) {
                $this->deleteSearchPreset($options['delete']);
            }

            // Fields to print.
            $header = array('Preset', 'Query');
            $rows = $this->getSearchPresets();

            $table = new Table($output);
            $table->setHeaders($header)
                ->setRows($rows)
                ->render();


        } catch (Exception $e) {
            return $output->writeln('<error>'. $e->getMessage() . '</error>');
        }
    }

    /**
     * Get a list of saved search presets.
     *
     * @return array|mixed
     */
    public function getSearchPresets()
    {
        $presets = \FileDB::select('redmine_search_presets', 'rsp')
          ->fields('rsp')
          ->orderBy('rsp.preset', SORT_ASC)
          ->execute()
          ->fetchAll(false, false);

        $results = array();

        if (!empty($presets)) {
            foreach ($presets as $i => $preset) {
                foreach ($preset as $field => $value) {
                    if ($field === 'query') {
                        $query_options = unserialize($value);
                        $args = '';
                        foreach ($query_options as $opt_name => $opt_value) {
                            if (is_array($opt_value)) {
                                foreach ($opt_value as $opt_v) {
                                    $args .= $this->formatSearchArgument($opt_name, $opt_v);
                                }
                            }
                            else {
                                $args .= $this->formatSearchArgument($opt_name, $opt_value);
                            }
                        }
                        $presets[$i][$field] = $args;
                    }
                }
            }

            $results = $presets;
        }

        return $results;
    }

    /**
     * Formats a search argument in a human readable way.
     *
     * @param  string $name
     * @param  string $value
     * @return string
     */
    private function formatSearchArgument($name, $value) 
    {
        return sprintf(' --%s="%s"', $name, $value);
    }

    /**
     * Delete a saved search preset.
     *
     * @param string $preset
     */
    public function deleteSearchPreset($preset) 
    {
        \FileDB::delete('redmine_search_presets')
          ->condition('preset', $preset)
          ->execute();
    }
}

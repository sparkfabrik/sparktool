<?php

/*
 * This file is part of the Spark tool package.
 *
 * (c) Paolo Mainardi <paolo.mainardi@sparkfabrik.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sparkfabrik\Tools\Spark;

use Sparkfabrik\Tools\Spark\SparkConfigurationWrapperInterface;
use Sparkfabrik\Tools\Spark\Config\YamlConfigLoader;
use Sparkfabrik\Tools\Spark\Config\SparkConfiguration;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Console\Output\OutputInterface;

class SparkConfigurationWrapper implements SparkConfigurationWrapperInterface
{
    private $processedConfiguration;
    private $fs;
    private $options = array();
    private $output;

    public function __construct($options = array(), OutputInterface $output = null)
    {
        $this->options = array_replace(
            array(
            'sparkHome' => getenv('HOME') . DIRECTORY_SEPARATOR . '.spark',
            'sparkConfigFile' => '.spark.yml',
            'currentDir' => getcwd(),
            ),
            $options
        );
        $this->initConfig();
        if (!$output) {
            $output = new ConsoleOutput();
        }
        $this->output = $output;
    }

    public function initConfig()
    {
        $this->fs = new Filesystem();
        $configFileStandardPath = $this->options['sparkHome'] . DIRECTORY_SEPARATOR . $this->options['sparkConfigFile'];
        if (!$this->fs->exists($configFileStandardPath)) {
            $this->fs->mkdir($this->options['sparkHome']);
            $defaultConfig = $this->dumpDefaultConfigurationFile();
            file_put_contents($configFileStandardPath, $defaultConfig);
        } else {
            // Merge configurations if needed.
            $dumper = new Dumper();
            $merge = array();

            // Get default config.
            $defaultConfig = Yaml::parse($this->dumpDefaultConfigurationFile());

            // Get Custom config.
            $customConfig = Yaml::parse(file_get_contents($configFileStandardPath));

            // Merge configs.
            if (count($defaultConfig['spark'], COUNT_RECURSIVE) !== count($customConfig['spark'], COUNT_RECURSIVE)) {
                $merge['spark'] = array_replace_recursive($defaultConfig['spark'], $customConfig['spark']);
                $yaml_merged = $dumper->dump($merge, 5);
                file_put_contents($configFileStandardPath, $yaml_merged);
            }

            $customConfig = Yaml::parse(file_get_contents($configFileStandardPath));

            // Then validate.
            $processor = new Processor();
            $sparkConfiguration = new SparkConfiguration();
            $processor->processConfiguration(
                $sparkConfiguration,
                $customConfig
            );
        }
    }

    /**
   * Get value from configuration file.
   *
   * @param  string $type
   * @param  string $name
   * @return mixed
   */
    public function getValueFromConfig($type, $name)
    {
        $this->getProcessedConfigurations();
        if (isset($this->processedConfiguration[$type][$name])) {
            return $this->processedConfiguration[$type][$name];
        }
    }

    /**
   * Loads values from the actual config file.
   *
   * @see http://blog.servergrove.com/2014/02/21/symfony2-components-overview-config/
   */
    public function loadConfig()
    {
        $configs = array();
        $locator = new FileLocator(array($this->options['sparkHome']));
        $loader = new YamlConfigLoader($locator);
        $locations = array_reverse(
            $locator->locate(
                $this->options['sparkConfigFile'],
                $this->options['currentDir'],
                false
            )
        );
        // Merge global and specific project configuration file.
        foreach ($locations as $location) {
            $yaml = $loader->load($location);
            if (is_array($yaml) && isset($yaml['spark'])) {
                $configs[] = $yaml;
            }
        }
        if (count($configs) > 1) {
            $configs = call_user_func_array(array($this, 'arrayMergeDeep'), $configs);
        } else {
            $configs = reset($configs);
        }
        try {
            $processor = new Processor();
            $sparkConfiguration = new SparkConfiguration();
            $this->processedConfiguration = $processor->processConfiguration(
                $sparkConfiguration,
                $configs
            );
        } catch (InvalidConfigurationException $e) {
            $stderr = $this->output->getErrorOutput();
            $stderr->writeln('<error>ERROR</error> while loading configuration:');
            $message = sprintf(
                'The path "<bg=red>%s</bg=red>" cannot contain an empty value.',
                $e->getPath()
            );

            $stderr->writeln($message);
            $stderr->writeln('Configuration files are searched in:');
            $stderr->writeln($this->options['sparkHome'].DIRECTORY_SEPARATOR.$this->options['sparkConfigFile']);
            $stderr->writeln($this->options['currentDir'].DIRECTORY_SEPARATOR.$this->options['sparkConfigFile']);
            throw $e;
        }
    }

    /**
     * @see https://api.drupal.org/api/drupal/includes%21bootstrap.inc/function/drupal_array_merge_deep/7
     */
    public function arrayMergeDeep()
    {
        $args = func_get_args();
        return $this->arrayMergeDeepArray($args);
    }

    /**
     * @see https://api.drupal.org/api/drupal/includes%21bootstrap.inc/function/drupal_array_merge_deep_array/7
     */
    public function arrayMergeDeepArray($arrays)
    {
        $result = array();

        foreach ($arrays as $array) {
            foreach ($array as $key => $value) {
              // Renumber integer keys as array_merge_recursive() does. Note that PHP
              // automatically converts array keys that are integer strings (e.g., '1')
              // to integers.
                if (is_integer($key)) {
                    $result [] = $value;
                } // Recurse when both values are arrays.
                elseif (isset($result [$key]) && is_array($result [$key]) && is_array($value)) {
                    $result [$key] = $this->arrayMergeDeepArray(array($result [$key], $value));
                } // Otherwise, use the latter value, overriding any previous value.
                else {
                    $result [$key] = $value;
                }
            }
        }

        return $result;
    }

    /**
   * Return a default configuration file.
   *
   * @return mixed
   */
    public function dumpDefaultConfigurationFile()
    {
        $dumper = new YamlReferenceDumper();
        $configuration = new SparkConfiguration();
        return $dumper->dump($configuration);
    }

    /**
   * Return processed configurations.
   *
   * @return mixed
   */
    public function getProcessedConfigurations()
    {
        if (!isset($this->processedConfiguration)) {
            $this->loadConfig();
        }
        return $this->processedConfiguration;
    }
}

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

class SparkConfigurationWrapper {
  private $processedConfiguration;
  private $fs;
  private $options = array();

  public function __construct($options = array()) {
    $this->options = array_replace(
      array(
        'sparkHome' => getenv('HOME') . DIRECTORY_SEPARATOR . '.spark',
        'sparkConfigFile' => '.spark.yml',
        'currentDir' => getcwd(),
      ), $options);
    $this->initConfig();
  }

  public function initConfig() {
    $this->fs = new Filesystem();
    $configFileStandardPath = $this->options['sparkHome'] . DIRECTORY_SEPARATOR . $this->options['sparkConfigFile'];
    if (!$this->fs->exists($configFileStandardPath)) {
      $this->fs->mkdir($this->options['sparkHome']);
      $defaultConfig = $this->dumpDefaultConfigurationFile();
      file_put_contents($configFileStandardPath, $defaultConfig);
    }
    else {
      try {
        // Merge configurations if needed.
        $dumper = new Dumper();
        $merge = array();

        // Get default config.
        $defaultConfig = Yaml::parse($this->dumpDefaultConfigurationFile());

        // Get Custom config.
        $customConfig = Yaml::parse(file_get_contents($configFileStandardPath));
        if (count($defaultConfig['spark']) !== count($customConfig['spark'])) {
          $merge['spark'] = array_merge($defaultConfig['spark'], $customConfig['spark']);
          $yaml_merged = $dumper->dump($merge, 5);
          file_put_contents($configFileStandardPath, $yaml_merged);
        }
      }
      catch (\Exception $e) {
        die($e->getMessage() . PHP_EOL);
      }
    }
  }

  /**
   * Get value from configuration file.
   *
   * @param string $type
   * @param string $name
   * @return mixed
   */
  public function getValueFromConfig($type, $name) {
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
  public function loadConfig() {
    $configs = array();
    $locator = new FileLocator(array($this->options['sparkHome']));
    $loader = new YamlConfigLoader($locator);
    $locations = array_reverse($locator->locate($this->options['sparkConfigFile'], $this->options['currentDir'], false));
    // Merge global and specific project configuration file.
    foreach ($locations as $location) {
      $yaml = $loader->load($location);
      if (is_array($yaml) && isset($yaml['spark'])) {
        $configs[] = $yaml['spark'];
      }
    }
    if (!$configs) {
      throw new \Exception('Configuration file empty');
    }
    try {
      $processor = new Processor();
      $sparkConfiguration = new SparkConfiguration();
      $this->processedConfiguration = $processor->processConfiguration(
        $sparkConfiguration,
        $configs
      );
    }
    catch (\Exception $e) {
      die($e->getMessage() . PHP_EOL);
    }
  }

  /**
   * Return a default configuration file.
   *
   * @return mixed
   */
  public function dumpDefaultConfigurationFile() {
    $dumper = new YamlReferenceDumper();
    $configuration = new SparkConfiguration();
    return $dumper->dump($configuration);
  }

  /**
   * Return processed configurations.
   *
   * @return mixed
   */
  public function getProcessedConfigurations() {
    if (!isset($this->processedConfiguration)) {
      $this->loadConfig();
    }
    return $this->processedConfiguration;
  }
}

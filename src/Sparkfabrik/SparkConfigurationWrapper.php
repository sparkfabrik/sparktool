<?php

namespace Sparkfabrik\Tools\Spark;

use Sparkfabrik\Tools\Spark\Config\YamlConfigLoader;
use Sparkfabrik\Tools\Spark\Config\SparkConfiguration;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class SparkConfigurationWrapper {
  const SPARK_CONFIG_FILE = '.spark.yml';

  /**
   * @var array
   */
  protected $processedConfiguration;
  protected $fs;
  protected $sparkHome;

  public function __construct() {
    // @todo this is not compatible with non unixes OS.
    $this->sparkHome = getenv('HOME') . DIRECTORY_SEPARATOR . '.spark';
    $this->initConfig();
  }

  private function initConfig() {
    $this->fs = new Filesystem();
    // @todo we need to handle errors.
    if (!$this->fs->exists($this->sparkHome)) {
      $this->fs->mkdir($this->sparkHome);
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
    if (!isset($this->processedConfiguration)) {
      $this->loadConfig();
    }
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
    $locator = new FileLocator(array($this->sparkHome));
    $loader = new YamlConfigLoader($locator);
    $locations = array_reverse($locator->locate(static::SPARK_CONFIG_FILE, getcwd(), false));
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
    catch (Exception $e) {
       echo $e->getMessage() . PHP_EOL;
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
}

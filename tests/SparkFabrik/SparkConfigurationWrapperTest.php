<?php

/*
 * This file is part of the Spark tool package.
 *
 * (c) Paolo Mainardi <paolo.mainardi@sparkfabrik.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sparkfabrik\Tools\Spark\Tests;
use Sparkfabrik\Tools\Spark\SparkConfigurationWrapper;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Dumper;

class RedmineIssueCommandTest extends \PHPUnit_Framework_TestCase
{
  /**
   * @var \Symfony\Component\Filesystem\Filesystem $filesystem
   */
  private $configuration = null;
  private $sparkFileName = '.spark.yml';

  /**
   * @var string $workspace
   */
  protected $workspace = null;
  protected $fullPath = null;

  protected function setUp()
  {
    $this->umask = umask(0);
    $this->workspace = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.time().rand(0, 1000);
    mkdir($this->workspace, 0777, true);
    $this->workspace = realpath($this->workspace);
    $this->fullPath = $this->workspace . DIRECTORY_SEPARATOR . $this->sparkFileName;
  }

  protected function tearDown()
  {
      $this->clean($this->workspace);
      umask($this->umask);
  }

  /**
   * @param string $file
   */
  protected function clean($file)
  {
      if (is_dir($file) && !is_link($file)) {
          $dir = new \FilesystemIterator($file);
          foreach ($dir as $childFile) {
              $this->clean($childFile);
          }

          rmdir($file);
      } else {
          unlink($file);
      }
  }

  public function testFileNotExists()
  {
    $this->assertFileNotExists($this->fullPath);
  }

  public function testFileExists()
  {
    $this->configuration = new SparkConfigurationWrapper($this->workspace, $this->sparkFileName);
    $this->assertFileExists($this->fullPath);
  }

  public function testDefaultConfigEqualsToCustomConfiguarion() {
    $this->configuration = new SparkConfigurationWrapper($this->workspace, $this->sparkFileName);
    $defaultConfig = serialize(Yaml::parse($this->configuration->dumpDefaultConfigurationFile()));
    $customConfig = serialize(Yaml::parse(file_get_contents($this->fullPath)));
    $this->assertEquals($defaultConfig, $customConfig);
  }

  public function testGetProcessedConfigurations() {
    $this->configuration = new SparkConfigurationWrapper($this->workspace, $this->sparkFileName);
    $values = $this->configuration->getProcessedConfigurations();
    $this->assertTrue(is_array($values));
    $this->assertTrue(count($values) > 0);
    $this->assertArrayHasKey('services', $values);
    $this->assertTrue(is_array($values['services']));
    $this->assertArrayHasKey('projects', $values);
    $this->assertTrue(is_array($values['projects']));
    $this->assertArrayHasKey('git', $values);
    $this->assertTrue(is_array($values['git']));
  }

  public function testGetValueFromConfig() {
    $this->configuration = new SparkConfigurationWrapper($this->workspace, $this->sparkFileName);
    $value_from_config = $this->configuration->getValueFromConfig('git', 'branch_pattern');
    $this->assertNotEmpty($value_from_config);
  }

  public function testMergeConfigurations() {
    $this->configuration = new SparkConfigurationWrapper($this->workspace, $this->sparkFileName);
    $customConfig = Yaml::parse(file_get_contents($this->fullPath));

    // Unset an attribute (this can happen when the user has an old configuration file).
    unset($customConfig['spark']['git']);

    // Write the file to workspace.
    $dumper = new Dumper();
    $yaml_merged = $dumper->dump($customConfig, 5);
    file_put_contents($this->fullPath, $yaml_merged);

    // Reinit and test if they are equal.
    $this->configuration->initConfig();
    $defaultConfig = serialize(Yaml::parse($this->configuration->dumpDefaultConfigurationFile()));
    $customConfig = serialize(Yaml::parse(file_get_contents($this->fullPath)));
    $this->assertEquals($defaultConfig, $customConfig);
  }

}

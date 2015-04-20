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
  private $configuration = null;
  private $sparkFileName = '.spark.yml';
  private $workspace = null;
  private $workspace_project = null;
  private $fullPathWorkspace = null;
  protected static $fixturesPath;

  public static function setUpBeforeClass()
  {
    self::$fixturesPath = __DIR__ . '/Fixtures/';
  }

  protected function setUp()
  {
    $this->umask = umask(0);
    $this->workspace = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.time().rand(0, 1000);
    $this->workspace_project = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.time().rand(0, 1000);
    mkdir($this->workspace, 0777, true);
    mkdir($this->workspace_project, 0777, true);
    $this->workspace = realpath($this->workspace);
    $this->workspace_project = realpath($this->workspace_project);
    $this->fullPathWorkspace = $this->workspace . DIRECTORY_SEPARATOR . $this->sparkFileName;
  }

  protected function tearDown()
  {
      $this->clean($this->workspace);
      $this->clean($this->workspace_project);
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

  protected function getArguments() {
    return array(
      'sparkHome' => $this->workspace,
      'sparkConfigFile' => '.spark.yml',
      'currentDir' => $this->workspace_project
    );
  }

  public function testFileNotExists()
  {
    $this->assertFileNotExists($this->fullPathWorkspace);
  }

  public function testFileExists()
  {
    $this->configuration = new SparkConfigurationWrapper($this->getArguments());
    $this->assertFileExists($this->fullPathWorkspace);
  }

  public function testDefaultConfigEqualsToCustomConfiguration()
  {
    $this->configuration = new SparkConfigurationWrapper($this->getArguments());

    $defaultConfig = serialize(Yaml::parse($this->configuration->dumpDefaultConfigurationFile()));
    $customConfig = serialize(Yaml::parse(file_get_contents($this->fullPathWorkspace)));

    $this->assertEquals($defaultConfig, $customConfig);
  }

  public function testGetProcessedConfigurations()
  {
    $this->configuration = new SparkConfigurationWrapper($this->getArguments());
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

  public function testGetValueFromConfig()
  {
    $this->configuration = new SparkConfigurationWrapper($this->getArguments());
    $value_from_config = $this->configuration->getValueFromConfig('git', 'branch_pattern');
    $this->assertNotEmpty($value_from_config);
  }

  protected function configurationDeleteElements($customConfig) {
    $dumper = new Dumper();
    $yaml_merged = $dumper->dump($customConfig, 5);
    file_put_contents($this->fullPathWorkspace, $yaml_merged);

    // Reinit and test if they are equal.
    $this->configuration->initConfig();

    $defaultConfig = serialize(Yaml::parse($this->configuration->dumpDefaultConfigurationFile()));
    $customConfig = serialize(Yaml::parse(file_get_contents($this->fullPathWorkspace)));
    $this->assertEquals($defaultConfig, $customConfig);
  }

  public function testConfigurationsAddNewElementsFromDefaultToConfigurationFirstLevel()
  {
    $this->configuration = new SparkConfigurationWrapper($this->getArguments());
    $customConfig = Yaml::parse(file_get_contents($this->fullPathWorkspace));

    unset($customConfig['spark']['git']);
    $this->configurationDeleteElements($customConfig);
  }

  public function testConfigurationsAddNewElementsFromDefaultToConfigurationSecondLevel() {
    $this->configuration = new SparkConfigurationWrapper($this->getArguments());
    $customConfig = Yaml::parse(file_get_contents($this->fullPathWorkspace));

    unset($customConfig['spark']['services']['redmine_project_id']);
    $this->configurationDeleteElements($customConfig);
  }

  public function testConfigurationMergeProjectConfigurationWithDefault() {
    $project_conf = file_get_contents(self::$fixturesPath . 'SparkConfigurationHome.yml');
    $project_conf_parsed = Yaml::parse($project_conf);

    // Write configuration file to workspace home.
    file_put_contents($this->fullPathWorkspace, $project_conf);

    $this->configuration = new SparkConfigurationWrapper($this->getArguments());

    // Check they get correctly merged.
    $value_from_config = $this->configuration->getValueFromConfig('projects', 'redmine_project_id');
    $this->assertEquals($value_from_config, $project_conf_parsed['spark']['projects']['redmine_project_id']);
  }

  /**
   * @expectedExceptionMessage Unrecognized option "not_existing_project" under "spark.projects"
   */
  public function testConfigurationMergeProjectConfigurationWithDefaultWrongOptions() {
    $project_conf = file_get_contents(self::$fixturesPath . 'SparkConfigurationHomeWrongOptions.yml');
    $project_conf_parsed = Yaml::parse($project_conf);

    // Write configuration file to workspace home.
    file_put_contents($this->fullPathWorkspace, $project_conf);
    $this->configuration = new SparkConfigurationWrapper($this->getArguments());
  }
}

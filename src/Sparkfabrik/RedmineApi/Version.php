<?php

/*
 * This file is part of the Spark tool package.
 *
 * (c) Paolo Mainardi <paolo.mainardi@sparkfabrik.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sparkfabrik\Tools\Spark\RedmineApi;
use Redmine\Api\Version as RedmineVersion;

class Version extends RedmineVersion {
  private $versions = array();

  /**
   * List versions.
   *
   * @link http://www.redmine.org/projects/redmine/wiki/Rest_Versions#GET
   *
   * @param string|int $project project id or literal identifier
   * @param array      $params  optional parameters to be passed to the api (offset, limit, ...)
   *
   * @return array list of versions found
   */
  public function all($project, array $params = array())
  {
      $this->versions = $this->retrieveAll('/projects/'.$project.'/versions.json', $params);

      return $this->versions;
  }
  /**
   * Returns an array of name/id pairs (or id/name if not $reverse) of issue versions for $project.
   *
   * @param string|int $project     project id or literal identifier
   * @param boolean    $forceUpdate to force the update of the projects var
   * @param boolean    $reverse     to return an array indexed by name rather than id
   *
   * @return array list of projects (id => project name)
   */
  public function listing($project, $forceUpdate = false, $reverse = true, array $params = array())
  {
      if (true === $forceUpdate || empty($this->versions)) {
          $this->all($project, $params);
      }
      $ret = array();
      foreach ($this->versions['versions'] as $e) {
          $ret[(int) $e['id']] =  $e['name'];
      }

      return $reverse ? array_flip($ret) : $ret;
  }

  /**
   * Get an issue version id given its name and related project.
   *
   * @param string|int $project project id or literal identifier
   * @param string     $name
   *
   * @return int|false
   */
  public function getIdByName($project, $name, array $params = array())
  {
      $arr = $this->listing($project, false, true, $params);
      if (!isset($arr[$name])) {
          return false;
      }

      return $arr[(string) $name];
  }
}

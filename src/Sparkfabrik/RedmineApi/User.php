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

use Redmine\Api\User as RedmineUser;

class User extends RedmineUser
{
    private $users = array();

    /**
   * List users.
   *
   * @link http://www.redmine.org/projects/redmine/wiki/Rest_Users#GET
   *
   * @param array $params to allow offset/limit (and more) to be passed
   *
   * @return array list of users found
   */
    public function all(array $params = array())
    {
        $this->users = $this->retrieveAll('/users.json', $params);
        return $this->users;
    }

    /**
   * Returns an array of users with login/id pairs.
   *
   * @param boolean $forceUpdate to force the update of the users var
   *
   * @return array list of users (id => username)
   */
    public function listing($forceUpdate = false, array $params = array())
    {
        if (empty($this->users) || $forceUpdate) {
            $this->all($params);
        }
        $ret = array();
        if (is_array($this->users) && isset($this->users['users'])) {
            foreach ($this->users['users'] as $e) {
                $ret[$e['login']] = (int) $e['id'];
            }
        }

        return $ret;
    }

    /**
   * Returns an array of users with id/name+lastname pairs.
   *
   * @param boolean $forceUpdate to force the update of the users var
   *
   * @return array list of users (id => username)
   */
    public function listingComplete(array $params = array())
    {
        if (empty($this->users) || $forceUpdate) {
            $this->all($params);
        }
        $ret = array();
        if (is_array($this->users) && isset($this->users['users'])) {
            foreach ($this->users['users'] as $e) {
                $fullName = strtolower($e['firstname'] . ' ' . $e['lastname']);
                $ret[$fullName] = (int) $e['id'];
            }
        }

        return $ret;
    }

    /**
   * Get a user id given its first+last name.
   *
   * @param string $name
   *
   * @return integer|boolean
   */
    public function getIdByFirstLastName($name, array $params = array())
    {
        if (!isset($params['limit'])) {
            // Force to an higher number just to be sure to retrieve all users.
            $params['limit'] = 100;
        }
        $name = strtolower($name);
        $arr = $this->listingComplete($params);
        if (!isset($arr[$name])) {
            return false;
        }
        return $arr[(string) $name];
    }
}

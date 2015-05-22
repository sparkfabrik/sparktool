<?php

/*
 * This file is part of the Spark tool package.
 *
 * (c) Paolo Mainardi <paolo.mainardi@sparkfabrik.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sparkfabrik\Tools\Spark\Helpers\Traits\Command\Redmine;

trait RedmineSearchTrait
{
     /**
      * Returns an array of users with id/name+lastname pairs.
      *
      * @param boolean $forceUpdate to force the update of the users var
      *
      * @return array list of users (id => username)
      */
    public function redmineUsersObjectToFirstLastname($users)
    {
        $ret = array();
        foreach ($users['users'] as $e) {
            $fullName = strtolower($e['firstname'] . ' ' . $e['lastname']);
            $ret[$fullName] = (int) $e['id'];
        }
        return $ret;
    }


    /**
   * List users.
   *
   * @link http://www.redmine.org/projects/redmine/wiki/Rest_Users#GET
   *
   * @param object $client Redmine client
   *
   * @param array $projectId Redmine projectid used to get users
   * from membership if current user is not admin
   *
   * @param array $params to allow offset/limit (and more) to be passed
   *
   * @return array list of users found
   */
    public function redmineUsersGetAll($client, $projectId, array $params = array())
    {
        $users = array(
          'users' => array(),
        );
        if ($this->currentUserIsAdmin($client)) {
            $users = $client->api('user')->all($params);
        } else {
            $memberships = $client->api('membership')->all($projectId, $params);
            if (is_array($memberships) && isset($memberships['memberships'])) {
                foreach ($memberships['memberships'] as $member) {
                    $user = $client->get('/users/'.urlencode($member['user']['id']).'.json');
                    if (is_array($user) && isset($user['user'])) {
                        $users['users'][$member['user']['id']] = $user['user'];
                    }
                }
            }
        }
        return $users;
    }

    /**
     * Return admin status of the current user.
     *
     * @param object $client Redmine client
     *
     * @return bool
     */
    public function currentUserIsAdmin($client)
    {
        $current = $client->api('user')->getCurrentUser();

        if (is_array($current) && isset($current['user']) && isset($current['user']['status'])) {
            return true;
        }
        return false;
    }
}

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
}

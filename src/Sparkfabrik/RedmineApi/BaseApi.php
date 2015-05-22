<?php

/**
 * This file is part of the Spark tool package.
 *
 * (c) Paolo Mainardi <paolo.mainardi@sparkfabrik.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sparkfabrik\Tools\Spark\RedmineApi;

use Redmine\Api\AbstractApi;

class BaseApi extends AbstractApi {

    /**
     * Retrieves all the elements of a given endpoint (even if the
     * total number of elements is greater than 100) given external query.
     *
     * @param string $endpoint API end point
     * @param string $query External query.
     *
     * @return array elements found
     */
    protected function retrieveAllByQuery($endpoint, $query, $limit=25, $offset=0) {
        if (empty($query)) {
            return $this->get($endpoint);
        }

        $ret = array();

        while ($limit > 0) {
            if ($limit > 100) {
                $_limit = 100;
                $limit -= 100;
            } else {
                $_limit = $limit;
                $limit = 0;
            }
            $params['limit'] = $_limit;
            $params['offset'] = $offset;

            $newDataSet = (array) $this->get($endpoint . '?' . http_build_query($params) . $query);
            $ret = array_merge_recursive($ret, $newDataSet);

            $offset += $_limit;
            if (empty($newDataSet) || !isset($newDataSet['limit']) || (
                isset($newDataSet['offset']) &&
                isset($newDataSet['total_count']) &&
                $newDataSet['offset'] >= $newDataSet['total_count']
                )
            ) {
                $limit = 0;
            }
        }

        return $ret;
    }
}

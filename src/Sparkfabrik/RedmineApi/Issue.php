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

use Redmine\Api\Issue as RedmineIssue;
use Sparkfabrik\Tools\Spark\RedmineApi\BaseApi;

class Issue extends BaseApi {

    private $issues = array();
    private $endpoint = '/issues.json';

    /**
     * Filter issues by multiple subjects.
     *
     * @param array $subjects Issue Subjects for filtering.
     * @param array $params Additional request parameters.
     */
    public function getByMultipleSubjects($subjects, $params) {

        $ret = array();
        $totalCount = 0;
        $offset = (empty($params['offset'])) ? 0 : $params['offset'];
        $limit = (empty($params['limit'])) ? 0 : $params['limit'];

        $params['f[]'] = 'subject';
        $params['op[subject]'] = '~';

        foreach ($subjects as $subject) {
            $params['v[subject][]'] = $subject;

            $built_params = http_build_query($params);
            $built_params = preg_replace('/%5B[0-9]+%5D/simU', '', $built_params);

            $newDataSet = (array) $this->retrieveAllByQuery($this->endpoint, $built_params);
            $ret = array_merge_recursive($ret, $newDataSet);

            $totalCount += $newDataSet['total_count'];
            $offset += $newDataSet['offset'];
            $limit += $newDataSet['limit'];
        }

        $ret['total_count'] = $totalCount;
        $ret['offset'] = $offset;
        $ret['limit'] = $limit;

        return $ret;
    }

}

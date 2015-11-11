<?php

/*
 * This file is part of the Spark tool package.
 *
 * (c) Paolo Mainardi <paolo.mainardi@sparkfabrik.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sparkfabrik\Tools\Spark\Tests\Services\CacheService;

use Symfony\Component\Console\Application;
use Sparkfabrik\Tools\Spark\Services\CacheService;
use Symfony\Component\Console\Output\OutputInterface;

class CacheServiceTest extends \PHPUnit_Framework_TestCase
{

    private $cacheService;

    protected function setUp()
    {
        $this->cacheService = new CacheService();
    }

    /**
     * Test cache class.
     */
    public function testCacheServiceClass()
    {
        $data = 'abcedfgh';
        $this->cacheService->setData('cached_data', $data);
        $cached_data = $this->cacheService->getData('cached_data');
        $this->assertEquals($data, $cached_data);
    }
}

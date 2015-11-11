<?php

/*
 * This file is part of the Spark tool package.
 *
 * (c) Paolo Mainardi <paolo.mainardi@sparkfabrik.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sparkfabrik\Tools\Spark\Services;

use Zend\Cache\StorageFactory;

/**
 * Class to provide a common cache service for the application.
 *
 * @author Vincenzo Di Biaggio <vincenzo.dibiaggio@sparkfabrik.com>
 *
 * @api
 */
class CacheService
{
    protected $client;

    /**
     * [__construct description]
     */
    public function __construct()
    {
        if (empty($this->client)) {
            $cache = StorageFactory::factory(array(
                'adapter' => array(
                    'name'    => 'filesystem',
                    'options' => array('ttl' => 3600, 'cache_dir' => sys_get_temp_dir()),
                    'namespace' => 'sparktool'
                ),
                'plugins' => array(
                    'exception_handler' => array('throw_exceptions' => true),
                ),
            ));

            $this->client = $cache;
        }
    }

    public function getClient()
    {
        return $this->client;
    }

    /**
     * Get cached data identified with a placeholder
     * @param  string $placeholder Cache data identifier
     * @return mixed|null
     */
    public function getData($placeholder)
    {
        return $this->client->getItem($placeholder);
    }

    /**
     * Save data identified with a placeholder
     * @param  string $placeholder Cache data identifier
     * @param  mixed $data Data to store into cache
     *
     * @return bool
     */
    public function setData($placeholder, $data)
    {
        return $this->client->setItem($placeholder, $data);
    }
}

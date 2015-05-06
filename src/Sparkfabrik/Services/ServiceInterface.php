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

/**
 * ServiceInterfaces is the interface implemented by all Service classes.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @api
 */
interface ServiceInterface
{
    /**
     * Get the service object.
     *
     * @api
     */
    public function getClient();

    /**
     * Set the service object.
     *
     * @api
     */
    public function setClient($client);

    /**
     * Set the configuration object.
     *
     * @api
     */
    public function getConfig();

    /**
     * Get the configuration object.
     *
     * @api
     */
    public function setConfig($config);
}

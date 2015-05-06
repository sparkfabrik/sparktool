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
 * Base abstract class for all commands.
 *
 * @author Paolo Mainardi <paolo.mainardi@sparkfabrik.com>
 *
 * @api
 */
abstract class AbstractService implements ServiceInterface
{
  protected $config = array();
  protected $client;

  /**
   * Init configuration manager.
   *
   * @api
   */
  protected abstract function initConfig();

  /**
   * Init client.
   *
   * @api
   */
  protected abstract function initClient();

  /**
   * Instantiate config and client.
   *
   * @api
   */
  public function run() {
    $this->initConfig();
    $this->initClient();
  }

  /**
   * {@inheritDoc}
   *
   * @api
   */
  public function getClient() {
    if (empty($this->client)) {
      $this->initClient();
    }
    return $this->client;
  }

  /**
   * {@inheritDoc}
   *
   * @api
   */
  public function setClient($client) {
    $this->client = $client;
  }

  /**
   * {@inheritDoc}
   *
   * @api
   */
  public function getConfig() {
    if (empty($this->config)) {
      $this->initConfig();
    }
    return $this->config;
  }

  /**
   * Set service configuration.
   *
   * @api
   */
  public function setConfig($config) {
    $this->config = $config;
  }
}

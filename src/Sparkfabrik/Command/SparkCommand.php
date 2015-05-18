<?php

/*
 * This file is part of the Spark tool package.
 *
 * (c) Paolo Mainardi <paolo.mainardi@sparkfabrik.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sparkfabrik\Tools\Spark\Command;
use Symfony\Component\Console\Command\Command;
use Sparkfabrik\Tools\Spark\SparkConfigurationWrapper;

/**
 * Base abstract class for all commands.
 *
 * @author Paolo Mainardi <paolo.mainardi@sparkfabrik.com>
 *
 * @api
 */
abstract class SparkCommand extends Command
{
  protected $service;
  protected abstract function initService();

  /**
   * Return redmine service.
   */
  public function getService() {
    if (empty($this->service)) {
      $this->initService();
    }
    return $this->service;
  }

  /**
   * Set redmine service.
   */
  public function setService($service) {
    $this->service = $service;
  }

}

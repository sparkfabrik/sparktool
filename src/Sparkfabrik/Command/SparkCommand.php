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
    abstract protected function initService();

    /**
   * Return redmine service.
   */
    public function getService()
    {
        if (empty($this->service)) {
            $this->initService();
        }
        return $this->service;
    }

    /**
   * Set redmine service.
   */
    public function setService($service)
    {
        $this->service = $service;
    }

    /**
     * Insert a custom field in the default array of Fields in the desired place.
     *
     * @param  mixed[]        $fields   Originary field array
     * @param  string[]       $newField The new field with the format 'field_name|Label'
     * @param  boolean|string $position FALSE puts the field at the end. Otherwise use
     *                                  field_name to insert new field after the specified field.
     * @return mixed[] New field structure.
     */
    public function insertCustomFieldInOutput($fields, $newField, $position = false)
    {
        $fieldData = explode('|', $newField);
        if ($position === false) {
            array_push($fields, $fieldData);
        } else {
            $position = array_search($position, array_keys($fields));
            $fieldDataLabel[$fieldData[0]] = $fieldData[1];
            $fields = array_slice($fields, 0, $position + 1, true) +
                $fieldDataLabel +
                array_slice($fields, $position, count($fields) - $position, true);
        }

        return $fields;
    }
}

<?php

namespace Fiedsch\SqliteManager;

/**
 * Class ColumnConfguration
 * A Helper class for storing and validating daatbase column configurations
 *
 * @package Fiedsch\SqliteManager
 */
class ColumnConfguration
{
    /**
     * @var string
     */
    const KEY_TYPE = 'type';

    /**
     * @var string
     */
    const KEY_UNIQUE = 'unique';

    /**
     * @var string
     */
    const KEY_MANDATORY = 'mandatory';

    /**
     * @var string
     */
    const KEY_DEFAULT = 'default';

    /**
     * @var array Allowd SQLite column types ("affinities")
     */
    const ALLOWED_TYPES = ['INTEGER', 'TEXT', 'BLOB', 'REAL', 'NUMERIC'];

    /**
     * @var string
     */
    const DEFAULT_TYPE = 'TEXT';

    /**
     * @var boolean
     */
    const DEFAULT_MANDATORY = false;

    /**
     * @var boolean
     */
    const DEFAULT_UNIQUE = false;

    /**
     * @var null|string|integer
     */
    const DEFAULT_DEFAULT = null;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var array
     */
    protected $errors;

    /**
     * ColumnConfguration constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->errors = [];
        $this->cleanConfig();
        $this->augmentConfig();
        $this->checkConfig();
    }

    /**
     * Clean the configuration
     * - fix case of key names
     * - "convert" values containing strings like 'true' or 'false' to true or false
     */
    protected function cleanConfig()
    {
        // map spelling of keys to what is defined in this class's constants
        foreach ([self::KEY_TYPE, self::KEY_MANDATORY, self::KEY_UNIQUE, self::KEY_DEFAULT] as $key) {
            foreach (array_keys($this->config) as $originalKey) {
                // same same but different ;-)
                if (strtolower($key) === strtolower($originalKey) && $key !== $originalKey) {
                    if (isset($this->config[$key])) {
                        $this->errors[] = sprintf("both '%s' and '%s' were specified. removing '%s'",
                            $originalKey, $key, $originalKey);
                        unset($this->config[$originalKey]);
                    } else {
                        $this->config[$key] = $this->config[$originalKey];
                        unset($this->config[$originalKey]);
                    }
                }
            }
        }

        // map value of 'type' to UPPERCASE
        if (isset($this->config[self::KEY_TYPE])) {
            $this->config[self::KEY_TYPE] = strtoupper($this->config[self::KEY_TYPE]);
        }

        // convert booleans (if 'true' or 'false' are found, make them true or false respectively)
        foreach ([self::KEY_MANDATORY, self::KEY_UNIQUE] as $key) {
            $map = [
                'true'  => true,
                'false' => false,
            ];
            if (isset($this->config[$key]) && is_string($this->config[$key]) &&
                in_array(strtolower($this->config[$key]), array_keys($map))
            ) {
                $this->config[$key] = $map[strtolower($this->config[$key])];
            }
        }
        // convert string 'null' to null for default values
        if (isset($this->config[self::KEY_DEFAULT]) &&
            is_string($this->config[self::KEY_DEFAULT]) &&
            strtolower($this->config[self::KEY_DEFAULT]) === 'null'
        ) {
            $this->config[self::KEY_DEFAULT] = null;
        }
    }

    /**
     * Augment the configuration, i.e. add entries for optional
     * settings and set default values. By doing so we do not have
     * to care if a value "isset()" or letter care for case of keys
     * when working with the config.
     */
    protected function augmentConfig()
    {
        if (!isset($this->config[self::KEY_TYPE])) {
            $this->config[self::KEY_TYPE] = self::DEFAULT_TYPE;
        }
        if (!isset($this->config[self::KEY_MANDATORY])) {
            $this->config[self::KEY_MANDATORY] = self::DEFAULT_MANDATORY;
        }
        if (!isset($this->config[self::KEY_UNIQUE])) {
            $this->config[self::KEY_UNIQUE] = self::DEFAULT_UNIQUE;
        }
        if (!isset($this->config[self::KEY_DEFAULT])) {
            $this->config[self::KEY_DEFAULT] = self::DEFAULT_DEFAULT;
        }
    }

    /**
     * Check the configuration
     */
    protected function checkConfig()
    {
        $this->checkType();
        $this->checkUnique();
        $this->checkMandatory();
        $this->checkDefault();
        $this->checkUniqueAndDefault();
    }

    /**
     * Ceck that 'type' has a valid value
     */
    protected function checkType()
    {
        if (!in_array($this->config[self::KEY_TYPE], self::ALLOWED_TYPES)) {
            $this->errors[] = sprintf("setting 'type to '%s' is not supported",
                $this->config[self::KEY_TYPE]
            );
        }
    }

    /**
     * After cleaning and augmentig the config this setting (which requires
     * a boolean value)  hase to be either true or false.
     */
    protected function checkUnique()
    {
        if (!is_bool($this->config[self::KEY_UNIQUE])) {
            $this->errors[] = sprintf("value for setting '%s' is not correct (expected true or false)",
                self::KEY_UNIQUE
            );
        }
    }

    /**
     * After cleaning and augmentig the config this setting (which requires
     * a boolean value)  hase to be either true or false.
     */
    protected function checkMandatory()
    {
        if (!is_bool($this->config[self::KEY_MANDATORY])) {
            $this->errors[] = sprintf("value for setting '%s' is not correct (expected true or false)",
                self::KEY_MANDATORY
            );
        }
    }

    /**
     *
     */
    protected function checkDefault()
    {
        // nothing to do as the default can contain pretty much anything
    }

    /**
     * Requiring a unique column with a default value does (other than null)
     * not make sense
     */
    protected function checkUniqueAndDefault()
    {
        if ($this->config[self::KEY_UNIQUE] && $this->config[self::KEY_DEFAULT] !== null) {
            $this->errors[] = sprintf("requiring a unique column with a default value '%s' does not make sense",
                $this->config[self::KEY_DEFAULT]
                );
        }
    }

    /**
     * @return array the augmented and checked configuration or null if the configuration
     * contained errors
     */
    public function getConfiguration()
    {
        return $this->config;
    }

    /**
     * Did the configuration have errors?
     *
     * @return bool
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * An array of configuration error messages.
     * Empty if there were no errors.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

}

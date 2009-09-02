<?php

//Disable direct view.
if (!defined('IN_PLUGIN'))
    die('You can not access this file directly.');

/**
 * Registry class.
 *
 * @author Marijan Šuflaj <msufflaj32@gmail.com>
 * @category Tqe
 * @package Tqe_Registry
 */
class Tqe_Registry
{

    /**
     * Registry elements.
     *
     * @var array
     */
    private static $_regElements        = array(
        'default'   => array()
    );



    /**
     * Returns value from registry.
     *
     * @param string|int $key Value key that is retreived
     * @param string|int $nameSpace Namespace where $key is stored
     * @return mixed Value
     * @throws Exception If $nameSpace does not exist
     * @throws Exception If $key does not exist in $nameSpace
     */
    public static function get($key, $nameSpace = 'default')
    {
        if (isset(self::$_regElements[$nameSpace])) {
            if (isset(self::$_regElements[$nameSpace][$key]))
                return self::$_regElements[$nameSpace][$key];
            else
                throw new Exception(sprintf('`%s` entry does not exist in `%s` namespace.', $key, $nameSpace));
        }
        else
            throw new Exception(sprintf('`%s` namespace does not exist registry.', $nameSpace));
    }

    /**
     * Saves value to registry.
     *
     * @param string|int $key Key name that is used to save value
     * @param mixed $value Value that will be saved
     * @param string|int $nameSpace Namespace where value will be stored
     * @throws Exception If $key is not integer or string
     * @throws Exception If $nameSpace is not integer or string
     * @throws Exception If $key exists in $nameSpace
     */
    public static function save($key, $value, $nameSpace = 'default')
    {
        if (!is_string($key) && !is_int($key))
            throw new Exception('Key can only be integer or string.');

        if (!is_string($nameSpace) && !is_int($nameSpace))
            throw new Exception('Namespace can only be integer or string.');

        if (isset(self::$_regElements[$nameSpace])) {
            if (isset(self::$_regElements[$nameSpace][$key]))
                throw new Exception(sprintf('Key `%s` exist in `%s` namespace. Use Tqe_Registry::update() instead.', $key, $nameSpace));
            else
                self::$_regElements[$nameSpace][$key] = $value;
        }
        else
            self::$_regElements[$nameSpace][$key] = $value;
    }

    /**
     * Updates value in registry
     *
     * @param string|int $key Key name that is used to update value
     * @param mixed $value Value that will be saved
     * @param string|int $nameSpace Namespace where value is stored
     * @throws Exception If $nameSpace does not exist
     * @throws Exception If $key does not exists in $nameSpace
     */
    public static function update($key, $value, $nameSpace)
    {
        if (isset(self::$_regElements[$nameSpace])) {
            if (isset(self::$_regElements[$nameSpace][$key])) {
                $return = self::$_regElements[$nameSpace][$key];
                self::$_regElements[$nameSpace][$key] = $value;
                return $return;
            }
            else
                throw new Exception(sprintf('Key `%s` does not exist in `%s` namespace. Use Tqe_Registry::save() instead.', $key, $nameSpace));
        }
        else
            throw new Exception(sprintf('`%s` namespace does not exist in registry.', $nameSpace));
    }

    /**
     * Deletes value from registry
     *
     * @param string|int $key Key name that is used to delete value
     * @param string|int $nameSpace Namespace from where value will be deleted
     * @throws Exception If $nameSpace does not exist
     * @throws Exception If $key does not exists in $nameSpace
     */
    public static function delete($key, $nameSpace)
    {
        if (isset(self::$_regElements[$nameSpace])) {
            if (isset(self::$_regElements[$nameSpace][$key]))
                unset(self::$_regElements[$nameSpace][$key]);
            else
                throw new Exception(sprintf('`%s` entry does not exist in `%s` namespace. Nothing to delete.', $key, $nameSpace));
        }
        else
            throw new Exception(sprintf('`%s` namespace does not exist registry. Nothing to delete.', $nameSpace));
    }
}
<?php

//Disable direct view.
if (!defined('IN_PLUGIN'))
    die('You can not access this file directly.');

/**
 * Config class.
 *
 * @author Marijan Šuflaj <msufflaj32@gmail.com>
 * @category Tqe
 * @package Tqe_Config
 */
class Tqe_Config
{

    /**
     * Namespace used in Tqe_Registry to store data for WP config.
     *
     * @var string
     */
    private static $_wpNameSpace        = 'wpConfig';

    /**
     * Namespace used in Tqe_Registry to store data for TQE config.
     *
     * @var string
     */
    private static $_tqeNameSpace       = 'tqeConfig';

    /**
     * Returns config from WordPress.
     *
     * @param string $name Option name
     * @return mixed Option value
     * @throws Exception If unable to load WordPress option
     */
    public static function getWpConfig($name)
    {
        try {
            return Tqe_Registry::get($name, self::$_wpNameSpace);
        }
        catch (Exception $return) {
            if (($return = get_option($name)) === false)
                throw new Exception(sprintf('Unable to load WordPress option `%s`.', $name), WP_OPTION_ERROR);

            Tqe_Registry::save($name, $return, self::$_wpNameSpace);
        }

        return $return;
    }

    /**
     * Updates option in WordPress.
     *
     * @param string $name Option name
     * @param mixed $value Option value
     * @throws Exception If unable to update WordPress option
     */
    public static function updateWpConfig($name, $value)
    {
        if (Tqe_Config::getWpConfig($name) !== sanitize_option($name, $value)) {

            if (!update_option($name, $value))
                throw new Exception(sprintf('Unable to save WordPress option `%s`.', $name), WP_OPTION_ERROR);

            Tqe_Registry::update($name, $value, self::$_wpNameSpace);
        }
    }

    /**
     * Deletes WordPress otpion.
     *
     * @param string $name Option name
     * @returns Exception If unable to delete WordPress option
     */
    public static function deleteWpConfig($name)
    {
        if (!delete_option($name))
            throw new Exception(sprintf('Unable to delete WordPress option `%s`.', $name), WP_OPTION_ERROR);

        Tqe_Registry::delete($name, self::$_wpNameSpace);
    }

    /**
     * Inserts WordPress option.
     *
     * @param string $name Option name
     * @param mixed $value Option value
     * @param string $autoLoad Yes if option is autoloaded, No if not autoloaded
     * @param string $deprecated String if it's deprecated
     * @throws Exception If unable to insert WordPress option
     */
    public static function insertWpConfig($name, $value, $autoLoad = 'no', $deprecated = '')
    {
        add_option($name, $value, $deprecated, $autoLoad);

        Tqe_Registry::save($name, $value, self::$_wpNameSpace);
    }

    /**
     * Returns config.
     *
     * @param string $name Option name
     * @return string Option value
     * @throws Exception If unable to load option
     */
    public static function getConfig($name)
    {
        try {
            return Tqe_Registry::get($name, self::$_tqeNameSpace);
        }
        catch (Exception $result) {
            global $wpdb;

            $sql = 'SELECT `value` '
            . 'FROM `' . $wpdb->qe_settings . '`'
            . 'WHERE `name` = %s '
            . 'AND userid = %d '
            . 'LIMIT 1';

            if (($result = $wpdb->query($wpdb->prepare($sql, $name, Tqe_Registry::get('userInfo')->ID))) === false)
                throw new Exception('Could not retrieve config value from database.');

            if ($result < 1)
                throw new Exception(sprintf('Config entry with key `%s` does not exist.', $name));

            Tqe_Registry::save($name, $wpdb->last_result[0]->value, self::$_tqeNameSpace);

        }

        return $wpdb->last_result[0]->value;
    }

    /**
     * Updates option.
     *
     * @param string $name Option name
     * @param mixed $value Option value
     * @throws Exception If unable to update option
     */
    public static function updateConfig($name, $value)
    {
        try {
            return Tqe_Registry::get($name, self::$_tqeNameSpace);
        }
        catch (Exception $result) {
            global $wpdb;

            $sql = 'SELECT `id` '
            . 'FROM `' . $wpdb->qe_settings . '`'
            . 'WHERE `name` = %s '
            . 'AND userid = %d '
            . 'LIMIT 1';

            if (($result = $wpdb->query($wpdb->prepare($sql, $name, Tqe_Registry::get('userInfo')->ID))) === false)
                throw new Exception('Could not check if entry exists.');

            if ($result < 1)
                throw new Exception(sprintf('Config entry with key `%s` does not exist.', $name));

            if ($wpdb->update($wpdb->qe_settings, array(
                'value' => $value
            ), array(
                'id' => $wpdb->last_result[0]->id
            ), array('%s'), array('%d')) === false)
                throw new Exception('Could not update config value in database.');

            Tqe_Registry::save($name, $value, self::$_tqeNameSpace);

        }

        return $wpdb->last_result[0]->value;
    }
}
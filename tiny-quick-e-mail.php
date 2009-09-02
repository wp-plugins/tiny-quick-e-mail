<?php
/**
 * @author Marijan Šuflaj <msufflaj32@gmail.com>
 * @link http://www.php4every1.com
 */

/*
    Plugin Name: Tiny Quick E-mail
    Plugin URI: http://php4every1.com/scripts/tiny-quick-email-wordpress-plugin/
    Description: Plugin that enables you to send e-mails quickly from your wordpress. You can create categories, sort your users and each admin on your blog can have its own address book with its own settings.
    Version: 0.8.30
    Author: Marijan Šuflaj
    Author URI: http://www.php4every1.com
*/

/*
    Copyright 2009  Marijan Šuflaj  (email : msufflaj32@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

//Used for preventing direct view.
define('IN_PLUGIN', true);

//Shorter DIRECTORY_SEPARATOR string.
define('DS', DIRECTORY_SEPARATOR);

//Current version.
define('CURR_VER', '0.8.30');

//Real path
define('WP_ROOT_FOLDER', realpath(dirname(__FILE__) . '/../../../'));

//Templates directory.
define('TQE_TEMPLATES_DIR', realpath(dirname(__FILE__) . '/templates'));

//To determine if WP error
define('WP_OPTION_ERROR', 1024);

//Main path like http://domain.com/wp-content/plugins/tiny-quick-e-mail.
define('ROOT_URL_PATH', get_bloginfo('url') . '/wp-content/plugins/tiny-quick-e-mail');

//AJAX request url.
define('AJAX_URL', get_bloginfo('url') . '/wp-admin/?ajax');

//Set include path
set_include_path(
    realpath(__FILE__)
    . PATH_SEPARATOR
    . get_include_path()
);

/**
 * Autoload class. If class starts with 'Tqe' then load it.
 *
 * @param string $class Class name
 * @return bool False
 */
function __autoload($class)
{
    if (substr($class, 0, 3) === 'Tqe')
        require_once str_replace('_', DS, $class) . '.php';

    return false;
}

/**
 * Returns $_GET value or false if $key not presented.
 *
 * @var string $key Key in $_GET array
 * @return string|bool $key value or false if $key not presented
 */
function retGet($key)
{
    return isset($_GET[$key]) ? $_GET[$key] : false;
}

/**
 * Returns $_POST value or false if $key not presented.
 *
 * @var string $key Key in $_POST array
 * @return string|bool $key value or false if $key not presented
 */
function retPost($key)
{
    return isset($_POST[$key]) ? $_POST[$key] : false;
}

try {
    //Gets instance
    $tinyQe =& Tqe_Tqe::getInstance();

    //Initialize
    $tinyQe->initialize();

} catch (Exception $e) {

    //If there is exception and we are in AJAX return it
    if (is_object($tinyQe) && $tinyQe->isAjax()) {
        $tinyQe->dumpError(array(
            'exception' => $e->getMessage()
        ));
    //Else just log error
    } else
        error_log(
            spritf("%s : %s  [%s:%d]\n", date_i18n('Y-m-d H:i:s'), $e->getMessage(), $e->getFile(), $e->getLine())
            , 3, 'error.log'
        );
}
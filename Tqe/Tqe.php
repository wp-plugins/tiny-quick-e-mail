<?php

//Disable direct view.
if (!defined('IN_PLUGIN'))
    die('You can not access this file directly.');

/**
 * Main class for Tiny Quick Email plugin.
 *
 * @author Marijan Šuflaj <msufflaj32@gmail.com>
 * @category Tqe
 * @package Tqe_Tqe
 */
class Tqe_Tqe
{

    /**
     * Class instance.
     *
     * @var Tqe_Tqe
     */
    private static $_instance       = null;

    /**
     * Dashboard flag.
     *
     * @var bool
     */
    private $_isDashboard           = false;

    /**
     * Returns class instance.
     *
     * @return Tqe_Tqe Tqe_Tqe class instance
     */
    public static function getInstance()
    {
        if (is_null(self::$_instance))
            self::$_instance = new Tqe_Tqe();

        return self::$_instance;
    }

    /**
     * Constructor.
     *
     */
    private function __construct()
    {
        global $wpdb;

        //Register tables
        $wpdb->qe_abook = $wpdb->prefix . 'qe_abook';
        $wpdb->qe_mails = $wpdb->prefix . 'qe_mails';
        $wpdb->qe_cats = $wpdb->prefix . 'qe_cats';
        $wpdb->qe_settings = $wpdb->prefix . 'qe_settings';

        if ($this->isAjax()) {
            define('DOING_AJAX', true);
            define('WP_ADMIN', true);

            require_once WP_ROOT_FOLDER . DS . 'wp-load.php';
            require_once WP_ROOT_FOLDER . DS . 'wp-admin' . DS . 'admin.php';
        }

        //Functions missing?
        if (!function_exists('get_currentuserinfo') && !function_exists('is_user_logged_in'))
            require_once WP_ROOT_FOLDER . DS . 'wp-includes' . DS . 'pluggable.php';

        if (is_user_logged_in()) {
            global $current_user;

            get_currentuserinfo();

            Tqe_Registry::save('userInfo', $current_user);
        }
        else
            Tqe_Registry::save('userInfo', false);

    }


    /**
     * Initialization method.
     *
     * @return bool True on success, false if not enabled
     */
    public function initialize()
    {
        //User can do this?
        $user = Tqe_Registry::get('userInfo');
        if ($user && (!array_key_exists('administrator', $user->{$user->cap_key}) || !$user->{$user->cap_key}['administrator']))
            return false;

        //Need install or update?
        if ($this->_needInstall())
            $this->_install();

        if(!$this->_hasAllSettings())
            $this->_insertSettingsData();

        /*if ($this->_needUpdate())
            $this->_update();*/


        //Are we in AJAX?
        if ($this->isAjax()) {

            if ((int) Tqe_Registry::get('userInfo')->user_level < 9)
                throw new Exception('You can not access this page.');

            if (!is_user_logged_in())
                throw new Exception('You need to log in first.');

            //If in AJAX do not display errors.
            //error_reporting(0);

            $ajax = new Tqe_Ajax();

            if (!$ajax->isValid())
                throw new Exception('Your AJAX call is not valid.');

            $obj = $ajax->execute(retGet('class'));

            $ajax->buildResponse($obj);

            $ajax->send();
        }
        else {

            if (($user = Tqe_Registry::get('userInfo')) !== false) {
                $temp = Tqe_Config::getWpConfig('tqeUserSessions');
                $temp[$user->ID] = array();
                Tqe_Config::updateWpConfig('tqeUserSessions', $temp);
            }

            //Setup admin menus
            add_action('admin_menu', array($this, 'addAdminMenu'));

            //Setup dashboard widget
            add_action('wp_dashboard_setup', array($this, 'addDashBoard'));

            //Load style and script (add 999 priority to make sure it's loaded last)
            add_action('admin_head', array($this, 'printHead'), 999);
        }

        return true;
    }

    /**
     * Dumps Exception and sends it.
     *
     * @param string $error Error message
     */
    public function dumpError($error)
    {
        $errorObj = new Tqe_Ajax_Result();
        $errorObj->addErrorMsg($error);

        $ajax = new Tqe_Ajax();
        $ajax->buildResponse($errorObj);
        $ajax->send();
    }

    /**
     * Adds admin menu pages.
     *
     */
    public function addAdminMenu()
    {
        add_menu_page('Tiny QE Settings', 'Tiny QE', 8, 'settings');
        add_submenu_page('settings', 'Tiny QE Settings', 'Settings', 8, 'settings', array($this, 'buildSettings'));
        //add_submenu_page('settings', 'Tiny QE Backup', 'Backup', 8, 'backup', array($this, 'buildBackup'));
        add_submenu_page('settings', 'Tiny QE Uninstall', 'Uninstall', 8, 'uninstall', array($this, 'buildUninstall'));
    }

    /**
     * Checks if this is ajax request
     */
    public function isAjax()
    {
        return retGet('ajax') !== false;
    }

    /**
     * Adds dashboard widget.
     *
     */
    public function addDashBoard()
    {
        wp_add_dashboard_widget('tiny_qe', 'Tiny Quick E-mail', array($this, 'buildDashBoard'));
        $this->_isDashboard = true;
    }

    /**
     * Builds dashboard.
     *
     */
    public function buildDashBoard()
    {
        $data = new stdClass();
        $data->delay = Tqe_Config::getConfig('autoSaveEvery');
        $template = new Tqe_Template(TQE_TEMPLATES_DIR, $data);
        $template->assignFile('widget.phtml')
            ->render();
    }

    /**
     * Builds settings.
     *
     */
    public function buildSettings()
    {
        $data = new stdClass();
        $data->displayContacts = (int) Tqe_Config::getConfig('displayContacts');
        $data->displayCategories = (int) Tqe_Config::getConfig('displayCategories');
        $data->displayEmails = (int) Tqe_Config::getConfig('displayEmails');
        $data->autoSaveEvery = Tqe_Config::getConfig('autoSaveEvery');
        $data->deleteTrashAfter = Tqe_Config::getConfig('deleteTrashAfter');
        $template = new Tqe_Template(TQE_TEMPLATES_DIR, $data);
        $template->assignFile('menu/settings.phtml')
            ->render();
    }

    /**
     * Builds uninstall.
     *
     */
    public function buildUninstall()
    {
        $data = new stdClass();
        $template = new Tqe_Template(TQE_TEMPLATES_DIR, $data);
        $template->assignFile('menu/uninstall.phtml')
            ->render();
    }

    /**
     * Echoes styles and scripts.
     *
     */
    public function printHead()
    {
?>
<!-- Tiny QE Start -->

<!-- Tiny QE  Styles -->
<style type="text/css">
    @import url("<?php echo ROOT_URL_PATH ?>/css/main.css");
</style>

<!-- Tiny QE Scripts -->
<script type="text/javascript" src="<?php echo ROOT_URL_PATH ?>/js/lib/php.js.js"></script>
<script type="text/javascript" src="<?php echo ROOT_URL_PATH ?>/js/tinyQE.js"></script>
<?php if ($this->_isDashboard) : ?>
<script type="text/javascript" src="<?php echo ROOT_URL_PATH ?>/js/main.js"></script>
<?php else : ?>
<script type="text/javascript" src="<?php echo ROOT_URL_PATH ?>/js/menu.js"></script>
<?php endif; ?>
<!-- Tiny QE End -->
<?php
    }

    /**
     * Check if user has all settings.
     *
     * @return bool True if has, false if not
     */
    private function _hasAllSettings()
    {
        global $wpdb;

        $sql = 'SELECT `id` '
        . 'FROM `' . $wpdb->qe_settings . '` '
        . 'WHERE `userid` = %d';

        if (($result = $wpdb->query(sprintf($sql, Tqe_Registry::get('userInfo')->ID))) === false)
            throw new Exception('Could not check if user has all settings.');

        if ($result < 5) {

            $sql = 'DELETE `' . $wpdb->qe_settings . '` '
            . 'FROM `' . $wpdb->qe_settings . '` '
            . 'WHERE `userid` = %d';

            if (($result = $wpdb->query(sprintf($sql, Tqe_Registry::get('userInfo')->ID))) === false)
                throw new Exception('Could not delete settings.');

            return false;
        }

        return true;

    }

    /**
     * Checks if we need to install it.
     *
     * @return bool True if we need to install, false if not
     * @throws Exception Exception upon error
     */
    private function _needInstall()
    {
        try {
            Tqe_Config::getWpConfig('TQE_Version');
        }
        catch (Exception $e) {
            if ($e->getCode() === WP_OPTION_ERROR) {
                return true;
            }
            throw $e;
        }

        return false;
    }

    /**
     * Installs plugin.
     *
     */
    private function _install()
    {

        global $wpdb;

        Tqe_Config::insertWpConfig('TQE_Version', '0.8.30');
        Tqe_Config::insertWpConfig('tqeUserSessions', array());

        $sql = 'CREATE TABLE IF NOT EXISTS `%s` ( '
        . ' `id` int(11) NOT NULL AUTO_INCREMENT, '
        . '  `firstName` varchar(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, '
        . '  `middleName` varchar(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, '
        . '  `lastName` varchar(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, '
        . '  `email` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, '
        . '  `userid` int(11) NOT NULL, '
        . '  `category` int(11) NOT NULL, '
        . '  PRIMARY KEY (`id`), '
        . '  KEY `category` (`category`), '
        . '  KEY `userid` (`userid`) '
        . ') ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;';

        if ($wpdb->query(sprintf($sql, $wpdb->qe_abook)) === false)
            throw new Exception(sprintf('Could not create table `%s`.', $wpdb->qe_abook));

        $sql = 'CREATE TABLE IF NOT EXISTS `%s` ( '
        . '  `id` int(11) NOT NULL AUTO_INCREMENT, '
        . '  `name` varchar(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, '
        . '  `userid` int(11) NOT NULL, '
        . '  PRIMARY KEY (`id`), '
        . '  KEY `userid` (`userid`) '
        . ') ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;';

        if ($wpdb->query(sprintf($sql, $wpdb->qe_cats)) === false)
            throw new Exception(sprintf('Could not create table `%s`.', $wpdb->qe_cats));

        $sql = 'CREATE TABLE IF NOT EXISTS `%s` ( '
        . '  `id` int(11) NOT NULL AUTO_INCREMENT, '
        . '  `name` varchar(255) COLLATE utf8_bin NOT NULL, '
        . '  `value` longtext COLLATE utf8_bin NOT NULL, '
        . '  `userid` int(11) NOT NULL, '
        . '  PRIMARY KEY (`id`), '
        . '  KEY `name` (`name`) '
        . ') ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;';

        if ($wpdb->query(sprintf($sql, $wpdb->qe_settings)) === false)
            throw new Exception(sprintf('Could not create table `%s`.', $wpdb->qe_settings));


        $sql = 'CREATE TABLE IF NOT EXISTS `%s` ( '
        . '  `id` int(11) NOT NULL AUTO_INCREMENT, '
        . "  `userId` int(11) NOT NULL DEFAULT '0', "
        . '  `to` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, '
        . "  `useCc` tinyint(1) NOT NULL DEFAULT '0', "
        . '  `cc` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, '
        . "  `useBcc` tinyint(1) NOT NULL DEFAULT '0', "
        . '  `bcc` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, '
        . '  `subject` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, '
        . '  `message` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, '
        . "  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00', "
        . "  `updated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00', "
        . "  `deleted` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00', "
        . "  `type` int(11) NOT NULL DEFAULT '2', "
        . '  `usersInFields` longtext CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, '
        . '  PRIMARY KEY (`id`) '
        . ') ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;';

        if ($wpdb->query(sprintf($sql, $wpdb->qe_mails)) === false)
            throw new Exception(sprintf('Could not create table `%s`.', $wpdb->qe_mails));

        $this->_insertSettingsData();

    }

    /**
     * Inserts settings.
     *
     */
    private function _insertSettingsData()
    {
        global $wpdb;

        $id = Tqe_Registry::get('userInfo')->ID;

        ob_start();

        $sql = 'INSERT INTO `%s` (`id`, `name`, `value`, `userid`) VALUES '
        . "(null, 'displayCategories', '10', %2\$d), "
        . "(null, 'displayContacts', '10', %2\$d), "
        . "(null, 'autoSaveEvery', '10000', %2\$d), "
        . "(null, 'displayEmails', '10', %2\$d), "
        . "(null, 'deleteTrashAfter', '259200', %2\$d);";

        if ($wpdb->query(sprintf($sql, $wpdb->qe_settings, $id)) === false)
            throw new Exception(sprintf('Could not insert settings in `%s`.', $wpdb->qe_settings));
    }
}
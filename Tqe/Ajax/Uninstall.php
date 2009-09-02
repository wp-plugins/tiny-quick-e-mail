<?php

//Disable direct view.
if (!defined('IN_PLUGIN'))
    die('You can not access this file directly.');

class Tqe_Ajax_Uninstall implements Tqe_Ajax_Interface
{

    private $_result                = null;

    private $_toTemplate            = null;

    private $_urlArgs               = array();

    private $_historyId             = null;

    private $_available             = array(
        'uninstall'
    );

    public function __construct()
    {
        $this->_result = new Tqe_Ajax_Result();
    }

    public function buildResponse()
    {
        if (!$this->_isValidAction())
            throw new Exception('Your action is not valid.');

        call_user_func(array($this, '_' . $_GET['action']));
    }

    private function _isValidAction()
    {
        if (!isset($_GET['action']))
            return false;

        return in_array($_GET['action'], $this->_available);
    }

    public function getResponseObj()
    {
        return $this->_result;
    }

    private function _uninstall()
    {

        global $wpdb;

        $plugins = Tqe_Config::getWpConfig('active_plugins');

        foreach ($plugins as $k => $v) {
            if ($v === 'tiny-quick-email/tiny-quick-email.php') {
                unset($plugins[$k]);
                break;
            }
        }

        Tqe_Config::updateWpConfig('active_plugins', $plugins);

        Tqe_Config::deleteWpConfig('TQE_Version');

        Tqe_Config::deleteWpConfig('tqeUserSessions');

        $sql = 'DROP TABLE '
        . 'IF EXISTS `' . $wpdb->qe_mails . '`, `' . $wpdb->qe_cats . '`, `' . $wpdb->qe_abook . '`, `' . $wpdb->qe_settings . '`';

        if ($wpdb->query($sql) === false)
            throw new Exception(
                'Could not delete selected tables. '
                . 'Do you have permission to do that? '
                . 'Plugin deactivated.' . mysql_error()
            );


        $this->_result->noticeMessage('Tables removed from database. Go to `Plugins` and remove files from server.');
    }
}
<?php

//Disable direct view.
if (!defined('IN_PLUGIN'))
    die('You can not access this file directly.');

class Tqe_Ajax_Settings implements Tqe_Ajax_Interface
{

    private $_result                = null;

    private $_toTemplate            = null;

    private $_urlArgs               = array();

    private $_historyId             = null;

    private $_available             = array(
        'save', 'default'
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

    private function _save()
    {
        if (!retPost('displayContacts'))
            $this->_result->addErrorMsg(array('noDisplayContacts' => 'You did not provide value for number of displayed contacts'));

        if (!retPost('displayCategories'))
            $this->_result->addErrorMsg(array('noDisplayCategories' => 'You did not provide value for number of displayed categories'));

        if (!retPost('displayEmails'))
            $this->_result->addErrorMsg(array('noDisplayEmails' => 'You did not provide value for number of displayed e-mails'));

        if (!retPost('autoSaveEvery'))
            $this->_result->addErrorMsg(array('noAutoSaveEvery' => 'You did not provide value for auto save delay'));

        if (!retPost('deleteTrashAfter'))
            $this->_result->addErrorMsg(array('noDeleteTrashAfter' => 'You did not provide value trash cleaning'));

        if ($this->_result->isError())
                return ;

        Tqe_Config::updateConfig('displayContacts', retPost('displayContacts'));

        Tqe_Config::updateConfig('displayCategories', retPost('displayCategories'));

        Tqe_Config::updateConfig('displayEmails', retPost('displayEmails'));

        Tqe_Config::updateConfig('autoSaveEvery', retPost('autoSaveEvery'));

        Tqe_Config::updateConfig('deleteTrashAfter', retPost('deleteTrashAfter'));

        $this->_result->noticeMessage('Options saved.');

    }

    private function _default()
    {
        Tqe_Config::updateConfig('displayContacts', 10);

        Tqe_Config::updateConfig('displayCategories', 10);

        Tqe_Config::updateConfig('displayEmails', 10);

        Tqe_Config::updateConfig('autoSaveEvery', 10000);

        Tqe_Config::updateConfig('deleteTrashAfter', 259200);

        $this->_result->noticeMessage('Options restored to default default values.');

        $this->_result->customContent(array(
            'displayContacts'   => 10,
            'displayCategories' => 10,
            'displayEmails'     => 10,
            'autoSaveEvery'     => 10000,
            'deleteTrashAfter'  => 259200
        ));
    }
}
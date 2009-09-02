<?php

//Disable direct view.
if (!defined('IN_PLUGIN'))
    die('You can not access this file directly.');

class Tqe_Ajax_Result
{

    private $_error              = false;

    private $_errorMsg           = array();

    private $_content            = '';

    private $_notice             = '';

    private $_custom             = array();



    public function __construct()
    {

    }

    public function isError()
    {
        return $this->_error;
    }

    public function noticeMessage($notice = null)
    {
        if (is_null($notice))
            return $this->_notice;

        $this->_notice = $notice;
        return true;
    }

    public function customContent($content = null) {
        if (is_null($content))
            return $this->_custom;

        if (!is_array($content))
            throw new Exception('Invalid content passed to Tqe_Ajax_Result object.');

        foreach ($content as $code => $message)
            $this->_custom[$code] = $message;

        return true;
    }

    public function body($content = null)
    {
        if (is_null($content))
            return $this->_content;

        $this->_content = $content;

        return true;
    }

    public function getMessages()
    {
        return $this->_errorMsg;
    }

    public function getMessage($code)
    {
        return isset($this->_errorMsg[$code]) ? $this->_errorMsg[$code] : false;
    }

    public function addErrorMsg($msg)
    {
        if (!is_array($msg))
            throw new Exception('Invalid error message passed to Tqe_Ajax_Result object.');

        $this->_error = true;

        foreach ($msg as $code => $message)
            $this->_errorMsg[$code] = $message;
    }
}
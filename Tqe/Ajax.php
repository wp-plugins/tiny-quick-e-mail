<?php

//Disable direct view.
if (!defined('IN_PLUGIN'))
    die('You can not access this file directly.');

/**
 * Ajax class.
 *
 * @author Marijan Šuflaj <msufflaj32@gmail.com>
 * @category Tqe
 * @package Tqe_Ajax
 */
class Tqe_Ajax
{

    /**
     * Body value.
     *
     * @var int
     */
    const BODY                      = 0x1;

    /**
     * Message value.
     *
     * @var int
     */
    const MSG                       = 0x2;

    /**
     * Custom value.
     *
     * @var int
     */
    const CUSTOM                    = 0x4;

    /**
     * Available classes.
     *
     * @var array
     */
    private $_available             = array(
        'Tqe_Ajax_Abook',
        'Tqe_Ajax_Mail',
        'Tqe_Ajax_Sent',
        'Tqe_Ajax_Draft',
        'Tqe_Ajax_Trash',
        'Tqe_Ajax_Settings',
        'Tqe_Ajax_Uninstall'
    );

    /**
     * Available return types.
     *
     * @var array
     */
    private $_types                 = array('json');

    /**
     * Instance of come classes in $_available.
     *
     * @var object
     */
    private $_ajaxObj               = null;

    /**
     * Response object.
     *
     * @var Tqe_Ajax_Result
     */
    private $_response              = null;

    /**
     * Result array.
     *
     * @var array
     */
    private $_result                = array();


    /**
     * Constructor
     *
     */
    public function __construct()
    {
        if (!retPost('returnType') || !in_array(retPost('returnType'), $this->_types))
            $_POST['returnType'] = 'json';
    }

    /**
     * Checks if request is valid.
     *
     * @return bool True if valid, false if nots
     */
    public function isValid()
    {
        if (!retGet('class'))
            return false;

        if (!in_array(retGet('class'), $this->_available))
            return false;

        return true;
    }

    /**
     * Executes request
     *
     * @param string $class Class name
     * @return Instance of come classes in $_available
     * @throws Exception If $class does not implement Tqe_Ajax_Interface interface
     */
    public function execute($class)
    {
        $reg = new ReflectionClass($class);

        if (!$reg->implementsInterface('Tqe_Ajax_Interface'))
            throw new Exception('Requested AJAX class does not implement Tqe_Ajax_Interface.');

        $this->_ajaxObj = new $_GET['class'];

        $this->_ajaxObj->buildResponse();

        return $this->_ajaxObj->getResponseObj();
    }

    /**
     * Builds response.
     *
     * @param Tqe_Ajax_Result $responseObj Response object
     */
    public function buildResponse($responseObj)
    {
        $this->_response = $responseObj;

        if ($this->_response->isError()) {
            $this->_result['error'] = true;
            $this->_result['msg'] = $this->_response->getMessages();
        }
        else {
            if (retPost('returnCont') & Tqe_Ajax::BODY)
                $this->_result['body'] = $this->_response->body();

            if (retPost('returnCont') & Tqe_Ajax::MSG)
                $this->_result['msg'] = $this->_response->noticeMessage();

            if (retPost('returnCont') & Tqe_Ajax::CUSTOM)
                $this->_result['custom'] = $this->_response->customContent();
        }
    }

    /**
     * Send response.
     *
     */
    public function send()
    {
        switch (retPost('returnType')) {
            default :
                echo json_encode($this->_result);
                break;
        }
        die();
    }
}
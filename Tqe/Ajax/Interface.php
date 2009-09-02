<?php

//Disable direct view.
if (!defined('IN_PLUGIN'))
    die('You can not access this file directly.');

interface Tqe_Ajax_Interface
{
    public function buildResponse();

    public function getResponseObj();
}
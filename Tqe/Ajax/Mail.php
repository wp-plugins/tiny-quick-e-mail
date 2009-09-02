<?php

//Disable direct view.
if (!defined('IN_PLUGIN'))
    die('You can not access this file directly.');

class Tqe_Ajax_Mail implements Tqe_Ajax_Interface
{

    private $_result                = null;

    private $_toTemplate            = null;

    private $_urlArgs               = array();

    private $_historyId             = null;

    private $_available             = array(
        'send', 'save', 'discard', 'list'
    );

    public function __construct()
    {
        $this->_result = new Tqe_Ajax_Result();
        $this->_toTemplate = new stdClass();

        $this->_toTemplate->currUrl = get_bloginfo('url') . $_SERVER['REQUEST_URI'];

        $temp = Tqe_Config::getWpConfig('tqeUserSessions');
        $id = Tqe_Registry::get('userInfo')->ID;

        if (retGet('historyId') !== false) {
            if (isset($temp[$id][retGet('historyId')]))
                $this->_toTemplate->prevUrl = $temp[$id][retGet('historyId')] . '&historyLink=';
            else
                $this->_toTemplate->prevUrl = '';

            if (isset($temp[$id][retGet('historyId') + 2]))
                $this->_toTemplate->nextUrl = $temp[$id][retGet('historyId') + 2] . '&historyLink=';
            else
                $this->_toTemplate->nextUrl = '';


            if ((int) retGet('historyId') === count($temp[$id]) - 1 || retGet('historyLink') === false)
                $temp[$id][retGet('historyId') + 1] = $this->_toTemplate->currUrl;

            if (retGet('historyLink') === false) {
                if (isset($temp[$id][retGet('historyId') + 2])) {
                    unset($temp[$id][retGet('historyId') + 2]);
                    $this->_toTemplate->nextUrl = '';
                }
            }

        }
        else
            $temp[$id][] = $this->_toTemplate->currUrl;

        if ((int) retGet('historyId') === count($temp[$id]) - 2 || retGet('historyId') === false || retGet('historyLink') === false)
            $this->_historyId = retGet('historyId') + 1;
        else
            $this->_historyId = retGet('historyId');

        Tqe_Config::updateWpConfig('tqeUserSessions', $temp);
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

    private function _send()
    {
        global $wpdb;

        if ($this->_checkEmailHelper())
            return ;

        if (($result = $this->_checkIfMailsValidHelper(retPost('to'))) === false)
            return ;
        else
            $temp['to'] = $result;

        if (retGet('useCc') === '1') {
            if (($result = $this->_checkIfMailsValidHelper(retPost('cc'))) === false)
                return ;
            else
                $temp['cc'] = $result;
        }

        if (retGet('useBcc') === '1') {
            if (($result = $this->_checkIfMailsValidHelper(retPost('bcc'))) === false)
                return ;
            else
                $temp['bcc'] = $result;
        }

        global $phpmailer;

        if (!is_object($phpmailer) || !is_a($phpmailer, 'PHPMailer')) {
            require_once ABSPATH . WPINC . '/class-phpmailer.php';
            require_once ABSPATH . WPINC . '/class-smtp.php';
            $phpmailer = new PHPMailer();
        }

        $phpmailer->ClearAddresses();
        $phpmailer->ClearAllRecipients();
        $phpmailer->ClearAttachments();
        $phpmailer->ClearBCCs();
        $phpmailer->ClearCCs();
        $phpmailer->ClearCustomHeaders();
        $phpmailer->ClearReplyTos();

        $user = Tqe_Registry::get('userInfo');

        $phpmailer->From = $user->user_email;
        $phpmailer->FromName = $user->display_name;

        $phpmailer->Subject = retPost('subject');
        $phpmailer->Body = retPost('message');

        foreach ($temp['to'] as $mail)
            $phpmailer->AddAddress($mail[0], $mail[1]);

        if (retGet('useCc') === '1') {
            foreach ($temp['cc'] as $mail)
                $phpmailer->AddCC($mail[0], $mail[1]);
        }

        if (retGet('useBcc') === '1') {
            foreach ($temp['bcc'] as $mail)
                $phpmailer->AddBCC($mail[0], $mail[1]);
        }

        $phpmailer->ContentType = 'text/plain';

        $phpmailer->CharSet = get_bloginfo('charset');

        //if (!$phpmailer->Send())
            //throw new Exception('Failed to send e-mails.');

        $dataFields = array_merge($this->_createFields(), array(
            'type'  => '1'
        ));

        $dataFieldsTypes = array_merge($this->_createFieldsTypes(), array(
            '%d'
        ));

        if (retPost('id') !== 'null') {

            $sql = 'SELECT `id` '
            . 'FROM `' . $wpdb->qe_mails . '` '
            . 'WHERE `id` = %d '
            . 'AND `userid` = %d '
            . 'LIMIT 1';

            if (($result = $wpdb->query($wpdb->prepare($sql, retPost('id'), Tqe_Registry::get('userInfo')->ID))) === false)
                throw new Exception('Could not check if e-mail exists.');

            if ($result > 0)
                $exist = true;
            else
                $exist = false;
        }
        else
            $exist = false;

        if (!$exist) {
            if (!$wpdb->insert($wpdb->qe_mails, array_merge(
                $dataFields, array(
                    'created'   => $dataFields['updated'],
                    'userid'    => Tqe_Registry::get('userInfo')->ID
                )), array_merge(
                    $dataFieldsTypes, array(
                        '%s', '%d'
                    )
                )
            ))
                throw new Exception('Could not insert e-mail into database');
        }
        else {
            if ($wpdb->update($wpdb->qe_mails, $dataFields, array(
                'id' => retPost('id'),
                'userid' => Tqe_Registry::get('userInfo')->ID
            ), $dataFieldsTypes, array('%d', '%d')) === false)
                throw new Exception('Could not update e-mail in database.');
        }

        $this->_result->noticeMessage('All e-mails successfully sent.');

        return ;
    }

    private function _checkIfMailsValidHelper($mails)
    {
        $mails = explode(',', $mails);

        $allMatches = array();

        $i = 0;

        foreach ($mails as $mail) {
            $mail = trim($mail);
            $match = array();
            if (!preg_match('/\A([\w\s]+) \<([_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+)\>\Z|\A(([_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+))\Z/i', $mail, $match))
                $this->_result->addErrorMsg(array(sprintf('invalid_%d', $i) => sprintf('E-mail `%s` is not valid.', $mail)));
            else {
                if (isset($match[5]) && !empty($match[5]))
                    $allMatches[] = array(trim($match[5]), '');
                else
                    $allMatches[] = array(trim($match[2]), trim($match[1]));
            }

            $i++;
        }

        return $allMatches;
    }

    private function _checkEmailHelper()
    {
        if (retPost('id') === false)
            $this->_result->addErrorMsg(array('noID' => 'You did not provide e-mail ID.'));

        if (retPost('to') === false)
            $this->_result->addErrorMsg(array('noTo' => 'You did not provide receivers field.'));

        if (retPost('useCc') === false)
            $this->_result->addErrorMsg(array('noUseCc' => 'You did not say if you are using copy field.'));

        if (retPost('cc') === false)
            $this->_result->addErrorMsg(array('noCc' => 'You did not provide copy field.'));

        if (retPost('useBcc') === false)
            $this->_result->addErrorMsg(array('noUseBcc' => 'You did not say if you are using hidden copy field.'));

        if (retPost('bcc') === false)
            $this->_result->addErrorMsg(array('noBcc' => 'You did not provide hidden copy field.'));

        if (retPost('subject') === false)
            $this->_result->addErrorMsg(array('noSubj' => 'You did not provide subject field.'));

        if (retPost('message') === false)
            $this->_result->addErrorMsg(array('noMsg' => 'You did not provide message field.'));

        return $this->_result->isError();
    }

    private function _createFields()
    {
        return array(
            'to'            => retPost('to'),
            'useCc'         => retPost('useCc'),
            'cc'            => retPost('cc'),
            'useBcc'        => retPost('useBcc'),
            'bcc'           => retPost('bcc'),
            'subject'       => retPost('subject'),
            'message'       => retPost('message'),
            'usersInFields' => retPost('users'),
            'updated'       => date_i18n('Y-m-d H:i:s')
        );
    }

    private function _createFieldsTypes()
    {
        return array('%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s');
    }

    private function _save()
    {

        global $wpdb;

        if ($this->_checkEmailHelper())
            return ;

        $dataFields = $this->_createFields();

        $dataFieldsTypes = $this->_createFieldsTypes();

        if (retPost('id') !== 'null') {

            $sql = 'SELECT `id` '
            . 'FROM `' . $wpdb->qe_mails . '` '
            . 'WHERE `id` = %d '
            . 'AND `userid` = %d '
            . 'LIMIT 1';

            if (($result = $wpdb->query($wpdb->prepare($sql, retPost('id'), Tqe_Registry::get('userInfo')->ID))) === false)
                throw new Exception('Could not check if e-mail exists.');

            if ($result > 0)
                $exist = true;
            else
                $exist = false;
        }
        else
            $exist = false;

        if (!$exist) {
            if (!$wpdb->insert($wpdb->qe_mails, array_merge(
                $dataFields, array(
                    'created'   => $dataFields['updated'],
                    'userid'    => Tqe_Registry::get('userInfo')->ID
                )), array_merge(
                    $dataFieldsTypes, array(
                        '%s', '%d'
                    )
                )
            ))
                throw new Exception('Could not insert e-mail into database');
        }
        else {
            if ($wpdb->update($wpdb->qe_mails, $dataFields, array(
                'id'     => retPost('id'),
                'userid' => Tqe_Registry::get('userInfo')->ID
            ), $dataFieldsTypes, array('%d', '%d')) === false)
                throw new Exception('Could not update e-mail in database.');
        }

        if (retPost('autosave') === 'autosave')
            $autosave = 'auto';
        else
            $autosave = '';

        $this->_result->noticeMessage(sprintf('E-mail %ssaved at %s.', $autosave, date_i18n(Tqe_Config::getWpConfig('time_format'))));
        $this->_result->customContent(array(
            'id'    => (!$exist) ? $wpdb->insert_id : retPost('id')
        ));

        return ;
    }

    private function _discard()
    {
        global $wpdb;

        if (retPost('id') === false)
            throw new Exception('You did not provide e-mail ID.');

        $sql = 'DELETE `' . $wpdb->qe_mails . '` '
        . 'FROM `' . $wpdb->qe_mails . '` '
        . 'WHERE `userid` = %d '
        . 'AND `id` = %d';

        if ($wpdb->query($wpdb->prepare($sql, Tqe_Registry::get('userInfo')->ID, retPost('id'))) === false)
                throw new Exception('Could not check if e-mail exists.');

        $this->_result->noticeMessage('E-mail deleted from database.');
    }

    private function _list()
    {
        global $wpdb;

        $this->_urlArgs = array(
            'class'     => 'Tqe_Ajax_Mail',
            'action'    => 'list',
            'cond'      => retGet('cond'),
            'order'     => retGet('order'),
            'desc'      => retGet('desc'),
            'fieldType' => retGet('fieldType'),
            'historyId' => $this->_historyId
        );

        if (retGet('currPage'))
            $currPage = (int) $_GET['currPage'];
        else
            $currPage = 1;

        $limit = Tqe_Config::getConfig('displayContacts');

        $condStr = '';
        $condMsg = '';

        if (($cond = retGet('cond')) !== false) {
            if (isset($cond['category'])) {
                $condStr .= $wpdb->prepare('AND `category` = %d ', $cond['category']);

                $sql = 'SELECT `id`, `name` '
                . 'FROM `' . $wpdb->qe_cats . '` '
                . 'WHERE `id` = %d '
                . 'AND `userid` = %d '
                . 'LIMIT 1';

                if (($result = $wpdb->query($wpdb->prepare($sql, $cond['category'], Tqe_Registry::get('userInfo')->ID))) === false)
                    throw new Exception('Could retrieve category name.');

                $condMsg = 'from category %s ';

                if ($result > 0)
                    $condMsg = sprintf($condMsg, '`' . $wpdb->last_result[0]->name . '`');
                else
                    $condMsg = sprintf($condMsg, 'that does not exist');
            }
        }

        $sql = 'SELECT COUNT(`id`) '
        . 'AS `count` '
        . 'FROM `' . $wpdb->qe_abook . '` '
        . 'WHERE `userid` = %d '
        . $condStr;

        if ($wpdb->query($wpdb->prepare($sql, Tqe_Registry::get('userInfo')->ID)) === false)
            throw new Exception('Could retrieve total count of contacts.');

        $count = $wpdb->last_result[0]->count;

        if ($wpdb->last_result[0]->count < 1)
            $this->_toTemplate->count = $count;
        else {
            $sql = 'SELECT `id`, `firstName`, `middleName`, `lastName`, `email`, ( '
            . 'SELECT `name` '
            . 'FROM `' . $wpdb->qe_cats . '` '
            . 'WHERE `' . $wpdb->qe_cats . '`.`id` =  `' . $wpdb->qe_abook . '`.`category` '
            . 'AND `userid` = %d'
            . ') AS `catName` '
            . 'FROM `' . $wpdb->qe_abook . '` '
            . 'WHERE `userid` = %d '
            . $condStr;

            $sql = $wpdb->prepare($sql, Tqe_Registry::get('userInfo')->ID, Tqe_Registry::get('userInfo')->ID);

            if (retGet('order')) {
                switch (retGet('order')) {
                    case 'middleName' :
                        $sql .= sprintf('ORDER BY `%s` ', 'middleName');
                        break;
                    case 'lastName' :
                        $sql .= sprintf('ORDER BY `%s` ', 'lastName');
                        break;
                    case 'email' :
                        $sql .= sprintf('ORDER BY `%s` ', 'email');
                        break;
                    case 'category' :
                        $sql .= sprintf('ORDER BY `%s` ', 'category');
                        break;
                    default :
                        $sql .= sprintf('ORDER BY `%s` ', 'firstName');
                        break;
                }

                if (retGet('desc') && retGet('desc') === 'true') {
                    $sql .= 'DESC ';
                }
            }


            $sql .= 'LIMIT %d, %d';

            if ($wpdb->query($wpdb->prepare($sql, (($currPage * $limit) - $limit), $limit)) === false)
                throw new Exception('Could retrieve contacts.');

            $this->_toTemplate->result = $wpdb->last_result;
            $this->_toTemplate->pagin = paginate_links(array(
                'base'      => AJAX_URL . '%_%',
                'format'    => '&currPage=%#%',
                'total'     => ceil($count / $limit),
                'current'   => $currPage,
                'prev_text' => __('&laquo;'),
                'next_text' => __('&raquo;'),
                'add_args'  => $this->_urlArgs
            ));

            $this->_toTemplate->displaying = sprintf(
                '<span class="displaying-num">%s</span>', sprintf(
                    __( 'Displaying contacts %s%s&#8211;%s of %s' ),
                    $condMsg,
                    (($currPage * $limit) - ($limit - 1)),
                    (($currPage * $limit > $count) ? $count : ($currPage * $limit)),
                    sprintf('<span class="total-type-count">%s</span>', $count)
                )
            );
        }

        $this->_urlArgs['currPage'] = retGet('currPage');

        $this->_initTemplate();
    }

    private function _initTemplate()
    {
        $this->_toTemplate->urlArgs = $this->_urlArgs;
        $template = new Tqe_Template(TQE_TEMPLATES_DIR, $this->_toTemplate);
        $template->assignFile('mail/list.phtml');
        $this->_result->body($template->returnRender());
    }
}
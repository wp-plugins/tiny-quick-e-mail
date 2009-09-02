<?php

//Disable direct view.
if (!defined('IN_PLUGIN'))
    die('You can not access this file directly.');

class Tqe_Ajax_Draft implements Tqe_Ajax_Interface
{

    private $_result                = null;

    private $_toTemplate            = null;

    private $_urlArgs               = array();

    private $_historyId             = null;

    private $_available             = array(
        'list', 'view', 'del', 'load'
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

    private function _load()
    {

        global $wpdb;

        if (retGet('id') === false)
            throw new Exception('You did not provide e-mail ID.');

        $sql = 'SELECT `id`, `to`, `useCc`, `cc`, `useBcc`, `bcc`, `subject`, `message`, `usersInFields` '
        . 'FROM `' . $wpdb->qe_mails . '` '
        . 'WHERE `userid` = %d '
        . 'AND `id` = %d '
        . 'AND `type` = 2';

        if (($result = $wpdb->query($wpdb->prepare($sql, Tqe_Registry::get('userInfo')->ID, retGet('id')))) === false)
            throw new Exception('Could not load e-mail.');

        if ($result < 1)
            return ;

         $this->_result->customContent(array(
            'draft' => $wpdb->last_result[0]
         ));
    }

    private function _del()
    {
        if (retPost('post')) {
            if (retPost('ids') === false || !is_array(retPost('ids')))
                throw new Exception('You did not provide valid array with e-mail ID-s.');

            if (empty($_POST['ids']))
                throw new Exception('You did not select any e-mail to be deleted.');

            foreach (retPost('ids') as $id)
                $this->_delHelper($id);
        }
        else {
             if (!retGet('id'))
                throw new Exception('You did not provide valid e-mail ID.');

             $this->_delHelper(retGet('id'));
        }

        $plural = retPost('post') === false ? '' : (count(retPost('ids')) > 1 ? 's' : '');

        $this->_result->noticeMessage(sprintf('E-mail%s deleted.', $plural));

        $_GET['action'] = 'list';

        $this->buildResponse();
    }

    private function _delHelper($id)
    {
        global $wpdb;

        $sql = 'SELECT `id` '
        . 'FROM `' . $wpdb->qe_mails . '` '
        . 'WHERE `userid` = %d '
        . 'AND `id` = %d '
        . 'AND `type` = 2';

        if (($result = $wpdb->query($wpdb->prepare($sql, Tqe_Registry::get('userInfo')->ID, $id))) === false)
            throw new Exception('Could not check if e-mail exists.');

        if ($result < 1)
            return ;

         $sql = 'DELETE `' . $wpdb->qe_mails . '` '
         . 'FROM `' . $wpdb->qe_mails . '` '
         . 'WHERE `userid` = %d '
         . 'AND `id` = %d '
         . 'AND `type` = 2';

         if ($wpdb->query($wpdb->prepare($sql, Tqe_Registry::get('userInfo')->ID, $id)) === false)
            throw new Exception('Could not delete e-mail.');

         return ;
    }

    private function _view()
    {

        global $wpdb;

        if (retGet('id') === false)
            throw new Exception('You did not provide e-mail ID.');

        $sql = 'SELECT `id`, `to`, `useCc`, `cc`, `useBcc`, `bcc`, `subject`, `message`, UNIX_TIMESTAMP(`created`) '
        . 'AS `date`, UNIX_TIMESTAMP(`updated`) '
        . 'AS `edit` '
        . 'FROM `' . $wpdb->qe_mails . '` '
        . 'WHERE `userid` = %d '
        . 'AND `type` = 2 '
        . 'AND `id` = %d';

        if (($result = $wpdb->query($wpdb->prepare($sql, Tqe_Registry::get('userInfo')->ID, retGet('id')))) === false)
            throw new Exception('Could not retrieve e-mail information.');

        if ($result < 1)
            throw new Exception(sprintf('E-mail with ID `%s` does not exist', retGet('id')));

        $this->_toTemplate->result = $wpdb->last_result[0];

        $this->_initTemplate();
    }

    private function _list()
    {
        global $wpdb;

        $this->_urlArgs = array(
            'class'     => 'Tqe_Ajax_Draft',
            'action'    => 'list',
            'order'     => retGet('order'),
            'desc'      => retGet('desc'),
            'fieldType' => retGet('fieldType'),
            'historyId' => $this->_historyId
        );

        if (retGet('currPage'))
            $currPage = (int) $_GET['currPage'];
        else
            $currPage = 1;

        $limit = Tqe_Config::getConfig('displayEmails');

        $sql = 'SELECT COUNT(`id`) '
        . 'AS `count` '
        . 'FROM `' . $wpdb->qe_mails . '` '
        . 'WHERE `userid` = %d '
        . 'AND `type` = 2';


        if ($wpdb->query($wpdb->prepare($sql, Tqe_Registry::get('userInfo')->ID)) === false)
            throw new Exception('Could retrieve total count of saved e-mails.');

        $count = $wpdb->last_result[0]->count;

        if ($wpdb->last_result[0]->count < 1)
            $this->_toTemplate->count = $count;
        else {
            $sql = 'SELECT `message`, `subject`, UNIX_TIMESTAMP(`created`) '
            . 'AS `date`, `id` '
            . 'FROM `' . $wpdb->qe_mails . '` '
            . 'WHERE `userid` = %d '
            . 'AND `type` = 2 ';

            $sql = $wpdb->prepare($sql, Tqe_Registry::get('userInfo')->ID);

            if (retGet('order')) {
                switch (retGet('order')) {
                    case 'date' :
                        $sql .= sprintf('ORDER BY UNIX_TIMESTAMP(`%s`) ', 'updated');
                        break;
                    case 'subject' :
                    default :
                        $sql .= sprintf('ORDER BY `%s` ', 'subject');
                        break;
                }

                if (retGet('desc') && retGet('desc') === 'true') {
                    $sql .= 'DESC ';
                }
            }


            $sql .= 'LIMIT %d, %d';

            if ($wpdb->query($wpdb->prepare($sql, (($currPage * $limit) - $limit), $limit)) === false)
                throw new Exception('Could retrieve saved e-mails.');

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
                    __( 'Displaying draft e-mails %s&#8211;%s of %s' ),
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
        $template->assignFile(sprintf('draft/%s.phtml', retGet('action')));
        $this->_result->body($template->returnRender());
    }
}
<?php

//Disable direct view.
if (!defined('IN_PLUGIN'))
    die('You can not access this file directly.');

/**
 * Abook class.
 *
 * @author Marijan Šuflaj <msufflaj32@gmail.com>
 * @category Tqe
 * @package Tqe_Ajax
 */
class Tqe_Ajax_Abook implements Tqe_Ajax_Interface
{

    /**
     * Result object.
     *
     * @var Tqe_Ajax_Result
     */
    private $_result                = null;

    /**
     * Template data.
     *
     * @var stdClass
     */
    private $_toTemplate            = null;

    /**
     * Arguments used to build url.
     *
     * @var array
     */
    private $_urlArgs               = array();

    /**
     * History ID.
     *
     * @var int
     */
    private $_historyId             = null;

    /**
     * Available actions.
     *
     * @var array
     */
    private $_available             = array(
        'add', 'view', 'edit', 'del', 'list',
        'addCat', 'viewCat', 'editCat', 'delCat', 'listCat'
    );

    /**
     * Constructor
     *
     */
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

    /**
     * Builds response.
     *
     */
    public function buildResponse()
    {
        if (!$this->_isValidAction())
            throw new Exception('Your action is not valid.');

        call_user_func(array($this, '_' . $_GET['action']));
    }

    /**
     * Check is action is valid.
     *
     * @return bool True if valid, false if not
     */
    private function _isValidAction()
    {
        if (!isset($_GET['action']))
            return false;

        return in_array($_GET['action'], $this->_available);
    }

    /**
     * Returns response object
     *
     * @return Tqe_Ajax_Result
     */
    public function getResponseObj()
    {
        return $this->_result;
    }

    /**
     * Adds contact.
     *
     */
    private function _add()
    {

        global $wpdb;

        $this->_urlArgs = array(
            'class'     => 'Tqe_Ajax_Abook',
            'action'    => 'add',
            'historyId' => $this->_historyId
        );

        if (retPost('isPost')) {

            if (retPost('firstName') === false)
                $this->_result->addErrorMsg(array('noFN' => 'You did not provide first name.'));

            if (retPost('category') === false)
                $this->_result->addErrorMsg(array('noCat' => 'You did not provide category ID.'));

            if (retPost('email') === false)
                $this->_result->addErrorMsg(array('noCat' => 'You did not provide e-mail address.'));

            if ($this->_result->isError())
                return ;

            if (empty($_POST['firstName']))
                $this->_result->addErrorMsg(array('fnEmpt' => 'First name can not be empty.'));

            if (empty($_POST['category']))
                $this->_result->addErrorMsg(array('catEmpt' => 'Category can not be empty.'));

            if ((int) retPost('category') < 1)
                $this->_result->addErrorMsg(array('invCat' => sprintf('Category `%s` ID is not valid.', retPost('category'))));

            if (!preg_match('/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+$/', retPost('email')))
                $this->_result->addErrorMsg(array('invMail' => sprintf('E-mail address `%s` is not valid.', retPost('email'))));

            if ($this->_result->isError())
                return ;

            $sql = "SELECT CONCAT(`firstName`, ' ',  `middleName`, ' ', `lastName`) "
            . 'AS `contact` '
            . 'FROM `' . $wpdb->qe_abook . '` '
            . 'WHERE `email` = %s '
            . 'AND `userid` = %d '
            . 'LIMIT 1';

            if (($result = $wpdb->query($wpdb->prepare($sql, retPost('email'), Tqe_Registry::get('userInfo')->ID))) === false)
                throw new Exception('Could not check if e-mail address exists.');

            if ($result > 0)
                throw new Exception(sprintf('Contact `%s` has this e-mail address.', trim($wpdb->last_result[0]->contact)));

            if (!$wpdb->insert($wpdb->qe_abook, array(
                'firstName'     => trim(retPost('firstName')),
                'middleName'    => trim(retPost('middleName')),
                'lastName'      => trim(retPost('lastName')),
                'email'         => trim(retPost('email')),
                'category'      => retPost('category'),
                'userid'        => Tqe_Registry::get('userInfo')->ID
            ), array('%s', '%s', '%s', '%s', '%d', '%d')))
                throw new Exception('Could not insert contact into database');

            $this->_result->noticeMessage('Contact created successfully.');
        }
        else {

            $sql = 'SELECT `id`, `name` '
            . 'FROM `' . $wpdb->qe_cats . '` '
            . 'WHERE `userid` = %d ';

            if (($result = $wpdb->query($wpdb->prepare($sql, Tqe_Registry::get('userInfo')->ID))) === false)
                throw new Exception('Could not retrieve categories from database.');

            $this->_toTemplate->cats = $wpdb->last_result;

            $this->_initTemplate();
        }
    }

    /**
     * Generates category detailed view.
     *
     */
    private function _viewCat()
    {
        global $wpdb;

        $this->_urlArgs = array(
            'class'     => 'Tqe_Ajax_Abook',
            'action'    => 'viewCat',
            'cond'      => retGet('cond'),
            'order'     => retGet('order'),
            'desc'      => retGet('desc'),
            'currPage'  => retGet('currPage'),
            'historyId' => $this->_historyId
        );

        if (retGet('id') === false)
            throw new Exception('You did not provide valid category ID.');

        $sql = 'SELECT `id`, `name`, ( '
        . 'SELECT COUNT(`id`) '
        . 'FROM `' . $wpdb->qe_abook . '` '
        . 'WHERE `' . $wpdb->qe_cats . '`.`id` =  `' . $wpdb->qe_abook . '`.`category` '
        . 'AND `userid` = %d'
        . ') AS `count` '
        . 'FROM `' . $wpdb->qe_cats . '` '
        . 'WHERE `userid` = %d '
        . 'AND `id` = %d '
        . 'LIMIT 1';

        if (($result = $wpdb->query($wpdb->prepare($sql, Tqe_Registry::get('userInfo')->ID, Tqe_Registry::get('userInfo')->ID, retGet('id')))) === false)
            throw new Exception('Error while retrieving category data.');

        if ($result < 1)
            throw new Exception(sprintf('Category with ID `%s` is not found.', retGet('id')));

        $this->_toTemplate->info = $wpdb->last_result[0];

        $this->_initTemplate();
    }

    /**
     * Generates contact detailed view.
     *
     */
    private function _view()
    {
        global $wpdb;

        $this->_urlArgs = array(
            'class'     => 'Tqe_Ajax_Abook',
            'action'    => 'view',
            'cond'      => retGet('cond'),
            'order'     => retGet('order'),
            'desc'      => retGet('desc'),
            'currPage'  => retGet('currPage'),
            'historyId' => $this->_historyId
        );

        if (retGet('id') === false)
            throw new Exception('You did not provide valid contact ID.');

        $sql = 'SELECT `id`, `firstName`, `middleName`, `lastName`, `email`, ( '
        . 'SELECT `name` '
        . 'FROM `' . $wpdb->qe_cats . '` '
        . 'WHERE `' . $wpdb->qe_cats . '`.`id` =  `' . $wpdb->qe_abook . '`.`category` '
        . 'AND `userid` = %d'
        . ') AS `category` '
        . 'FROM `' . $wpdb->qe_abook . '` '
        . 'WHERE `userid` = %d '
        . 'AND `id` = %d';

        if (($result = $wpdb->query($wpdb->prepare($sql, Tqe_Registry::get('userInfo')->ID, Tqe_Registry::get('userInfo')->ID, retGet('id')))) === false)
            throw new Exception('Error while retrieving contact data.');

        if ($result < 1)
            throw new Exception(sprintf('Contact with ID `%s` is not found.', retGet('id')));

        $this->_toTemplate->info = $wpdb->last_result[0];

        $this->_initTemplate();
    }

    /**
     * Lists contacts
     *
     */
    private function _list()
    {
        global $wpdb;

        $this->_urlArgs = array(
            'class'     => 'Tqe_Ajax_Abook',
            'action'    => 'list',
            'cond'      => retGet('cond'),
            'order'     => retGet('order'),
            'desc'      => retGet('desc'),
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
            $sql = 'SELECT `id`, `firstName`, `middleName`, `lastName`, ( '
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

    /**
     * List categories.
     *
     */
    private function _listCat()
    {

        global $wpdb;

        $this->_urlArgs = array(
            'class'     => 'Tqe_Ajax_Abook',
            'action'    => 'listCat',
            'order'     => retGet('order'),
            'desc'      => retGet('desc'),
            'historyId' => $this->_historyId
        );

        if (retGet('currPage'))
            $currPage = (int) $_GET['currPage'];
        else
            $currPage = 1;

        $limit = Tqe_Config::getConfig('displayCategories');

        $sql = 'SELECT COUNT(`id`) '
        . 'AS `count` '
        . 'FROM `' . $wpdb->qe_cats . '` '
        . 'WHERE `userid` = %d ';


        if ($wpdb->query($wpdb->prepare($sql, Tqe_Registry::get('userInfo')->ID)) === false)
            throw new Exception('Could retrieve total count of categories.');

        $count = $wpdb->last_result[0]->count;

        if ($wpdb->last_result[0]->count < 1)
            $this->_toTemplate->count = $count;
        else {
            $sql = 'SELECT `id`, `name`, ( '
            . 'SELECT COUNT(`id`) '
            . 'FROM `' . $wpdb->qe_abook . '` '
            . 'WHERE `' . $wpdb->qe_cats . '`.`id` =  `' . $wpdb->qe_abook . '`.`category` '
            . 'AND `userid` = %d'
            . ') AS `count` '
            . 'FROM `' . $wpdb->qe_cats . '` '
            . 'WHERE `userid` = %d ';

            $sql = $wpdb->prepare($sql, Tqe_Registry::get('userInfo')->ID, Tqe_Registry::get('userInfo')->ID);

            if (retGet('order')) {
                switch (retGet('order')) {
                    case 'count' :
                        $sql .= sprintf('ORDER BY `%s` ', 'count');
                        break;
                    default :
                        $sql .= sprintf('ORDER BY `%s` ', 'name');
                        break;
                }

                if (retGet('desc') && retGet('desc') === 'true') {
                    $sql .= 'DESC ';
                }
            }


            $sql .= 'LIMIT %d, %d';

            if ($wpdb->query($wpdb->prepare($sql, (($currPage * $limit) - $limit), $limit)) === false)
                throw new Exception('Could retrieve categories.');

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
                    __( 'Displaying categories %s&#8211;%s of %s' ),
                    (($currPage * $limit) - ($limit - 1)),
                    (($currPage * $limit > $count) ? $count : ($currPage * $limit)),
                    sprintf('<span class="total-type-count">%s</span>', $count)
                )
            );
        }

        $this->_urlArgs['currPage'] = retGet('currPage');

        $this->_initTemplate();
    }

    /**
     * For editing category.
     *
     */
    private function _editCat()
    {
        global $wpdb;

        $this->_urlArgs = array(
            'class'     => 'Tqe_Ajax_Abook',
            'action'    => 'viewCat',
            'cond'      => retGet('cond'),
            'order'     => retGet('order'),
            'desc'      => retGet('desc'),
            'currPage'  => retGet('currPage'),
            'historyId' => $this->_historyId
        );

        if (retPost('isPost') !== false) {

            if (retPost('id') === false)
                $this->_result->addErrorMsg(array('missId' => 'You did not provide category ID.'));

            if (retPost('categoryName') === false)
                 $this->_result->addErrorMsg(array('invId' => 'You did not provide category name.'));

            if ($this->_result->isError())
                return ;

            if (empty($_POST['categoryName']))
                 $this->_result->addErrorMsg(array('missName' => 'Category name can not be empty.'));

            if (empty($_POST['id']))
                 $this->_result->addErrorMsg(array('invId' => 'Category ID can not be empty.'));

            if ($this->_result->isError())
                return ;

            $sql = 'SELECT `id` '
            . 'FROM `' . $wpdb->qe_cats . '` '
            . 'WHERE LOWER(`name`) = %s '
            . 'AND `userid` = %d '
            . 'AND `id` <> %d '
            . 'LIMIT 1';

            if (($result = $wpdb->query($wpdb->prepare($sql, strtolower(retPost('categoryName')), (int) retPost('id'), Tqe_Registry::get('userInfo')->ID))) === false)
                throw new Exception('Could not check if category exists.');

            if ($result > 0)
                throw new Exception('Category with that name exists.');

            if ($wpdb->update($wpdb->qe_cats, array(
                'name'      => trim(htmlentities(strip_tags(retPost('categoryName'))))
            ), array(
                'id'     => retPost('id'),
                'userid' => Tqe_Registry::get('userInfo')->ID
            ), array('%s'), array('%d', '%d')) === false)
                throw new Exception('Could not update category in database.');

            $this->_result->noticeMessage('Category updated successfully.');
        }
        else {
            if (retGet('id') === false)
                throw new Exception('You did not provide category ID.');

            $sql = 'SELECT `id`, `name` '
            . 'FROM `' . $wpdb->qe_cats . '` '
            . 'WHERE `id` = %s '
            . 'AND `userid` = %d '
            . 'LIMIT 1';

            if (($result = $wpdb->query($wpdb->prepare($sql, retGet('id'), Tqe_Registry::get('userInfo')->ID))) === false)
                throw new Exception('Could not check if category exists.');


            if ($result < 1)
                throw new Exception(sprintf('There is no category with ID `%s`.', retGet('id')));

            $this->_toTemplate->result = $wpdb->last_result[0];

            $this->_initTemplate();
        }

        return ;
    }

    /**
     * For editing user.
     *
     */
    private function _edit()
    {
        global $wpdb;

        $this->_urlArgs = array(
            'class'     => 'Tqe_Ajax_Abook',
            'action'    => 'edit',
            'cond'      => retGet('cond'),
            'order'     => retGet('order'),
            'desc'      => retGet('desc'),
            'currPage'  => retGet('currPage'),
            'historyId' => $this->_historyId
        );

        if (retPost('isPost') !== false) {

            if (retPost('id') === false)
                $this->_result->addErrorMsg(array('missId' => 'You did not provide contact ID.'));

            if (retPost('firstName') === false)
                $this->_result->addErrorMsg(array('noFN' => 'You did not provide first name.'));

            if (retPost('category') === false)
                $this->_result->addErrorMsg(array('noCat' => 'You did not provide category ID.'));

            if (retPost('email') === false)
                $this->_result->addErrorMsg(array('noCat' => 'You did not provide e-mail address.'));

            if ($this->_result->isError())
                return ;

            if (empty($_POST['firstName']))
                $this->_result->addErrorMsg(array('fnEmpt' => 'First name can not be empty.'));

            if (empty($_POST['category']))
                $this->_result->addErrorMsg(array('catEmpt' => 'Category can not be empty.'));

            if ((int) retPost('category') < 1)
                $this->_result->addErrorMsg(array('invCat' => sprintf('Category `%s` ID is not valid.', retPost('category'))));

            if (!preg_match('/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+$/', retPost('email')))
                $this->_result->addErrorMsg(array('invMail' => sprintf('E-mail address `%s` is not valid.', retPost('email'))));

            if (empty($_POST['id']))
                 $this->_result->addErrorMsg(array('invId' => 'Contact ID can not be empty.'));

            if ($this->_result->isError())
                return ;

            $sql = "SELECT CONCAT(`firstName`, ' ',  `middleName`, ' ', `lastName`) "
            . 'AS `contact` '
            . 'FROM `' . $wpdb->qe_abook . '` '
            . 'WHERE `email` = %s '
            . 'AND `userid` = %d '
            . 'AND `id` <> %d '
            . 'LIMIT 1';

            if (($result = $wpdb->query($wpdb->prepare($sql, retPost('email'), Tqe_Registry::get('userInfo')->ID, retPost('id')))) === false)
                throw new Exception('Could not check if e-mail address exists.');

            if ($result > 0)
                throw new Exception(sprintf('Contact `%s` has this e-mail address.', trim($wpdb->last_result[0]->contact)));

            if ($wpdb->update($wpdb->qe_abook, array(
                'firstName'     => trim(retPost('firstName')),
                'middleName'    => trim(retPost('middleName')),
                'lastName'      => trim(retPost('lastName')),
                'email'         => trim(retPost('email')),
                'category'      => retPost('category')
            ), array(
                'id'     => retPost('id'),
                'userid' => Tqe_Registry::get('userInfo')->ID
            ), array('%s', '%s', '%s', '%s', '%d'), array('%d', '%d')) === false)
                throw new Exception('Could not update contact in database.');

            $this->_result->noticeMessage('Contact updated successfully.');
        }
        else {
            if (retGet('id') === false)
                throw new Exception('You did not provide contact ID.');

            $sql = 'SELECT `id`, `firstName`, `middleName`, `lastName`, `email`, `category` '
            . 'FROM `' . $wpdb->qe_abook . '` '
            . 'WHERE `userid` = %d '
            . 'AND `id` = %d';

            if (($result = $wpdb->query($wpdb->prepare($sql, Tqe_Registry::get('userInfo')->ID, retGet('id')))) === false)
                throw new Exception('Could not check if contact exists.');

            if ($result < 1)
                throw new Exception(sprintf('There is no contact with ID `%s`.', retGet('id')));

            $this->_toTemplate->result = $wpdb->last_result[0];

            $sql = 'SELECT `id`, `name` '
            . 'FROM `' . $wpdb->qe_cats . '` '
            . 'WHERE `userid` = %d ';

            if (($result = $wpdb->query($wpdb->prepare($sql, Tqe_Registry::get('userInfo')->ID))) === false)
                throw new Exception('Could not retrieve categories from database.');

            $this->_toTemplate->cats = $wpdb->last_result;

            $this->_initTemplate();
        }

        return ;
    }

    /**
     * Deletes category.
     *
     */
    private function _delCat()
    {
        if (retPost('post')) {
            if (retPost('ids') === false || !is_array(retPost('ids')))
                throw new Exception('You did not provide valid array with category ID-s.');

            if (empty($_POST['ids']))
                throw new Exception('You did not select any category to be deleted.');

            foreach (retPost('ids') as $id)
                $this->_delCatHelper($id);
        }
        else {
             if (!retGet('id'))
                throw new Exception('You did not provide valid category ID.');

             $this->_delCatHelper(retGet('id'));
        }

        $plural = retPost('post') === false ? 'y' : (count(retPost('ids')) > 1 ? 'ies' : 'y');

        $this->_result->noticeMessage(sprintf('Categor%s deleted successfully.', $plural));

        $_GET['action'] = 'listCat';

        $this->buildResponse();
    }

    /**
     * Heleper for deleting category.
     *
     * @param string $id Category id
     */
    private function _delCatHelper($id)
    {
        global $wpdb;

        $sql = 'SELECT `id` '
        . 'FROM `' . $wpdb->qe_abook . '` '
        . 'WHERE `userid` = %d '
        . 'AND `category` = %d';

        if (($result = $wpdb->query($wpdb->prepare($sql, Tqe_Registry::get('userInfo')->ID, $id))) === false)
            throw new Exception('Could not check if category has contacts.');

        if ($result < 1)
            throw new Exception(sprintf('Category with ID `%s` does not exist or can not be deleted.', $id));

        if ($wpdb->last_result[0]->count > 0) {

         $sql = 'SELECT `id` '
         . 'FROM `' . $wpdb->qe_cats . '` '
         . "WHERE `name` = 'Uncategorized' "
         . 'AND `userid` = %d '
         . 'AND `id` <> %d';

         if (($result = $wpdb->query($wpdb->prepare($sql, Tqe_Registry::get('userInfo')->ID, $id))) === false)
            return ;

         if ($result < 1) {
            if ($wpdb->insert($wpdb->qe_cats, array(
                'name'      => 'Uncategorized',
                'userid'    => Tqe_Registry::get('userInfo')->ID
            ), array('%s', '%d')) === false)
                throw new Exception('Could not create `Uncategorized` category.');

            $newId = $wpdb->insert_id;
         }
         else
            $newId = $wpdb->last_result[0]->id;

         if ($wpdb->update($wpdb->qe_abook, array(
            'category' => $newId
         ), array(
            'category' => $id
         ), array('%d'), array('%d')) === false)
            throw new Exception('Could not update users category entry.');

         }

         $sql = 'DELETE `' . $wpdb->qe_cats . '` '
         . 'FROM `' . $wpdb->qe_cats . '` '
         . 'WHERE `userid` = %d '
         . 'AND `id` = %d';

         if ($wpdb->query($wpdb->prepare($sql, Tqe_Registry::get('userInfo')->ID, $id)) === false)
            throw new Exception('Could not delete category.');

         return ;
    }

    /**
     * Deletes contacts.
     *
     * @throws Exception If $_POST['ids'] not set
     * @throws Exception If $_POST['ids'] empty
     * @throws Exception If $_GET['id'] not set
     */
    private function _del()
    {
        if (retPost('post')) {
            if (retPost('ids') === false || !is_array(retPost('ids')))
                throw new Exception('You did not provide valid array with contact ID-s.');

            if (empty($_POST['ids']))
                throw new Exception('You did not select any contact to be deleted.');

            foreach (retPost('ids') as $id)
                $this->_delHelper($id);
        }
        else {
             if (!retGet('id'))
                throw new Exception('You did not provide valid contact ID.');

             $this->_delHelper(retGet('id'));
        }

        $plural = retPost('post') === false ? '' : (count(retPost('ids')) > 1 ? 's' : '');

        $this->_result->noticeMessage(sprintf('Contact%s deleted successfully.', $plural));

        $_GET['action'] = 'list';

        $this->buildResponse();
    }

    /**
     * Heleper for deleting contacts.
     *
     * @param string $id Contact id
     */
    private function _delHelper($id)
    {
        global $wpdb;

        $sql = 'SELECT `id` '
        . 'FROM `' . $wpdb->qe_abook . '` '
        . 'WHERE `userid` = %d '
        . 'AND `category` = %d';

        if (($result = $wpdb->query($wpdb->prepare($sql, Tqe_Registry::get('userInfo')->ID, $id))) === false)
            throw new Exception('Could not check if contact exists.');

        if ($result < 1)
            return ;

         $sql = 'DELETE `' . $wpdb->qe_abook . '` '
         . 'FROM `' . $wpdb->qe_abook . '` '
         . 'WHERE `userid` = %d '
         . 'AND `id` = %d';

         if ($wpdb->query($wpdb->prepare($sql, Tqe_Registry::get('userInfo')->ID, $id)) === false)
            throw new Exception('Could not delete contact.');

         return ;
    }

    /**
     * Adds category.
     *
     * @throws Exception If $_POST['categoryName'] not set
     * @throws Exception If $_POST['categoryName'] empty
     * @throws Exception If failed to check if category exists
     * @throws Exception If category with that name exists
     * @throws Exception If could not create category
     */
    private function _addCat()
    {
        global $wpdb;

        $this->_urlArgs = array(
            'class'     => 'Tqe_Ajax_Abook',
            'action'    => 'addCat',
            'historyId' => $this->_historyId
        );

        if (retPost('isPost')) {
            if (retPost('categoryName') === false)
                throw new Exception('You did not provide category name.');

            if (empty($_POST['categoryName']))
                throw new Exception('Category name can not be empty.');

            $sql = 'SELECT `id` '
            . 'FROM `' . $wpdb->qe_cats . '` '
            . 'WHERE LOWER(`name`) = %s '
            . 'AND `userid` = %d '
            . 'LIMIT 1';

            if (($result = $wpdb->query($wpdb->prepare($sql, strtolower(retPost('categoryName')), Tqe_Registry::get('userInfo')->ID))) === false)
                throw new Exception('Could not check if category exists.');

            if ($result > 0)
                throw new Exception('Category with that name exists.');

            if (!$wpdb->insert($wpdb->qe_cats, array(
                'name'      => trim(htmlentities(strip_tags(retPost('categoryName')))),
                'userid'    => Tqe_Registry::get('userInfo')->ID
            ), array('%s', '%d')))
                throw new Exception('Could not insert category into database');

            $this->_result->noticeMessage('Category created successfully.');
        }
        else
            $this->_initTemplate();
    }

    /**
     * Inits template.
     *
     */
    private function _initTemplate()
    {
        $this->_toTemplate->urlArgs = $this->_urlArgs;
        $template = new Tqe_Template(TQE_TEMPLATES_DIR, $this->_toTemplate);
        $template->assignFile('abook/wrapper.php');
        $this->_result->body($template->returnRender());
    }
}
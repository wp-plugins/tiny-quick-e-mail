<?php

//Disable direct view.
if (!defined('IN_PLUGIN'))
    die('You can not access this file directly.');

$template = new Tqe_Template(TQE_TEMPLATES_DIR, $this->_data);
?>
<table>
    <tbody>
        <tr class="emailForm">
            <td id="tqeSidebar"><?php $template->assignFile('abook/sideBar.phtml')->render(); ?></td>
            <td><?php $template->assignFile('abook/' . retGet('action') . '.phtml')->render(); ?></td>
        </tr>
        <tr class="emailForm">
            <td>&nbsp;</td>
            <td class="alignRight">
                <?php $template->assignFile('misc/nav.phtml')->render(); ?>
            </td>
        </tr>
    </tbody>
</table>
<?php

/* Copyright (C) 2012      Mikael Carlavan        <contact@mika-carl.fr>
 *                                                http://www.mikael-carlavan.fr
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**	    \file       htdocs/ndfp/tpl/ndfp.note.tpl.php
 *		\ingroup    ndfp
 *		\brief      Ndfp module expense note view
 */

llxHeader();

echo ($message ? dol_htmloutput_mesg($message, '', ($error ? 'error' : 'ok'), 0) : '');

dol_fiche_head($head, $current_head, $langs->trans('Ndfp'));

?>

<form name="save" action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
<input type="hidden" name="action" value="setcomments" />
<input type="hidden" name="id" value="<?php echo $ndfp->id; ?>" />

<table class="border" width="100%">
    <tr>
        <td width="20%"><?php echo $langs->trans('Ref'); ?></td>
        <td colspan="5"><?php echo $html->showrefnav($ndfp,'ref','',1,'ref','ref',''); ?></td>
    </tr>
        
    <tr>
        <td><?php echo $langs->trans('User'); ?></td>
        <td colspan="5"><?php echo $userstatic->getNomUrl(1); ?></td>
    </tr>   

    <tr>
        <td width="20%"><?php echo $langs->trans('Desc'); ?></td>    
        <td colspan="5"><?php echo $ndfp->description; ?></td>
       
    </tr>
    
    <tr>
        <td><?php echo $langs->trans('DateStart'); ?></td>
        <td colspan="3"><?php echo dol_print_date($ndfp->dates, 'daytext'); ?></td>
     </tr>

    <tr>
        <td><?php echo $langs->trans('DateEnd'); ?></td>
        <td colspan="3"><?php echo dol_print_date($ndfp->datee, 'daytext'); ?></td>    
    </tr>
    

     <tr>
        <td><?php echo $langs->trans('UserComment'); ?></td>
        <td align="left" colspan="3">
        <?php if ($action == 'edit'){
            $userCommentEditor->create();
        }else{
            echo nl2br($ndfp->comment_user);
        }?>
        </td>
    </tr>
    
         <tr>
        <td><?php echo $langs->trans('AdminComment'); ?></td>
        <td align="left" colspan="3">
        <?php if ($action == 'edit'){
            $adminCommentEditor->create();
        }else{
            echo nl2br($ndfp->comment_admin);
        }?>
        </td>
    </tr>
                                          
</table>
<br />

</div>


<div class="tabsAction">
<?php echo $button; ?>
</div>

</form>

<?php dol_fiche_end(); ?>

<?php llxFooter(''); ?>


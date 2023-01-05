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

/**	    \file       htdocs/ndfp/tpl/ndfp.create.tpl.php
 *		\ingroup    ndfp
 *		\brief      Ndfp module create view
 */

llxHeader('',$langs->trans('NewNdfp'));

print_fiche_titre($langs->trans("NewNdfp"));

echo ($message ? dol_htmloutput_mesg($message, '', ($error ? 'error' : 'ok'), 0) : '');


?>

<form name="add" action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
<input type="hidden" name="action" value="add" />
<input type="hidden" name="id" value="<?php echo $ndfp->id; ?>" />

<table class="border" width="100%">
<tr>
    <td class="fieldrequired"><?php echo $langs->trans('Ref'); ?></td>
    <td colspan="2"><?php echo (empty($ndfp->ref) ? $langs->trans('Draft') : $ndfp->ref); ?></td>
</tr>

<tr>
    <td class="fieldrequired"><?php echo $langs->trans('User'); ?></td>
    <td colspan="2">
        <?php if ($user->rights->ndfp->allactions->create){
                echo $html->select_users($ndfp->fk_user, 'fk_user');
            }else{
                echo $user->firstname .' '. $user->lastname;
                ?>
                <input type="hidden" name="fk_user" value="<?php echo $user->id; ?>"/>
        <?php } ?>
    </td>
</tr>

<tr>
    <td class="field"><?php echo $langs->trans('Society'); ?></td>
    <td colspan="2"><?php echo $html->select_company($ndfp->fk_soc, 'fk_soc', '', 1); ?></td>
</tr>

<?php if ($conf->projet->enabled){ ?>
<tr>
    <td class="field"><?php echo $langs->trans('Project'); ?></td>
    <td colspan="2"><?php select_projects(-1, $ndfp->fk_project, 'fk_project'); ?></td>
</tr>    
<?php } ?>


<tr>
    <td class="fieldrequired"><?php echo $langs->trans('Currency'); ?></td>
    <td colspan="2"><?php echo $html->select_currency($ndfp->cur_iso, 'currency' ); ?></td>
</tr>

<tr>
    <td class="field"><?php echo $langs->trans('Desc'); ?></td>
    <td colspan="2"><input type="text" name="description" value="<?php echo $ndfp->description; ?>" size="52"/></td>
</tr>

<tr>
    <td class="fieldrequired"><?php echo $langs->trans('DateStart'); ?></td>
    <td colspan="2"><?php echo $html->select_date($ndfp->dated, 'd', 0, 0, 0,"dates"); ?></td>
</tr>

<tr>
    <td class="fieldrequired"><?php echo $langs->trans('DateEnd'); ?></td>
    <td colspan="2"><?php echo $html->select_date($ndfp->datef, 'f',0, 0, 0,"datee"); ?></td>
</tr>

<tr>
    <td class="fieldrequired"><?php echo $langs->trans('TaxRating'); ?></td>
    <td colspan="2">
        <?php echo $ndfpHtml->select_cat($ndfp->fk_cat, 'fk_cat'); ?>
    </td>
</tr>



<tr>
    <td class="field"><?php echo $langs->trans('PDFModel'); ?></td>
    <td colspan="2"><?php echo $html->selectarray('model', $models, $conf->global->NDFP_ADDON_PDF); ?></td>
</tr>

<tr>
    <td class="field"><?php echo $langs->trans('UserComment'); ?></td>
    <td colspan="2"><?php $userCommentEditor->create(); ?></td>
</tr>
<?php if ($user->admin){ ?>
<tr>
    <td class="field"><?php echo $langs->trans('AdminComment'); ?></td>
    <td colspan="2"><?php $adminCommentEditor->create(); ?></td>
</tr>    
<?php } ?>

</table>
<br />
<center>
<input type="submit" class="button" name="bouton" value="<?php echo $langs->trans('CreateDraft'); ?>" />
</center>

</form>
   
<?php llxFooter(''); ?>

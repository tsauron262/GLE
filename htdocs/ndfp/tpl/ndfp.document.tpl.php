<?php

/* Copyright (C) 2012      Mikael Carlavan        <contact@mika-carl.fr>
 *                                                http://www.mikael-carlavan.fr
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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

/**	    \file       htdocs/ndfp/tpl/ndfp.document.tpl.php
 *		\ingroup    ndfp
 *		\brief      Ndfp module document view
 */

llxHeader();

echo ($message ? dol_htmloutput_mesg($message, '', ($error ? 'error' : 'ok'), 0) : '');


dol_fiche_head($head, $current_head, $langs->trans('Ndfp'));

echo ($formconfirm ? $formconfirm : '');

?>

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
        <td><?php echo $langs->trans('NbOfAttachedFiles'); ?></td>
        <td colspan="5"><?php echo sizeof($filearray); ?></td>
    </tr>
    
    <tr>
        <td><?php echo $langs->trans('TotalSizeOfAttachedFiles'); ?></td>
        <td colspan="5"><?php echo $totalsize.' '.$langs->trans("bytes"); ?></td>
    </tr>                                              
</table>
<br />

</div>

<?php $formfile->form_attach_new_file(DOL_URL_ROOT.'/ndfp/document.php?id='.$ndfp->id, '', 0, 0, $can_upload); ?>


<?php $formfile->list_of_documents($filearray, $ndfp, 'ndfp', '&id='.$ndfp->id); ?>

<?php dol_fiche_end(); ?>

<?php llxFooter(''); ?>


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


/**	    \file       htdocs/ndfp/tpl/admin.dictexptax.tpl.php
 *		\ingroup    ndfp
 *		\brief      Admin setup view
 */
 
llxHeader("", $langs->trans("NdfpSetup"));

echo ($message ? dol_htmloutput_mesg($message, '', ($error ? 'error' : 'ok'), 0) : '');



print_fiche_titre($langs->trans("DictionnarySetup"), $linkback, 'setup');

dol_fiche_head($head, 'dict', $langs->trans("Ndfp"));

echo $langs->trans('ExpTaxDesc');

?>
<br /><br />
<form action="<?php echo DOL_URL_ROOT.'/ndfp/admin/dictexptax.php'; ?>" method="post">
<input type="hidden" name="rowid" value="<?php echo $rowid; ?>" />
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
<input type="hidden" name="page" value="<?php echo $page; ?>" />
   
<?php 
if (sizeof($cats)){
    foreach ($cats as $cat){
        if ($cat->fk_parent == 0){
            continue;
        }
        
        $catTaxes = $taxes[$cat->rowid]; 
                                
?>


<table class="noborder" width="100%">
<tr class="liste_titre">
    <td>&nbsp;</td>
    <?php for($i=0; $i<sizeof($catTaxes); $i++){ ?>
    <td align="right"  width="20%">
        <?php 
        if ($i == (sizeof($catTaxes)-1)){
            echo $langs->trans('Beyond') .' '. $catTaxes[$i]->range;
        }else{
            echo $langs->trans('From2') .' '. $catTaxes[$i]->range .' '.$langs->trans('To').' '.$catTaxes[$i+1]->range;
        }
        echo ' '.$langs->trans('Km2');
        ?>
    </td>
    <?php } ?>
</tr>
<tr class="impair">
    <td>
    <?php if ($cat->fk_parent){
        echo $langs->trans($cats[$cat->fk_parent]->label).' - '.$langs->trans($cat->label);
    }else{
        echo $langs->trans($cat->label);
    } ?>
    </td>    
    
    <?php for($i=0; $i<sizeof($catTaxes); $i++){ ?>
    <td align="right"  width="20%">
        <?php if ($action == 'modify' && $catTaxes[$i]->rowid == $rowid){ ?>
            <input type="text" name="offset" size="5" value="<?php echo $catTaxes[$i]->offset; ?>" /> + 
            (d x <input type="text" name="coef" size="5" value="<?php echo $catTaxes[$i]->coef; ?>" />)
            <br />
            <input type="submit" class="button" name="actionmodify" value="<?php echo $langs->trans("Modify"); ?>" />
            <input type="submit" class="button" name="actioncancel" value="<?php echo $langs->trans("Cancel"); ?>" />        
        <?php }else{
            echo $catTaxes[$i]->offset.' + (d x '.$catTaxes[$i]->coef.') ';
            echo $catTaxes[$i]->modify; 
        }?>
    </td>
    <?php } ?>
</tr>    
</table>
<br />
<?php }
}
?>            

</form> 
    
<?php llxFooter(''); ?>

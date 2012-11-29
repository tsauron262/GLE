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


/**	    \file       htdocs/ndfp/tpl/admin.dictexptaxrange.tpl.php
 *		\ingroup    ndfp
 *		\brief      Admin setup view
 */
 
llxHeader("", $langs->trans("NdfpSetup"));

echo ($message ? dol_htmloutput_mesg($message, '', ($error ? 'error' : 'ok'), 0) : '');




print_fiche_titre($langs->trans("DictionnarySetup"), $linkback, 'setup');

dol_fiche_head($head, 'dict', $langs->trans("Ndfp"));

echo $langs->trans('ExpTaxRangeDesc');

echo $formconfirm;

?>
<br />
<br />
    <form action="<?php echo DOL_URL_ROOT.'/ndfp/admin/dictexptaxrange.php'; ?>" method="post">
    <input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
    
    <table class="noborder" width="100%">
        <tr class="liste_titre">
            <td><?php echo $langs->trans('Range'); ?></td>
            <td><?php echo $langs->trans('Category'); ?></td>
            <td colspan="3">&nbsp;</td>
        </tr>
    
        <tr class="impair">
            <td><input type="text"  class="flat" size="32" name="range" value="" /></td>
            <td><?php echo $ndfpHtml->select_cat('', 'fk_cat'); ?></td>
            <td colspan="3" align="right">
                <input type="submit" class="button" name="actionadd" value="<?php echo $langs->trans("Add"); ?>" />
            </td>
        </tr>
        
        <tr>
            <td colspan="5">&nbsp;</td>
        </tr>

        <?php if ($num > $listlimit){ ?>
        <tr class="none">
            <td align="right" colspan="5">
                    <?php print_fleche_navigation($page, $_SERVER["PHP_SELF"],'',($num > $listlimit),$langs->trans("Page").' '.($page+1)); ?>
            </td>
        </tr>
        <?php } ?>
        
        <?php 
            if (sizeof($ranges)){ 
            $i = 0;        
        ?>
        <tr class="liste_titre">
            <?php print_liste_field_titre($langs->trans('Range'), "dictexptaxrange.php", "r.range",($page ? 'page='.$page.'&':''),"","",$sortfield,$sortorder); ?>
            <?php print_liste_field_titre($langs->trans('Category'), "dictexptaxrange.php", "r.fk_cat",($page ? 'page='.$page.'&':''),"","",$sortfield,$sortorder); ?>
            <?php print_liste_field_titre($langs->trans("Status"),"dictexptaxrange.php","r.active",($page?'page='.$page.'&':''),"",'align="center"',$sortfield,$sortorder); ?>
            <td colspan="2"  class="liste_titre">&nbsp;</td>
        </tr>
        
        <?php foreach ($ranges as $range){ ?>
            <tr class="<?php echo ($i%2==0 ? 'impair' : 'pair'); ?>">
            <td>
                <?php if ($action == 'modify' && $range->rowid == $rowid){ ?>
                        <form action="<?php echo DOL_URL_ROOT.'/ndfp/admin/dictexptaxrange.php'; ?>" method="post">
                        <input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
                        <input type="hidden" name="rowid" value="<?php echo $range->rowid; ?>" />
                        <input type="hidden" name="active" value="<?php echo $range->active; ?>" />
                        <input type="hidden" name="page" value="<?php echo $page; ?>" />
                                        
                        <input type="text" name="range" class="flat" size="32" value="<?php echo $range->range; ?>" />
                <?php }else{
                        echo $range->range; 
                } ?>
            </td>
            <td>
                <?php if ($action == 'modify' && $range->rowid == $rowid){
                         echo $ndfpHtml->select_cat($range->fk_cat, 'fk_cat');
                     }else{
                        echo $cats_names[$range->fk_cat]; 
                } ?>             
            </td>
                <?php if ($action == 'modify' && $range->rowid == $rowid){ ?>
                    <td colspan="3" align="right">
                        <input type="submit" class="button" name="actionmodify" value="<?php echo $langs->trans("Modify"); ?>" />
                        <input type="submit" class="button" name="actioncancel" value="<?php echo $langs->trans("Cancel"); ?>" />
                    </td>                        
                     <?php }else{ ?>
                    <td align="center" nowrap="nowrap">
                        <?php echo $range->activate; ?>
                    </td>
                    <td align="center">
                        <?php echo $range->modify; ?>
                    </td>
                    <td align="center">
                        <?php echo $range->delete; ?>
                    </td>
                <?php } ?>              
        
            </tr>                
        <?php 
            $i++;
            } 
        ?>
      
        <?php } ?>
    </table>
    </form>            
<br />

<?php llxFooter(''); ?>

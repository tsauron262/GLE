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

/**	    \file       htdocs/ndfp/tpl/admin.dictexp.tpl.php
 *		\ingroup    ndfp
 *		\brief      Admin setup view
 */
 
llxHeader("", $langs->trans("NdfpSetup"));

echo ($message ? dol_htmloutput_mesg($message, '', ($error ? 'error' : 'ok'), 0) : '');



print_fiche_titre($langs->trans("DictionnarySetup"), $linkback, 'setup');

dol_fiche_head($head, 'dict', $langs->trans("Ndfp"));

echo $langs->trans('ExpDesc');

echo $formconfirm;

?>
<br />
<br />
    <form action="<?php echo DOL_URL_ROOT.'/ndfp/admin/dictexp.php'; ?>" method="post">
    <input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
    
    <table class="noborder" width="100%">
        <tr class="liste_titre">
            <td><?php echo $langs->trans('Code'); ?></td>
            <td><?php echo $langs->trans('Label'); ?></td>
            <td><?php echo $langs->trans('TVARate'); ?></td>
            <td colspan="3">&nbsp;</td>
        </tr>
    
        <tr class="impair">
            <td><input type="text"  class="flat" size="32" name="code" value="" /></td>
            <td><input type="text"  class="flat" size="32" name="label" value="" /></td>
            <td><?php echo $html->load_tva('fk_tva'); ?></td>
            <td colspan="3" align="right">
                <input type="submit" class="button" name="actionadd" value="<?php echo $langs->trans("Add"); ?>" />
            </td>
        </tr>
        
        <tr>
            <td colspan="6">&nbsp;</td>
        </tr>

        <?php if ($num > $listlimit){ ?>
        <tr class="none">
            <td align="right" colspan="6">
                    <?php print_fleche_navigation($page, $_SERVER["PHP_SELF"],'&table='.$table,($num > $listlimit),$langs->trans("Page").' '.($page+1)); ?>
            </td>
        </tr>
        <?php } ?>
        
        <?php 
            if (sizeof($exps)){ 
            $i = 0;        
        ?>
        <tr class="liste_titre">
            <?php print_liste_field_titre($langs->trans('Code'), "dictexp.php", "e.code",($page ? 'page='.$page.'&':''),"","",$sortfield,$sortorder); ?>
            <?php print_liste_field_titre($langs->trans('Label'), "dictexp.php", "e.label",($page ? 'page='.$page.'&':''),"","",$sortfield,$sortorder); ?>
            <?php print_liste_field_titre($langs->trans('TVARate'), "dictexp.php", "e.fk_tva",($page ? 'page='.$page.'&':''),"","",$sortfield,$sortorder); ?>
            <?php print_liste_field_titre($langs->trans("Status"),"dictexp.php","e.active",($page?'page='.$page.'&':''),"",'align="center"',$sortfield,$sortorder); ?>
            <td colspan="2"  class="liste_titre">&nbsp;</td>
        </tr>
        
        <?php foreach ($exps as $exp){ ?>
            <tr class="<?php echo ($i%2==0 ? 'impair' : 'pair'); ?>">
            <td>
                <?php if ($action == 'modify' && $exp->rowid == $rowid){ ?>
                        <form action="<?php echo DOL_URL_ROOT.'/ndfp/admin/dictexp.php'; ?>" method="post">
                        <input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
                        <input type="hidden" name="rowid" value="<?php echo $exp->rowid; ?>" />
                        <input type="hidden" name="active" value="<?php echo $exp->active; ?>" />
                        <input type="hidden" name="page" value="<?php echo $page; ?>" />
                        
                        <?php if ($exp->code == 'EX_KME'){ ?>
                            <?php echo $exp->code; ?><input type="hidden" name="code" class="flat" size="32" value="<?php echo $exp->code; ?>" />
                        <?php }else{ ?>
                            <input type="text" name="code" class="flat" size="32" value="<?php echo $exp->code; ?>" />
                        <?php } ?>                
                        
                <?php }else{
                        echo $exp->code; 
                } ?>
            </td>
            <td>
                <?php if ($action == 'modify' && $exp->rowid == $rowid){ ?>
                        <input type="text" name="label" class="flat" size="32" value="<?php echo $langs->trans($exp->label); ?>" />
                <?php }else{
                        echo $langs->trans($exp->label); 
                } ?>            
            </td>
            <td>
                <?php if ($action == 'modify' && $exp->rowid == $rowid){
                         echo $html->load_tva('fk_tva', $exp->taux);
                     }else{
                        echo $exp->taux .'%'; 
                } ?>             
            </td>
                <?php if ($action == 'modify' && $exp->rowid == $rowid){ ?>
                    <td colspan="3" align="right">
                        <input type="submit" class="button" name="actionmodify" value="<?php echo $langs->trans("Modify"); ?>" />
                        <input type="submit" class="button" name="actioncancel" value="<?php echo $langs->trans("Cancel"); ?>" />
                    </td>                        
                     <?php }else{ ?>
                    <td align="center" nowrap="nowrap">
                        <?php echo $exp->activate; ?>
                    </td>
                    <td align="center">
                        <?php echo $exp->modify; ?>
                    </td>
                    <td align="center">
                        <?php echo $exp->delete; ?>
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

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


/**	    \file       htdocs/ndfp/tpl/ndfp.index.tpl.php
 *		\ingroup    ndfp
 *		\brief      Ndfp module default view
 */

llxHeader();

print_fiche_titre($langs->trans("MenuTitle"));


echo ($message ? dol_htmloutput_mesg($message, '', ($error ? 'error' : 'ok'), 0) : '');



?>


<table border="0" width="100%" class="notopnoleftnoright">
<tr>
<td valign="top" width="30%" class="notopnoleft">
    <form method="post" action="<?php echo DOL_URL_ROOT.'/ndfp/ndfp.php'; ?>">
    <input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
    <table class="noborder" width="100%">
        <tr class="liste_titre">
            <td colspan="3"><?php echo $langs->trans("SearchNdfp"); ?></td>
        </tr>
        <tr class="impair">
            <td nowrap><?php echo $langs->trans("Ref"); ?> :</td>
            <td><input type="text" class="flat" name="ref" size="18" /></td>
            <td rowspan="2"><input type="submit" value="<?php echo $langs->trans("Search"); ?>" class="button" /></td>
        </tr>
        <tr class="impair">
            <td nowrap><?php echo $langs->trans("Other"); ?> :</td>
            <td><input type="text" class="flat" name="sall" size="18" /></td>
        </tr>
    </table>
    </form>
    <br />
    
    <table class="noborder" width="100%">
        <tr class="liste_titre">
		  <td colspan="3"><?php echo $langs->trans("DraftNdfps").(sizeof($drafts)?' ('.sizeof($drafts).')':''); ?></td>
        </tr>

<?php 
    if (sizeof($drafts)) { 
    $i = 0;
    ?>
        
    <?php foreach ($drafts as $draft){ ?>
        <tr class="<?php echo $i%2 == 0 ? 'impair' : 'pair'; ?>">
				<td nowrap="nowrap"><?php echo $draft->url; ?></td>
				<td nowrap="nowrap"><?php echo $draft->username; ?></td>
				<td align="right" nowrap="nowrap"><?php echo price($draft->total_ttc); ?></td>
		</tr>
    <?php $i++; } ?>

<?php }else{ ?>
    <tr class="impair">
        <td colspan="3"><?php echo $langs->trans("NoResults"); ?></td>
    </tr>    
<?php } ?>
            
</table>
<br />    
</td>
<td valign="top" width="70%" class="notopnoleftnoright">
    <table class="noborder" width="100%">
        <tr class="liste_titre">
            <td colspan="3"><?php echo $langs->trans("LastNdfps", $limit); ?></td>
            <td align="right"><?php echo $langs->trans("TTCAmount"); ?></td>
            <td align="right"><?php echo $langs->trans("ModificationDate"); ?></td>
        </tr> 
           
<?php 
    if (sizeof($ndfps)) { 
        $i = 0;   
    ?>
    
    <?php foreach ($ndfps as $ndfp){ ?>
		<tr class="<?php echo $i%2 == 0 ? 'impair' : 'pair'; ?>">
				<td nowrap="nowrap">
				    <table class="nobordernopadding">
                        <tr class="nocellnopadd">
				            <td width="100" class="nobordernopadding" nowrap="nowrap">
                            <?php echo $ndfp->url; ?>
                            </td>
				            <td width="20" class="nobordernopadding" nowrap="nowrap">
                            </td>
				            <td width="16" align="right" class="nobordernopadding">
				            <?php $formfile->show_documents('ndfp', $ndfp->filename, $ndfp->filedir, $ndfp->urlsource,'','','',1,'',1); ?>
                            </td>
                        </tr>
                    </table>

				</td>
                
				<td align="left">
				<?php echo $ndfp->username; ?>
                </td>                
				
                <td align="left">
				<?php echo $ndfp->society; ?>
                </td>
				
                <td align="right"><?php echo price($ndfp->total_ttc); ?></td>
				<td align="right"><?php echo dol_print_date($ndfp->mdate,'day'); ?></td>
        </tr>   
    <?php 
        $i++;
        } ?>

<?php }else{ ?>
    <tr class="impair">
        <td colspan="5"><?php echo $langs->trans("NoResults"); ?></td>
    </tr>    
<?php } ?>
    </table>
    <br />
    
	<table class="noborder" width="100%">
	   <tr class="liste_titre">
			<td colspan="2"><?php echo $langs->trans("NdfpToBill").(sizeof($unpaid_ndfps)?' ('.sizeof($unpaid_ndfps).')':''); ?></td>
			<td align="right"><?php echo $langs->trans("AmountTTC"); ?></td>
			<td align="right"><?php echo $langs->trans("Paid"); ?></td>
			<td width="16">&nbsp;</td>
	   </tr>
<?php if (sizeof($unpaid_ndfps)) { 
    $i = 0;
    ?>       
    <?php foreach ($unpaid_ndfps as $ndfp){ ?>

        <tr class="<?php echo $i%2 == 0 ? 'impair' : 'pair'; ?>">
				<td nowrap="nowrap">

				    <table class="nobordernopadding">
                        <tr class="nocellnopadd">
				            <td width="100" class="nobordernopadding" nowrap="nowrap">
				                <?php echo $ndfp->url; ?>
				            </td>
				            <td width="20" class="nobordernopadding" nowrap="nowrap">&nbsp;</td>
				            <td width="16" align="right" class="nobordernopadding">
				                <?php $formfile->show_documents('ndfp', $ndfp->filename, $ndfp->filedir, $ndfp->urlsource,'','','',1,'',1); ?>
                            </td>
                        </tr>
                    </table>

				</td>

				<td align="left">
                    <?php echo $ndfp->username; ?>
				</td>
                
                                
				<td align="right"><?php echo price($ndfp->total_ttc); ?></td>
				<td align="right"><?php echo price($ndfp->already_paid); ?></td>
				<td><?php echo $ndfp->statut; ?></td>
        </tr>
    <?php $i++;
    } ?>

	<tr class="liste_total">
        <td colspan="2"><?php echo $langs->trans("Total");?></td>
		<td align="right"><?php echo price($tot_ttc); ?></td>
    	<td align="right"><?php echo price($tot_paid); ?></td>
    	<td>&nbsp;</td>
	</tr>
<?php }else{ ?>
    <tr class="impair">
        <td colspan="5"><?php echo $langs->trans("NoResults"); ?></td>
    </tr>    
<?php } ?>    
	</table>
    <br />
</td>
</tr>

</table>

<?php llxFooter(''); ?>

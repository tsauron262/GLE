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

/**	    \file       htdocs/ndfp/tpl/ndfp.default.tpl.php
 *		\ingroup    ndfp
 *		\brief      Ndfp module default view
 */

llxHeader('', '', '', '', 0, 0, array(0 => '/ndfp/js/functions.js.php?fk_cat='.$ndfp->fk_cat));

echo ($message ? dol_htmloutput_mesg($message, '', ($error ? 'error' : 'ok'), 0) : '');

dol_fiche_head($head, $current_head, $langs->trans('Ndfp'));

echo $formconfirm;

?>
<table class="border" width="100%">
    <tr>
        <td width="20%"><?php echo $langs->trans('Ref'); ?></td>
        <td colspan="5"><?php echo $html->showrefnav($ndfp,'ref','',1,'ref','ref',''); ?></td>
    </tr>
    
    <tr>
        <td>
            <table class="nobordernopadding" width="100%">
                <tr>
                    <td><?php echo $langs->trans('Company'); ?></td>
                    <td colspan="5">
                        <?php if ($ndfp->statut == 0){ ?>
                            <a href="<?php echo $_SERVER["PHP_SELF"].'?action=editsoc&amp;id='.$ndfp->id; ?>">
                                <?php echo img_edit($langs->trans('SetLinkToThirdParty'),1); ?>
                            </a>                            
                        <?php } ?>
                    </td>
                </tr>
            </table>
        </td>
        <td colspan="5">
			<?php 
            if ($action == 'editsoc'){
                $html->form_thirdparty($_SERVER['PHP_SELF'].'?id='.$ndfp->id,$ndfp->fk_soc,'fk_soc');
            }else if ($ndfp->fk_soc > 0){ ?>
			&nbsp;<?php echo $societestatic->getNomUrl(1,'compta'); ?>
            &nbsp; (<a href="<?php echo DOL_URL_ROOT.'/ndfp/ndfp.php?fk_soc='.$ndfp->fk_soc; ?>"><?php echo $langs->trans('OtherNdfp'); ?></a>)
			<?php } ?>        
        </td>
    </tr>            
        
    <tr>
        <td>
            <table class="nobordernopadding" width="100%">
                <tr>
                    <td><?php echo $langs->trans('User'); ?></td>
                    <td colspan="5">
                        <?php if ($ndfp->statut == 0){ ?>
			             <a href="<?php echo $_SERVER["PHP_SELF"].'?action=edituser&amp;id='.$ndfp->id; ?>">
                            <?php echo img_edit($langs->trans('SetUser'),1); ?>
                        </a>
                        <?php } ?>
                    </td>
                </tr>
            </table>
        </td>
        <td colspan="5">
			<?php 
            if ($action == 'edituser'){
                $html->form_users($_SERVER['PHP_SELF'].'?id='.$ndfp->id,$ndfp->fk_user,'fk_user');
            }else{ ?>
			&nbsp;<?php echo $userstatic->getNomUrl(1); ?>
            &nbsp; (<a href="<?php echo DOL_URL_ROOT.'/ndfp/ndfp.php?fk_user='.$ndfp->fk_user; ?>"><?php echo $langs->trans('OtherNdfp'); ?></a>)
			<?php } ?>          
        </td>
    </tr>   

    <tr>
        <td width="20%">        
            <table class="nobordernopadding" width="100%">
                <tr>
                    <td><?php echo $langs->trans('Desc'); ?></td>
                    <td colspan="5">
                        <?php if ($ndfp->statut == 0){ ?>
			             <a href="<?php echo $_SERVER["PHP_SELF"].'?action=editdesc&amp;id='.$ndfp->id; ?>">
                            <?php echo img_edit($langs->trans('SetDesc'),1); ?>
                        </a>
                        <?php } ?>
                    </td>
                </tr>
            </table>
        </td>    
        <td colspan="5">
			<?php if ($action == 'editdesc'){ ?>
            <form method="POST" action="<?php echo $_SERVER["PHP_SELF"].'?id='.$ndfp->id; ?>" name="formdesc">
                <input type="hidden" name="action" value="setdesc" />
                <input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
                <table class="nobordernopadding" cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" name="description" value="<?php echo $ndfp->description; ?>" size="52"/></td>
                        <td align="left"><input type="submit" class="button" value="<?php echo $langs->trans('Modify'); ?>" /></td>
                    </tr>
                </table>
            </form>            
            <?php }else{ echo $ndfp->description; } ?>          
        </td>
       
    </tr>

    <tr>
        <td width="20%">        
            <table class="nobordernopadding" width="100%">
                <tr>
                    <td><?php echo $langs->trans('TaxRating'); ?></td>
                    <td colspan="5">
                         <?php if ($ndfp->statut == 0){ ?>
			             <a href="<?php echo $_SERVER["PHP_SELF"].'?action=editcat&amp;id='.$ndfp->id; ?>">
                            <?php echo img_edit($langs->trans('SetCat'),1); ?>
                        </a>
                        <?php } ?>
                    </td>
                </tr>
            </table>
        </td>    
        <td colspan="5">
			<?php if ($action == 'editcat'){ ?>
            <form method="POST" action="<?php echo $_SERVER["PHP_SELF"].'?id='.$ndfp->id; ?>" name="formdesc">
                <input type="hidden" name="action" value="setcat" />
                <input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
                <table class="nobordernopadding" cellpadding="0" cellspacing="0">
                    <tr>
                        <td><?php echo $ndfpHtml->select_cat($ndfp->fk_cat, 'fk_cat'); ?></td>
                        <td align="left"><input type="submit" class="button" value="<?php echo $langs->trans('Modify'); ?>" /></td>
                    </tr>
                </table>
            </form>            
            <?php }else{ 
                echo $ndfp->get_cat_name(); 
            } ?>          
        </td>
       
    </tr>
        
    <tr>
        <td>
            <table class="nobordernopadding" width="100%">
                <tr>
                    <td>
                    <?php echo $langs->trans('DateStart'); ?>
                    </td>
                    <?php if ($action != 'editdates' && $ndfp->statut == 0){ ?>
                        <td align="right">
                            <a href="<?php echo $_SERVER["PHP_SELF"].'?action=editdates&amp;id='.$ndfp->id; ?>"><?php echo img_edit($langs->trans('SetDate'),1); ?></a>
                        </td>
                    <?php } ?> 
                </tr>
            </table>
        </td>
        <td colspan="3">
            <?php if ($action == 'editdates'){
                    $html->form_date($_SERVER['PHP_SELF'].'?id='.$ndfp->id, $ndfp->dates, 'dates');
                }else{ 
                    echo dol_print_date($ndfp->dates, 'daytext');
                } ?>
        </td>
        <td rowspan="<?php echo $nbrows; ?>" colspan="2" valign="top"> 
            <table class="nobordernopadding" width="100%">
                <tr class="liste_titre">
                    <td><?php echo $langs->trans('Payments'); ?></td>
                    <td><?php echo $langs->trans('Type'); ?></td>
                    <td align="right"><?php echo $langs->trans('Amount'); ?></td>
                    <td width="18">&nbsp;</td>
                </tr>
                
                <?php for($k=0; $k < sizeof($payments); $k++){ ?>
                 <tr class="<?php echo ($k%2==0 ? 'pair' : 'impair'); ?>">
                     <td>
                         <a href="<?php echo DOL_URL_ROOT.'/ndfp/payment.php?id='.$payments[$k]->rowid; ?>">
                            <?php echo img_object($langs->trans('ShowPayment'),'payment').' '.dol_print_date($db->jdate($payments[$k]->dp),'day'); ?>
                         </a>
                     </td>
                     <td><?php echo $payments[$k]->label.' '.$payments[$k]->payment_number; ?></td>
                     <td align="right"><?php echo price($payments[$k]->amount); ?></td>
                     <td>&nbsp;</td>
                 </tr>                
                <?php } ?>
                
               
               <tr>
                    <td colspan="2" align="right">
                    <?php echo $langs->trans('AlreadyPaid'); ?>
                    </td>
                    <td align="right">
                        <?php echo price($already_paid); ?>
                    </td>
                    <td>&nbsp;</td>
               </tr>

                
                <?php if ($ndfp->statut == 3){ ?>
                    <tr>
                        <td colspan="2" align="right" nowrap="1">

                        <?php 
                            echo $html->textwithpicto($langs->trans("Abandoned").':',$langs->trans("HelpAbandonOther"),-1);
                         ?>
                        </td>
                        <td align="right">
                        <?php echo price($ndfp->total_ttc - $already_paid); ?>
                        </td>
                        <td>&nbsp;</td>
                    </tr>
                    <?php $remain_to_pay_for_display = 0;
                } ?>
    
                <tr>
                    <td colspan="2" align="right">
                        <?php echo $langs->trans("Billed"); ?> :
                    </td>
                    <td align="right" style="border: 1px solid;">
                    <?php echo price($ndfp->total_ttc); ?>
                    </td>
                    <td>&nbsp;</td>
                </tr>

                <tr>
                    <td colspan="2" align="right">
                    <?php 
                        echo $langs->trans('RemainderToPay').' :';
                    ?> 
                    </td>
                    <td align="right" style="border: 1px solid;" bgcolor="#f0f0f0">
                        <b><?php echo price($remain_to_pay_for_display); ?></b>
                    </td>
                    <td nowrap="nowrap">&nbsp;</td>
                </tr>                        
            </table>
       </td>        
    </tr>

    <tr>
        <td>
            <table class="nobordernopadding" width="100%">
                <tr>
                    <td>
                    <?php echo $langs->trans('DateEnd'); ?>
                    </td>
                    <?php if ($action != 'editdatee' && $ndfp->statut == 0){ ?>
                        <td align="right">
                            <a href="<?php echo $_SERVER["PHP_SELF"].'?action=editdatee&amp;id='.$ndfp->id; ?>"><?php echo img_edit($langs->trans('SetDate'),1); ?></a>
                        </td>
                    <?php } ?> 
                </tr>
            </table>
        </td>
        <td colspan="3">
            <?php if ($action == 'editdatee'){
                    $html->form_date($_SERVER['PHP_SELF'].'?id='.$ndfp->id, $ndfp->datee, 'datee');
                }else{ 
                    echo dol_print_date($ndfp->datee, 'daytext');
                } ?>
        </td>    
    </tr>

    <tr>
        <td><?php echo $langs->trans('HTAmount'); ?></td>
        <td align="right" colspan="2" nowrap><?php echo price($ndfp->total_ht); ?></td>
        <td><?php echo $langs->trans('Currency'.$ndfp->cur_iso); ?></td>
    </tr> 

     <tr>
        <td><?php echo $langs->trans('TVAAmount'); ?></td>
        <td align="right" colspan="2" nowrap><?php echo price($ndfp->total_tva); ?></td>
        <td><?php echo $langs->trans('Currency'.$ndfp->cur_iso); ?></td>
    </tr> 
    
    <tr>
        <td><?php echo $langs->trans('TTCAmount'); ?></td>
        <td align="right" colspan="2" nowrap><?php echo price($ndfp->total_ttc); ?></td>
        <td><?php echo $langs->trans('Currency'.$ndfp->cur_iso); ?></td>
    </tr>
         
     <tr>
        <td><?php echo $langs->trans('Status'); ?></td>
        <td align="left" colspan="3"><?php echo $ndfp->get_lib_statut(4, $already_paid); ?></td>
    </tr>
    
    <?php if ($conf->projet->enabled){ ?>
    <tr>
        <td>
           <table class="nobordernopadding" width="100%">
                <tr>
                    <td><?php echo $langs->trans('Project'); ?></td>
                <?php if ($action != 'setproject' && $ndfp->statut == 0){ ?>
                    <td align="right">
                        <a href="<?php echo $_SERVER["PHP_SELF"].'?action=setproject&amp;id='.$ndfp->id; ?>">
                            <?php echo img_edit($langs->trans('SetProject'),1); ?>
                        </a>
                    </td>
                <?php } ?>
                </tr>
           </table>
        </td>
        <td colspan="3">
                <?php if ($action == 'setproject'){
                    $html->form_project($_SERVER['PHP_SELF'].'?id='.$ndfp->id,$ndfp->fk_soc, $ndfp->fk_project,'fk_project');
                }else{
                    if ($projectstatic->id > 0){
                        echo $projectstatic->getNomUrl(1);
                    }
                } ?>
        </td>
   </tr>
   <?php } ?>                                           
</table>
<br />

<table id="tablelines" class="noborder" width="100%">
<?php if ($numLines > 0){ ?>
    <tr class="liste_titre nodrag nodrop">
        <td><?php echo $langs->trans('Type'); ?></td>
        <td align="right" width="90"><?php echo $langs->trans('DateStart'); ?></td>
        <td align="right" width="90"><?php echo $langs->trans('DateEnd'); ?></td>
		<td align="right" width="50"><?php echo $langs->trans('Qty'); ?></td>
		<td align="right" width="70"><?php echo $langs->trans('TVA'); ?></td>
		<td align="right" width="70"><?php echo $langs->trans('Total_HT'); ?> (<?php echo $langs->trans('Currency'.$ndfp->cur_iso); ?>)</td>
		<td align="right" width="70"><?php echo $langs->trans('Total_TTC'); ?> (<?php echo $langs->trans('Currency'.$ndfp->cur_iso); ?>)</td>        
		<td width="50">&nbsp;</td>
	</tr>


<?php 

for($i = 0; $i < $numLines; $i++){
    $line = $ndfp->lines[$i];
    
    if ($action == 'editline' && $lineid == $line->rowid){ ?>
    
    <form action="<?php echo $_SERVER["PHP_SELF"].'?id='.$ndfp->id; ?>" method="POST">
    <input type="hidden" name="token" value="<?php  echo $_SESSION['newtoken']; ?>" />
    <input type="hidden" name="action" value="updateline" />
    <input type="hidden" name="id" value="<?php echo $ndfp->id; ?>" />
    <input type="hidden" name="lineid" value="<?php echo $line->rowid; ?>" />
    
    <?php } ?>
        
    <tr class="<?php echo ($i%2==0 ? 'impair' : 'pair'); ?>">     
        <td>
            <?php if ($action == 'editline' && $lineid == $line->rowid){ ?>
                <select id="fk_exp" name="fk_exp" onchange="changeStateTTC();changeTVA();">
                    <?php for ($k=0; $k<sizeof($predefined_expenses); $k++){ ?>
                        <option value="<?php echo $predefined_expenses[$k]->rowid; ?>" <?php echo ($predefined_expenses[$k]->rowid==$line->fk_exp ? 'selected="selected"' : ''); ?> >
                            <?php echo $langs->trans($predefined_expenses[$k]->label); ?>
                        </option>
                    <?php } ?>
                </select>                
            <?php }else{ echo $langs->trans($line->label); }  ?>
        </td>
        <td width="90" align="right" nowrap="nowrap">
            <?php if ($action == 'editline' && $lineid == $line->rowid){ ?>
                <?php echo $langs->trans("DateStart"); ?> : <?php echo $html->select_date($line->dated, 'es', 0, 0, 0,"addexpense"); ?>
            <?php }else{
                echo dol_print_date($line->dated, '%d/%m/%Y');
                }
            ?> 
        </td>
        <td width="90" align="right" nowrap="nowrap">
            <?php if ($action == 'editline' && $lineid == $line->rowid){ ?>
                <?php echo $langs->trans("DateEnd"); ?> : <?php echo $html->select_date($line->datef, 'ee', 0, 0, 0,"addexpense"); ?>
            <?php }else{
                echo dol_print_date($line->datef, '%d/%m/%Y'); 
                }
            ?> 
        </td>                    
        <td align="right" nowrap="nowrap">
            <?php if ($action == 'editline' && $lineid == $line->rowid){ ?>
                <input type="text" size="8" id="qty" name="qty" value="<?php echo $line->qty; ?>" onkeydown="changeTTC(event);" onchange="computeTTC(this.value);"/>
            <?php }else{ echo $line->qty; } ?>
        </td>
        
        <td align="right" nowrap="nowrap">
            <?php if ($action == 'editline' && $lineid == $line->rowid){
                 echo $ndfpHtml->select_tva($line->fk_tva, 'fk_tva', 'onchange="computeHT();"');                
                }else{ echo $line->taux.'%'; } ?>
        </td>
    
    
        <td align="right" nowrap="nowrap">
            <?php if ($action == 'editline' && $lineid == $line->rowid){ ?>            
                <input type="text" size="8" id="total_ht" name="total_ht" value="<?php echo ($line->total_ht); ?>" disabled="disabled"/>
            <?php }else{ echo price($line->total_ht); } ?>
        </td>
    
        <td align="right" nowrap="nowrap">
            <?php if ($action == 'editline' && $lineid == $line->rowid){ ?> 
                <input type="text" size="8" id="total_ttc" name="total_ttc" value="<?php echo ($line->total_ttc); ?>" onchange="computeHT();" onkeydown="changeHT(event);" />       
            <?php }else{ echo price($line->total_ttc); } ?>
        </td>
    
        <?php if ($action == 'editline' && $lineid == $line->rowid){ ?> 
        <td align="right">
            <input type="submit" class="button" name="save" value="<?php echo $langs->trans("Save"); ?>" />&nbsp;<input type="submit" class="button" name="cancel" value="<?php echo $langs->trans("Cancel"); ?>" />
        </td>
        
        </form>
        <?php }else{ ?>
            <?php if ($ndfp->statut == 0) { ?>
            <td align="right">
                <a href="<?php echo $_SERVER["PHP_SELF"].'?id='.$ndfp->id.'&amp;action=editline&amp;lineid='.$line->rowid; ?>">
                    <?php echo img_edit(); ?>
                </a>
                <a href="<?php echo $_SERVER["PHP_SELF"].'?id='.$ndfp->id.'&amp;action=ask_deleteline&amp;lineid='.$line->rowid; ?>">
                    <?php echo img_delete(); ?>
                </a>
            </td>                                    
        <?php }else{ ?>
        <td>&nbsp;</td>
        <?php } } ?>
    </tr>
<?php } ?>    


<?php } ?>


<?php if ($can_add_expenses){ ?>
      
<tr class="liste_titre nodrag nodrop">
	<td colspan="3"><?php echo $langs->trans("AddNewExpense"); ?></td>
	<td align="right"><?php echo $langs->trans('Qty'); ?></td>
	<td align="right"><?php echo $langs->trans('TVA'); ?></td>
    <td align="right"><?php echo $langs->trans('Total_HT'); ?> (<?php echo $langs->trans('Currency'.$ndfp->cur_iso); ?>)</td>  
	<td align="right"><?php echo $langs->trans('Total_TTC'); ?> (<?php echo $langs->trans('Currency'.$ndfp->cur_iso); ?>)</td>        
	<td>&nbsp;</td>
</tr>

<form name="addexpense" id="addexpense" action="<?php echo $_SERVER["PHP_SELF"].'?id='.$ndfp->id; ?>" method="POST">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
<input type="hidden" name="action" value="addline" />
<input type="hidden" name="id" value="<?php echo $ndfp->id; ?>" />

<tr class="pair">
	<td colspan="3">
        <select id="fk_exp" name="fk_exp" onchange="changeStateTTC();changeTVA();">
            <?php for ($i=0; $i<sizeof($predefined_expenses); $i++){ ?>
                <option value="<?php echo $predefined_expenses[$i]->rowid; ?>" <?php echo ($i==0 ? 'selected="selected"' : ''); ?> >
                    <?php echo $langs->trans($predefined_expenses[$i]->label); ?>
                </option>
            <?php } ?>
        </select>
	<br />
    <?php echo $langs->trans("DateStart"); ?> : <?php echo $html->select_date($ndfp->dates, 'es', 0, 0, 0,"addexpense"); ?>&nbsp;
    <?php echo $langs->trans("DateEnd"); ?> : <?php echo $html->select_date($ndfp->datee, 'ee', 0, 0, 0,"addexpense"); ?>
    </td>
	<td align="right"><input type="text" size="8" id="qty" name="qty" value="0" onkeydown="changeTTC(event);" onchange="computeTTC(this.value);"/></td>
    <td align="right"><?php echo $ndfpHtml->select_tva($selected_vat, 'fk_tva', 'onchange="computeHT();"'); ?></td>
    <td align="right"><input type="text" size="8" id="total_ht" name="total_ht" value="0" disabled="disabled"/></td>
    <td align="right"><input type="text" size="8" id="total_ttc" name="total_ttc" value="0" onkeydown="changeHT(event);" onchange="computeHT();" /></td>

	<td align="center" align="right">
        <input type="submit" class="button" value="<?php echo $langs->trans("Add"); ?>" name="addline" />
    </td>
</tr>

</form>
<?php } ?>
</table>
</div>

<?php if ($action != 'presend'){ ?>
    <div class="tabsAction">
    <?php foreach ($buttons as $button)
    {
        echo $button;
    }
    ?>
    </div>
    
    <br />
    <table width="100%">
        <tr>
            <td width="50%" valign="top">
                <?php $formfile->show_documents('ndfp', $filename, $filedir, $urlsource, $genallowed, $delallowed, $ndfp->modelpdf,1,0,0,28,0,'','','',$user->lang,''); ?>
            </td>
            
            <td valign="top" width="50%">
                <?php $formactions->showactions($ndfp, 'ndfp', ''); ?>
            </td>
        </tr>
    </table>     
    
<?php }else{ ?>

    <?php $formmail->show_form(); ?>

<?php } ?>
       
<br />

<?php dol_fiche_end(); ?>

<?php llxFooter(''); ?>


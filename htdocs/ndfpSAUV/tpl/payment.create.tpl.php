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

/**	    \file       htdocs/ndfp/tpl/payment.create.tpl.php
 *		\ingroup    ndfp
 *		\brief      Ndfp module payment create view
 */

llxHeader('');

echo ($message ? dol_htmloutput_mesg($message, '', ($error ? 'error' : 'ok'), 0) : '');

print_fiche_titre($langs->trans('DoPayment'));

?>


<form name="addpaiement" action="payment.php" method="post">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
<input type="hidden" name="action" value="add" />
<input type="hidden" name="fk_user" value="<?php echo $fk_user; ?>" />

<table class="border" width="100%">

    <tr class="liste_titre">
        <td colspan="3"><?php echo $langs->trans('Payment'); ?></td>
    </tr>
    <tr>
        <td><?php echo $langs->trans('User'); ?></td>
        <td colspan="2"><?php echo $userstatic->getNomUrl(1); ?></td>
    </tr>
    <tr>
        <td class="fieldrequired"><?php echo $langs->trans('Date'); ?></td>
        <td><?php echo $html->select_date($datep, '', '', '', '',"addpaiement", 1, 1); ?></td>
        <td><?php echo $langs->trans('Comments'); ?></td>
    </tr>
    <tr>
        <td class="fieldrequired"><?php echo $langs->trans('PaymentMode'); ?></td>
        <td><?php echo $html->select_types_paiements($fk_payment, 'fk_payment'); ?></td>
        <td rowspan="3" valign="top">
            <textarea name="note" wrap="soft" cols="60" rows="<?php echo ROWS_3; ?>">
            <?php echo $note; ?>
            </textarea>
        </td>
    </tr>
    <tr>
        <td><?php echo $langs->trans('PaymentNumber'); ?></td>
        <td>
            <input name="payment_number" type="text" value="<?php echo $payment_number; ?>" />
        </td>
    </tr>
    
    <?php if ($conf->banque->enabled){ ?>
        <tr>
            <td class="fieldrequired"><?php echo $langs->trans('Account'); ?></td>
            <td><?php echo $html->select_comptes($fk_account, 'fk_account', 0,'', 2); ?></td>
        </tr>
    <?php }else{ ?>
        <tr>
            <td colspan="2">&nbsp;</td>
        </tr>
    <?php } ?>
    </table>
        
    <br />

    <?php if (sizeof($payments)){ ?>
   
    <?php echo $langs->trans('NdfpUp'); ?>
    <br />
    
    <table class="noborder" width="100%">
        <tr class="liste_titre">
            <td><?php echo $langs->trans('Ref') ?></td>
            <td><?php echo $langs->trans('Client'); ?></td>
            <td align="center"><?php echo $langs->trans('Date'); ?></td>
            <td align="right"><?php echo $langs->trans('AmountTTC'); ?></td>
            <td align="right"><?php echo $langs->trans('AlreadyPaid'); ?></td>
            <td align="right"><?php echo $langs->trans('RemainderToPay'); ?></td>
            <td align="center"><?php echo $langs->trans('Amount'); ?></td>
        </tr>

        <?php for($i=0; $i<sizeof($payments); $i++){ ?>
        
        <tr class="<?php echo ($i%2==0 ? 'impair' : 'pair'); ?>">
            <td>
                <a href="<?php echo DOL_URL_ROOT ."/ndfp/ndfp.php?id=".$payments[$i]->rowid; ?>">
                    <?php echo img_object($langs->trans('ShowBill'),'bill').' '.$payments[$i]->ref; ?>
                </a>
            </td>
            <td>
                <?php echo $payments[$i]->client; ?>
            </td>
            <td align="center">
                <?php echo dol_print_date($db->jdate($payments[$i]->datef)); ?>
            </td>

            <td align="right">
                <?php echo price($payments[$i]->total_ttc); ?>
            </td>
            <td align="right">
                <?php echo price($payments[$i]->total_paid); ?>
            </td>
            <td align="right">
                <?php echo price($payments[$i]->total_ttc - $payments[$i]->total_paid); ?>
            </td>
            <td align="center">
                <?php if ($action == 'create'){?>
                    <input type="text" size="8" name="<?php echo 'amount_'.$payments[$i]->rowid; ?>"  value="<?php echo isset($amounts[$payments[$i]->rowid]) ? $amounts[$payments[$i]->rowid] : ''; ?>"/>
                <?php }else{ ?>
                    <input type="text" size="8" name="<?php echo 'amount_'.$payments[$i]->rowid.'_disabled'; ?>"  value="<?php echo isset($amounts[$payments[$i]->rowid]) ? $amounts[$payments[$i]->rowid] : ''; ?>" disabled="true"/>
                <?php } ?>
                
            </td>
        </tr>
            
        <?php } ?>

        <?php if (sizeof($payments) > 1){ ?>
        
        <tr class="liste_total">
            <td colspan="3" align="left"><?php echo $langs->trans('TotalTTC'); ?>:</td>
            <td align="right"><b><?php echo price($total_ttc); ?></b></td>
            <td align="right"><b><?php echo price($total_paid); ?></b></td>
            <td align="right"><b><?php echo price($total_ttc - $total_paid); ?></b></td>
            <td align="center">&nbsp;</td>
        </tr>            
        <?php } ?> 

  </table>  
   
   <?php } ?>                 

    <?php if ($action == 'create'){?>
    <center>
        <br />
        <input type="checkbox" checked="checked" name="closepaidndfp" /><?php echo $langs->trans("ClosePaidNdfpAutomatically"); ?>
    	<br />
        <input type="submit" class="button" value="<?php echo $langs->trans('Save'); ?>" />
    </center>
    
    </form>
    <?php }else{ ?>
    
    </form>
    
    <?php echo $formconfirm ? $formconfirm : ''; ?> 
           
    <?php } ?>

<br /> 
     
<?php llxFooter(''); ?>


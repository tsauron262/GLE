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

/**	    \file       htdocs/ndfp/tpl/payment.default.tpl.php
 *		\ingroup    ndfp
 *		\brief      Ndfp module payment default view
 */

llxHeader('');

echo ($message ? dol_htmloutput_mesg($message, '', ($error ? 'error' : 'ok'), 0) : '');

dol_fiche_head($head, $current_head, $langs->trans('NdfpPayment'));

echo ($formconfirm ? $formconfirm : '');

?>

<table class="border" width="100%">

<tr>
    <td valign="top" width="140"><?php echo $langs->trans('Ref'); ?></td>
    <td colspan="3"><?php echo $payment->id; ?></td>
</tr>

<tr>
    <td valign="top" width="120"><?php echo $langs->trans('Date'); ?></td>
    <td colspan="3"><?php echo dol_print_date($payment->datep,'day'); ?></td>
</tr>

<tr>
    <td valign="top"><?php echo $langs->trans('Mode'); ?></td>
    <td colspan="3"><?php echo $payment_label; ?></td>
</tr>

<?php if ($payment->payment_number){ ?>
<tr>
    <td valign="top"><?php echo $langs->trans('PaymentNumber'); ?></td>
    <td colspan="3"><?php echo $payment->payment_number; ?></td>
</tr>
<?php } ?>


<tr>
    <td valign="top"><?php echo $langs->trans('Amount'); ?></td>
    <td colspan="3"><?php echo price($payment->amount).'&nbsp;'.$langs->trans('Currency'.$conf->currency); ?></td>
</tr>


<tr>
    <td valign="top">
        <table class="nobordernopadding" width="100%">
            <tr>
                <td nowrap="nowrap"><?php echo $langs->trans('Note'); ?></td>
                <td align="right">
		             <a href="<?php echo $_SERVER["PHP_SELF"].'?action=editnote&amp;id='.$payment->id; ?>">
                        <?php echo img_edit($langs->trans('SetNote'),1); ?>
                    </a>
                </td>
            </tr>
        </table>    
    </td>
    <td colspan="3">
 			<?php if ($action == 'editnote'){ ?>
            <form method="POST" action="<?php echo $_SERVER["PHP_SELF"].'?id='.$payment->id; ?>" name="formdesc">
                <input type="hidden" name="action" value="set_note" />
                <input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
                <table class="nobordernopadding" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>
                            <textarea name="note" wrap="soft" cols="60" rows="<?php echo ROWS_3; ?>"><?php echo trim($payment->note); ?></textarea>                        
                        </td>
                        <td align="left"><input type="submit" class="button" value="<?php echo $langs->trans('Modify'); ?>" /></td>
                    </tr>
                </table>
            </form>            
            <?php }else{ echo $payment->note; } ?> 
    </td>
</tr>

<?php if ($conf->banque->enabled && $payment->fk_bank > 0){ ?>

<tr>
    <td><?php echo $langs->trans('BankTransactionLine'); ?></td>
    <td colspan="3"><?php echo $writing; ?></td>
</tr>    

<?php } ?>
</table>
<br />

<table class="noborder" width="100%">
    <tr class="liste_titre">
        <td><?php echo $langs->trans('NdfpUp'); ?></td>
        <td><?php echo $langs->trans('User'); ?></td>
        <td align="right"><?php echo $langs->trans('ExpectedToPay'); ?></td>
        <td align="right"><?php echo $langs->trans('PayedByThisPayment'); ?></td>
        <td align="right"><?php echo $langs->trans('RemainderToPay'); ?></td>
        <td align="right"><?php echo $langs->trans('Status'); ?></td>
    </tr>
    
    <?php for($i=0; $i < sizeof($ndfps); $i++){ ?>
    <tr class="<?php echo ($i%2==0 ? 'impair' : 'pair'); ?>">
        <td><?php echo $ndfps[$i]->ndfp; ?></td>
        <td><?php echo $ndfps[$i]->user; ?></td>
        <td align="right"><?php echo price($ndfps[$i]->total_ttc); ?></td>
        <td align="right"><?php echo price($ndfps[$i]->amount); ?></td>
        <td align="right"><?php echo price($ndfps[$i]->remain_to_pay); ?></td>
        <td align="right"><?php echo $ndfps[$i]->statut; ?></td>  
    </tr>        
    <?php } ?>

</table>
<br />

<?php dol_fiche_end(); ?>

<div class="tabsAction">
    <?php if ($can_delete){ ?>
        <a class="butActionDelete" href="<?php echo DOL_URL_ROOT.'/ndfp/payment.php?id='.$payment->id.'&amp;action=delete'; ?>"><?php echo $langs->trans('Delete'); ?></a>
    <?php }else{ ?>
        <a class="butActionRefused" href="#" title="<?php echo dol_escape_htmltag($langs->transnoentitiesnoconv("CantRemovePaymentWithOneNdfpPaid")); ?>">
            <?php echo $langs->trans('Delete'); ?>
        </a>
    <?php } ?>
</div>

<?php llxFooter(''); ?>




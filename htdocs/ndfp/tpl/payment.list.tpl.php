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

/**	    \file       htdocs/ndfp/tpl/payment.list.tpl.php
 *		\ingroup    ndfp
 *		\brief      Ndfp module payment default view
 */

llxHeader();

echo ($message ? dol_htmloutput_mesg($message, '', ($error ? 'error' : 'ok'), 0) : '');

print_barre_liste($langs->trans('PaymentsNdfp'), $page, 'payment.php', '', $sortfield, $sortorder, '', $num);

?>

<form method="get" action="<?php echo $_SERVER["PHP_SELF"]; ?>">
<table class="liste" width="100%">
<tr class="liste_titre">
            <?php print_liste_field_titre($langs->trans('Ref'),$_SERVER['PHP_SELF'],'p.rowid','','','',$sortfield,$sortorder); ?>
            <?php print_liste_field_titre($langs->trans('PaymentDate'),$_SERVER['PHP_SELF'],'dp','','','align="center"',$sortfield,$sortorder); ?>
            <?php print_liste_field_titre($langs->trans('User'),$_SERVER['PHP_SELF'],'u.name','','','',$sortfield,$sortorder); ?>
            <?php print_liste_field_titre($langs->trans('Type'),$_SERVER['PHP_SELF'],'payment_label','','','',$sortfield,$sortorder); ?>
            <?php print_liste_field_titre($langs->trans('Account'),$_SERVER['PHP_SELF'],' ba.label','','','align="left"',$sortfield,$sortorder); ?>
            <?php print_liste_field_titre($langs->trans('Amount'),$_SERVER['PHP_SELF'],'p.amount','','','align="right"',$sortfield,$sortorder); ?>
</tr>
<tr class="liste_titre">
    <td class="liste_titre" align="left">
        <input class="flat" size="10" type="text" name="search_ref" value="<?php echo $search_ref; ?>" />
    </td>
    <td class="liste_titre" align="center">
        <input class="flat" type="text" size="1" maxlength="2" name="search_month" value="<?php echo $search_month; ?>" />
        <?php $htmlother->select_year($search_year ? $search_year : -1,'search_year', 1, 20, 5); ?>
    </td>
    <td class="liste_titre" align="left">
        <input class="flat" type="text" name="search_user" value="<?php echo $search_user; ?>" />
    </td>
    <td class="liste_titre" align="left">
        <?php echo $html->select_types_paiements($search_payment_label,'search_payment_label', '', 2, 1, 1); ?>
    </td>    
    <td class="liste_titre" align="left">
        <?php echo $html->select_comptes($search_account,'search_account', 0, '', 1); ?>
    </td>
    <td class="liste_titre" align="right">
        <input class="flat" type="text" size="10" name="search_amount" value="<?php echo $search_amount; ?>" />
        <input type="image" class="liste_titre" name="button_search" src="<?php echo DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search"));?>" title="<?php echo dol_escape_htmltag($langs->trans("Search")); ?>" />
    </td>
</tr>
            
<?php 
    if (sizeof($payments) > 0){ 
    $i = 0;?>
    <?php foreach($payments AS $payment){ ?>
        <tr class="<?php echo $i%2 == 0 ? 'impair' : 'pair'; ?>">
           <td nowrap="nowrap">
                <?php echo $payment->url; ?>
            </td>
            <td align="center" nowrap><?php echo dol_print_date($payment->pdate,'day'); ?></td>
            <td><?php echo $payment->username; ?></td>
            <td><?php echo $langs->trans("PaymentTypeShort".$payment->payment_code).' '.$payment->payment_number; ?></td>
            <td><?php echo $payment->account; ?></td>
            <td align="right"><?php echo price($payment->amount); ?></td>       
        </tr>
    <?php $i++;} ?>
<?php }else{ ?>
     <tr class="impair">
        <td colspan="6"><?php echo $langs->trans("NoResults"); ?></td>
    </tr>    
<?php } ?>
</table>
</form>
            
<?php llxFooter(''); ?>

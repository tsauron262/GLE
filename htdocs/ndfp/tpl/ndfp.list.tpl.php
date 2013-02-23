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

/**	    \file       htdocs/ndfp/tpl/ndfp.note.tpl.php
 *		\ingroup    ndfp
 *		\brief      Ndfp module expenses list view
 */


llxHeader();


print_barre_liste($langs->trans('Ndfps'), $page, 'ndfp.php', '', $sortfield, $sortorder, '', $num);

?>

<form method="get" action="<?php echo $_SERVER["PHP_SELF"]; ?>">
<table class="liste" width="100%">
<tr class="liste_titre">
            <?php print_liste_field_titre($langs->trans('Ref'),$_SERVER['PHP_SELF'],'n.ref','','','',$sortfield,$sortorder); ?>
            <?php print_liste_field_titre($langs->trans('DateStart'),$_SERVER['PHP_SELF'],'n.dates','','','align="center"',$sortfield,$sortorder); ?>
            <?php print_liste_field_titre($langs->trans('DateEnd'),$_SERVER['PHP_SELF'],"n.datee",'',"",'align="center"',$sortfield,$sortorder); ?>
            <?php print_liste_field_titre($langs->trans('User'),$_SERVER['PHP_SELF'],'u.name','','','',$sortfield,$sortorder); ?>
            <?php print_liste_field_titre($langs->trans('Society'),$_SERVER['PHP_SELF'],'soc_name','','','',$sortfield,$sortorder); ?>
            <?php print_liste_field_titre($langs->trans('Total_HT'),$_SERVER['PHP_SELF'],'n.total_ht','','','align="right"',$sortfield,$sortorder); ?>
            <?php print_liste_field_titre($langs->trans('Total_TTC'),$_SERVER['PHP_SELF'],'n.total_ttc','','','align="right"',$sortfield,$sortorder); ?>
            <?php print_liste_field_titre($langs->trans('Paid'),$_SERVER['PHP_SELF'],'already_paid','','','align="right"',$sortfield,$sortorder); ?>
            <?php print_liste_field_titre($langs->trans('Status'),$_SERVER['PHP_SELF'],'n.statut','','','align="right"',$sortfield,$sortorder); ?>
</tr>
<tr class="liste_titre">
    <td class="liste_titre" align="left">
        <input class="flat" size="10" type="text" name="search_ref" value="<?php echo $search_ref; ?>" />
    </td>
    <td class="liste_titre" align="center">
        <input class="flat" type="text" size="1" maxlength="2" name="search_month_s" value="<?php echo $search_month_s; ?>" />
        <?php $htmlother->select_year($search_year_s ? $search_year_s : -1,'search_year_s', 1, 20, 5); ?>
    </td>
    <td class="liste_titre" align="center">
        <input class="flat" type="text" size="1" maxlength="2" name="search_month_e" value="<?php echo $search_month_e; ?>" />
        <?php $htmlother->select_year($search_year_e ? $search_year_e : -1,'search_year_e', 1, 20, 5); ?>    
    </td>
    <td class="liste_titre" align="left">
        <input class="flat" type="text" name="search_user" value="<?php echo $search_user; ?>" />
    </td>
    <td class="liste_titre" align="left">
        <input class="flat" type="text" name="search_soc" value="<?php echo $search_soc; ?>" />
    </td>    
    <td class="liste_titre" align="right">
        <input class="flat" type="text" size="10" name="search_ht_amount" value="<?php echo $search_ht_amount; ?>" />
    </td>
    <td class="liste_titre" align="right">
        <input class="flat" type="text" size="10" name="search_ttc_amount" value="<?php echo $search_ttc_amount; ?>" />
    </td>
    <td class="liste_titre" align="right">
        <input class="flat" type="text" size="10" name="search_total_paid" value="<?php echo $search_total_paid; ?>" />
    </td>
    <td class="liste_titre" align="right">
        <input type="image" class="liste_titre" name="button_search" src="<?php echo DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search"));?>" title="<?php echo dol_escape_htmltag($langs->trans("Search")); ?>" />
    </td>
</tr>
            
<?php 
    if (sizeof($ndfps) > 0){ 
    $i = 0;?>
    <?php foreach($ndfps AS $ndfp){ ?>
        <tr class="<?php echo $i%2 == 0 ? 'impair' : 'pair'; ?>">
           <td nowrap="nowrap">
                <table class="nobordernopadding">
                    <tr class="nocellnopadd">
                        <td class="nobordernopadding" nowrap="nowrap"><?php echo $ndfp->url;?></td>
                        <td width="16" align="right" class="nobordernopadding">
                            <?php $formfile->show_documents('ndfp',$ndfp->filename,$ndfp->filedir,$ndfp->urlsource,'','','',1,'',1); ?>
                        </td>
                    </tr>
                </table>
            </td>
            <td align="center" nowrap><?php echo dol_print_date($ndfp->dates,'day'); ?></td>
            <td align="center" nowrap><?php echo dol_print_date($ndfp->datee,'day'); ?></td>
            <td><?php echo $ndfp->username; ?></td>
            <td><?php echo $ndfp->society; ?></td>
            <td align="right"><?php echo price($ndfp->total_ht); ?></td>
            <td align="right"><?php echo price($ndfp->total_ttc); ?></td>
            <td align="right"><?php echo price($ndfp->already_paid); ?></td>
            <td align="right" nowrap="nowrap"><?php echo $ndfp->statut; ?></td>        
        </tr>
    <?php $i++;} ?>
<?php }else{ ?>
     <tr class="impair">
        <td colspan="9"><?php echo $langs->trans("NoResults"); ?></td>
    </tr>    
<?php } ?>
</table>
</form>
            
<?php llxFooter(''); ?>

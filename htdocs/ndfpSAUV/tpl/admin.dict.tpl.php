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

/**	    \file       htdocs/ndfp/tpl/admin..dict.tpl.php
 *		\ingroup    ndfp
 *		\brief      Admin setup view
 */
 
llxHeader("", $langs->trans("NdfpSetup"));

echo ($message ? dol_htmloutput_mesg($message, '', ($error ? 'error' : 'ok'), 0) : '');



print_fiche_titre($langs->trans("DictionnarySetup"), $linkback, 'setup');

dol_fiche_head($head, 'dict', $langs->trans("Ndfp"));

?>
<br />

<table class="noborder" width="100%">
    <tr class="liste_titre">
        <td colspan="1"><?php echo $langs->trans("Dictionnary"); ?></td>
        <td><?php echo $langs->trans("Table"); ?></td>
    </tr>
    
<?php foreach($tables as $table){ ?>
    <tr class="<?php echo ($i%2==0 ? 'pair' : 'impair'); ?>">
        <td width="30%">
            <a href="<?php echo $table->url; ?>">
                <?php echo $langs->trans($table->name); ?>
            </a>
        </td>
        <td>
            <?php echo $table->mysqlname; ?>
        </td>
    </tr>
    <?php $i++; ?>    
<?php } ?>
</table> 

<br />

<?php llxFooter(''); ?>

<?php
/* Copyright (C) 2005       Matthieu Valleton   <mv@seeschloss.org>
 * Copyright (C) 2005       Eric Seigne         <eric.seigne@ryxeo.com>
 * Copyright (C) 2006-2016  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2007       Patrick Raguin      <patrick.raguin@gmail.com>
 * Copyright (C) 2005-2012  Regis Houssin       <regis.houssin@capnetworks.com>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
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

/**
 *      \file       bimpproductbrowser/linkCreated.php
 *      \ingroup    bimpproductbrowser
 *      \brief      Create link, in the database, between category and (category or product)
 */

require '../main.inc.php';



$arrayofid = GETPOST ('ids');

$fp = fopen('/tmp/test.txt','w');

foreach ($arrayofid as $id ){
	fwrite($fp, $id.' ');
}

fclose($fp);

/*
$db->begin();
$sql=''
try
{
	$db->query($sql);
	$db->commit();
} catch
$db->rollback();
*/

$db->close();
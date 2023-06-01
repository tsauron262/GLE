<?php
/* Copyright (C) 2022	Regis Houssin	<regis.houssin@inodbox.com>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 */

global $usercanread, $usercancreate, $usercandelete, $usercanvalidate, $usercansend, $usercanclose;
global $permissionnote, $permissiondellink, $permissiontoedit;
global $disableedit, $disablemove, $disableremove;

if (empty($user->rights->multicompany->propal->read)) {
	$usercanread = false;
}
if (empty($user->rights->multicompany->propal->write)) {
	$usercancreate = false;

	$permissionnote = $usercancreate; // Used by the include of actions_setnotes.inc.php
	$permissiondellink = $usercancreate;	// Used by the include of actions_dellink.inc.php
	$permissiontoedit = $usercancreate; // Used by the include of actions_lineupdown.inc.php

	// for object lines
	$disableedit = true;
	$disablemove = true;
	$disableremove = true;
}
if (empty($user->rights->multicompany->propal->delete)) {
	$usercandelete = false;
}
if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($usercancreate))
	|| (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->multicompany->propal_advance->validate))) {
		$usercanvalidate = false;
}
if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($usercanread))
	|| (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->multicompany->propal_advance->send))) {
	$usercansend = false;
}
if (empty($user->rights->multicompany->propal->close)) {
	$usercanclose = false;
}

?>

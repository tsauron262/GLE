<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@capnetworks.com>
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
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.*//*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Create on : 4-1-2009
  *
  * Infos on http://www.finapro.fr
  *
  *//*
 */

/**
        \file       htdocs/synopsis_demandeinterv/index.php
        \brief      Page accueil espace fiches interventions
        \ingroup    demandeInterv
        \version    $Id: index.php,v 1.40 2008/04/09 18:13:50 eldy Exp $
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
require_once(DOL_DOCUMENT_ROOT."/Babel_Magento/magento_soap.class.php");

$langs->load("product");
$langs->load("synopsisGene@Synopsis_Tools");

// Security check
if ($user->societe_id) $socid=$user->societe_id;
//$result = restrictedArea($user, 'demandeInterv', $demandeIntervid,'');



/*
*    View
*/

llxHeader();
//load dashboard ?

$db->close();

llxFooter('$Date: 2008/04/09 18:13:50 $ - $Revision: 1.40 $');
?>

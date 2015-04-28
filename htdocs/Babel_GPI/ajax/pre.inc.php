<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * $Id: pre.inc.php,v 1.10 2006/12/04 18:03:23 rodolphe Exp $
 * $Source: /cvsroot/dolibarr/dolibarr/htdocs/contrat/pre.inc.php,v $
 */
/*
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
  */
/**
    \file       htdocs/contrat/pre.inc.php
    \ingroup    contrat
    \brief      Fichier de gestion du menu gauche de l'espace contrat
    \version    $Revision: 1.10 $
*/

require("main.inc.php");

function llxHeader($head = "", $urlp = "",$disableScriptaculous=0)
{
  global $user, $conf, $langs;
  $langs->load("synopsisGene@Synopsis_Tools");

  top_menu($head,"","",$disableScriptaculous);


  left_menu($menu->liste);
}
?>

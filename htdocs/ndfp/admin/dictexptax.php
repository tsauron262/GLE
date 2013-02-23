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


/**
 *      \file       htdocs/ndfp/admin/dictexptax.php
 *		\ingroup    ndfp
 *		\brief      Page to display expense tax rates
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/ndfp/class/ndfp.class.php");

$langs->load("admin");
$langs->load("ndfp");
$langs->load("other");
$langs->load("errors");

if (!$user->admin)
{
   accessforbidden(); 
}

//Init error
$error = false;
$message = false;

$acts[0] = "activate";
$acts[1] = "disable";
$actl[0] = img_picto($langs->trans("Disabled"),'off');
$actl[1] = img_picto($langs->trans("Activated"),'on');

$listoffset = GETPOST('listoffset');
$listlimit = GETPOST('listlimit')>0 ? GETPOST('listlimit') : 1000;

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');

$action = GETPOST("action",'alpha');

$coef = GETPOST("coef",'int');
$off = GETPOST("offset",'int');

$cancel = (isset($_POST['actioncancel']) ? true : false);
$modify = (isset($_POST['actionmodify']) ? true : false);

$rowid = GETPOST("rowid",'int');

if ($page == -1)
{ 
    $page = 0; 
}

$offset = $listlimit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$html = new Form($db);

// Get categories
$cats = array();
$taxes = array();

$sql = "";

$sql  = " SELECT c.rowid, c.label, c.fk_parent";
$sql .= " FROM ".MAIN_DB_PREFIX."c_exp_tax_cat c";
$sql .= " WHERE c.active = 1";
$sql .= " ORDER BY c.rowid ASC";

dol_syslog("Dictexptax sql=".$sql, LOG_DEBUG);
$result = $db->query($sql);

if ($result)
{
    $num = $db->num_rows($result);
    $i = 0;
    
    if ($num)
    {
        while ($i < $num)
        {
            $obj = $db->fetch_object($result);
                       
            $cats[$obj->rowid] = $obj;                                     
            $i++;
        }
    }
}
else
{
    $error = true;
    $message = $db->error()." sql=".$sql;
}

if ($modify)
{
    
    if (empty($coef))//Offset could be 0, but coef can not
    {
        $error = true;
        $message = $langs->trans('MissionField');
    }
     

    $sql  = " UPDATE ".MAIN_DB_PREFIX."c_exp_tax SET `coef` = ".$db->escape($coef).", `offset` = ".$db->escape($off)." ";
    $sql .= " WHERE `rowid` = ".$rowid;                     


    dol_syslog("Dictexptax sql=".$sql);
    
    $result = $db->query($sql);
    
    if ($result > 0)
    {
        $message = $langs->trans('TaxUpdated');
    }
    else
    {
        $error = true;
        $message = $langs->trans('TaxNotUpdated');
    }
    
}

$sql  = " SELECT t.rowid, t.fk_cat, t.fk_range, r.range, r.rowid as rid, t.coef, t.offset";
$sql .= " FROM ".MAIN_DB_PREFIX."c_exp_tax t";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_exp_tax_range r ON r.rowid = t.fk_range";
$sql .= $db->plimit($listlimit+1,$offset);

dol_syslog("Dictexptaxcat sql=".$sql, LOG_DEBUG);
$result = $db->query($sql);

if ($result > 0)
{
    $num = $db->num_rows($result);
    $i = 0;
    
    if ($num)
    {
        while ($i < $num)
        {
            $obj = $db->fetch_object($result);                       
            $obj->modify = '<a href="'.$_SERVER["PHP_SELF"].'?'.($page?'page='.$page.'&':'').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.$obj->rowid.'&amp;action=modify">'.img_edit().'</a>';
            
            $taxes[$obj->fk_cat][] = $obj;  
                                    
            $i++;
        }
    }
}
else
{
    $error = true;
    $message = $db->error()." sql=".$sql;
}

$h = 0;
$head = array();

$head[$h][0] = DOL_URL_ROOT.'/ndfp/admin/config.php';
$head[$h][1] = $langs->trans("Setup");
$head[$h][2] = 'config';
$h++;

$head[$h][0] = DOL_URL_ROOT.'/ndfp/admin/dict.php';
$head[$h][1] = $langs->trans("Dict");
$head[$h][2] = 'dict';
$h++;

$current_head = 'dict';


$linkback = '<a href="'.DOL_URL_ROOT.'/ndfp/admin/dict.php">'.$langs->trans("BackToDictionnariesList").'</a>';           
/*
 * View
 */

require_once("../tpl/admin.dictexptax.tpl.php");

$db->close();

?>

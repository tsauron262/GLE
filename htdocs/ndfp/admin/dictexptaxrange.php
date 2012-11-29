<?php
/* Copyright (C) 2012      Mikael Carlavan        <contact@mika-carl.fr>
 *                                                http://www.mikael-carlavan.fr
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *      \file       htdocs/ndfp/admin/dictexptaxrange.php
 *		\ingroup    ndfp
 *		\brief      Page to display ranges for expense tax
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/ndfp/class/ndfp.class.php");
require_once(DOL_DOCUMENT_ROOT."/ndfp/class/html.form.ndfp.class.php");

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
$confirm = GETPOST("confirm",'alpha');

$active = GETPOST("active");
$fk_cat = GETPOST("fk_cat");
$range = GETPOST("range");

$rowid = GETPOST("rowid",'int');

if ($page == -1) 
{ 
    $page = 0; 
}

$offset = $listlimit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$cancel = (isset($_POST['actioncancel']) ? true : false);
$add = (isset($_POST['actionadd']) ? true : false);
$modify = (isset($_POST['actionmodify']) ? true : false);


$html = new Form($db);
$ndfpHtml = new NdfpForm($db);

$formconfirm = '';
if ($action == 'delete')
{
    $formconfirm = $html->formconfirm($_SERVER["PHP_SELF"].'?'.($page?'page='.$page.'&':'').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.$rowid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_delete','',0,1);
}
        
if ($add || $modify)
{
    if (empty($range))
    {
        $error = true;
        $message = $langs->trans('MissingField');
    }
     
     // Check if range does not exist
    $sql  = " SELECT r.rowid";
    $sql .= " FROM ".MAIN_DB_PREFIX."c_exp_tax_range r";
    $sql .= " WHERE r.fk_cat = ".$fk_cat." AND r.range = ".$range;
            
    
    dol_syslog("Dictexptaxrange sql=".$sql, LOG_DEBUG);
    $result = $db->query($sql);
    
    if ($result)
    {
        $num = $db->num_rows($result);
        
        if ($num)
        {
            $error = true;
            $message = $langs->trans('RangeAlreadyExists');
        }
    }
    else
    {
        $error = true;
        $message = $db->error()." sql=".$sql;
    }
    
    if (!$error)
    {
        
        if ($add)
        {      
            $sql = " INSERT INTO ".MAIN_DB_PREFIX."c_exp_tax_range (`fk_cat`, `range`, `active`)";
            $sql .= " VALUES ('".$db->escape($fk_cat)."', ".$db->escape($range).", 1)";
            
            dol_syslog("Dictexptaxrange sql=".$sql);
            $result = $db->query($sql);
            $rangeid = 0;
            
            // Insert new tax
            if ($result)
            {
                $rangeid = $db->last_insert_id(MAIN_DB_PREFIX."c_exp_tax_range");
                
                $sql = " INSERT INTO ".MAIN_DB_PREFIX."c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`)";
                $sql .= " VALUES (".$db->escape($fk_cat).", ".$db->escape($rangeid).", 0, 0)";
            }
            else
            {
                $error = true;
            }                
        }
        else
        {
            $sql  = " UPDATE ".MAIN_DB_PREFIX."c_exp_tax_range SET `fk_cat` = ".$db->escape($fk_cat).", `range` = ".$db->escape($range).",";
            $sql .= " `active` = ".$active." WHERE `rowid` = ".$rowid;
            
            $result = $db->query($sql);
            dol_syslog("Dictexptaxrange sql=".$sql);
            
            // Update all tax using this range
            if ($result)
            {
                $rangeid = $db->last_insert_id(MAIN_DB_PREFIX."c_exp_tax_range");
                
                $sql  = " UPDATE ".MAIN_DB_PREFIX."c_exp_tax SET `fk_cat` = ".$db->escape($fk_cat)."";
                $sql .= " WHERE `fk_range` = ".$rowid;
            }
            else
            {
                $error = true;
            }                                   
        }
        
        dol_syslog("Dictexptaxrange sql=".$sql);
        
        $result = $db->query($sql);
        
        if ($result > 0)
        {
            $message = ($add ? $langs->trans('RangeAdded') : $langs->trans('RangeUpdated'));
        }
        else
        {
            $error = true;
            $message = ($add ? $langs->trans('RangeNotAdded') : $langs->trans('RangeNotUpdated'));
        }
    }
}

if ($cancel)
{
   $rowid = 0;
}

if ($action == $acts[0])       // activate
{
    $sql = "UPDATE ".MAIN_DB_PREFIX."c_exp_tax_range SET `active` = 1 WHERE rowid = ".$rowid;
    dol_syslog("Dictexptaxrange sql=".$sql);

    $result = $db->query($sql);
    
    if ($result)
    {
        $message = $langs->trans('RangeUpdated');
    }
    else
    {
        $error = true;
        $message = $langs->trans('RangeNotUpdated');        
    }
}

if ($action == $acts[1])       // disable
{
    $sql = "UPDATE ".MAIN_DB_PREFIX."c_exp_tax_range SET `active` = 0 WHERE rowid = ".$rowid;
    dol_syslog("Dictexptaxrange sql=".$sql);
    
    $result = $db->query($sql);
    if ($result)
    {
        $message = $langs->trans('RangeUpdated');
    }
    else
    {
        $error = true;
        $message = $langs->trans('RangeNotUpdated');        
    }
}

if ($action == 'confirm_delete' && $confirm == 'yes')       // delete
{

    // Check if not a 0 range
     // Check if range does not exist
    $sql  = " SELECT r.range FROM ".MAIN_DB_PREFIX."c_exp_tax_range r WHERE r.rowid = ".$rowid;
    $result = $db->query($sql);
    if (! $result)
    {
        $num = $db->num_rows($result);
        
        if ($num)
        {
            $r = $db->fetch_object($result);
            if ($r->range == 0)
            {
                $error = true;
                $message = $langs->trans('RangeCanNotBeDeleted');          
            }
        }
        else
        {
            $error = true;
            $message = $langs->trans('RangeDoesNotExist');            
        }
    }
            
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."c_exp_tax_range WHERE rowid = ".$rowid;

    dol_syslog("Dictexptaxrange sql=".$sql);
    $result = $db->query($sql);
    if (! $result)
    {
        $error = true;
        $message = $langs->trans('RangeNotDeleted');
    }
    
    if (!$error)
    {
        $sql = " DELETE FROM ".MAIN_DB_PREFIX."c_exp_tax WHERE fk_range = ".$rowid;
    
        dol_syslog("Dictexptaxrange sql=".$sql);
        $result = $db->query($sql);
        
        if (! $result)
        {
            $error = true;
            $message = $langs->trans('RangeNotDeleted');
        }
        
        if (!$error)
        {
            $message = $langs->trans('RangeDeleted');
        }        
    }

}


// Get ranges
$sql  = " SELECT r.rowid, r.fk_cat, r.range, r.active";
$sql .= " FROM ".MAIN_DB_PREFIX."c_exp_tax_range r";
$sql .= (!empty($sortfield) ? " ORDER BY ".$sortfield : " ORDER BY r.fk_cat, r.range");
$sql .= (!empty($sortorder) ? " ".strtoupper($sortorder) : " ASC");
$sql .= $db->plimit($listlimit+1,$offset);
        

dol_syslog("Dictexptaxrange sql=".$sql, LOG_DEBUG);
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
             
            $ranges[$i] = $obj;  
            
            if ($obj->range == 0)
            {
                $ranges[$i]->activate = $actl[$obj->active];
                $ranges[$i]->modify = '';
                $ranges[$i]->delete = '';
            }
            else
            {
                $ranges[$i]->activate = '<a href="'.$_SERVER["PHP_SELF"].'?'.($page?'page='.$page.'&':'').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.$obj->rowid.'&amp;action='.$acts[$obj->active].'">'.$actl[$obj->active].'</a>';
                $ranges[$i]->modify = '<a href="'.$_SERVER["PHP_SELF"].'?'.($page?'page='.$page.'&':'').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.$obj->rowid.'&amp;action=modify">'.img_edit().'</a>';
                $ranges[$i]->delete = '<a href="'.$_SERVER["PHP_SELF"].'?'.($page?'page='.$page.'&':'').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.$obj->rowid.'&amp;action=delete">'.img_delete().'</a>';                        
            }   
            
            $i++;
        }
    }
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

$cats_names = $ndfpHtml->get_cats_name();
           
/*
 * View
 */

require_once("../tpl/admin.dictexptaxrange.tpl.php");

$db->close();

?>

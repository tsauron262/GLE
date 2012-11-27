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
 *      \file       htdocs/ndfp/admin/config.php
 *		\ingroup    ndfp
 *		\brief      Page to display expense tax categories
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
$fk_parent = GETPOST("fk_parent", 'int');
$label = GETPOST("label");

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

//
$html = new Form($db);
$ndfpHtml = new NdfpForm($db);

$formconfirm = '';
if ($action == 'delete')
{
    $formconfirm = $html->formconfirm($_SERVER["PHP_SELF"].'?'.($page?'page='.$page.'&':'').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.$rowid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_delete','','no',1);
}
        
if ($add || $modify)
{
    if (empty($label))
    {
        $err = true;
        $errMsg = $langs->trans('MissionField');
    }
     
     // Check if parent category does exist
     if ($fk_parent > 0)
     {
        $sql  = " SELECT c.label";
        $sql .= " FROM ".MAIN_DB_PREFIX."c_exp_tax_cat c";
        $sql .= " WHERE c.rowid = ".$fk_parent;
                
        
        dol_syslog("Dictexptaxcat sql=".$sql, LOG_DEBUG);
        $result = $db->query($sql);
        
        if ($result)
        {
            $num = $db->num_rows($result);
            
            if (!$num)
            {
                $error = true;
                $message = $langs->trans('CatDoesNotExist');
            }
        }
        else
        {
            $error = true;
            $message = $db->error()." sql=".$sql;            
        }        
     }

    if (!$error)
    {
        
        if ($add)
        {      
            $sql = " INSERT INTO ".MAIN_DB_PREFIX."c_exp_tax_cat (`fk_parent`, `label`, `active`)";
            $sql .= " VALUES (".$db->escape($fk_parent).", '".$db->escape($label)."', 1)";
            
            $result = $db->query($sql);
            $catid = 0;
            
            // Insert new range
            if ($result)
            {
                $catid = $db->last_insert_id(MAIN_DB_PREFIX."c_exp_tax_cat");
                
                $sql = " INSERT INTO ".MAIN_DB_PREFIX."c_exp_tax_range (`fk_cat`, `range`, `active`)";
                $sql .= " VALUES (".$db->escape($catid).", 0, 1)";
                
                $result = $db->query($sql);
                $rangeid = 0;
                
                // Insert new tax
                if ($result)
                {
                    $rangeid = $db->last_insert_id(MAIN_DB_PREFIX."c_exp_tax_range");
                    
                    $sql = " INSERT INTO ".MAIN_DB_PREFIX."c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`)";
                    $sql .= " VALUES (".$db->escape($catid).", ".$db->escape($rangeid).", 0, 0)";
                }
                else
                {
                    $error = true;
                    $message = $db->error()." sql=".$sql;            
                }              
            }
            else
            {
                $error = true;
                $message = $db->error()." sql=".$sql;            
            }              
        }
        else
        {
            $sql  = " UPDATE ".MAIN_DB_PREFIX."c_exp_tax_cat SET `fk_parent` = '".$db->escape($fk_parent)."', `label` = '".$db->escape($label)."',";
            $sql .= " `active` = ".$active." WHERE `rowid` = ".$rowid;           
        }
        
        dol_syslog("Dictexptaxcat sql=".$sql);
        
        $result = $db->query($sql);
        
        if ($result > 0)
        {
            $message = ($add ? $langs->trans('CatAdded') : $langs->trans('CatUpdated'));
        }
        else
        {
            $error = true;
            $message = ($add ? $langs->trans('CatNotAdded') : $langs->trans('CatNotUpdated'));
        }
    }
}


if ($cancel)
{
   $rowid = 0;
}

if ($action == $acts[0])
{       // activate
    $sql = "UPDATE ".MAIN_DB_PREFIX."c_exp_tax_cat SET `active` = 1 WHERE rowid = ".$rowid;

    $result = $db->query($sql);
    if ($result)
    {
        $message = $langs->trans('CatUpdated');
    }
    else
    {
        $error = true;
        $message = $langs->trans('CatNotUpdated');        
    }
}

if ($action == $acts[1])
{       // disable
    $sql = "UPDATE ".MAIN_DB_PREFIX."c_exp_tax_cat SET `active` = 0 WHERE rowid = ".$rowid;

    $result = $db->query($sql);
    if ($result)
    {
        $message = $langs->trans('CatUpdated');
    }
    else
    {
        $error = true;
        $message = $langs->trans('CatNotUpdated');        
    }
}

if ($action == 'confirm_delete' && $confirm == 'yes')
{       // delete

    $sql = "DELETE FROM ".MAIN_DB_PREFIX."c_exp_tax_cat WHERE rowid = ".$rowid;

    dol_syslog("Dictexptaxcat sql=".$sql);
    $result = $db->query($sql);
    
    if (! $result){
        $error = true;
        $message = $langs->trans('CatNotDeleted');
    }
    
    $sql = " DELETE FROM ".MAIN_DB_PREFIX."c_exp_tax_range WHERE fk_cat = ".$rowid;

    dol_syslog("Dictexptaxcat sql=".$sql);
    $result = $db->query($sql);
    
    if (! $result)
    {
        $error = true;
        $message = $langs->trans('CatNotDeleted');
    }
                    
    $sql = " DELETE FROM ".MAIN_DB_PREFIX."c_exp_tax WHERE fk_cat = ".$rowid;

    dol_syslog("Dictexptaxcat sql=".$sql);
    $result = $db->query($sql);
    
    if (! $result)
    {
        $error = true;
        $message = $langs->trans('CatNotDeleted');
    }
    
    if (!$error)
    {
        $message = $langs->trans('CatDeleted');
    }
}
// Get categories
$cats = array();

$sql  = " SELECT c.rowid, c.label, c.fk_parent, c.active";
$sql .= " FROM ".MAIN_DB_PREFIX."c_exp_tax_cat c";
$sql .= (!empty($sortfield) ? " ORDER BY ".$sortfield : " ORDER BY c.label");
$sql .= (!empty($sortorder) ? " ".strtoupper($sortorder) : " ASC");
$sql .= $db->plimit($listlimit+1,$offset);

dol_syslog("Dictexptaxcat sql=".$sql, LOG_DEBUG);
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

            $obj->activate = '<a href="'.$_SERVER["PHP_SELF"].'?'.($page?'page='.$page.'&':'').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.$obj->rowid.'&amp;action='.$acts[$obj->active].'">'.$actl[$obj->active].'</a>';
            $obj->modify = '<a href="'.$_SERVER["PHP_SELF"].'?'.($page?'page='.$page.'&':'').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.$obj->rowid.'&amp;action=modify">'.img_edit().'</a>';
            $obj->delete = '<a href="'.$_SERVER["PHP_SELF"].'?'.($page?'page='.$page.'&':'').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.$obj->rowid.'&amp;action=delete">'.img_delete().'</a>';                        
        
            $cats[$obj->rowid] = $obj;
                                         
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
/*
 * View
 */

require_once("../tpl/admin.dictexptaxcat.tpl.php");

$db->close();

?>

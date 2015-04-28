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
 *      \file       htdocs/ndfp/admin/dictexp.php
 *		\ingroup    ndfp
 *		\brief      Page to display expense types
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
$confirm = GETPOST("confirm",'alpha');

$active = GETPOST("active");
$fk_tva = GETPOST("fk_tva");
$label = GETPOST("label");
$code = strtoupper(GETPOST("code"));

$rowid = GETPOST("rowid",'int');

if ($page == -1) 
{ 
    $page = 0 ; 
}

$offset = $listlimit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$cancel = (isset($_POST['actioncancel']) ? true : false);
$add = (isset($_POST['actionadd']) ? true : false);
$modify = (isset($_POST['actionmodify']) ? true : false);

//
$html = new Form($db);

$formconfirm = '';

if ($action == 'delete')
{
    $formconfirm = $html->formconfirm($_SERVER["PHP_SELF"].'?'.($page?'page='.$page.'&':'').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.$rowid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_delete','','no',1);
}
        
if ($add || $modify)
{
    if (empty($code) || empty($label))
    {
        $error = true;
        $message = $langs->trans('MissionField');
    }

    // Check TVA
    $tva_id = 0;
    if (!empty($fk_tva))
    {
        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."c_tva WHERE taux = '".$fk_tva."'";
        $resql = $db->query($sql);
    
        
        if ($resql){
            $num = $db->num_rows($resql);
            $i = 0;
            
            if ($num){
                    // Get TVA id
                    $obj = $db->fetch_object($resql);
                    $tva_id = $obj->rowid; 
            }else{
                $error = true;
                $message = $langs->trans('TVASelectedDoesNotExist');
            }
        }            
    }
     
    if (!$error)
    {
        
        if ($add)
        {      
            $sql = " INSERT INTO ".MAIN_DB_PREFIX."c_exp (`code`, `label`, `fk_tva`, `active`)";
            $sql .= " VALUES ('".$db->escape($code)."', '".$db->escape($label)."', '".$db->escape($tva_id)."', 1)";
        }
        else
        {
            $sql  = " UPDATE ".MAIN_DB_PREFIX."c_exp  SET `label` = '".$db->escape($label)."', `code` = '".$db->escape($code)."', ";
            $sql .= " `fk_tva` = ".$db->escape($tva_id).", `active` = ".$active." WHERE `rowid` = ".$rowid;           
        }
        
        dol_syslog("Dictexp sql=".$sql);
        
        $result = $db->query($sql);
        
        if ($result > 0)
        {
            $message = ($add ? $langs->trans('ExpAdded') : $langs->trans('ExpUpdated'));
        }
        else
        {
            $error = true;
            $message = ($add ? $langs->trans('ExpNotAdded') : $langs->trans('ExpNotUpdated'));
        }
    }
}

if ($cancel)
{
   $rowid = 0;
}

if ($action == $acts[0])       // activate
{
    $sql = "UPDATE ".MAIN_DB_PREFIX."c_exp SET `active` = 1 WHERE rowid = ".$rowid;
    dol_syslog("Dictexp sql=".$sql);
    
    $result = $db->query($sql);
    if ($result)
    {
        $message = $langs->trans('ExpUpdated');
    }
    else
    {
        $error = true;
        $message = $langs->trans('ExpNotUpdated');        
    }
}

if ($action == $acts[1])       // disable
{
    $sql = "UPDATE ".MAIN_DB_PREFIX."c_exp SET `active` = 0 WHERE rowid = ".$rowid;
    dol_syslog("Dictexp sql=".$sql);
    
    $result = $db->query($sql);
    if ($result > 0)
    {
        $message = $langs->trans('ExpUpdated');
    }
    else
    {
        $error = true;
        $message = $langs->trans('ExpNotUpdated');        
    }
}

if ($action == 'confirm_delete' && $confirm == 'yes')       // delete
{

    $sql = "DELETE FROM ".MAIN_DB_PREFIX."c_exp WHERE rowid = ".$rowid;

    dol_syslog("Dictexp sql=".$sql);
    $result = $db->query($sql);
    
    if ($result > 0)
    {
        $message = $langs->trans('ExpDeleted');
    }
    else
    {
        $error = true;
        $message = $langs->trans('ExpNotDeleted');
    }
}
    
// 
$exps = array();

$sql  = " SELECT e.rowid, e.label, e.code, e.fk_tva, e.active, t.note, t.taux";
$sql .= " FROM ".MAIN_DB_PREFIX."c_exp e";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_tva t ON t.rowid = e.fk_tva";
$sql .= (!empty($sortfield) ? " ORDER BY ".$sortfield : " ORDER BY e.code");
$sql .= (!empty($sortorder) ? " ".strtoupper($sortorder) : " ASC");
$sql .= $db->plimit($listlimit+1,$offset);
    

dol_syslog("Dictexp sql=".$sql, LOG_DEBUG);
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
             
            $exps[$i] = $obj;     
            $exps[$i]->activate = '<a href="'.$_SERVER["PHP_SELF"].'?'.($page?'page='.$page.'&':'').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.$obj->rowid.'&amp;action='.$acts[$obj->active].'">'.$actl[$obj->active].'</a>';
            $exps[$i]->modify = '<a href="'.$_SERVER["PHP_SELF"].'?'.($page?'page='.$page.'&':'').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.$obj->rowid.'&amp;action=modify">'.img_edit().'</a>';
            $exps[$i]->delete = '<a href="'.$_SERVER["PHP_SELF"].'?'.($page?'page='.$page.'&':'').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.$obj->rowid.'&amp;action=delete">'.img_delete().'</a>';            
            
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

require_once("../tpl/admin.dictexp.tpl.php");

$db->close();

?>

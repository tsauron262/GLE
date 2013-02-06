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
 *	\file       htdocs/ndfp/index.php
 *	\ingroup    ndfp
 *	\brief      Main page of ndfp module
 */

require('../main.inc.php');
require_once(DOL_DOCUMENT_ROOT ."/ndfp/class/ndfp.class.php");
require_once(DOL_DOCUMENT_ROOT ."/core/class/html.formfile.class.php");

$langs->load("ndfp");
$langs->load('main');
$langs->load('other');

if (!$user->rights->ndfp->myactions->read && !$user->rights->ndfp->allactions->read)
{
    accessforbidden();
}  

$html = new Form($db);
$formfile = new FormFile($db);

$limit = 3;

//Retrieve error
$error = false;
$message = false;

$message = (empty($_SESSION['message']) ? ''  : $_SESSION['message']);
$error = (empty($_SESSION['error']) ? false  : true);


// Get last X notes
$ndfps = array();
$unpaid_ndfps = array();
$drafts = array();

if (!$user->rights->ndfp->myactions->read && !$user->rights->ndfp->allactions->read)
{
    $error = true;
    $message = $langs->trans('NotEnoughPermissions');
}
else
{
    $sql = " SELECT n.rowid, n.ref, n.tms, n.fk_user, n.statut, n.fk_soc, n.dates,"; 
    $sql.= " u.rowid as uid, u.name, u.firstname, s.nom AS soc_name, s.rowid AS soc_id, u.login, n.total_tva, n.total_ht, n.total_ttc";
    $sql.= " FROM ".MAIN_DB_PREFIX."ndfp as n";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user AS u ON n.fk_user = u.rowid";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe AS s ON s.rowid = n.fk_soc";   
    $sql.= " WHERE n.entity = ".$conf->entity;
    
    if ($user->rights->ndfp->myactions->read && !$user->rights->ndfp->allactions->read)
    {
        $sql.= " AND n.fk_user = ".$user->id; // Only get mine notes    
    }

    if ($user->societe_id) // Security check
    {
       $sql.= " AND n.fk_soc = ".$user->societe_id; 
    } 
                             
    $sql.= " ".$db->order('n.datec', 'DESC');
    $sql.= " ".$db->plimit($limit, 0);
    
    dol_syslog("Ndfp::index sql=".$sql, LOG_DEBUG);
    
    $result = $db->query($sql);
    if ($result)
    {   
        $num = $db->num_rows($result);
        $i = 0;
        
    	while ($i < $num)
    	{
            $obj = $db->fetch_object($result);
            
            $userstatic = new User($db);
            $ndfpstatic = new Ndfp($db);
            $societestatic = new Societe($db);
            
    
            $userstatic->nom  = $obj->name;
            $userstatic->prenom = $obj->firstname;
            $userstatic->id = $obj->uid;
            
            $societestatic->id = $obj->soc_id;
            $societestatic->name = $obj->soc_name;
            
            $ndfpstatic->id = $obj->rowid;
            $ndfpstatic->ref = $obj->ref;
            
            $ndfps[$i]->mdate = $db->jdate($obj->tms);    
            $ndfps[$i]->username = $userstatic->getNomUrl(1);
            $ndfps[$i]->total_ttc = $obj->total_ttc;
            $ndfps[$i]->url = $ndfpstatic->get_clickable_name(1);
            
            $ndfps[$i]->already_paid = $ndfpstatic->get_amount_payments_done();
            
            $ndfps[$i]->society = ($obj->fk_soc > 0 ? $societestatic->getNomUrl(1) : '');
            
            
            $ndfps[$i]->statut = $obj->statut;
            
            $ndfps[$i]->dates = $obj->dates;
    
            $ndfps[$i]->filename = dol_sanitizeFileName($obj->ref);
            $ndfps[$i]->filedir = $conf->ndfp->dir_output . '/' . dol_sanitizeFileName($obj->ref);
            $ndfps[$i]->urlsource = DOL_URL_ROOT ."/ndfp/ndfp.php?id=".$obj->rowid;
            
                            
            $i++;
        }
        
        $db->free($result);
    	
    }
    else
    {
        $error = true;
        $message = $db->error()." sql=".$sql;    
    }
    
    // Get all unpaid notes
    $sql = "SELECT n.rowid, n.ref, n.tms, n.fk_user, n.statut, n.fk_soc, n.dates,"; 
    $sql.= " n.total_tva, n.total_ht, n.total_ttc, u.rowid as uid, u.name, u.firstname";
    $sql.= " FROM ".MAIN_DB_PREFIX."ndfp as n";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user AS u ON n.fk_user = u.rowid";
    $sql.= " WHERE n.statut = 1 AND n.entity = ".$conf->entity;
    
    if ($user->rights->ndfp->myactions->read && !$user->rights->ndfp->allactions->read)
    {
        $sql.= " AND n.fk_user = ".$user->id; // Only get mine notes    
    }
    if ($user->societe_id) // Security check
    {
       $sql.= " AND n.fk_soc = ".$user->societe_id; 
    }
                                      
    $sql.= " ".$db->order('n.datec', 'DESC');
    
    dol_syslog("Ndfp::index sql=".$sql, LOG_DEBUG);
    
    $result = $db->query($sql);
    if ($result)
    {
        
        $num = $db->num_rows($result);
        $i = 0;
        $tot_ttc = 0;
        $tot_paid = 0;
                
    	while ($i < $num)
    	{
            $obj = $db->fetch_object($result);
            
            $userstatic = new User($db);
            $ndfpstatic = new Ndfp($db);
            $societestatic = new Societe($db);
            
            $userstatic->nom  = $obj->name;
            $userstatic->prenom = $obj->firstname;
            $userstatic->id = $obj->uid;
    
            
            $ndfpstatic->id = $obj->rowid;
            $ndfpstatic->ref = $obj->ref;
            $ndfpstatic->statut = $obj->statut;
            
            $unpaid_ndfps[$i]->modifdate = dol_print_date($db->jdate($obj->tms),'day');    
            $unpaid_ndfps[$i]->username = $userstatic->getNomUrl(1);
            $unpaid_ndfps[$i]->total_ttc = $obj->total_ttc;
            $unpaid_ndfps[$i]->url = $ndfpstatic->get_clickable_name(1);
          
            $already_paid = $ndfpstatic->get_amount_payments_done();
            
            $tot_ttc += $obj->total_ttc;
            $tot_paid += $already_paid; 
                
            $unpaid_ndfps[$i]->already_paid = $already_paid;
                 
            
            $unpaid_ndfps[$i]->statut = $ndfpstatic->get_lib_statut(3, $unpaid_ndfps[$i]->already_paid);
            $unpaid_ndfps[$i]->dates = $obj->dates;
    
            $unpaid_ndfps[$i]->filename = dol_sanitizeFileName($obj->ref);
            $unpaid_ndfps[$i]->filedir = $conf->ndfp->dir_output . '/' . dol_sanitizeFileName($obj->ref);
            $unpaid_ndfps[$i]->urlsource = DOL_URL_ROOT ."/ndfp/ndfp.php?id=".$obj->rowid;
            
                            
            $i++;
        }
        
        $db->free($result);
    	
    }
    else
    {
        $error = true;
        $message = $db->error()." sql=".$sql;    
    }
    
    // Get all draft notes   
    $sql = "SELECT n.rowid, n.ref, n.tms, n.total_ht, n.total_ttc, n.fk_user, n.statut, n.fk_soc, n.dates,"; 
    $sql.= " u.rowid as uid, u.name, u.firstname, s.nom AS soc_name, s.rowid AS soc_id, u.login, n.total_tva, n.total_ht";
    $sql.= " FROM ".MAIN_DB_PREFIX."ndfp as n";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON n.fk_user = u.rowid";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = n.fk_soc";  
    $sql.= " WHERE n.statut = 0 AND n.entity = ".$conf->entity;
    
    if ($user->rights->ndfp->myactions->read && !$user->rights->ndfp->allactions->read)
    {
        $sql.= " AND n.fk_user = ".$user->id; // Only get mine notes    
    }
    if ($user->societe_id) // Security check
    {
       $sql.= " AND n.fk_soc = ".$user->societe_id; 
    } 
                                     
    $sql.= " ".$db->order('n.datec', 'DESC');
    
    dol_syslog("Ndfp::index sql=".$sql, LOG_DEBUG);
    
    $result = $db->query($sql);
    if ($result)
    {      
        $num = $db->num_rows($result);
        $i = 0;    
            
    	while ($i < $num)
    	{
            $obj = $db->fetch_object($result);
            
            $userstatic = new User($db);
            $ndfpstatic = new Ndfp($db);
            $societestatic = new Societe($db);
            
            $userstatic->nom  = $obj->name;
            $userstatic->prenom = $obj->firstname;
            $userstatic->id = $obj->uid;
    
            $societestatic->id = $obj->soc_id;
            $societestatic->name = $obj->soc_name;
            
            $ndfpstatic->id = $obj->rowid;
            $ndfpstatic->ref = $obj->ref;
            
            $drafts[$i]->mdate = $db->jdate($obj->tms);    
            $drafts[$i]->username = $userstatic->getNomUrl(1);
            $drafts[$i]->total_ttc = $obj->total_ttc;
            $drafts[$i]->total_ht = $obj->total_ht;
            
            
            $drafts[$i]->url = $ndfpstatic->get_clickable_name(1);
            
            $drafts[$i]->already_paid = $ndfpstatic->get_amount_payments_done();
            
            $drafts[$i]->society = ($obj->fk_soc > 0 ? $societestatic->getNomUrl(1) : '');
            
            
            $drafts[$i]->statut =  $ndfpstatic->get_lib_statut(3, $drafts[$i]->already_paid);
            $drafts[$i]->dates = $obj->dates;
    
            $drafts[$i]->filename = dol_sanitizeFileName($obj->ref);
            $drafts[$i]->filedir = $conf->ndfp->dir_output . '/' . dol_sanitizeFileName($obj->ref);
            $drafts[$i]->urlsource = DOL_URL_ROOT ."/ndfp/ndfp.php?id=".$obj->rowid;
            
                            
            $i++;
        }
        
        $db->free($result);    	
    }
    else
    {
        $error = true;
        $message = $db->error()." sql=".$sql;    
    }    
}

    
/*
 * View
 */
require_once('tpl/ndfp.index.tpl.php');

        
$db->close();

?>

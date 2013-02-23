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
 *	\file       htdocs/ndfp/ndfp.php
 *	\ingroup    ndfp
 *	\brief      Page to create/modify/view a credit note or all credit notes
 */

require('../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php');
include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php');
require_once(DOL_DOCUMENT_ROOT."/ndfp/class/ndfp.class.php");
require_once(DOL_DOCUMENT_ROOT."/ndfp/class/html.form.ndfp.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");


if ($conf->projet->enabled)
{
    require_once(DOL_DOCUMENT_ROOT."/core/lib/project.lib.php");
    require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
} 

 
$langs->load('companies');
$langs->load('ndfp');
$langs->load('main');

if (!$user->rights->ndfp->myactions->read && !$user->rights->ndfp->allactions->read)
{
    accessforbidden();
}  
        
$id = GETPOST('id');
$lineid = GETPOST('lineid');
$ref = GETPOST('ref');

$action = GETPOST('action');
$confirm = GETPOST('confirm');

$cancel = GETPOST('cancel') ? true : false;


//Init error
$error = false;
$message = false;

/*
 * View
 */
$ndfp = new Ndfp($db);
$ndfps = array();

$ndfpHtml = new NdfpForm($db); 
   
$html = new Form($db);
$htmlother = new FormOther($db);
$formfile = new FormFile($db);
// List of actions on element      
$formactions = new FormActions($db);

$now = dol_now();


$remain_to_pay_for_display = 0;
$already_paid = 0;
$remain_to_pay = 0;


if ($id > 0 || !empty($ref))
{
    $result = $ndfp->fetch($id, $ref);
    
    if ($result < 0)
    {            
	    header("Location: ".DOL_URL_ROOT.'/ndfp/ndfp.php');
    }
                
}
else
{
    if (empty($action) && ($user->rights->ndfp->myactions->read || $user->rights->ndfp->allactions->read))
    {
        // Get all notes
    
        $sortfield = GETPOST("sortfield",'alpha');
        $sortorder = GETPOST("sortorder",'alpha');
        $filter = GETPOST("filter",'alpha');
        
        $page = GETPOST("page",'int');
    
        // Filter search
        $search_ref = GETPOST("search_ref");
        
        $search_month_s = GETPOST("search_month_s",'int');
        $search_year_s = GETPOST("search_year_s",'int');

        $search_month_e = GETPOST("search_month_e",'int');
        $search_year_e = GETPOST("search_year_e",'int');
                
        $search_soc = GETPOST("search_soc",'alpha');
        $search_user = GETPOST("search_user",'alpha');
        
        $search_ht_amount = GETPOST("search_ht_amount");
        $search_ttc_amount = GETPOST("search_ttc_amount");
        $search_total_paid = GETPOST("search_total_paid");
        //
        
        $fk_user = GETPOST('fk_user', 'int');
        $fk_soc = GETPOST('fk_soc', 'int');
        
        if ($user->societe_id) // Security check
        {
           $fk_soc = $user->societe_id; 
        }
          
        if ($page == -1) 
        { 
            $page = 0; 
        }
        
        $offset = $conf->liste_limit * $page;
        
        if (! $sortorder) $sortorder = 'DESC';
        if (! $sortfield) $sortfield = 'n.datef';
        
        $limit = $conf->liste_limit;
    
        $pageprev = $page - 1;
        $pagenext = $page + 1;
                
        $sql = "SELECT n.rowid, n.ref, n.tms, n.total_ht, n.total_ttc, n.fk_user, n.statut, n.fk_soc, n.dates, n.datee,"; 
        $sql.= " u.rowid as uid, u.name, u.firstname, s.nom AS soc_name, s.rowid AS soc_id, u.login, n.total_tva, SUM(p.amount) AS already_paid";
        $sql.= " FROM ".MAIN_DB_PREFIX."ndfp as n";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON n.fk_user = u.rowid";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = n.fk_soc";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."ndfp_pay_det as p ON p.fk_ndfp = n.rowid";      
        $sql.= " WHERE n.entity = ".$conf->entity; 
        
        if ($filter == 'unpaid')
        {
            $sql.= " AND n.statut = 1";    
        }                    

        if ($fk_soc > 0)
        {
            $sql .= " AND n.fk_soc = ".$fk_soc;
        }
        
        if ($fk_user > 0)
        {
            $sql .= " AND n.fk_user = ".$fk_user;
        }
                        
        if ($user->rights->ndfp->myactions->read && !$user->rights->ndfp->allactions->read)
        {
            $sql .= " AND n.fk_user = ".$user->id;
        }  

        if ($search_ref)
        {
            $sql.= ' AND n.ref LIKE \'%'.$db->escape(trim($search_ref)).'%\'';
        }        
        if ($search_soc)
        {
            $sql.= ' AND s.nom LIKE \'%'.$db->escape(trim($search_soc)).'%\'';
        }
        if ($search_user)
        {
            $sql.= ' AND u.name LIKE \'%'.$db->escape(trim($search_user)).'%\' OR u.firstname LIKE \'%'.$db->escape(trim($search_user)).'%\'';
        }
                
        if ($search_ht_amount)
        {
            $sql.= ' AND n.total_ht = '.$db->escape(price2num(trim($search_ht_amount)));
        }
        if ($search_ttc_amount)
        {
            $sql.= ' AND n.total_ttc = '.$db->escape(price2num(trim($search_ttc_amount)));
        }
        if ($search_total_paid)
        {
            $sql.= ' AND already_paid = '.$db->escape(price2num(trim($search_total_paid)));
        }
        
        if ($search_month_s > 0)
        {
            if ($search_year_s > 0)
            $sql.= " AND n.dates BETWEEN '".$db->idate(dol_get_first_day($search_year_s,$search_month_s,false))."' AND '".$db->idate(dol_get_last_day($search_year_s,$search_month_s,false))."'";
            else
            $sql.= " AND date_format(n.dates, '%m') = '".$search_month_s."'";
        }
        else if ($search_year_s > 0)
        {
            $sql.= " AND n.dates BETWEEN '".$db->idate(dol_get_first_day($search_year_s,1,false))."' AND '".$db->idate(dol_get_last_day($search_year_s,12,false))."'";
        }

        if ($search_month_e > 0)
        {
            if ($search_year_e > 0)
            $sql.= " AND n.datee BETWEEN '".$db->idate(dol_get_first_day($search_year_e,$search_month_e,false))."' AND '".$db->idate(dol_get_last_day($search_year_e,$search_month_e,false))."'";
            else
            $sql.= " AND date_format(n.datee, '%m') = '".$search_month_e."'";
        }
        else if ($search_year_e > 0)
        {
            $sql.= " AND n.datee BETWEEN '".$db->idate(dol_get_first_day($search_year_e,1,false))."' AND '".$db->idate(dol_get_last_day($search_year_e,12,false))."'";
        }
                                  
        $sql.= ' GROUP BY n.rowid ORDER BY '.$sortfield.' '.$sortorder.', n.rowid DESC ';
        $sql.= $db->plimit($limit+1, $offset);
    
        dol_syslog("Ndfp::ndfp sql=".$sql, LOG_DEBUG);
        $result = $db->query($sql);
        
        
        if ($result)
        {
            $num = $db->num_rows($result);
            $i = 0;
            
            if ($num)
            {
                while ($i < $num)
                {
                    $userstatic = new User($db);
                    $ndfpstatic = new Ndfp($db);
                    $societestatic = new Societe($db);
                    
                    $obj = $db->fetch_object($resql);
                     
                    $ndfps[$i] = $obj;

                    
                    $userstatic->nom  = $obj->name;
                    $userstatic->prenom = $obj->firstname;
                    $userstatic->id = $obj->uid;
                    
                    $societestatic->id = $obj->soc_id;
                    $societestatic->name = $obj->soc_name;
                    
                    $ndfpstatic->id = $obj->rowid;
                    $ndfpstatic->ref = $obj->ref;
                    $ndfpstatic->statut = $obj->statut;
                    
                    $ndfps[$i]->mdate = $db->jdate($obj->tms);    
                    $ndfps[$i]->username = $userstatic->getNomUrl(1);
                    $ndfps[$i]->total_ttc = $obj->total_ttc;
                    $ndfps[$i]->url = $ndfpstatic->get_clickable_name(1);
                    
                    $ndfps[$i]->society = ($obj->fk_soc > 0 ? $societestatic->getNomUrl(1) : '');
                    
                    
                    $ndfps[$i]->statut = $ndfpstatic->get_lib_statut(5, $obj->already_paid);
                    
                    $ndfps[$i]->dates = $db->jdate($obj->dates);
                    $ndfps[$i]->datee = $db->jdate($obj->datee);
                    
                    $ndfps[$i]->filename = dol_sanitizeFileName($obj->ref);
                    $ndfps[$i]->filedir = $conf->ndfp->dir_output . '/' . dol_sanitizeFileName($obj->ref);
                    $ndfps[$i]->urlsource = DOL_URL_ROOT ."/ndfp/ndfp.php?id=".$obj->rowid;
                                 
                    $i++;
                }
            }
        }
        
        $db->free($resql);        
    }

                    
}


    
if ($action != 'create' && $action != 'delete' && $action != 'setproject' && !$cancel)
{
    $result = $ndfp->call($action, array($user));  
    
    if ($result > 0)
    {
        if ($action == 'confirm_delete')
        {
            header("Location: ".DOL_URL_ROOT.'/ndfp/ndfp.php');
        }
        else
        {
            $message = $ndfp->error; //     
        }
    
    }
    else
    {
        if ($action == 'add')
        {
            $action = 'create';
            $ndfp->ref = '';
        }
                
        $message = $ndfp->error;
        $error = true;        
    }    
}



/*
 * Add file in email form
 */
if ($_POST['addfile'])
{

    // Set tmp user directory
    $vardir = $conf->user->dir_output. "/" .$user->id;
    $upload_dir_tmp = $vardir.'/temp';

    dol_add_file_process($upload_dir_tmp, 0, 0);
    $message = $langs->trans('FileTransferComplete');
    $error = false;
        
    $action = 'presend';
}

/*
 * Remove file in email form
 */
if (! empty($_POST['removedfile']))
{

    // Set tmp user directory
    $vardir = $conf->user->dir_output. "/" .$user->id;
    $upload_dir_tmp = $vardir.'/temp';

    dol_remove_file_process($_POST['removedfile'], 0);

    $message = $langs->trans('FileHasBeenRemoved');
    $error = false;
    $action = 'presend';
}

$formconfirm = '';
// Confirmations
if ($action == 'clone')
{
    $formconfirm = $html->formconfirm($_SERVER["PHP_SELF"].'?id='.$ndfp->id,$langs->trans('CloneNdfp'),$langs->trans('ConfirmCloneNdfp',$object->ref),'confirm_clone','','yes',1);
}

if ($action == 'delete')
{
    $formconfirm = $html->formconfirm($_SERVER["PHP_SELF"].'?id='.$ndfp->id,$langs->trans('DeleteNdfp'),$langs->trans('ConfirmDeleteNdfp'),'confirm_delete','','no',1);
}

if ($action == 'ask_deleteline')
{
    $formconfirm = $html->formconfirm($_SERVER["PHP_SELF"].'?id='.$ndfp->id.'&lineid='.$lineid, $langs->trans('DeleteLineNdfp'), $langs->trans('ConfirmDeleteLineNdfp'), 'confirm_deleteline', '', 'no', 1);
}

if ($action == 'valid')
{
    $ndfpref = substr($ndfp->ref, 1, 4);
    
    if ($ndfpref == 'PROV')
    {
        $numref = $ndfp->get_next_num_ref($ndfp->fk_soc);
    }
    else
    {
        $numref = $ndfp->ref;
    }

    $formconfirm = $html->formconfirm($_SERVER["PHP_SELF"].'?id='.$ndfp->id, $langs->trans('ValidateNdfp'), $langs->trans('ConfirmValidateNdfp', $numref), 'confirm_valid','', "yes", 1);
}

if ($action == 'canceled')
{
    $formconfirm = $html->formconfirm($_SERVER["PHP_SELF"].'?id='.$ndfp->id,$langs->trans('CancelNdfp'),$langs->trans('ConfirmCancelNdfp',$ndfp->ref),'confirm_canceled','','yes', 1);
}

if ($action == 'paid')
{
    $formconfirm = $html->formconfirm($_SERVER["PHP_SELF"].'?id='.$ndfp->id,$langs->trans('PaidNdfp'),$langs->trans('ConfirmPaidNdfp',$ndfp->ref),'confirm_paid','','yes',1);
}
            
if ($action == 'presend')
{
    /*
     * Display mail form
     */

    $ref = dol_sanitizeFileName($ndfp->ref);
    $upload_dir = $conf->ndfp->dir_output .'/'. $ref;

    $file = $upload_dir . '/' . $ref . '.pdf';
    // Construit PDF si non existant
    if (! is_readable($file))
    {
        $result = $ndfp->generate_pdf($user);        
    }

    
    $formmail = new FormMail($db);
    $formmail->fromtype = 'user';
    $formmail->fromid   = $user->id;
    $formmail->fromname = $user->getFullName($langs);
    $formmail->frommail = $user->email;
    $formmail->withfrom = 1;
    $formmail->withto  = empty($_POST["sendto"]) ? 1 : $_POST["sendto"];
    $formmail->withtosocid = 0;
    $formmail->withtocc = 1;
    $formmail->withtoccsocid = 0;
    $formmail->withtoccc = $conf->global->MAIN_EMAIL_USECCC;
    $formmail->withtocccsocid = 0;
    $formmail->withtopic = $langs->transnoentities('SendNdfpMailTopic','__NDFPREF__');
    $formmail->withfile = 2;
    $formmail->withbody = $langs->transnoentities('SendNdfpMailBody','__NDFPREF__');
    $formmail->withdeliveryreceipt = 1;
    $formmail->withcancel = 1;
  
    $formmail->substit['__NDFPREF__'] = $ndfp->ref;

    $formmail->param['action'] = 'send';
    $formmail->param['ndfpid'] = $ndfp->id;
    $formmail->param['returnurl'] = $_SERVER["PHP_SELF"].'?id='.$ndfp->id;

    // Init list of files
    if (! empty($_REQUEST["mode"]) && $_REQUEST["mode"]=='init')
    {
        $formmail->clear_attached_files();
        // Get list of files
        $filearray = dol_dir_list($upload_dir, "files", 0, '', '\.meta$', 'name', SORT_ASC, 0);
        
        foreach($filearray AS $file)        
        {
            $formmail->add_attached_files($file['fullname'], dol_sanitizeFilename($file['name']), dol_mimetype($file['fullname']));
        }
        
    }

}      

if ($ndfp->id > 0 || $action == 'create')
{
    if ($action == 'create' || $action == 'edit')
    {
    
        $model = new ModeleNdfp();
        $models = $model->liste_modeles($db);
    
        $userCommentEditor = new DolEditor('comment_user', $ndfp->comment_user, '',200,'dolibarr_notes','',false,true, $conf->fckeditor->enabled,ROWS_6,50);
        $adminCommentEditor = new DolEditor('comment_admin', $ndfp->comment_admin, '',200,'dolibarr_notes','',false,true, $conf->fckeditor->enabled,ROWS_6,50);
        
        require_once('tpl/ndfp.create.tpl.php');
        
    }
    else if ($action == 'followup')
    {
       // Prepare head
    	$h = 0;
    	$head = array();
    
        $current_head = 'followup';
        
    	$head[$h][0] = DOL_URL_ROOT.'/ndfp/ndfp.php?id='.$ndfp->id;
    	$head[$h][1] = $langs->trans('NdfpSheet');
    	$head[$h][2] = 'ndfp';
    	$h++;
           
    	$head[$h][0] = DOL_URL_ROOT.'/ndfp/note.php?id='.$ndfp->id;
    	$head[$h][1] = $langs->trans('NdfpNotes');
    	$head[$h][2] = 'notes';
    	$h++; 
                
    	$head[$h][0] = DOL_URL_ROOT.'/ndfp/document.php?id='.$ndfp->id;
    	$head[$h][1] = $langs->trans('NdfpAttachedFiles');
    	$head[$h][2] = 'documents';
    	$h++;   

    
    	$head[$h][0] = DOL_URL_ROOT.'/ndfp/ndfp.php?action=followup&id='.$ndfp->id;
    	$head[$h][1] = $langs->trans('Followup');
    	$head[$h][2] = 'followup';
    	$h++; 
        
    
        $userstatic = new User($db);   
        $userstatic->fetch($ndfp->fk_user_author); 
    
        
        require_once('tpl/ndfp.followup.tpl.php');             
    }
    else
    {
        /**
        /* Default view
        **/
       // Prepare head
    	$h = 0;
    	$head = array();
    
        $current_head = 'ndfp';
        
    	$head[$h][0] = DOL_URL_ROOT.'/ndfp/ndfp.php?id='.$ndfp->id;
    	$head[$h][1] = $langs->trans('NdfpSheet');
    	$head[$h][2] = 'ndfp';
    	$h++;
           
    	$head[$h][0] = DOL_URL_ROOT.'/ndfp/note.php?id='.$ndfp->id;
    	$head[$h][1] = $langs->trans('NdfpNotes');
    	$head[$h][2] = 'notes';
    	$h++; 
                
    	$head[$h][0] = DOL_URL_ROOT.'/ndfp/document.php?id='.$ndfp->id;
    	$head[$h][1] = $langs->trans('NdfpAttachedFiles');
    	$head[$h][2] = 'documents';
    	$h++;   

    
    	$head[$h][0] = DOL_URL_ROOT.'/ndfp/ndfp.php?action=followup&id='.$ndfp->id;
    	$head[$h][1] = $langs->trans('Followup');
    	$head[$h][2] = 'followup';
    	$h++; 

        $already_paid  = $ndfp->get_amount_payments_done();
        $remain_to_pay = price2num($ndfp->total_ttc - $already_paid,'MT');  
                
        $societestatic = new Societe($db);
        $userstatic = new User($db);
        $projectstatic = null;
        
        if ($conf->projet->enabled && $ndfp->fk_project > 0)
        {
            $projectstatic = new Project($db);
            $projectstatic->fetch($ndfp->fk_project);
        } 
    
        
        $societestatic->fetch($ndfp->fk_soc);    
        $userstatic->fetch($ndfp->fk_user);
        
        if ($ndfp->fk_soc)
        {
            $soc = $societestatic->getNomUrl(1);
            $otherNdf = '<a href="'.DOL_URL_ROOT.'/ndfp/index.php?socid='.$societestatic->id.'">'.$langs->trans("OtherNdfp").'</a>';                
        }
        else
        {
            $soc = '';
            $otherNdf = '';
        } 
        
        $nbrows = 6;
        if ($conf->projet->enabled)
        {
            $nbrows++;
        } 
        
        $payments = $ndfp->get_payments();                       
    
        $remain_to_pay_for_display = $remain_to_pay;
        
        $can_add_expenses = $ndfp->can_add_expenses($action);            
        
        
        // Define available actions
        $buttons = $ndfp->get_available_actions($action);          
    
        // Load predefined expenses
        $predefined_expenses = array();
    
        $sql  = " SELECT e.rowid, e.code, e.fk_tva, e.label, t.taux";
        $sql .= " FROM ".MAIN_DB_PREFIX."c_exp e";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_tva t";
        $sql .= " ON e.fk_tva = t.rowid WHERE e.active = 1";                        
        $sql .= " ORDER BY e.rowid DESC";
        
        dol_syslog("Ndfp::ndfp sql=".$sql, LOG_DEBUG);
        
        $result = $db->query($sql);
        $i = 0;
        if ($result)
        {
            $num = $db->num_rows($result);
            
        	while ($i < $num)
        	{
                $predefined_expenses[$i] = $db->fetch_object($result);        
                                
                $i++;
            }
            
            $db->free($result);    	
        }
        
        $selected_vat = 0;
        if (sizeof($predefined_expenses))
        {
            $selected_vat = $predefined_expenses[0]->fk_tva;
        }
        
 
        
        $k = 0;
        $numLines = sizeof($ndfp->lines);
    
    
        // Documents and actions
        $filename = dol_sanitizeFileName($ndfp->ref);
        $filedir = $conf->ndfp->dir_output . '/' . dol_sanitizeFileName($ndfp->ref);
        $urlsource = $_SERVER['PHP_SELF'].'?id='.$ndfp->id;
        
        $genallowed = $user->rights->ndfp->myactions->create;
        $delallowed = $user->rights->ndfp->myactions->delete;
    
         
        require_once('tpl/ndfp.default.tpl.php');
    }    
}
else
{
    // Display all notes
    
    require_once('tpl/ndfp.list.tpl.php');
            
}


$db->close();

?>
<?php

/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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

 * along with this program; if not, write to the Free Software

 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */
/*
  /*
 */

/**
 *    \file       htdocs/projet/fiche.php
 *    \ingroup    projet
 *    \brief      Fiche projet
 *    \version    $Id: fiche.php,v 1.57.2.1 2008/09/10 09:46:02 eldy Exp $
 */
require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php");
require_once(DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");
//require_once(DOL_DOCUMENT_ROOT . "/core/lib/project.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/synopsis_project.lib.php");


if (!isset($_REQUEST['action']))
    $_REQUEST['action'] = '';


$langs->load("project@projet");

$projetid = '';
if ($_GET["id"]) {
    $projetid = $_GET["id"];
}

// Security check
$socid=GETPOST('socid');
if ($user->societe_id > 0) $socid=$user->societe_id;
$result = restrictedArea($user, 'projet', $object->id);

if ($projetid == '' && ($_GET['action'] != "create" && $_POST['action'] != "add" && $_REQUEST['action'] != "update" && !$_POST["cancel"]))
    accessforbidden();

$date_start=dol_mktime(0,0,0,GETPOST('projectmonth','int'),GETPOST('projectday','int'),GETPOST('projectyear','int'));
$date_end=dol_mktime(0,0,0,GETPOST('projectendmonth','int'),GETPOST('projectendday','int'),GETPOST('projectendyear','int'));


/*
 * Actions
 */

$parameters=array('id'=>$socid, 'objcanvas'=>$objcanvas);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
$error=$hookmanager->error; $errors=array_merge($errors, (array) $hookmanager->errors);

if (empty($reshook))
{

	// Cancel
	if (GETPOST("cancel") && ! empty($backtopage))
	{
		if (GETPOST("comefromclone")==1)
		{
		    $result=$object->delete($user);
		    if ($result > 0)
		    {
		        header("Location: index.php");
		        exit;
		    }
		    else
		    {
		        dol_syslog($object->error,LOG_DEBUG);
		        $mesg='<div class="error">'.$langs->trans("CantRemoveProject").'</div>';
		    }
		}
	    header("Location: ".$backtopage);
	    exit;
	}

	//if cancel and come from clone then delete the cloned project
	if (GETPOST("cancel") && (GETPOST("comefromclone")==1))
	{
	    $result=$object->delete($user);
	    if ($result > 0)
	    {
	        header("Location: index.php");
	        exit;
	    }
	    else
	    {
	        dol_syslog($object->error,LOG_DEBUG);
	        $mesg='<div class="error">'.$langs->trans("CantRemoveProject").'</div>';
	    }
	}

	if ($action == 'add' && $user->rights->projet->creer)
	{
	    $error=0;
	    if (empty($_POST["ref"]))
	    {
	        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Ref")).'</div>';
	        $error++;
	    }
	    if (empty($_POST["title"]))
	    {
	        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Label")).'</div>';
	        $error++;
	    }

	    if (! $error)
	    {
	        $error=0;

	        $db->begin();

	        $object->ref             = GETPOST('ref','alpha');
	        $object->title           = GETPOST('title'); // Do not use 'alpha' here, we want field as it is
	        $object->socid           = GETPOST('socid','int');
	        $object->description     = GETPOST('description'); // Do not use 'alpha' here, we want field as it is
	        $object->public          = GETPOST('public','alpha');
	        $object->datec=dol_now();
	        $object->date_start=$date_start;
	        $object->date_end=$date_end;

	        // Fill array 'array_options' with data from add form
	        $ret = $extrafields->setOptionalsFromPost($extralabels,$object);

	        $result = $object->create($user);
	        if ($result > 0)
	        {
	            // Add myself as project leader
	            $result = $object->add_contact($user->id, 'PROJECTLEADER', 'internal');
	            if ($result < 0)
	            {
	                $langs->load("errors");
	                $mesg='<div class="error">'.$langs->trans($object->error).'</div>';
	                $error++;
	            }
	        }
	        else
	        {
	            $langs->load("errors");
	            $mesg='<div class="error">'.$langs->trans($object->error).'</div>';
	            $error++;
	        }

	        if (! $error)
	        {
	            $db->commit();

	            header("Location:fiche.php?id=".$object->id);
	            exit;
	        }
	        else
	        {
	            $db->rollback();

	            $action = 'create';
	        }
	    }
	    else
	    {
	        $action = 'create';
	    }
	}

	if ($action == 'update' && ! $_POST["cancel"] && $user->rights->projet->creer)
	{
	    $error=0;

	    if (empty($ref))
	    {
	        $error++;
	        //$_GET["id"]=$_POST["id"]; // On retourne sur la fiche projet
	        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Ref")).'</div>';
	    }
	    if (empty($_POST["title"]))
	    {
	        $error++;
	        //$_GET["id"]=$_POST["id"]; // On retourne sur la fiche projet
	        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Label")).'</div>';
	    }

	    $db->begin();

	    if (! $error)
	    {
	        $object->oldcopy = dol_clone($object);

			$old_start_date = $object->date_start;

	        $object->ref          = GETPOST('ref','alpha');
	        $object->title        = GETPOST('title'); // Do not use 'alpha' here, we want field as it is
	        $object->socid        = GETPOST('socid','int');
	        $object->description  = GETPOST('description');	// Do not use 'alpha' here, we want field as it is
	        $object->public       = GETPOST('public','alpha');
	        $object->date_start   = empty($_POST["project"])?'':$date_start;
	        $object->date_end     = empty($_POST["projectend"])?'':$date_end;

	        // Fill array 'array_options' with data from add form
	        $ret = $extrafields->setOptionalsFromPost($extralabels,$object);
			if ($ret < 0)
			{
				$error++;
			}
	    }

	    if (! $error)
	    {
	    	$result=$object->update($user);
	    	if ($result < 0)
	    	{
	    		$error++;
	    		setEventMessage($object->errors,'errors');
	    	}
	    }

	    if (! $error)
	    {
	    	if (GETPOST("reportdate") && ($object->date_start!=$old_start_date))
	    	{
	    		$result=$object->shiftTaskDate($old_start_date);
	    		if ($result < 0)
	    		{
	    			$error++;
	    			$mesg='<div class="error">'.$langs->trans("ErrorShiftTaskDate").':'.$object->error.'</div>';
	    		}
	    	}
	    }

	    if ($error)
	    {
			$db->rollback();
	    	$action='edit';
	    }
	    else
		{
	    	$db->commit();

			if (GETPOST('socid','int') > 0) $object->societe->fetch(GETPOST('socid','int'));
			else unset($object->societe);
	    }
	}

	// Build doc
	if ($action == 'builddoc' && $user->rights->projet->creer)
	{
		// Save last template used to generate document
		if (GETPOST('model')) $object->setDocModel($user, GETPOST('model','alpha'));

	    $outputlangs = $langs;
	    if (GETPOST('lang_id'))
	    {
	        $outputlangs = new Translate("",$conf);
	        $outputlangs->setDefaultLang(GETPOST('lang_id'));
	    }
	    $result=project_pdf_create($db, $object, $object->modelpdf, $outputlangs);
	    if ($result <= 0)
	    {
	        dol_print_error($db,$result);
	        exit;
	    }
	}

	// Delete file in doc form
	if ($action == 'remove_file' && $user->rights->projet->creer)
	{
	    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	    if ($object->id > 0)
	    {
	        $langs->load("other");
	        $upload_dir =	$conf->projet->dir_output . "/";
	        $file =	$upload_dir	. '/' .	GETPOST('file');
	        $ret=dol_delete_file($file);
	        if ($ret) setEventMessage($langs->trans("FileWasRemoved", GETPOST('urlfile')));
	        else setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), 'errors');
	    }
	}


	if ($action == 'confirm_validate' && GETPOST('confirm') == 'yes')
	{
	    $result = $object->setValid($user);
	    if ($result <= 0)
	    {
	        $mesg='<div class="error">'.$object->error.'</div>';
	    }
	}

	if ($action == 'confirm_close' && GETPOST('confirm') == 'yes')
	{
	    $result = $object->setClose($user);
	    if ($result <= 0)
	    {
	        $mesg='<div class="error">'.$object->error.'</div>';
	    }
	}

	if ($action == 'confirm_reopen' && GETPOST('confirm') == 'yes')
	{
	    $result = $object->setValid($user);
	    if ($result <= 0)
	    {
	        $mesg='<div class="error">'.$object->error.'</div>';
	    }
	}

	if ($action == 'confirm_delete' && GETPOST("confirm") == "yes" && $user->rights->projet->supprimer)
	{
	    $object->fetch($id);
	    $result=$object->delete($user);
	    if ($result > 0)
	    {
	        header("Location: index.php");
	        exit;
	    }
	    else
	    {
	        dol_syslog($object->error,LOG_DEBUG);
	        $mesg='<div class="error">'.$langs->trans("CantRemoveProject").'</div>';
	    }
	}

	if ($action == 'confirm_clone' && $user->rights->projet->creer && GETPOST('confirm') == 'yes')
	{
	    $clone_contacts=GETPOST('clone_contacts')?1:0;
	    $clone_tasks=GETPOST('clone_tasks')?1:0;
		$clone_project_files = GETPOST('clone_project_files') ? 1 : 0;
		$clone_task_files = GETPOST('clone_task_files') ? 1 : 0;
	    $clone_notes=GETPOST('clone_notes')?1:0;
	    $result=$object->createFromClone($object->id,$clone_contacts,$clone_tasks,$clone_project_files,$clone_task_files,$clone_notes);
	    if ($result <= 0)
	    {
	        $mesg='<div class="error">'.$object->error.'</div>';
	    }
	    else
	    {
	    	$object->fetch($result);	// Load new object
	    	$action='edit';
	    	$comefromclone=true;
	    }
	}
}


/*
 *    View
 */

if ($_REQUEST['action'] != 'create') {
    $csspath = DOL_URL_ROOT . '/Synopsis_Common/css/';
    $jspath = DOL_URL_ROOT . '/Synopsis_Common/jquery/';
    $jqueryuipath = DOL_URL_ROOT . '/Synopsis_Common/jquery/ui/';

    $header = '<script language="javascript" src="' . $jspath . 'jquery.dimensions.js"></script>' . "\n";
    $header .= '<script language="javascript" src="' . $jspath . 'jquery.tooltip.js"></script>' . "\n";
    $header .= "<style type='text/css'>.ui-progressbar{ height: 13px; background-color: #ffffff; margin: 0px;}</style>";
    $header .= "<style type='text/css'>.ui-progressbar-value{ border:1px solid #000000; }</style>";

    $header .= "<script language='javascript'>";
    $projet = new Project($db);
    $projet->fetch($_GET["id"]);
    $projet->societe->fetch($projet->societe->id);

    $projet->getAllStats();
    $avancTime = $projet->workedPercentTime;
    $avancQual = $projet->statAvgProgByGroup[0];

    $header .= <<<EOF

jQuery(document).ready(function(){
    jQuery("#progressBarTime").progressbar({ value: $avancTime });
    jQuery("#progressBarQual").progressbar({ value: $avancQual });
    jQuery('#actoTooltip a').each(function(){
        jQuery(this).tooltip({
            delay: 0,
            showURL: false,
            showBody: " - ",
            fade: 250,
            bodyHandler: function() {
                var ret =  "<div>"+jQuery(this).parent().find('.jqtoolTipInfo').html()+"</div>";
                //alert ($(this).parent().find('.jqtoolTipInfo').html());
                return(jQuery(ret));
            },
            extraClass: "floatTooltip ui-corner-all",
            top: 0,
            left: 5,
            track: true
        });
    });

});

EOF;

    $header .= "</script>";
    $header .= "<style>.floatTooltip { position: absolute; background-color: #ffff99; border: 1px Solid #000000; padding : 10px; }</style>";
}


//llxHeader($header,$langs->trans("Projects"),"Projet","1");


llxHeader($header, $langs->trans("Project"));

if (isset($msg) && $msg . "x" != "x") {
    print "<br/>";
    print "<div class='ui-state-error error'>" . $msg . "</div>";
    print "<br/>";
    print "<br/>";
}

$html = new Form($db);

if ($_REQUEST['action'] == 'create' && $user->rights->synopsisprojet->creer) {
    /*
     * Create
     */

	$thirdparty=new Societe($db);
	if ($socid > 0) $thirdparty->fetch($socid);

    print_fiche_titre($langs->trans("NewProject"));

    if (isset($mesg))
        print $mesg;

    print '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">';
    //if ($_REQUEST["socid"]) print '<input type="hidden" name="socid" value="'.$_REQUEST["socid"].'">';
    print '<table cellpadding=15 class="border" width="100%">';
    print '<input type="hidden" name="action" value="add">';
    print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

    print '<table class="border" width="100%">';

    $defaultref='';
    $modele = empty($conf->global->PROJECT_ADDON)?'mod_project_simple':$conf->global->PROJECT_ADDON;

    // Search template files
    $file=''; $classname=''; $filefound=0;
    $dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);
    foreach($dirmodels as $reldir)
    {
    	$file=dol_buildpath($reldir."core/modules/project/".$modele.'.php',0);
    	if (file_exists($file))
    	{
    		$filefound=1;
    		$classname = $modele;
    		break;
    	}
    }

    if ($filefound)
    {
	    $result=dol_include_once($reldir."core/modules/project/".$modele.'.php');
	    $modProject = new $classname;

	    $defaultref = $modProject->getNextValue($thirdparty,$object);
    }

    if (is_numeric($defaultref) && $defaultref <= 0) $defaultref='';

    // Ref
//    print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("Ref").'</th>
//               <td class="ui-widget-content"><input size="8" type="text" name="ref" value="'.$_POST["ref"].'"></td></tr>';
    //Type
    print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("Type") . '</th>
               <td class="ui-widget-content">';
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_projet_type WHERE active = 1 ORDER BY type";
    $sql = $db->query($requete);
    print "<SELECT name='type' id='type'>";
    while ($res = $db->fetch_object($sql)) {
        if ($res->defaut == 1)
            print "<OPTION SELECTED value='" . $res->id . "'>" . $res->type . "</OPTION>";
        else
            print "<OPTION value='" . $res->id . "'>" . $res->type . "</OPTION>";
    }
    print "</SELECT>";
    print '</td></tr>';

    // Label
    print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("Label") . '</th>
               <td class="ui-widget-content"><input size="30" type="text" name="title" value="' . (isset($_POST["title"]) ? $_POST["title"] : '') . '"></td></tr>';

    // Client
    print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("Company") . '</th>
               <td class="ui-widget-content">';
    //print $_REQUEST["socid"];
    print $html->select_company($_REQUEST["socid"], 'socid', '', 1);
    print '</td></tr>';

    // Auteur du projet
    print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("Author") . '</th>
               <td class="ui-widget-content">' . $user->getNomUrl(1, 6) . '</td></tr>';

    // Responsable du projet
    print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("OfficerProject") . '</th>
               <td class="ui-widget-content">';
    $html->select_users($user->id, 'officer_project', 1);
    print '</td></tr>';

    print '<tr><th class="ui-widget-header ui-state-default" colspan="2" align="center"><button class="butAction ui-widget-header ui-state-default ui-corner-all" value="' . $langs->trans("Create") . '">' . $langs->trans("Create") . '</button></tr>';
    print '</table>';
    print '</form>';
} else {
    /*
     * Show or edit
     */

    if ($mesg)
        print $mesg;


    $head = synopsis_project_prepare_head($projet);
    dol_fiche_head($head, 'project', $langs->trans("Project"));

    if ($_REQUEST['action'] == 'delete') {
        $html->form_confirm("fiche.php?id=" . $_GET["id"], $langs->trans("DeleteAProject"), $langs->trans("ConfirmDeleteAProject"), "confirm_delete");
        print "<br>";
    }

    if ($_REQUEST['action'] == 'edit') {
        print '<form method="post" action="fiche.php">';
        print '<input type="hidden" name="action" value="update">';
        print '<input type="hidden" name="id" value="' . $_GET["id"] . '">';

        print '<table class="border" width="100%" cellpadding="15">';

        // Ref
        print '<tr><th class="ui-widget-header ui-state-default" width="25%">' . $langs->trans("Ref") . '</th>
                   <td class="ui-widget-content">' . $projet->ref . '</td>';

        // Label
        print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("Label") . '</th>
                   <td class="ui-widget-content"><input size="30" name="title" value="' . $projet->title . '"></td></tr>';

        // Customer
        print '<tr><td>'.$langs->trans("ThirdParty").'</td><td>';
        $text=$form->select_company($object->societe->id,'socid','',1,1);
        $texthelp=$langs->trans("IfNeedToUseOhterObjectKeepEmpty");
        print $form->textwithtooltip($text.' '.img_help(),$texthelp,1);
        print '</td></tr>';

        // Responsable du projet
        print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("OfficerProject") . '</th>
                   <td class="ui-widget-content" valign=middle>';
        $html->select_users($projet->user_resp_id, 'officer_project', 1);
        print '</td></tr>';

        print '<tr><td align="center" colspan="2" class="ui-widget-header ui-state-default"><input name="update" class="button" type="submit" value="' . $langs->trans("Modify") . '"> &nbsp; <input type="submit" class="button" name="cancel" Value="' . $langs->trans("Cancel") . '"></td></tr>';
        print '</table>';
        print '</form>';
    } else {
        $projet->fetch_user($projet->user_resp_id);
        //saveHistoUser($projet->id, "projet", $projet->ref);

        print '<table class="border" width="100%" cellpadding=15>';


        // Ref
        print '<tr><th class="ui-widget-header ui-state-default" width="25%">' . $langs->trans("Ref") . '</th>
                   <td class="ui-widget-content">' . $projet->ref . '</td>';

        // Statut
        print '     <th class="ui-widget-header ui-state-default" width="25%">' . $langs->trans("Statut") . '</th>
                    <td class="ui-widget-content">' . $projet->getLibStatut(4) . '</td></tr>';
 
        // Third party
        print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("Company") . '</th>
                   <td class="ui-widget-content" colspan=3>';
        if ($projet->societe->id > 0)
            print $projet->societe->getNomUrl(1);
        else
            print'&nbsp;';
        print '</td></tr>';

        // Label
        print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("Label") . '</th>
                   <td class="ui-widget-content" colspan=3>' . $projet->title . '</td></tr>';

        // Third party
        print '<tr><td>'.$langs->trans("ThirdParty").'</td><td>';
        if ($object->societe->id > 0) print $object->societe->getNomUrl(1);
        else print'&nbsp;';
        print '</td></tr>';

        //print '<tr><th class="ui-widget-header ui-state-default">'.$langs->trans("ProjectBegin").'</th>
        //         <td class="ui-widget-content">'.date('d/m/Y',strtotime($projet->date_valid)).'</td>';
        //print '    <th class="ui-widget-header ui-state-default">'.$langs->trans("ProjectEnd").'</th>
        //           <td class="ui-widget-content">'.date('d/m/Y',strtotime($projet->date_cloture)).'</td></tr>';
        print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("ProjectActors") . '</th>
                   <td class="ui-widget-content" id="actoTooltip" colspan=3>';
        $arr = array();
        $arrObj = array();
        foreach ($projet->tasks as $key1 => $val1) {
            if (isset($val1['acto']) && is_array($val1['acto'])) {
                foreach ($val1['acto'] as $key2 => $val2) {
                    foreach ($val2 as $key => $val) {
                        if ('x' . $val['userid'] != 'x') {
                            if (!in_array($val['userid'], $arr)) {
                                $arr[] = $val['userid'];
                                $arrObj[] = $val['userobj'];
                                //Tooltip
                                print "<div class='jqtooltip'><div style='display:none;' class='jqtoolTipInfo'>";
                                print $val['userobj']->getNomUrl(1);
                                $requete2 = "SELECT ".MAIN_DB_PREFIX."Synopsis_projet_task.title , ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.role
                                           FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_actors,
                                                ".MAIN_DB_PREFIX."Synopsis_projet_task
                                          WHERE ".MAIN_DB_PREFIX."Synopsis_projet_task.rowid = ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.fk_projet_task
                                            AND fk_user = " . $val['userid'] . " AND ".MAIN_DB_PREFIX."Synopsis_projet_task.fk_projet = " . $_REQUEST['id'] . " ORDER BY role ASC";
                                $sql2 = $db->query($requete2);
                                print "<table width=100% border=1 style='border-collapse: collapse; min-width:300px '>";
                                print "<tr class='ui-widget-header ui-state-default'><th align='center' style='width: 200px'>T&acirc;che</th><th align='center'>R&ocirc;le</th></tr>";
                                $pair = false;
                                $remRole = false;
                                while ($res2 = $db->fetch_object($sql2)) {
                                    $class = "impair";
                                    if ($res2->role != $remRole) {
                                        $pair = !$pair;
                                    }
                                    if ($pair) {
                                        $class = "pair";
                                    }
                                    $remRole = $res2->role;
                                    print "<tr class='" . $class . "'><td align='center'>" . $res2->title . "</td><td align='center'>" . $res2->role . "</td></tr>";
                                }
                                print "</table>";


                                print "</div>";
                                //Affichage
                                $val['userobj']->fetch($val['userobj']->id);
                                print $val['userobj']->getNomUrl(1) . "</div>";
                            }
                        }
                    }
                }
            }
        }
        print '</td></tr>';


        print '<tr><th class="ui-widget-header ui-state-default">' . $langs->trans("ProjectStats") . '</td><td style="padding: 0;" class="ui-widget-content" colspan=3>';
        print '<table  style="border-collapse: collapse;" width=100% cellpadding=10><tbody><tr>
                    <th style="border-top:0px" class="ui-widget-header ui-state-default" width=25%>' . $langs->trans('AvancementTemps') . '</td>
                    <td style="border-top:0px;border-right:0px;" class="ui-widget-content"> <div style="float: left; background-color: #FFFFFF; margin-left: 50%; margin-top: -1px; padding: 2px; padding-left: 5px; padding-right: 5px; opacity: 0.95; " class="ui-corner-all">' . $avancTime . '%</div>';
        //Avancement qualitatif
        print '<div id="progressBarTime"></div>';
        print '</td></tr><tr><th style="border-top:0px" class="ui-widget-header ui-state-default" width=25%>' . $langs->trans('AvancementQualitatif') . '</td>
                             <td style="border-top:0px;border-right:0px;" class="ui-widget-content"><div style="float: left; background-color: #FFFFFF; margin-left: 50%; margin-top: -1px; padding: 2px; padding-left: 5px; padding-right: 5px; opacity: 0.95; " class="ui-corner-all">' . $projet->statAvgProgByGroup[0] . '%</div>';
        //Avancement Horaire
        print '<div id="progressBarQual"></div>';
        print '<tr><th style="border-top:0px" class="ui-widget-header ui-state-default" width=25%>' . $langs->trans('TempsPrevu') . '</th><td style="border-top:0px; border-right:0px;" class="ui-widget-content">' . $projet->totDuration/3600 . "h" .($projet->totDuration > (3600*24) ? " (".sec2time($projet->totDuration). ")" : ''). '</td></tr>';
        print '<tr><th style="border-top:0px;border-bottom:0px" class="ui-widget-header ui-state-default" width=25%>' . $langs->trans('TempsEffectue') . '</th><td style="border-right:0px; border-top:0px" class="ui-widget-content">' . $projet->workedDuration/3600 ."h" .($projet->workedDuration > (3600*24) ? " (". sec2time($projet->workedDuration) . ")" : ''). '</td></tr>';

        print '</td></tr>';
        print "</tbody></table>";

        print '</table>';
    }

    print '</div>';
    /*
     * Boutons actions
     */
    print '<div class="tabsAction">';

    if ($_REQUEST['action'] != "edit" &&
            ($projet->user_resp_id == $user->id || $user->rights->synopsisprojet->modAll)) {
        if ($user->rights->synopsisprojet->creer) {
            print '<a class="butAction" href="fiche.php?id=' . $projet->id . '&amp;action=edit">' . $langs->trans("Modify") . '</a>';
        }

        if ($user->rights->synopsisprojet->creer && $projet->statut == 0) {
            print '<a class="butAction" href="fiche.php?id=' . $projet->id . '&amp;action=valid">' . $langs->trans("Planifier") . '</a>';
        }
        if ($user->rights->synopsisprojet->creer && $projet->statut == 5) {
            print '<a class="butAction" href="fiche.php?id=' . $projet->id . '&amp;action=launch">' . $langs->trans("Lancer") . '</a>';
        }
        if ($user->rights->synopsisprojet->creer && $projet->statut == 10) {
            print '<a class="butAction" href="fiche.php?id=' . $projet->id . '&amp;action=cloture">' . $langs->trans("Cloturer") . '</a>';
        }

        if ($user->rights->synopsisprojet->supprimer) {
            print '<a class="butActionDelete" href="fiche.php?id=' . $projet->id . '&amp;action=delete">' . $langs->trans("Delete") . '</a>';
        }
    }

    print "</div>";
    print "<br>\n";

    if ($action != 'presend')
    {
        print '<table width="100%"><tr><td width="50%" valign="top">';
        print '<a name="builddoc"></a>'; // ancre


        /*
         * Documents generes
         */
        $filename=dol_sanitizeFileName($object->ref);
        $filedir=$conf->projet->dir_output . "/" . dol_sanitizeFileName($object->ref);
        $urlsource=$_SERVER["PHP_SELF"]."?id=".$object->id;
        $genallowed=($user->rights->projet->lire && $userAccess > 0);
        $delallowed=($user->rights->projet->creer && $userWrite > 0);

        $var=true;

        $somethingshown=$formfile->show_documents('project',$filename,$filedir,$urlsource,$genallowed,$delallowed,$object->modelpdf);

        print '</td><td valign="top" width="50%">';

        if (!empty($object->id))
        {
	        // List of actions on element
	        include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
	        $formactions=new FormActions($db);
	        $somethingshown=$formactions->showactions($object,'project',$socid);
        }

        print '</td></tr></table>';
    }

    // Hook to add more things on page
    $parameters=array();
    $reshook=$hookmanager->executeHooks('mainCardTabAddMore',$parameters,$object,$action); // Note that $action and $object may have been modified by hook

}

$db->close();

llxFooter('$Date: 2008/09/10 09:46:02 $ - $Revision: 1.57.2.1 $');

function sec2time($sec) {
    $returnstring = " ";
    $days = intval($sec / 86400);
    $hours = intval(($sec / 3600) - ($days * 24));
    $minutes = intval(($sec - (($days * 86400) + ($hours * 3600))) / 60);
    $seconds = $sec - ( ($days * 86400) + ($hours * 3600) + ($minutes * 60));

    $returnstring .= ($days) ? (($days == 1) ? "1 j" : $days . "j") : "";
    $returnstring .= ($days && $hours && !$minutes && !$seconds) ? "" : "";
    $returnstring .= ($hours) ? ( ($hours == 1) ? " 1h" : " " . $hours . "h") : "";
    $returnstring .= (($days || $hours) && ($minutes && !$seconds)) ? "  " : " ";
    $returnstring .= ($minutes) ? ( ($minutes == 1) ? " 1 min" : " " . $minutes . "min") : "";
    //$returnstring .= (($days || $hours || $minutes) && $seconds)?" et ":" ";
    //$returnstring .= ($seconds)?( ($seconds == 1)?"1 second":"$seconds seconds"):"";
    return ($returnstring);
}

function sec2hour($sec) {
    $days = false;
    $returnstring = " ";
    $hours = intval(($sec / 3600));
    $minutes = intval(($sec - ( ($hours * 3600))) / 60);
    $seconds = $sec - ( ($hours * 3600) + ($minutes * 60));

    $returnstring .= ($days) ? (($days == 1) ? "1 j" : $days . "j") : "";
    $returnstring .= ($days && $hours && !$minutes && !$seconds) ? "" : "";
    $returnstring .= ($hours) ? ( ($hours == 1) ? " 1h" : " " . $hours . "h") : "";
    $returnstring .= (($days || $hours) && ($minutes && !$seconds)) ? "  " : " ";
    $returnstring .= ($minutes) ? ( ($minutes == 1) ? " 1 min" : " " . $minutes . "min") : "";
    //$returnstring .= (($days || $hours || $minutes) && $seconds)?" et ":" ";
    //$returnstring .= ($seconds)?( ($seconds == 1)?"1 second":"$seconds seconds"):"";
    return ($returnstring);
}

?>

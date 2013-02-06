<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005      Regis Houssin         <regis@dolibarr.fr>
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
  * GLE by Synopsis et DRSI
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
/*
 */

/**
        \file       htdocs/synopsis_demandeinterv/document.php
        \ingroup    demandeInterv
        \brief      Page de gestion des documents attachees a une di
        \version    $Id: document.php,v 1.43 2008/07/10 17:11:05 eldy Exp $
*/

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT."/Synopsis_DemandeInterv/demandeInterv.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/demandeInterv.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");

$langs->load('compta');
$langs->load('other');
$langs->load("synopsisGene@Synopsis_Tools");
$langs->load('interventions');

$action=empty($_GET['action']) ? (empty($_POST['action']) ? '' : $_POST['action']) : $_GET['action'];

$id = isset($_GET["id"])?$_GET["id"]:'';

// Security check
if ($user->societe_id)
{
    unset($_GET["action"]);
    $action='';
    $socid = $user->societe_id;
}
$result = restrictedArea($user, 'synopsisdemandeinterv', $id, 'demandeInterv');

// Get parameters
$page=$_GET["page"];
$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];

if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="name";
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;




if ($_REQUEST['SynAction'] == 'dlZip')
{
    require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
    $zipFilename =  tempnam("/tmp", "zipping-dolibarr-prop-");
    unlink($zipFilename);
    $zipFilename .= ".zip";
    $societe = new Societe($db);
    $demandeInterv = new demandeInterv($db);

    if ($demandeInterv->fetch($id))
    {
        $upload_dir = $conf->synopsisdemandeinterv->dir_output.'/'.sanitize_string($demandeInterv->ref);
        if ($societe->fetch($demandeInterv->socid))
        {
            $finalFileName = "doc_demandeInterv_".sanitize_string($demandeInterv->ref)."_".sanitize_string($societe->nom) ."-". date("Ymd-Hi", time()).".zip";
            $filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
            $zip = new ZipArchive();
            if ( $zip->open($zipFilename,ZIPARCHIVE::CREATE) === TRUE)
            {
                $zip->setArchiveComment('Generate by GLE - Synopsis et DRSI');
                foreach($filearray as $key=>$val)
                {
                    //Add files
                     $zip->addFile($val['fullname'], "".sanitize_string($societe->nom).'/'.$val['name']);
                }
            $zip->close();
            }
        }
        if (is_file($zipFilename) &&filesize($zipFilename))
        {
              header("content-type: application/zip");
              header("Content-Disposition: attachment; filename=".$finalFileName);
              print file_get_contents($zipFilename);

              unlink ($zipFilename);
        }
    }
}


/*
 * Actions
 */

// Envoi fichier
if ($_POST["sendit"] && $conf->upload)
{
    $demandeInterv = new demandeInterv($db);

    if ($demandeInterv->fetch($id))
    {
        $upload_dir = $conf->synopsisdemandeinterv->dir_output . "/" . sanitize_string($demandeInterv->ref);
        if (! is_dir($upload_dir)) dol_mkdir($upload_dir);
        if (is_dir($upload_dir))
        {
            $tmpName = $_FILES['userfile']['name'];
            //decode decimal HTML entities added by web browser
            $tmpName = dol_unescapefile($tmpName );

            if (dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $tmpName,0) > 0)
            {
                $mesg = '<div class="ok">'.$langs->trans("FileTransferComplete").'</div>';
                //add file to ecm
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface=new Interfaces($db);
                $interface->texte=$tmpName;
                $result=$interface->run_triggers('ECM_UL_DEMANDEINTERV',$demandeInterv,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
            } else {
                // Echec transfert (fichier depassant la limite ?)
                $mesg = '<div class="error ui-state-error">'.$langs->trans("ErrorFileNotUploaded").'</div>';
            }
        }
    }
}

// Delete
if ($action=='delete')
{
    $demandeInterv = new demandeInterv($db);

    $id=$_GET["id"];
    if ($demandeInterv->fetch($id))
    {
        $tmpName = $_FILES['userfile']['name'];
        //decode decimal HTML entities added by web browser
        $tmpName = dol_unescapefile($tmpName );

        $upload_dir = $conf->synopsisdemandeinterv->dir_output . "/" . sanitize_string($demandeInterv->ref);
        $file = $upload_dir . '/' . urldecode($_GET['urlfile']);
        dol_delete_file($file);
        $mesg = '<div class="ok">'.$langs->trans("FileWasRemoved").'</div>';
        // Appel des triggers
        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
        $interface=new Interfaces($db);
        $interface->texte=$tmpName;
        $result=$interface->run_triggers('ECM_UL_DEL_DEMANDEINTERV',$demandeInterv,$user,$langs,$conf);
        if ($result < 0) { $error++; $this->errors=$interface->errors; }

    }
}


/*
 * Affichage
 */

llxHeader();

if ($id > 0)
{
    $demandeInterv = new demandeInterv($db);
    if ($demandeInterv->fetch($id))
    {
        $upload_dir = $conf->synopsisdemandeinterv->dir_output.'/'.sanitize_string($demandeInterv->ref);

        $societe = new Societe($db);
        $societe->fetch($demandeInterv->socid);

        $head = demandeInterv_prepare_head($demandeInterv);
        dol_fiche_head($head, 'documents', $langs->trans('DI'));


        // Construit liste des fichiers
        $filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
        $totalsize=0;
        foreach($filearray as $key => $file)
        {
            $totalsize+=$file['size'];
        }


        print '<table class="border"width="100%">';

        // Ref
        print '<tr><td width="30%" class="ui-widget-header ui-state-default">'.$langs->trans('Ref').'</td>
                   <td colspan="3" class="ui-widget-content">'.$demandeInterv->ref.'</td></tr>';

        // Societe
        print '<tr><td class="ui-widget-header ui-state-default">'.$langs->trans('Company').'</td>
                   <td  class="ui-widget-content" colspan="5">'.$societe->getNomUrl(1).'</td></tr>';

        print '<tr><td class="ui-widget-header ui-state-default">'.$langs->trans("NbOfAttachedFiles").'</td>
                   <td colspan="3" class="ui-widget-content">'.sizeof($filearray).'</td></tr>';
        print '<tr><td class="ui-widget-header ui-state-default">'.$langs->trans("TotalSizeOfAttachedFiles").'</td>
                   <td  class="ui-widget-content" colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';

        print '</table>';

        print '</div>';

        if ($mesg) { print "$mesg<br>"; }

        // Affiche formulaire upload
        $formfile=new FormFile($db);
        $formfile->form_attach_new_file(DOL_URL_ROOT.'/Synopsis_DemandeInterv/document.php?id='.$demandeInterv->id);


        // List of document
        $param='&id='.$demandeInterv->id;
        $formfile->list_of_documents($filearray,$demandeInterv,'demandeInterv',$param);

        //Download all docs via zip
        print "<br>";
        print '<form action="?id='.$id.'" method="POST">';
        print '<input type="hidden" name="demandeInterv_id" value="'.$id.'"/>';
        print '<input type="hidden" name="SynAction" value="dlZip"/>';
        print '<input class="button" type="submit" value="'.$langs->trans('DownloadZip').'"/>';
        print '</form>';

    }
    else
    {
        dol_print_error($db);
    }
}
else
{
    print $langs->trans("UnkownError");
}

$db->close();

llxFooter('$Date: 2008/07/10 17:11:05 $ - $Revision: 1.43 $');
?>

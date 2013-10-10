<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005      Regis Houssin         <regis.houssin@capnetworks.com>
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
/*
 */

/**
        \file       htdocs/deplacement/document.php
        \ingroup    deplacements
        \brief      Page de gestion des documents attachees a une proposition commerciale
        \version    $Id: document.php,v 1.43 2008/07/10 17:11:05 eldy Exp $
*/

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT."/Babel_TechPeople/deplacements/deplacement.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/deplacement.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions.lib.php");

$langs->load('compta');
$langs->load('other');
$langs->load("synopsisGene@Synopsis_Tools");

$action=empty($_GET['action']) ? (empty($_POST['action']) ? '' : $_POST['action']) : $_GET['action'];

$id = isset($_GET["id"])?$_GET["id"]:'';

// Security check
if ($user->societe_id)
{
    unset($_GET["action"]);
    $action='';
    $socid = $user->societe_id;
}
$result = restrictedArea($user, 'deplacement', $id, 'deplacement');

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
    $ndf = new Ndf($db);

    if ($ndf->fetch($id))
    {
        $upload_dir = $conf->deplacement->dir_output.'/'.$user->id."/".sanitize_string($ndf->ref);
        if ($societe->fetch($ndf->socid))
        {
            $finalFileName = "doc_deplacement_".sanitize_string($ndf->ref)."_".sanitize_string($societe->nom) ."-". date("Ymd-Hi", time()).".zip";
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
    $ndf = new Ndf($db);

    if ($ndf->fetch($id))
    {
        $upload_dir = $conf->deplacement->dir_output . "/" .$user->id."/". sanitize_string($ndf->ref);

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
                $result=$interface->run_triggers('ECM_UL_NDF',$ndf,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
                // Fin appel triggers
//                require_once(DOL_DOCUMENT_ROOT . "/ecm/class/ecmdirectory.class.php" );
//                $ecm = new EcmDirectory($db);
//                $ecm->create_assoc("deplacement",$ndf, $_FILES['userfile']['name'],$user,$conf);
                //print_r($_FILES);
            }
            else
            {
                // Echec transfert (fichier depassant la limite ?)
                $mesg = '<div class="error ui-state-error">'.$langs->trans("ErrorFileNotUploaded").'</div>';
                // print_r($_FILES);
            }
        }
    }
}

// Delete
if ($action=='delete')
{
    $ndf = new Ndf($db);

    $id=$_GET["id"];
    if ($ndf->fetch($id))
    {
          $tmpName = $_FILES['userfile']['name'];
          //decode decimal HTML entities added by web browser
          $tmpName = dol_unescapefile($tmpName );

        $upload_dir = $conf->deplacement->dir_output . "/" .$user->id."/". sanitize_string($ndf->ref);
        $file = $upload_dir . '/' . urldecode($_GET['urlfile']);
        dol_delete_file($file);
        $mesg = '<div class="ok">'.$langs->trans("FileWasRemoved").'</div>';
        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
        $interface=new Interfaces($db);
        $interface->texte=$tmpName;
        $result=$interface->run_triggers('ECM_UL_DEL_NDF',$demandeInterv,$user,$langs,$conf);
        if ($result < 0) { $error++; $this->errors=$interface->errors; }

    }
}


/*
 * Affichage
 */

llxHeader();
if ($id > 0)
{
    $ndf = new Ndf($db);
    if ($ndf->fetch($id))
    {
        $upload_dir = $conf->deplacement->dir_output.'/'.$user->id."/".sanitize_string($ndf->ref);
    //cement);

        $userRef = new User($db);
        $userRef->fetch($ndf->fk_user_author);

//        $head = deplacement_prepare_head($ndf);
//        dol_fiche_head($head, 'document', $langs->trans('Proposal'));

          print_fiche_titre($langs->trans("Nouveau document"));
//          $demande = new Ndf($db);
//          $demande->fetch($_REQUEST['id']);
          $head = ndf_prepare_head();
          dol_fiche_head($head, "Documents", $langs->trans("Ndf"));


        // Construit liste des fichiers
        $filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
        $totalsize=0;
        foreach($filearray as $key => $file)
        {
            $totalsize+=$file['size'];
        }


        print '<table class="border"width="100%">';

        // Ref
        print '<tr><td class="ui-widget-header ui-state-default" width="30%">'.$langs->trans('Ref').'</td><td colspan="3" class="ui-widget-content">'.$ndf->ref.'</td></tr>';

        // Societe
        print '<tr><td class="ui-widget-header ui-state-default">'.$langs->trans('User').'</td><td colspan="5" class="ui-widget-content">'.$userRef->getNomUrl(1).'</td></tr>';

        print '<tr><td class="ui-widget-header ui-state-default">'.$langs->trans("NbOfAttachedFiles").'</td><td class="ui-widget-content" colspan="3">'.sizeof($filearray).'</td></tr>';
        print '<tr><td class="ui-widget-header ui-state-default">'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td class="ui-widget-content" colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';

        print '</table>';

        print '</div>';

        if ($mesg) { print "$mesg<br>"; }

        // Affiche formulaire upload
        $formfile=new FormFile($db);
        $formfile->form_attach_new_file(DOL_URL_ROOT.'/Babel_TechPeople/deplacements/document.php?id='.$ndf->id);


        // List of document
        $param='&id='.$ndf->id;
        $formfile->list_of_documents($filearray,$ndf,'deplacement',$param);

        //Download all docs via zip
        print "<br>";
        print '<form action="?id='.$id.'" method="POST">';
        print '<input type="hidden" name="id" value="'.$id.'"/>';
        print '<input type="hidden" name="SynAction" value="dlZip"/>';
        print '<input class="button" type="submit" value="'.$langs->trans('DownloadZip').'"/>';
        print '</form>';

    } else {
        dol_print_error($db);
    }
} else {
    print $langs->trans("UnkownError");
}

$db->close();

llxFooter('$Date: 2008/07/10 17:11:05 $ - $Revision: 1.43 $');
?>

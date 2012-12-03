<?php
/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
  * GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Create on : 4-1-2009
  *
  * Infos on http://www.finapro.fr
  *
  */
/*
 */

/**
 *  \file       htdocs/Chrono/document.php
 *  \brief      Tab for documents linked to third party
 *  \ingroup    Chrono
 *  \version    $Id: document.php,v 1.2 2008/07/10 17:11:04 eldy Exp $
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/synopsis_chrono.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/Synopsis_Chrono/Chrono.class.php");

$langs->load("companies");
$langs->load('other');
$langs->load("synopsisGene@Synopsis_Tools");

$mesg = "";

// Security check
$id = isset($_GET["socid"])?$_GET["socid"]:(! empty($_GET["id"])?$_GET["id"]:'');
if ($user->societe_id > 0)
{
    unset($_GET["action"]);
    $action='';
    $socid = $user->societe_id;
}



  if (!$user->rights->synopsischrono->read == 1)
  {
      accessforbidden("Ce module ne vous est pas accessible",0);
      exit;
  }
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

$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="name";

$upload_dir = $conf->synopsischrono->dir_output . "/" . $id ;


if ($_REQUEST['SynAction'] == 'dlZip')
{
    $zipFilename =  tempnam("/tmp", "zipping-GLE-chrono-");
    unlink($zipFilename);
    $zipFilename .= ".zip";
    $chrono = new Chrono($db);
    if ($chrono->fetch($id))
    {
        $finalFileName = "documents_".sanitize_string($Chrono->ref) ."-". date("Ymd-Hi", mktime()).".zip";
        $filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
        $zip = new ZipArchive();
        if ( $zip->open($zipFilename,ZIPARCHIVE::CREATE) === TRUE)
        {
            $zip->setArchiveComment('Generate by GLE - Synopsis et DRSI');
            foreach($filearray as $key=>$val)
            {
                //Add files
                 $zip->addFile($val['fullname'], "".sanitize_string($chrono->ref).'/'.sanitize_string($val['name']));
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



/*
 * Actions
 */
// Envoie fichier
if ( $_POST["sendit"])
{
  if (! is_dir($upload_dir)) dol_mkdir($upload_dir);

  if (is_dir($upload_dir))
  {
      $tmpName = $_FILES['userfile']['name'];
      //decode decimal HTML entities added by web browser
      $tmpName = dol_unescapefile($tmpName );

    $result = dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $tmpName,0,0,$_FILES['userfile']['error']);
    if ($result > 0)
    {
        $Chrono = new Chrono($db);
        $Chrono->fetch($id);
        // Appel des triggers
        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
        $interface=new Interfaces($db);
        $interface->texte=$tmpName;
//TODO
        $result=$interface->run_triggers('ECM_UL_CHRONO',$chrono,$user,$langs,$conf);
        if ($result < 0) { $error++; /*$this->errors=$interface->errors;*/ }
        // Fin appel triggers
        $mesg = '<div class="ok">'.$langs->trans("FileTransferComplete").'</div>';
        //print_r($_FILES);
    } else if ($result < 0) {
        // Echec transfert (fichier depassant la limite ?)
        $mesg = '<div class="error ui-state-error">'.$langs->trans("ErrorFileNotUploaded").'</div>';
        // print_r($_FILES);
    } else {
        // Fichier infecte par un virus
        $mesg = '<div class="error ui-state-error">'.$langs->trans("ErrorFileIsInfectedWith",$result).'</div>';
    }
  }
}

// Suppression fichier
if ($_POST['action'] == 'confirm_deletefile' && $_POST['confirm'] == 'yes')
{
  $file = $upload_dir . "/" . urldecode($_GET["urlfile"]);
  dol_delete_file($file);
  $tmpName = $_FILES['userfile']['name'];
  //decode decimal HTML entities added by web browser
  $tmpName = dol_unescapefile($tmpName );
          // Appel des triggers
        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
        $interface=new Interfaces($db);
        $interface->texte=$tmpName;
        $result=$interface->run_triggers('ECM_UL_DEL_CHRONO',$chrono,$user,$langs,$conf);
        if ($result < 0) { $error++; /*$this->errors=$interface->errors;*/ }
        // Fin appel triggers

  $mesg = '<div class="ok">'.$langs->trans("FileWasRemoved").'</div>';
}


/*
* View
*/

llxHeader("","Document Chrono");

if ($id > 0)
{
    $chrono = new Chrono($db);
    if ($chrono->fetch($id))
    {
        /*
        * Affichage onglets
        */
        $head = chrono_prepare_head($chrono);

        $html=new Form($db);

        dol_fiche_head($head, 'document', $langs->trans("ThirdParty"));

        // Construit liste des fichiers
        $filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
        $totalsize=0;
        foreach($filearray as $key => $file)
        {
            $totalsize+=$file['size'];
        }
        if ($totalsize >  1024 * 1024)
        {
            $totalsize = round (10 * $totalsize / (1024 * 1024))/10;
            $totalsize .= " Mo";
        } else if ($totalsize >  1024)
        {
            $totalsize = round (10 * $totalsize / 1024)/10;
            $totalsize .= " ko";
        }else {
            $totalsize .= ' o';
        }
        print '<table class="border" cellpadding=15 width="100%">';
        // Ref
        print '<tr><th width="30%" class="ui-state-default ui-widget-header">'.$langs->trans("Ref").'</th><td class="ui-widget-content" colspan="3">'.$chrono->getNomUrl(1).'</td></tr>';
        // Nbre fichiers
        print '<tr><th class="ui-state-default ui-widget-header">'.$langs->trans("NbOfAttachedFiles").'</th><td colspan="3" class="ui-widget-content">'.sizeof($filearray).'</td></tr>';
        //Total taille
        print '<tr><th class="ui-state-default ui-widget-header">'.$langs->trans("TotalSizeOfAttachedFiles").'</th><td colspan="3" class="ui-widget-content">'.$totalsize.'</td></tr>';
        print '</table>';

        print '</div>';

        if ($mesg) { print "$mesg<br>"; }

        /*
        * Confirmation de la suppression d'une ligne produit
        */
        if ($_GET['action'] == 'delete')
        {
            $html->form_confirm($_SERVER["PHP_SELF"].'?id='.$_GET["id"].'&amp;urlfile='.urldecode($_GET["urlfile"]), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile');
            print '<br>';
        }


        // Affiche formulaire upload
        $formfile=new FormFile($db);
        $formfile->form_attach_new_file(DOL_URL_ROOT.'/Synopsis_Chrono/document.php?id='.$id);


        // List of document
        $param='&id='.$chrono->id;
        $formfile->list_of_documents($filearray,$chrono,'synopsischrono',$param,1,$chrono->id."/");

//        print "<br/>";
//        print "<div class='titre'>Fichiers et documents associ&eacute;s</div>";
//        print "<br/>";
//        $requete = ""

        //Download all docs via zip
        print "<br>";
        print '<form action="?id='.$id.'" method="POST">';
        print '<input type="hidden" name="chrono_id" value="'.$id.'"/>';
        print '<input type="hidden" name="SynAction" value="dlZip"/>';
        print '<input class="button" type="submit" value="'.$langs->trans('DownloadZip').'"/>';
        print '</form>';

        print "<br><br>";

        print "</table>";
    } else {
        dol_print_error($db);
    }
} else {
    dol_print_error();
}

$db->close();

llxFooter('$Date: 2008/07/10 17:11:04 $ - $Revision: 1.2 $');

?>
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
 * BIMP-ERP by Synopsis et DRSI
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
  \file       htdocs/synopsisdemandeinterv/document.php
  \ingroup    synopsisdemandeinterv
  \brief      Page de gestion des documents attachees a une di
  \version    $Id: document.php,v 1.43 2008/07/10 17:11:05 eldy Exp $
 */
require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT . "/synopsisdemandeinterv/class/synopsisdemandeinterv.class.php");
require_once(DOL_DOCUMENT_ROOT . "/synopsisdemandeinterv/core/lib/synopsisdemandeinterv.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.formfile.class.php");

$langs->load('compta');
$langs->load('other');
$langs->load("synopsisGene@synopsistools");
$langs->load('interventions');

$action = empty($_GET['action']) ? (empty($_POST['action']) ? '' : $_POST['action']) : $_GET['action'];

$id = isset($_GET["id"]) ? $_GET["id"] : '';

// Security check
if ($user->societe_id) {
    unset($_GET["action"]);
    $action = '';
    $socid = $user->societe_id;
}
$result = restrictedArea($user, 'synopsisdemandeinterv', $id, 'synopsisdemandeinterv');

// Get parameters
$page = $_GET["page"];
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];

if (!$sortorder)
    $sortorder = "ASC";
if (!$sortfield)
    $sortfield = "name";
if ($page == -1) {
    $page = 0;
}
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;




if ($_REQUEST['SynAction'] == 'dlZip') {
    require_once(DOL_DOCUMENT_ROOT . "/societe/class/societe.class.php");
    $zipFilename = tempnam("/tmp", "zipping-dolibarr-prop-");
    unlink($zipFilename);
    $zipFilename .= ".zip";
    $societe = new Societe($db);
    $synopsisdemandeinterv = new Synopsisdemandeinterv($db);

    if ($synopsisdemandeinterv->fetch($id)) {
        $upload_dir = $conf->synopsisdemandeinterv->dir_output . '/' . sanitize_string($synopsisdemandeinterv->ref);
        if ($societe->fetch($synopsisdemandeinterv->socid)) {
            $finalFileName = "doc_synopsisdemandeinterv_" . sanitize_string($synopsisdemandeinterv->ref) . "_" . sanitize_string($societe->nom) . "-" . date("Ymd-Hi", time()) . ".zip";
            $filearray = dol_dir_list($upload_dir, "files", 0, '', '\.meta$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_ASC : SORT_DESC), 1);
            $zip = new ZipArchive();
            if ($zip->open($zipFilename, ZIPARCHIVE::CREATE) === TRUE) {
                $zip->setArchiveComment('Generate by BIMP-ERP - Synopsis et DRSI');
                foreach ($filearray as $key => $val) {
                    //Add files
                    $zip->addFile($val['fullname'], "" . sanitize_string($societe->nom) . '/' . $val['name']);
                }
                $zip->close();
            }
        }
        if (is_file($zipFilename) && filesize($zipFilename)) {
            header("content-type: application/zip");
            header("Content-Disposition: attachment; filename=" . $finalFileName);
            print file_get_contents($zipFilename);

            unlink($zipFilename);
        }
    }
}


/*
 * Actions
 */

// Envoi fichier
if ($_POST["sendit"] && !empty($conf->global->MAIN_UPLOAD_DOC)) {
	require_once(DOL_DOCUMENT_ROOT."/core/lib/images.lib.php");
    $synopsisdemandeinterv = new Synopsisdemandeinterv($db);

    if ($synopsisdemandeinterv->fetch($id)) {
        $upload_dir = $conf->synopsisdemandeinterv->dir_output . "/" . sanitize_string($synopsisdemandeinterv->ref);
        if (!is_dir($upload_dir))
            dol_mkdir($upload_dir);
        if (is_dir($upload_dir)) {
            $tmpName = $_FILES['userfile']['name'];
            //decode decimal HTML entities added by web browser
            $tmpName = dol_unescapefile($tmpName);

            if (dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $tmpName, 0) > 0) {
                $mesg = '<div class="ok">' . $langs->trans("FileTransferComplete") . '</div>';
                //add file to ecm
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($db);
                $interface->texte = $tmpName;
                $result = $interface->run_triggers('ECM_UL_synopsisdemandeinterv', $synopsisdemandeinterv, $user, $langs, $conf);

                if (image_format_supported($upload_dir . "/" . $_FILES['userfile']['name']) == 1) {
                    // Create small thumbs for company (Ratio is near 16/9)
                    // Used on logon for example
                    $imgThumbSmall = vignette($upload_dir . "/" . $_FILES['userfile']['name'], $maxwidthsmall, $maxheightsmall, '_small', $quality, "thumbs");

                    // Create mini thumbs for company (Ratio is near 16/9)
                    // Used on menu or for setup page for example
                    $imgThumbMini = vignette($upload_dir . "/" . $_FILES['userfile']['name'], $maxwidthmini, $maxheightmini, '_mini', $quality, "thumbs");
                }
                $mesg = '<div class="ok">' . $langs->trans("FileTransferComplete") . '</div>';

                if ($result < 0) {
                    $error++;
                    $this->errors = $interface->errors;
                }
            } else {
                // Echec transfert (fichier depassant la limite ?)
                $mesg = '<div class="error ui-state-error">' . $langs->trans("ErrorFileNotUploaded") . '</div>';
            }
        }
    }
}

// Delete
if ($action == 'delete') {
    $synopsisdemandeinterv = new Synopsisdemandeinterv($db);

    $id = $_GET["id"];
    if ($synopsisdemandeinterv->fetch($id)) {
        $tmpName = $_FILES['userfile']['name'];
        //decode decimal HTML entities added by web browser
        $tmpName = dol_unescapefile($tmpName);

        $upload_dir = $conf->synopsisdemandeinterv->dir_output . "/" . sanitize_string($synopsisdemandeinterv->ref);
        $file = $upload_dir . '/' . urldecode($_GET['urlfile']);
        dol_delete_file($file);
        $mesg = '<div class="ok">' . $langs->trans("FileWasRemoved") . '</div>';
        // Appel des triggers
        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
        $interface = new Interfaces($db);
        $interface->texte = $tmpName;
        $result = $interface->run_triggers('ECM_UL_DEL_synopsisdemandeinterv', $synopsisdemandeinterv, $user, $langs, $conf);
        if ($result < 0) {
            $error++;
            $this->errors = $interface->errors;
        }
    }
}


/*
 * Affichage
 */

llxHeader();

if ($id > 0) {
    $synopsisdemandeinterv = new Synopsisdemandeinterv($db);
    if ($synopsisdemandeinterv->fetch($id)) {
        $upload_dir = $conf->synopsisdemandeinterv->dir_output . '/' . sanitize_string($synopsisdemandeinterv->ref);

        $societe = new Societe($db);
        $societe->fetch($synopsisdemandeinterv->socid);

        $head = synopsisdemandeinterv_prepare_head($synopsisdemandeinterv);
        dol_fiche_head($head, 'documents', $langs->trans('DI'));


        // Construit liste des fichiers
        $filearray = dol_dir_list($upload_dir, "files", 0, '', '\.meta$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_ASC : SORT_DESC), 1);
        $totalsize = 0;
        foreach ($filearray as $key => $file) {
            $totalsize+=$file['size'];
        }


        print '<table class="border"width="100%">';

        // Ref
        print '<tr><td width="30%" class="ui-widget-header ui-state-default">' . $langs->trans('Ref') . '</td>
                   <td colspan="3" class="ui-widget-content">' . $synopsisdemandeinterv->ref . '</td></tr>';

        // Societe
        print '<tr><td class="ui-widget-header ui-state-default">' . $langs->trans('Company') . '</td>
                   <td  class="ui-widget-content" colspan="5">' . $societe->getNomUrl(1) . '</td></tr>';

        print '<tr><td class="ui-widget-header ui-state-default">' . $langs->trans("NbOfAttachedFiles") . '</td>
                   <td colspan="3" class="ui-widget-content">' . sizeof($filearray) . '</td></tr>';
        print '<tr><td class="ui-widget-header ui-state-default">' . $langs->trans("TotalSizeOfAttachedFiles") . '</td>
                   <td  class="ui-widget-content" colspan="3">' . $totalsize . ' ' . $langs->trans("bytes") . '</td></tr>';

        print '</table>';

        print '</div>';

        if ($mesg) {
            print "$mesg<br>";
        }

        // Affiche formulaire upload
        $formfile = new FormFile($db);
        $formfile->form_attach_new_file(DOL_URL_ROOT . '/synopsisdemandeinterv/document.php?id=' . $synopsisdemandeinterv->id);


        // List of document
//        $param='&id='.$synopsisdemandeinterv->id;
        $formfile->list_of_documents($filearray, $synopsisdemandeinterv, 'synopsisdemandeinterv', $param);

        //Download all docs via zip
        print "<br>";
        print '<form action="?id=' . $id . '" method="POST">';
        print '<input type="hidden" name="synopsisdemandeinterv_id" value="' . $id . '"/>';
        print '<input type="hidden" name="SynAction" value="dlZip"/>';
        print '<input class="button" type="submit" value="' . $langs->trans('DownloadZip') . '"/>';
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

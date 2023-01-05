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
 
require('../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/ndfp/class/ndfp.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");

 

$langs->load('ndfp');
$langs->load('main');
$langs->load('other');

$id = GETPOST('id');
$ref = GETPOST('ref');
$confirm	= GETPOST('confirm');
$action = GETPOST('action');

if (!$user->rights->ndfp->myactions->read && !$user->rights->ndfp->allactions->read)
{
    accessforbidden();
}  

$ndfp = new Ndfp($db);


if ($id > 0 || !empty($ref))
{
    $result = $ndfp->fetch($id, $ref);
    
    if ($result < 0)
    {   
	    header("Location: ".DOL_URL_ROOT.'/ndfp/index.php');
    }
                
}
else
{   
    header("Location: ".DOL_URL_ROOT.'/ndfp/index.php');    
}

$error = false;
$message = false;
$formconfirm = false;

$html = new Form($db);
$formfile = new FormFile($db);

if ($_POST["sendit"])
{
    
	$upload_dir = $conf->ndfp->dir_output .'/'. dol_sanitizeFileName($ndfp->ref);

	if (dol_mkdir($upload_dir) >= 0)
	{
		$resupload = dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $_FILES['userfile']['name'],0,0,$_FILES['userfile']['error']);
		
        if (is_numeric($resupload) && $resupload > 0)
		{
			$message = $langs->trans("FileTransferComplete");
            $error = false;
		}
		else
		{
			$langs->load("errors");
            
			if ($resupload < 0)	// Unknown error
			{
				$message = $langs->trans("ErrorFileNotUploaded");
			}
			else if (preg_match('/ErrorFileIsInfectedWithAVirus/',$resupload))	// Files infected by a virus
			{
				$message = $langs->trans("ErrorFileIsInfectedWithAVirus");
			}
			else	// Known error
			{
				$message = $langs->trans($resupload);
			}
		}
	}
	
}

// Delete
if ($action == 'confirm_deletefile' && $confirm == 'yes')
{
	$upload_dir = $conf->ndfp->dir_output .'/'. dol_sanitizeFileName($ndfp->ref);
    
	$file = $upload_dir . '/' . $_GET['urlfile'];	
	dol_delete_file( $file, 0, 0, 0, 'FILE_DELETE', $object);
    
	$message = $langs->trans("FileHasBeenRemoved");
}

// Get all files
$sortfield  = GETPOST("sortfield", 'alpha');
$sortorder  = GETPOST("sortorder", 'alpha');
$page       = GETPOST("page", 'int');

if ($page == -1) 
{ 
    $page = 0; 
}

$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (!$sortorder) $sortorder = "ASC";
if (!$sortfield) $sortfield = "name";


$upload_dir = $conf->ndfp->dir_output .'/'. dol_sanitizeFileName($ndfp->ref);

$filearray = dol_dir_list($upload_dir, "files", 0, '', '\.meta$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
$totalsize = 0;
foreach($filearray as $key => $file)
{
	$totalsize += $file['size'];
}


if ($action == 'delete')
{
	$formconfirm = $html->formconfirm($_SERVER["PHP_SELF"].'?id='.$ndfp->id.'&urlfile='.urldecode($_GET["urlfile"]), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', 0, 1);
}
                
/**
/* Default view
**/
// Prepare head
$h = 0;
$head = array();

$current_head = 'documents';

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
$userstatic->fetch($ndfp->fk_user);

$can_upload = 0;

if ($user->rights->ndfp->allactions->create)
{
    $can_upload = 1;
}

if ($ndfp->fk_user == $user->id)
{
    $can_upload = 1;
}
 

require_once('tpl/ndfp.document.tpl.php');

$db->close();


?>

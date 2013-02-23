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
require_once(DOL_DOCUMENT_ROOT."/ndfp/class/ndfp.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");

 
$langs->load('companies');
$langs->load('ndfp');
$langs->load('main');

$id = GETPOST('id');
$ref = GETPOST('ref');

$action = GETPOST('action');

if (!$user->rights->ndfp->myactions->read && !$user->rights->ndfp->allactions->read)
{
    accessforbidden();
}  

//Init error
$error = false;
$message = false;


$ndfp = new Ndfp($db);


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
    header("Location: ".DOL_URL_ROOT.'/ndfp/ndfp.php');    
}



if ($action == 'setcomments')
{
    $result = $ndfp->call($action, array($user));  
    
    if ($result > 0)
    {
        
        $message = $ndfp->error; //     
    }
    else
    {           
        $message = $ndfp->error;
        $error = true;        
    }    
}
 
    
/**
/* Default view
**/
$html = new Form($db);

// Prepare head
$h = 0;
$head = array();

$current_head = 'notes';

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

$societestatic = new Societe($db);
$userstatic = new User($db);


$societestatic->fetch($ndfp->fk_soc);    
$userstatic->fetch($ndfp->fk_user);

$button = '';
if ($user->rights->ndfp->myactions->create){
    if ($action == 'edit'){
        $button = '<input type="submit" class="button" name="bouton" value="'.$langs->trans('Validate').'" />';       
    }else{
        $button = '<a class="butAction" href="note.php?id='.$ndfp->id.'&amp;action=edit">'.$langs->trans('Modify').'</a>'; 
    }
}else{
    accessforbidden();
}

$userCommentEditor = new DolEditor('comment_user', $ndfp->comment_user, '',200,'dolibarr_notes','',false,true, $conf->fckeditor->enabled,ROWS_6,50);
$adminCommentEditor = new DolEditor('comment_admin', $ndfp->comment_admin, '',200,'dolibarr_notes','',false,true, $conf->fckeditor->enabled,ROWS_6,50);
 
 
require_once('tpl/ndfp.note.tpl.php');

$db->close();


?>

<?php

    require_once '../main.inc.php';

    if(!isset($_REQUEST['nom']) || !isset($_REQUEST['folder'])) {
        accessforbidden();
    }

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename='.$_REQUEST['nom'].';');
    header('Cache-Control: Public, must-revalidate');
    header('Pragma: public');
    header('Content-Length: '.filesize(DIR_SYNCH_COMPTA . 'exportCegid/' . $_REQUEST['folder'] . '/' . $_REQUEST['nom']));
    readfile(DIR_SYNCH_COMPTA . 'exportCegid/' . $_REQUEST['folder'] . '/' . $_REQUEST['nom']);


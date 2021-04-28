<?php

require_once('../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';


llxHeader($js);



$head = calendars_prepare_head($paramnoaction);

dol_fiche_head($head, "caldav", $langs->trans('Agenda'), 0, 'action');


if (isset($conf->global->MAIN_MODULE_SYNOPSISCALDAV)) {
    $lienG = ((isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT']  == 443) ? 'https' : 'http') ."://". $_SERVER['HTTP_HOST'].DOL_URL_ROOT."/synopsiscaldav/html/cal.php/";
    $lien1 = $lienG."calendars/".$user->login."/Calendar";
    $lien2 = $lienG."principals/".$user->login;
    print"Lien CalDav Non Apple : <a href='".$lien1."'>".$lien1."</a>";
    print"<br/>Lien CalDav pour Apple : <a href='".$lien2."'>".$lien2."</a>";
}

dol_fiche_end();

llxFooter();
<?php

/*
 * BIMP-ERP by Synopsis et DRSI
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
require_once('../main.inc.php');

$mainmenu = isset($_GET["mainmenu"]) ? $_GET["mainmenu"] : "";
llxHeader("", "Importation de données");
dol_fiche_head('', 'SynopsisTools', $langs->trans("Importation de données"));


if ($user->rights->SynopsisTools->Global->import != 1) {
    print "Ce module ne vous est pas accessible";
    llxFooter();
    exit(0);
}
//$return = array();
//
//echo shell_exec("cd /opt/BIMP-ERP/main");
//echo shell_exec("git pull https://dsauron@bitbucket.org/dsauron/gle-dol-maj.git");
//echo shell_exec("freeparty");
//
//ex
//
//print_r($return);
//
//echo $stat1 . $stat2;

//include_once(DOL_DOCUMENT_ROOT."/synopsistools/PHPGit/Repository.php");
////$repo = new PHPGit_Repository('/home/jean/Bureau2/Workspace BIMP-ERP/GIT/PROJET/gle-dol-maj');
//$repo = new PHPGit_Repository('/opt/BIMP-ERP/main');
//
////print_r($repo->getBranches());
//$repo->git('pull https://dsauron@bitbucket.org/dsauron/gle-dol-maj.git');


exec("cd '/opt/BIMP-ERP/main' && /usr/bin/git pull https://dsauron@bitbucket.org/dsauron/gle-dol-maj.git");

llxFooter();

?>

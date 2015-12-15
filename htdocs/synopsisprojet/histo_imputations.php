<?php

/*
 */
/**
 *
 * Name : histo_imputations.php
 * GLE-2
 */
require_once('pre.inc.php');
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.formfile.class.php");

$langs->load("synopsisproject@synopsisprojet");
$userId = $user->id;


if (!isset($_REQUEST['action']))
    $_REQUEST['action'] = '';


define('_IMPUT_POURC_MULTI_USER_', false);

$messErreur = array();

if ($user->rights->synopsisprojet->voirImputations && isset($_REQUEST['userid']) && ($_REQUEST['userid'] > 0 || $_REQUEST['userid'] == -2))
    $userId = $_REQUEST['userid'];


$comref = sanitize_string("Imputations-" . date('Y') . '-' . $user->login);
$filedir = $conf->imputations->dir_output;

$curUser = new User($db);
$curUser->fetch($userId);

$format = 'weekly';
if (isset($_REQUEST['format']) && $_REQUEST['format'] . 'x' != "x")
    $format = $_REQUEST['format'];
$date = strtotime(date('Y-m-d'));
if (isset($_REQUEST['date']) && $_REQUEST['date'] . 'x' != "x")
    $date = $_REQUEST['date'];


global $modVal;
$modVal = 1;
if (isset($_SESSION['modVal']))
    $modVal = $_SESSION['modVal'];
if (isset($_REQUEST['modVal'])) {
    $modVal = $_REQUEST['modVal'];
    $_SESSION['modVal'] = $_REQUEST['modVal'];
}
//print_r($user->rights->synopsisprojet->caImput);die;
if (!$user->rights->synopsisprojet->caImput && $modVal == 3)
    $modVal = 2;


$grandType = 1;
if (isset($_SESSION['grandType']))
    $grandType = $_SESSION['grandType'];
if (isset($_REQUEST['grandType'])) {
    $grandType = $_REQUEST['grandType'];
    $_SESSION['grandType'] = $_REQUEST['grandType'];
    $modVal = 1; //Valeur auto mais pas obligé
    $format = 'weekly';
    $date = strtotime(date('Y-m-d'));
}


$formatView = 'norm';
if (isset($_SESSION['view']))
    $formatView = $_SESSION['view'];
if (isset($_REQUEST['view'])) {
    $formatView = $_REQUEST['view'];
    $_SESSION['view'] = $_REQUEST['view'];
}
if ($formatView == "month" && $format != 'annualy' && $format != "monthly") {
    if (isset($_REQUEST['view']))
        $format = 'annualy';
    else
        $formatView = "norm";
}



if ($grandType != 1) {
    if ($modVal == 1)
        $modVal = 2;
    $format = "annualy";
    $formatView = "month";
}


$monthDur = 30;

//Si format => weekly => debute un lundi, idem bi weekly
//Si format => monthly => debute le 1 du mois => doit determiner le nb de jour du mois
if (($format == "weekly" || $format == "biweekly") && date('w', $date) != 1) {
    while (date('w', $date) != 1) {
//        $date -= 3600 * 24;
        $date = strtotime("-1 day", $date);
    }
} else if ($format == 'monthly' && date('j', $date) != 1) {
    $date = strtotime(date('Y', $date) . "-" . date('m', $date) . "-01");
} else if ($format == 'annualy') {
    $date = strtotime(date('Y', $date) . "-01-01");
}
if ($format == 'monthly')
    $monthDur = date('t', $date);

$arrTitleNav = array('nextweekly' => "Semaine suivante", 'nextbiweekly' => "Semaine suivante", 'nextmonthly' => "Mois suivant",
    'prevweekly' => "Semaine pr&eacute;c&eacute;dente", 'prevbiweekly' => "Semaine pr&eacute;c&eacute;dente", 'prevmonthly' => "Mois pr&eacute;c&eacute;dent",);

$fromProj = false;
$projet = false;
if (isset($_REQUEST['fromProjet']) && $_REQUEST['fromProjet'] == 1 && $_REQUEST['id'] > 0) {
    require_once(DOL_DOCUMENT_ROOT . "/synopsisprojet/class/synopsisproject.class.php");
    require_once(DOL_DOCUMENT_ROOT . "/synopsisprojet/core/lib/synopsis_project.lib.php");
    $projet = new SynopsisProject($db);
    $projet->fetch($_REQUEST['id']);
    $fromProj = true;
}

if ($_REQUEST['action'] == 'builddoc') {    // In get or post
    require_once(DOL_DOCUMENT_ROOT . "/core/modules/imputation/modules_imputations.php");
    $outputlangs = '';
    if ($_REQUEST['lang_id']) {
        $outputlangs = new Translate("", $conf);
        $outputlangs->setDefaultLang($_REQUEST['lang_id']);
    }

    $result = imputations_pdf_create($db, $_REQUEST['id'], $_REQUEST['model'], $outputlangs);
    if ($result <= 0) {
        dol_print_error($db, $result);
        exit;
    } else {
        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
        $interface = new Interfaces($db);
        $result = $interface->run_triggers('ECM_GENIMPUTATIONS', false, $user, $langs, $conf);
        if ($result < 0) {
            $error++;
//    		$this->errors = $interface->errors ;
        }
        // Fin appel triggers
        Header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $_REQUEST['id'] . '#builddoc');
        exit;
    }
} else if ($_REQUEST['action'] == 'remove_file') {
    require_once(DOL_DOCUMENT_ROOT . "/core/lib/files.lib.php");

    $langs->load("other");
    $file = $filedir . '/' . GETPOST('file');
    dol_delete_file($file);
    $mesg = '<div class="ok">' . $langs->trans("FileWasRemoved", GETPOST('file')) . '</div>';
}

if ($_REQUEST['action'] == 'save') {
    $arrModTask = array();
    if ($userId > 0 && isset($_REQUEST['activity_hidden'])) {
        foreach ($_REQUEST['activity_hidden'] as $key => $val) {
            $arrModTask[$key] = $key;
            foreach ($val as $key1 => $val1) {
                $newVal = $_REQUEST['activity'][$key][$key1];
                if ($newVal != $val1) {
                    if ($grandType == 1) {
                        $requete2 = "SELECT sum(task_duration_effective) as sommeheure
                                   FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_time_effective
                                 WHERE task_date_effective = '" . date('Y-m-d H:i:s', $key1) . "'
                                   AND fk_user = " . $userId;
                        //AND fk_task = " . $key ;                     
                        $sql2 = $db->query($requete2);
                        $res2 = $db->fetch_object($sql2);

                        $requete3 = "SELECT *
                                   FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_time_effective
                                 WHERE task_date_effective = '" . date('Y-m-d H:i:s', $key1) . "'
                                   AND fk_user = " . $userId . "
                                   AND fk_task = " . $key;
                        $sql3 = $db->query($requete3);
                        $res3 = $db->fetch_object($sql3);
                        $existant = false;
                        if ($res3)
                            $existant = true;
                        $somh = $res2->sommeheure;
                        if ($existant)
                            $somh = $somh - $res3->task_duration_effective;
                        if ($newVal < 9 && (($somh / 3600) + $newVal) < 9) {//verif que on respecte le max d'heure par jour et par tache
                            if ($existant) {
                                $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_projet_task_time_effective
                                       SET task_duration_effective = " . intval($newVal * 3600) . "
                                     WHERE rowid = " . $res3->rowid;
                                $sql1 = $db->query($requete);
                            } else {
                                $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_projet_task_time_effective (task_duration_effective, task_date_effective, fk_task, fk_user)
                                         VALUES (" . intval($newVal * 3600) . ",'" . date('Y-m-d H:i:s', $key1) . "'," . $key . "," . $userId . ")";
                                $sql1 = $db->query($requete);
                            }
                        } else
                            $messErreur[] = "Plus de 9 h pour la journée " . date('Y-m-d H:i:s', $key1);
                    } elseif ($grandType == 2) {
//                    echo "<pre>";print_r($_REQUEST);die;
                        $requete2 = "SELECT sum(val) as sommeheure, rowid
                                   FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_AQ
                                 WHERE date = '" . date('Y-m-d H:i:s', $key1) . "'
                                     AND fk_task = " . $key . "
		" . (($userId != -2 && _IMPUT_POURC_MULTI_USER_) ? " AND fk_user = $userId " : "");
                        //AND fk_task = " . $key ;                     
                        $sql2 = $db->query($requete2);
                        $res2 = $db->fetch_object($sql2);
                        $existant = false;
                        $totPourc = getMoyPourc($key, 0, $userId);
                        if (isset($res2->sommeheure)) {
                            $existant = true;
                            $totPourc -= $res2->sommeheure;
                        }
                        if (($totPourc + $newVal) <= 100) {
                            if ($existant) {
                                $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_projet_task_AQ
                                       SET val = " . intval($newVal) . "
                                     WHERE rowid = " . $res2->rowid;
                                $sql1 = $db->query($requete);
                            } else {
                                $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_projet_task_AQ (val, date, fk_task, fk_user)
                                         VALUES (" . intval($newVal) . ",'" . date('Y-m-d H:i:s', $key1) . "'," . $key . "," . $userId . ")";
                                $sql1 = $db->query($requete);
                            }
                        } else
                            $messErreur[] = "Plus de 100% pour la tache " . $res2->rowid;
                    }
                }
            }
        }
    }
    if ($grandType == 1) {
        foreach ($arrModTask as $taskId) {
            $requete = "SELECT sum(task_duration_effective) as durEff FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_time_effective WHERE fk_task = " . $taskId;
            $sql = $db->query($requete);
            $res = $db->fetch_object($sql);
            $tot = $res->durEff;
            if ($tot <= 0)
                $tot = "0";
            $requete = "UPDATE " . MAIN_DB_PREFIX . "projet_task SET duration_effective = " . $tot . " WHERE rowid = " . $taskId;
            $sql = $db->query($requete);
            $requete = "UPDATE " . MAIN_DB_PREFIX . "projet_task SET progress = 100-((planned_workload - duration_effective) *100)/planned_workload WHERE rowid = " . $taskId;
            $sql = $db->query($requete);
        }
    }
//    header('location: ?' . ($fromProj ? 'fromProjet=1&id=' . $_REQUEST['id'] . '&' : '') . 'userid=' . $userId . "&format=" . $format . "&date=" . $date);
}


$js = <<<EOF
<link href="css/imputations.css" type="text/css" rel="stylesheet">
<script>
jQuery(document).ready(function(){
    jQuery('.div_scrollable_medium tr').each(function(){
        var self = jQuery(this);
        jQuery(this).mouseover(function(){
            self.addClass('ui-state-highlight');
            self.find('input').each(function(){
                jQuery(this).addClass('ui-state-hover');
            });
        });
        jQuery(this).mouseout(function(){
            self.removeClass('ui-state-highlight');
            self.find('input').each(function(){
                jQuery(this).removeClass('ui-state-hover');
            });
        });
    });
    jQuery('SELECT#userid').change(function(){
        jQuery('SELECT#userid').parents('form').submit();
    });
    jQuery('.tousUser').click(function(){
        window.location = "?userid=-2";
        return false;
    });

});
</script>
EOF;

llxHeader($js, "Imputations");


dol_htmloutput_mesg($mesg, $mesgs);

if ($fromProj) {
    $head = synopsis_project_prepare_head($projet);
    dol_fiche_head($head, 'Imputations', $langs->trans("Project"));
    //saveHistoUser( $projet->id, "projet", $projet->ref ) ;
}
print "<br/>";
print "<div class='titre'>Imputations projet</div>";


foreach ($messErreur as $erreur) {
    echo "<div class='error'>" . $erreur . "</div>";
}

print '    <div id="struct_main" class="activities">';



print '<p><table width=100%><tr><td style="width:130px;"><b>Type d\'imputation :</b>';
print '          <td><table>';
if ($grandType != 1)
    print '                     <tr><td><a href="?' . ($fromProj ? 'fromProjet=1&id=' . $_REQUEST['id'] . '&' : '') . 'userid=' . $userId . '&format=' . $format . '&amp;date=' . $date . '&amp;grandType=1">Par heures</a>';
if ($grandType != 2)
    print '                     <tr><td><a href="?' . ($fromProj ? 'fromProjet=1&id=' . $_REQUEST['id'] . '&' : '') . 'userid=' . $userId . '&format=' . $format . '&amp;date=' . $date . '&amp;grandType=2">Par avancements</a>';
if ($grandType != 3)
    print '                     <tr><td><a href="?' . ($fromProj ? 'fromProjet=1&id=' . $_REQUEST['id'] . '&' : '') . 'userid=' . $userId . '&format=' . $format . '&amp;date=' . $date . '&amp;grandType=3">Ratio</a>';
print '              </table>';
print '</table></p>';


print '<table><tr><td width="500px">';
print '<p><table width=100%><tr><td style="width:130px;"><b>Type valeur d\'affichage :</b>';
print '          <td><table>';
if ($modVal != '1' && $grandType == 1)
    print '                     <tr><td><a href="?' . ($fromProj ? 'fromProjet=1&id=' . $_REQUEST['id'] . '&' : '') . 'userid=' . $userId . '&format=' . $format . '&amp;date=' . $date . '&amp;modVal=1">Heures</a>';
if ($modVal != '2')
    print '                     <tr><td><a href="?' . ($fromProj ? 'fromProjet=1&id=' . $_REQUEST['id'] . '&' : '') . 'userid=' . $userId . '&format=' . $format . '&amp;date=' . $date . '&amp;modVal=2">Pourcentages</a>';
if ($modVal != '3' && $user->rights->synopsisprojet->caImput)
    print '                     <tr><td><a href="?' . ($fromProj ? 'fromProjet=1&id=' . $_REQUEST['id'] . '&' : '') . 'userid=' . $userId . '&format=' . $format . '&amp;date=' . $date . '&amp;modVal=3">Euros</a>';
print '              </table>';
print '</table></p>';



if ($grandType == 1) {
    print '<p><table width=100%><tr><td style="width:130px;"><b>Periode d\'affichage :</b>';
    print '          <td><table>';
    if ($format != 'monthly')
        print '                     <tr><td><a href="?' . ($fromProj ? 'fromProjet=1&id=' . $_REQUEST['id'] . '&' : '') . 'userid=' . $userId . '&format=monthly&amp;date=' . $date . '">Mensuel</a>';
    if ($format != 'biweekly')
        print '                     <tr><td><a href="?' . ($fromProj ? 'fromProjet=1&id=' . $_REQUEST['id'] . '&' : '') . 'userid=' . $userId . '&format=biweekly&amp;date=' . $date . '">Bihebdomadaire</a>';
    if ($format != 'weekly')
        print '                     <tr><td><a href="?' . ($fromProj ? 'fromProjet=1&id=' . $_REQUEST['id'] . '&' : '') . 'userid=' . $userId . '&format=weekly&amp;date=' . $date . '">Hebdomadaire</a>';
    if ($format != 'annualy')
        print '                     <tr><td><a href="?' . ($fromProj ? 'fromProjet=1&id=' . $_REQUEST['id'] . '&' : '') . 'userid=' . $userId . '&format=annualy&amp;date=' . $date . '">Annuel</a>';
    print '              </table>';
    print '</table></p>';




    print '<p><table width=100%><tr><td style="width:130px;"><b>Type d\'affichage :</b>';
    print '          <td><table>';
    if ($formatView != 'month')
        print '                     <tr><td><a href="?' . ($fromProj ? 'fromProjet=1&id=' . $_REQUEST['id'] . '&' : '') . 'userid=' . $userId . '&format=' . $format . '&amp;date=' . $date . '&amp;view=month">Par mois</a>';
    if ($formatView != 'norm')
        print '                     <tr><td><a href="?' . ($fromProj ? 'fromProjet=1&id=' . $_REQUEST['id'] . '&' : '') . 'userid=' . $userId . '&format=' . $format . '&amp;date=' . $date . '&amp;view=norm">Par jour</a>';
    print '              </table>';
    print '</table></p>';
//print '</table></p>';
}




if ($user->rights->synopsisprojet->voirImputations) {
    require_once(DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php");
    $html = new Form($db);
    print "<td><form action='?" . ($fromProj ? 'fromProjet=1&id=' . $_REQUEST['id'] . '&' : '') . "format=" . $format . "&date=" . $date . "' method=GET>";
    print "<table><tr><td>";
    $html->select_users($userId, 'userid', 1, '', 0);
    print "<td><button class='butAction'>OK</button>";
    print "<td><button class='butAction tousUser'>Tous</button>";
    print "</table>";
    print "</form>";
}


print '</tr></table>';


print '<form method="post" action="?' . ($fromProj ? 'fromProjet=1&id=' . $_REQUEST['id'] . '&' : '') . '&action=save&format=' . $format . '&date=' . $date . '">';
print '<input type="hidden" name="userid" value="' . $userId . '"></input>';
print '    <div style="width:100%;">';
print '    <table class="calendar" width=100%>';
if ($user->id == $userId)
    print '     <caption class="ui-state-default ui-widget-header">Mes imputations</caption>';
elseif ($userId == -2)
    print '     <caption class="ui-state-default ui-widget-header">Toutes les imputations</caption>';
else
    print '     <caption class="ui-state-default ui-widget-header">Les imputations de ' . $curUser->getNomUrl(1) . '</caption>';
print '       <thead>';
print '         <tr>';
print '           <th class="ui-state-hover ui-widget-header navigation" colspan="2">';
print '                 &nbsp;';

$prevDate = strtotime("-1 week", $date);
$nextDate = strtotime("+1 week", $date);
//die(date('Y-m', strtotime("+4 day", $date))."-01 00:00:00");
$miSemaine = strtotime("+4 day", $date);
if ($format == "monthly") {
    $prevDate = strtotime("-1 month", $date);
    $nextDate = strtotime("+1 month", $date);
}
if ($format == "annualy") {
    $prevDate = strtotime(date('Y', $date) - 1 . "-01-01");
    $nextDate = strtotime(date('Y', $date) + 1 . "-01-01");
}
print '                 <a href="?' . ($fromProj ? 'fromProjet=1&id=' . $_REQUEST['id'] . '&' : '') . 'userid=' . $userId . '&format=' . $format . '&amp;date=' . $prevDate . '">';
print '                     <span class="ui-icon ui-icon-arrowthickstop-1-w" title="' . $arrTitleNav['prev' . $format] . '" style="float:left"></span>';
print '                 </a>';
print '                 <a class="today" href="?' . ($fromProj ? 'fromProjet=1&id=' . $_REQUEST['id'] . '&' : '') . 'userid=' . $userId . '&format=' . $format . '">';
print '                     <span class="ui-icon ui-icon-arrowthickstop-1-s" title="Aujourd\'hui" style="float:left"></span>';
print '                 </a>';
print '                 <a href="?' . ($fromProj ? 'fromProjet=1&id=' . $_REQUEST['id'] . '&' : '') . 'userid=' . $userId . '&format=' . $format . '&amp;date=' . $nextDate . '">';
print '                     <span class="ui-icon ui-icon-arrowthickstop-1-e" title="' . $arrTitleNav['next' . $format] . '" style="float:left"></span>';
print '                 </a>';
$arrMonthFR = array('1' => 'Jan', "2" => "Fev", "3" => "Mar", "4" => "Avr", "5" => "Mai", "6" => "Jun", "7" => "Jui", "8" => "Aou", "9" => "Sep", "10" => "Oct", "11" => "Nov", "12" => "Dec");
if ($format == 'weekly') {
    print '                 Activit&eacute;s de la semaine ' . intval(date('W', $miSemaine)) . " (" . $arrMonthFR[date('n', $miSemaine)] . ") ";
} else if ($format == 'biweekly') {
    print '                 Activit&eacute;s des semaines ' . intval(date('W', $miSemaine)) . ' / ' . intval(date('W', $miSemaine) + 1) . " (" . $arrMonthFR[date('n', $miSemaine)] . ") ";
} else if ($format == 'monthly') {
    print '                 Activit&eacute;s du mois de ' . $arrMonthFR[date('n', $date)] . " - ";
}
print date('Y', $miSemaine) . '</th>             <th class="ui-state-hover ui-widget-header" colspan="1"></th>';
print '             <th class="ui-state-hover ui-widget-header" colspan="2">Total</th>';


$arrNbMonth = array('monthly' => 1, "annualy" => 12);
$totalDay = array();

$tmpDate = $date;
if ($formatView == "month") {
    $arrNbJour = array('monthly' => 1, "annualy" => 12);
    for ($i = 0; $i < $arrNbMonth[$format]; $i++) {
        print '<th class="ui-state-hover ui-widget-header day_' . date('w', $tmpDate) . '">' . date('m', $tmpDate) . '</th>';
        $tmpDate = strtotime(date('Y-', $tmpDate) . (date('m', $tmpDate) + 1) . "-01");
        $totalDay[$tmpDate] = 0;
    }
} else {
    $arrNbJour = array('monthly' => $monthDur, 'weekly' => 7, "biweekly" => 14, "annualy" => 365);
    for ($i = 0; $i < $arrNbJour[$format]; $i++) {
        if ($format != 'annualy')
            print '<th class="ui-state-hover ui-widget-header day_' . date('w', $tmpDate) . '">' . date('d', $tmpDate) . '</th>';
        else
            print '<th class="ui-state-hover ui-widget-header day_' . date('w', $tmpDate) . '">' . date('d/m', $tmpDate) . '</th>';
//        $tmpDate += 3600 * 24;
        $tmpDate = strtotime("+1 day", $tmpDate);
//        if(date('d/m', $tmpDate) == "04/10")
//                $tmpDate += 3600;
        $totalDay[$tmpDate] = 0;
    }
}
print "</tr>";
print '<tr>';
print '  <th class="ui-widget-header" style="width:270px;">';
print '  </th>';
print '  <th class="ui-widget-header">&nbsp;&nbsp;</th>';
print '             <th class="ui-widget-header" title="Restant">Res&nbsp;</th>';
print '             <th class="ui-widget-header">Global</th>';
print '             <th class="ui-widget-header">Période</th>';
$tmpDate = $date;
if ($formatView == "month") {
    $arrJourFR = array(1 => "Janv", 2 => "Fev", 3 => "Mars", 4 => "Avril", 5 => "Mai", 6 => "Juin", 7 => "Juillet", 8 => "Aout", 9 => "Sept", 10 => "Oct", 11 => "Nov", 12 => "Dec");
    for ($i = 0; $i < $arrNbJour[$format]; $i++) {
        print '<th class="ui-widget-header day_' . date('w', $tmpDate) . '">' . $arrJourFR[round(date('m', $tmpDate))] . '</th>';
        $tmpDate = strtotime(date('Y-', $tmpDate) . (date('m', $tmpDate) + 1) . "-01");
    }
} else {
    $arrJourFR = array(0 => "Dim", 1 => "Lun", 2 => "Mar", 3 => "Mer", 4 => "Jeu", 5 => "Ven", 6 => "Sam");
    for ($i = 0; $i < $arrNbJour[$format]; $i++) {
        print '<th class="ui-widget-header day_' . date('w', $tmpDate) . '">' . $arrJourFR[date('w', $tmpDate)] . '</th>';
//        $tmpDate += 3600 * 24;
        $tmpDate = strtotime("+1 day", $tmpDate);
    }
}

print "</tr>";
print "</thead>";
print '<tbody class="div_scrollable_medium">';
//trouve tous les projet de l'utilisateur ou il a un role
/* $requete1 = "SELECT sum(task_duration_effective) as sommeheure
  FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_time_effective
  WHERE fk_user = " . $userId ;

  $sql1 = $db->query( $requete1 ) ;
  $res1 = $db->fetch_object( $sql1 ) ;

  print '     <td nowrap class="display_value">' .$res1->sommeheure. '</td>' ; */


//$userId = -2;
if ($userId != -2 && $grandType > 1 && !_IMPUT_POURC_MULTI_USER_)
    $contraiteUser = " AND p.fk_user_resp = $userId ";
elseif ($userId != -2)
    $contraiteUser = " AND a.fk_user = $userId ";
else
    $contraiteUser = '';
$requete = "SELECT DISTINCT t.rowid as tid,
                  p.rowid as pid,
                  p.ref as pref,
                  t.label as title,
                  t.fk_statut as statut,
                  p.fk_statut
             FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors AS a,
                  " . MAIN_DB_PREFIX . "Synopsis_projet_view AS p,
                  " . MAIN_DB_PREFIX . "projet_task AS t
            WHERE p.rowid = t.fk_projet
              AND t.rowid = a.fk_projet_task
              AND a.type = 'user'
		" . $contraiteUser . "
	    ORDER BY p.rowid";

dol_syslog($requete,3);

$sql = $db->query($requete);
$remProjId = false;
$bool = true;
$arrPairImpair[false] = "ui-widget-content ui-priority-primary";
$arrPairImpair[true] = "ui-widget-content ui-priority-primary ui-state-default";
require_once(DOL_DOCUMENT_ROOT . '/synopsisprojet/class/synopsisproject.class.php');
$proj = new SynopsisProject($db);
$arrTaskId = array();
$grandTotalRestant = 0;
$grandTotalLigne = 0;
$grandTotalLigne2 = 0;
while ($res = $db->fetch_object($sql)) {
    global $prevue, $prixTot, $realiser;
    $tousVide = true;
    $html = '';
    $bool = !$bool;
    $arrTaskId[$res->tid] = $res->tid;
    $html .= '<tr class="' . $arrPairImpair[$bool] . '">';
    $html .= '  <td class="nowrap" colspan="1">';
    if (!$remProjId || $remProjId != $res->pid) {
        $proj->fetch($res->pid);
        $html .= "<label title='" . $proj->title . "'/>" . $proj->ref . " - " . $proj->getNomUrl(1, '', 25) . "</label>";
    }
    $html .= '  <td class="nowrap" colspan="1">';
    $html .= $res->title;
    $html .= '     </td>';

    $requete1 = "SELECT sum(task_duration) as sumTps
                  FROM " . MAIN_DB_PREFIX . "projet_task_time
                 WHERE fk_task = " . $res->tid
            . (($userId != -2 && ($grandType == 1 || _IMPUT_POURC_MULTI_USER_)) ? " AND fk_user = $userId " : "");

    $sql1 = $db->query($requete1);
    $res1 = $db->fetch_object($sql1);

    $prevue = round(intval($res1->sumTps) / 36) / 100;
    $realiser = getSumHeure($res->tid, $userId);

    if ($grandType == 1) {
//        $requete2 = "SELECT sum(task_duration_effective) as sumTps
//                  FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_time_effective
//                 WHERE fk_task = " . $res->tid . "
//		" . (($userId != -2) ? " AND fk_user = $userId " : "");
//        $sql2 = $db->query($requete2);
//        $res2 = $db->fetch_object($sql2);
//        $restant = round(intval($res1->sumTps - $res2->sumTps) / 36) / 100;
//        $totalLigne = round(intval($res2->sumTps) / 36) / 100;


        $totalLigne = $realiser;
        $restant = $prevue - $totalLigne;
    } elseif ($grandType == 3) {
        $pourcHeure = getMoyPourc($res->tid, $prevue, $userId);
        $pourcAvenc = $realiser / $prevue * 100;
        $totalLigne = $pourcHeure - $pourcAvenc;
        $restant = "n/c";
    } else {
        $totalLigne = getMoyPourc($res->tid, $prevue, $userId);
        $restant = 100 - $totalLigne;
    }

    /* $requete2 = "SELECT val as sumTps, fk_user
      FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_AQ
      WHERE fk_task = " . $res->tid . "
      " . (($userId != -2) ? " AND fk_user = $userId " : "");
      $sql2 = $db->query($requete2);
      $restant = 0;
      $totalLigneT = 0;
      while ($result = $db->fetch_object($sql2)) {
      $requete100 = "SELECT sum(task_duration) as sumTps
      FROM " . MAIN_DB_PREFIX . "projet_task_time
      WHERE fk_task = " . $res->tid . "
      AND fk_user = " . $result->fk_user;
      $sql100 = $db->query($requete100);
      $res100 = $db->fetch_object($sql100);
      $pourcUserTache = $res100->sumTps / $prevue / 3600;
      $avancementProrata = $result->sumTps * $pourcUserTache;
      $restant = 100 - $result->sumTps;
      if ($userId == -2)
      $totalLigne += $avancementProrata;
      else
      $totalLigne += $result->sumTps;
      }
      }
      $avancementProrata = $result->sumTps * $pourcUserTache;
      $restant = 100 - $avancementProrata;
      $totalLigne += $avancementProrata; */

    $commandes = $proj->get_element_list('order');
    $prixTot = 0;
    foreach ($commandes as $commande) {
        $comm = new Commande($db);
        $comm->fetch($commande);
        $prixTot += $comm->total_ht;
    }
//    die("lll".$proj->getStatsDuration() / 3600);
    $pourcTache = ($proj->getStatsDuration() > 0) ? $prevue / ($proj->getStatsDuration() / 3600) : 0;
    $prixTot = $prixTot * $pourcTache;
    $hourPerDay = $conf->global->PROJECT_HOUR_PER_DAY;
//    $totalLignePerDay = round(intval($res2->sumTps) / (36 * $hourPerDay)) / 100;

    $restant = toAffiche($restant);
    $totalLigne = toAffiche($totalLigne);
    //Restant
    $html .= '     <td nowrap class="display_value' . (($restant < 0) ? ' error' : '') . '">' . $restant . '</td>';
    //Total h
    $html .= '     <td nowrap class="display_value">' . $totalLigne . '</td>';


    $tmpDate = $date;
    $html2 = $html3 = '';
    $totalPeriode = 0;
    for ($i = 0; $i < $arrNbJour[$format]; $i++) {
        if ($formatView == "month") {
            if (date('m', $tmpDate) < 12)
                $tmpDate2 = strtotime(date('Y-', $tmpDate) . (date('m', $tmpDate) + 1) . date('-d', $tmpDate) . ' 00:00:00');
            else
                $tmpDate2 = strtotime((date('Y', $tmpDate) + 1) . '-01' . date('-d', $tmpDate) . ' 00:00:00');
        } else
            $tmpDate2 = strtotime(date('Y-m-d', $tmpDate) . ' 23:59:59');
        if ($grandType == 1)
            $nbHeure = getSumHeure($res->tid, $userId, $tmpDate, $tmpDate2);
        elseif ($grandType == 3) {
            $pourcHeure = getMoyPourc($res->tid, $prevue, $userId, $tmpDate, $tmpDate2);
            $pourcAvenc = getSumHeure($res->tid, $userId, $tmpDate, $tmpDate2) / $prevue * 100;
            if ($pourcAvenc > 0 || $pourcHeure > 0) {
                if ($modVal != 3)
                    $tousVide = false;
                $nbHeure = $pourcHeure - $pourcAvenc;
            } else
                $nbHeure = "n/c";
        } else
            $nbHeure = getMoyPourc($res->tid, $prevue, $userId, $tmpDate, $tmpDate2);


        /*            $requete = "SELECT sum(val) as task_duration_effective
          FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_AQ
          WHERE fk_task = " . $res->tid . "
          " . (($userId != -2) ? " AND fk_user = $userId " : "") . "
          AND date >= '" . date('Y-m-d', $tmpDate) . " 00:00:00' AND date < '" . date('Y-m-d H:i:s', $tmpDate2) . "'"; */


        $totalPeriode += $nbHeure;
//        $nbHeure = toAffiche($nbHeure);
        $totalDay2[$tmpDate] = toAffiche($nbHeure);
        $html2 .= '     <td class="day_' . date('w', $tmpDate) . '" style="text-align:center;overflow:auto;">';
        $html3 .= '     <td class="day_' . date('w', $tmpDate) . '" style="text-align:center;overflow:auto;">';
        $html2 .= '             <input type="hidden" name="activity_hidden[' . $res->tid . '][' . $tmpDate . ']" value="' . $nbHeure . '" size="1" ' . (($grandType == 1) ? 'maxlength="1"' : '') . '/>';
        $html2 .= '             <input type="text" name="activity[' . $res->tid . '][' . $tmpDate . ']" value="' . $nbHeure . '" size="1" ' . (($grandType == 1) ? 'maxlength="1"' : '') . '/>';
        $html3 .= toAffiche($nbHeure);
        $html2 .= '     </td>';
        $html3 .= '     </td>';
        if ($formatView == "month")
            $tmpDate = $tmpDate2;
        else
//            $tmpDate += 3600 * 24;
            $tmpDate = strtotime("+1 day", $tmpDate);
        if ($nbHeure != 0 && toAffiche($nbHeure) != 0)
            $tousVide = false;
    }

    $html2 .= '</tr>';
    $html3 .= '</tr>';

    foreach ($totalDay2 as $cle => $val)
        $totalDay[$cle] += $totalDay2[$cle];
    $stat = $res->fk_statut;

    //Total periode
    $html .= '     <td nowrap class="display_value">' . toAffiche($totalPeriode) . '</td>';

    $affiche = true;
    if ($userId != -2 && (($grandType == 2 && $modVal == 2) || ($grandType == 1 && $modVal == 1 && $formatView == 'norm')) && $res->statut == 0 && $stat != 0 && $stat != 5 && $stat != 50 && $stat != 999)
        $html .= $html2;
    elseif (!$tousVide)
        $html .= $html3;
    else
        $affiche = false;

    if ($affiche) {
        echo $html;
        $remProjId = $res->pid;
    }

    $grandTotalRestant += $restant;
    $grandTotalLigne += $totalLigne;
    $grandTotalLigne2 += toAffiche($totalPeriode);
}

print '    </tbody>';

print "<tfoot>";
if ($modVal != 2) {
    print '         <tr>';
    print '             <th class="ui-state-default ui-widget-header" colspan=2 align=right>Total&nbsp;';

//  $hourPerDay = $conf->global->PROJECT_HOUR_PER_DAY;
//  $grandTotalLignePerDay = round($grandTotalLigne * 100 / $hourPerDay) / 100;
//  $grandTotalLigne = round($grandTotalLigne * 100) / 100;
//Total restant
    print '             <th class="ui-state-default ui-widget-header">' . getUnite($grandTotalRestant) . '</th>';
//Total h
    print '             <th class="ui-state-default ui-widget-header">' . getUnite($grandTotalLigne) . '</th>';
//Total periode
    print '             <th class="ui-state-default ui-widget-header">' . getUnite($grandTotalLigne2) . '</th>';

//    $tmpDate = $date;
//    print_r($totalDay);
//    for ($i = 0; $i < $arrNbJour[$format]; $i++) {
//        echo $tmpDate;
//        if (!$totalDay[$tmpDate] > 0) {
//            $totalDay[$tmpDate] = 0;
//        }
//        print '<th class="ui-state-default ui-widget-header day_' . date('w', $tmpDate) . '">' . getUnite($totalDay[$tmpDate]) . '</th>';
//        $tmpDate += 3600 * 24;
//    }



    ksort($totalDay);
    $i = 0;
    foreach ($totalDay as $tmpDate => $val) {
        if ($tmpDate >= $date && $i < $arrNbJour[$format]) {
            $i++;
            if (!$val > 0) {
                $val = 0;
            }
            print '<th class="ui-state-default ui-widget-header day_' . date('w', $tmpDate) . '">' . getUnite($val) . '</th>';
        }
    }


    print "</tr>";
}
dol_syslog(join(',', $arrTaskId),3);
if ($modVal == 1 && count($arrTaskId) > 0) {
    $colspan = $arrNbJour[$format] - 5; // -5 -5 + 5
//Total Mois
    $requete = "SELECT sum(task_duration_effective) / 3600 as durEff
  FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_time_effective
  WHERE month(task_date_effective) = " . date('m', $date) . "
  AND year(task_date_effective) = " . date('Y', $date) . "
  AND fk_task in (" . join(',', $arrTaskId) . ")
		" . (($userId != -2) ? " AND fk_user = $userId " : "");

    $sql = $db->query($requete);

    if ($sql) {
        $res = $db->fetch_object($sql);
        print "<tr><td style='padding:10px;' colspan=" . $colspan . "</td>";
        print "    <th style='padding:10px;' align='right' class='ui-widget-header ui-state-default' colspan='" . ($colspan > 1 ? '5' : '3') . "'>Total mensuel&nbsp;</td>";
        print "    <td align=center style='padding:10px;' class='ui-widget-content' colspan='" . ($colspan > 1 ? '5' : '2') . "'>" . getUnite($res->durEff) . "</td>";
        print "</tr>";
    }

//Total Annee
    $requete = "SELECT sum(task_duration_effective) / 3600 as durEff
  FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_time_effective
  WHERE year(task_date_effective) = " . date('Y', $date) . "
  AND fk_task in (" . join(',', $arrTaskId) . ")
		" . (($userId != -2) ? " AND fk_user = $userId " : "");

    $sql = $db->query($requete);

    if ($sql) {
        $res = $db->fetch_object($sql);
        print "<tr><td style='padding:10px;' colspan=" . $colspan . "</td>";
        print "    <th style='padding:10px;' align='right' class='ui-widget-header ui-state-default' colspan='" . ($colspan > 1 ? '5' : '3') . "'>Total annuel&nbsp;</td>";
        print "    <td align=center style='padding:10px;' class='ui-widget-content' colspan='" . ($colspan > 1 ? '5' : '2') . "'>" . getUnite($res->durEff) . "</td>";
        print "</tr>";
    }
}
print "</tfoot>";


print '  </table>';

print "<div class='tabsAction'>";
print "<button class='butAction'>Sauvegarder</button>";
print "</div>";
print "</form>";

print '<table width="500"><tr><td width="50%" valign="top">';
print '<a name="builddoc"></a>'; // ancre

/*
 * Documents generes
 *
 */
$urlsource = $_SERVER["PHP_SELF"] . (isset($_REQUEST['id']) ? '?id=' . $_REQUEST['id'] : '');
$genallowed = $user->rights->synopsisprojet->creer;
$delallowed = $user->rights->synopsisprojet->supprimer;

$modelpdf = "";

$conf->global->IMPUTATIONS_ADDON_PDF = "caracal";
global $db;
$formfile = new FormFile($db);
include_once(DOL_DOCUMENT_ROOT . '/core/modules/imputation/modules_imputations.php');
$somethingshown = @$formfile->show_documents('imputations', $comref, $filedir . "/" . $comref, $urlsource, $genallowed, $delallowed, $modelpdf);

//    function show_documents($modulepart,$filename,$filedir,$urlsource,$genallowed,$delallowed=0,$modelselected='',$modelliste=array(),$forcenomultilang=0,$iconPDF=0,$maxfilenamelength=28,$display=true)


print "</table>";
global $logLongTime;
$logLongTime = false;
llxFooter("<em>Derni&egrave;re modification </em>");

function toAffiche($val, $unite = true) {
    global $prevue, $prixTot, $modVal, $grandType, $realiser;

    if ($val === "n/c")
        return '';


    if ($grandType == 1) {
        if ($prevue <= 0)
            $val = 0;
        elseif ($modVal == 3) {
            if ($val > 0) {
                $tot = ($realiser > $prevue) ? $realiser : $prevue;
                $val = $val / $tot * $prixTot;
            }
            else
                $val = 0;
        }
        elseif ($modVal == 2)
            $val = $val / $prevue * 100;
    }
    else {
        if ($modVal == 3)
            $val = $prixTot * $val / 100;
    }
    if ($unite)
        $val = getUnite($val);
    return $val;
}

function getUnite($val) {
    global $modVal;
    $val = round($val * 100) / 100;
    if ($modVal == 3)
        return $val . " €";
    elseif ($modVal == 1)
        return $val . " h";
    elseif ($modVal == 2)
        return $val . " %";
}

function getSumHeure($fk_task, $userId = -2, $tmpDate = null, $tmpDate2 = null) {
    global $db;
    $requete = "SELECT sum(task_duration_effective / 3600) as task_duration_effective
                     FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_time_effective as e
                    WHERE fk_task = " . $fk_task . "
		" . (($userId != -2) ? " AND fk_user = $userId " : "")
            . (($tmpDate) ? " AND task_date_effective >= '" . date('Y-m-d', $tmpDate) . " 00:00:00' AND task_date_effective < '" . date('Y-m-d H:i:s', $tmpDate2) . "'" : "");
    $sql1 = $db->query($requete);
    $res1 = $db->fetch_object($sql1);
    return ($res1->task_duration_effective > 0 ? (round($res1->task_duration_effective * 100) / 100) : 0);
}

function getMoyPourc($fk_task, $prevue, $userId = -2, $tmpDate = null, $tmpDate2 = null) {
    global $db;
    $requete = "SELECT val, fk_user
                  FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_AQ
                 WHERE fk_task = " . $fk_task . "
		" . (($userId != -2 && _IMPUT_POURC_MULTI_USER_) ? " AND fk_user = $userId " : "")
            . (($tmpDate) ? " AND date >= '" . date('Y-m-d', $tmpDate) . " 00:00:00' AND date < '" . date('Y-m-d H:i:s', $tmpDate2) . "'" : "");


    $sql = $db->query($requete);
    $total = 0;
    while ($result = $db->fetch_object($sql)) {
        $requete100 = "SELECT sum(task_duration) as sumTps
                      FROM " . MAIN_DB_PREFIX . "projet_task_time
                     WHERE fk_task = " . $fk_task . "
                     AND fk_user = " . $result->fk_user;
        ;
        $sql100 = $db->query($requete100);
        $res100 = $db->fetch_object($sql100);
        if ($userId == -2 && _IMPUT_POURC_MULTI_USER_)
            $pourcUserTache = $res100->sumTps / $prevue / 3600;
        else
            $pourcUserTache = 1;
        $total += $result->val * $pourcUserTache;
    }
    return $total;
}

?>

<?php

/*
 * GLE by Synopsis
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.2
 * Created on : 22 juil. 2010
 *
 * Infos on http://www.synopsis-erp.com
 *
 */
/**
 *
 * Name : statProject.php
 * GLE-1.2
 */
require_once("../../main.inc.php");

// The get widget handler.

function widget_statProject() {
    // A widget object as per jquery.dashboard.js.
    global $db, $conf, $user, $langs, $bc;
    if ($user->rights->projet->lire) {
        $socid = 0;
        if ($user->societe_id > 0) {
            $socid = $user->societe_id;
        }

        $langs->load("projects");
        $langs->load("companies");
        $langs->load("bills");
        $langs->load("orders");
        $langs->load("commercial");

        $table = '<p>';
        $jqueryuipath = DOL_URL_ROOT . "/Synopsis_Common/jquery/ui/";
        $table .= '<script language="javascript" src="' . $jqueryuipath . 'ui.progressbar.js"></script>' . "\n";
        $table .= "<style type='text/css'>.ui-progressbar{ height: 100%; background-color: #ffffff; margin: 0px;}</style>";
        $table .= "<style type='text/css'>.ui-progressbar-value{ border:1px solid #000000;  }</style>";

        $table .= '<table class="noborder" width="100%">';
        $table .= '<tr class="liste_titre">';
        $table .= "<td>" . $langs->trans("Project");
        $table .= '<td align="right">' . $langs->trans("NbOpenTasks") . '</td>';
        $table .= '<td align="right">' . $langs->trans("Avancement") . '</td>';
        $table .= "</tr>\n";

        $sql = "SELECT p.title, p.rowid, ifnull(count(t.rowid),0) as cnt, ifnull(avg(t.progress),0) as prg";
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= ", sc.fk_soc, sc.fk_user";
        $sql.= " FROM";
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= " " . MAIN_DB_PREFIX . "societe_commerciaux as sc,";
        $sql.= " " . MAIN_DB_PREFIX . "Synopsis_projet_view as p";
        $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "projet_task as t ON p.rowid = t.fk_projet";
        $sql.= " WHERE fk_statut < 50";
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " . $user->id;
        if ($socid) {
            $sql .= " AND p.fk_soc = " . $socid;
        }
        $sql.= " GROUP BY p.rowid";

        $var = true;
        $resql = $db->query($sql);
        if ($resql) {
            $num = $db->num_rows($resql);
            $i = 0;

            while ($i < $num) {
                $row = $db->fetch_row($resql);
                $var = !$var;
                $table .= "<tr $bc[$var]>";
                $table .= '<td nowrap="nowrap"><a href="' . DOL_URL_ROOT . '/projet/card.php?id=' . $row[1] . '">' . img_object($langs->trans("ShowProject"), "project") . " " . $row[0] . '</a></td>';
                $table .= '<td align="right">' . $row[2] . '</td>';
                $table .= '<td align="right"><div style="height: 16px; border:1px solid #000000;" id="progBar' . $row[1] . '"></div></td>';
                $table .= "</tr>\n";
                $table .= '<script type="text/javascript">jQuery(document).ready(function(){ jQuery("#progBar' . $row[1] . '").progressbar({ value:"' . round($row[3]) . '"}) });</script>';

                $i++;
            }

            $db->free($resql);
        }


        $table .="</p>";

        return array(
            'title' => 'Projets - Statistiques',
            'content' => $table,
            'initScript' => "",
            'classes' => 'ui-state-default ui-widget-header',
            'settings' => false,
        );
    } else {
        return array(
            'title' => 'Projets - Statistiques',
            'content' => "Ce module ne vous est pas accessible",
            'initScript' => "",
            'classes' => 'ui-state-default ui-widget-header',
            'settings' => false,
        );
    }
}

// The widget settings handler.
function widget_statProject_settings() {
    return(array('markup' => '<div>Aucune option pour ce module</div>'));
}

// A silly little helper function for the form.
//function selected($data, $color) {
//  if ($data['color'] == $color) {
//    return ' value="' . $color . '" selected="selected"';
//  }
//  else {
//    return ' value="' . $color . '"';
//  }
//}
?>

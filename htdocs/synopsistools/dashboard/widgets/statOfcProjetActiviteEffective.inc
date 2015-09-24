<?php

/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT . "/projet/class/project.class.php");

// The get widget handler.
function widget_statOfcProjetActiviteEffective() {
    // A widget object as per jquery.dashboard.js.
    global $db, $conf, $langs, $user, $bc;

    if (isset($conf->global->MAIN_MODULE_SYNOPSISPROJET)) {
    die(MAIN_MODULE_SYNOPSISPROCESS);
        $socid = 0;
        if ($user->societe_id > 0) {
            $socid = $user->societe_id;
        }
        $projet = new Project($db);
        $langs->load("projects");

        $table = "<p>";
        $table2 = "<p>";

        $jsMainpath = DOL_URL_ROOT . "/synopsistools/dashboard";

        $js = ' <script src="' . $jsMainpath . '/swfobject.js" type="text/javascript"></script>';



        $js .= '<script type="text/javascript">';

        $js .= 'swfobject.embedSWF(';
        $js .= '"' . DOL_URL_ROOT . '/Synopsis_Common/open-flash-chart/open-flash-chart.swf", "ProjetActiviteEffective_chart", "100%", "350px",';
        $js .= '"9.0.0", "expressInstall.swf",';
        $js .= '{"data-file":"' . DOL_URL_ROOT . '/projet/activity/activity-pie-chart.php?dur=effective"} );';

        $js .= "var DOL_URL_ROOT='" . DOL_URL_ROOT . "'";
        $js .= '</script>';


        $table .= $js;

        $table .= '    <div id="ProjetActiviteEffective_chart"></div>';
        $table2 .= '    <table width=100% cellpadding=15><tr><td class="ui-widget-content" valign=top width=400><div id="ProjetActiviteEffective_chart1"></div>';
        $table2 .= '       <td valign=top class="ui-widget-content"><table width=100%>';
        $table2 .= '       <tr><th style="padding: 15px; font-size: 16px;" colspan=4 class="ui-widget-header ui-state-default">Les 50 derni&egrave;res t&acirc;ches modifi&eacute;es';

        $sql = "SELECT ifnull(sum(" . MAIN_DB_PREFIX . "Synopsis_projet_task_time_effective.task_duration_effective),0) as td,
                   " . MAIN_DB_PREFIX . "projet_task.fk_projet,
                   " . MAIN_DB_PREFIX . "projet_task.rowid,
                   " . MAIN_DB_PREFIX . "Synopsis_projet_view.title
              FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors,
                   " . MAIN_DB_PREFIX . "Synopsis_projet_view,
                   " . MAIN_DB_PREFIX . "projet_task
         LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_projet_task_time_effective ON " . MAIN_DB_PREFIX . "Synopsis_projet_task_time_effective.fk_task = " . MAIN_DB_PREFIX . "projet_task.rowid
             WHERE " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors.fk_projet_task = " . MAIN_DB_PREFIX . "projet_task.rowid
               AND " . MAIN_DB_PREFIX . "Synopsis_projet_view.rowid = " . MAIN_DB_PREFIX . "projet_task.fk_projet
          GROUP BY " . MAIN_DB_PREFIX . "projet_task.fk_projet
          ORDER BY " . MAIN_DB_PREFIX . "projet_task.tms
  ";
//    $table.= $sql;
        $sql .= $db->plimit(50);

        $result = $db->query($sql);
        if ($result) {
            $num = $db->num_rows($result);
            $i = 0;

            $var = true;
            while ($i < $num) {
                $objp = $db->fetch_object($result);
                $var = !$var;
                $table2 .= "<tr $bc[$var]>";
                $table2 .= '<td><a href="' . DOL_URL_ROOT . '/product/stats/card.php?id=' . $objp->rowid . '">' . $objp->title . "</a>";
                $table2 .= '<td>' . $projet->sec2time($objp->td);
                $i++;
            }
            $db->free();
        }
        $table2 .= "</table>";


        $table .="</p>";
        $table2 .="</p>";
        return array(
            'title' => 'Stats - Activit&eacute;s effectu&eacute;es',
            'content' => $table,
            'initScript' => "",
            'classes' => 'ui-state-default ui-widget-header',
            'settings' => false,
            'fullscreen' => $table2,
            'fullscreenInitScript' => DOL_URL_ROOT . '/synopsistools/dashboard/widgets/scripts/statOfcProjetActiviteEffective_chart.js',
            'fullscreenScript' => DOL_URL_ROOT . '/synopsistools/dashboard/widgets/scripts/statOfcProjetActiviteEffective_chart.js',
        );
    } else {
        return array(
            'title' => 'Module non activé',
            'content' => utf8_encodeRien("Ce module ne vous est pas accessible."),
            'initScript' => "",
            'classes' => 'ui-state-default ui-widget-header',
            'settings' => false,
        );
    }
}

// The widget settings handler.
function widget_statOfcProjetActiviteEffective_settings() {
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

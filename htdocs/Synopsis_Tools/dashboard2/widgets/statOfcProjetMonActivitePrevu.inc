<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");

// The get widget handler.
function widget_statOfcProjetMonActivitePrevu() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user,$bc;

    if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
    $projet=new Project($db);
    $langs->load("projects");

    $table ="<p>";
    $table2 ="<p>";

    $jsMainpath = DOL_URL_ROOT."/Babel_Common/js";

    $js = ' <script src="'.$jsMainpath.'/swfobject.js" type="text/javascript"></script>';



    $js .= '<script type="text/javascript">';

    $js .= 'swfobject.embedSWF(';
    $js .= '"'.DOL_URL_ROOT.'/Babel_Common/open-flash-chart/open-flash-chart.swf", "ProjetMonActivitePrevu_chart", "100%", "350px",';
    $js .= '"9.0.0", "expressInstall.swf",';
    $js .= '{"data-file":"'.DOL_URL_ROOT.'/projet/activity/myactivity-pie-chart.php?userId='.$user->id.'"} );';

    $js .= "var DOL_URL_ROOT='".DOL_URL_ROOT."';";
    $js .= "var userId='".$user->id."';";
    $js .= '</script>';


    $table .=  $js;

    $table .=  '    <div id="ProjetMonActivitePrevu_chart"></div>';
    $table2 .=  '    <table width=100% cellpadding=15><tr><td class="ui-widget-content" valign=top width=400><div id="ProjetMonActivitePrevu_chart1"></div>';
    $table2 .= '       <td valign=top class="ui-widget-content"><table width=100%>';
    $table2 .= '       <tr><th style="padding: 15px; font-size: 16px;" colspan=4 class="ui-widget-header ui-state-default">Les 50 derni&egrave;res t&acirc;ches modifi&eacute;es';

    $sql  = "SELECT ifnull(sum(babel_projet_task_time.task_duration),0) as td,
                   babel_projet_task.fk_projet,
                   babel_projet.title
              FROM babel_projet_task_actors,
                   babel_projet,
                   babel_projet_task
         LEFT JOIN babel_projet_task_time ON babel_projet_task_time.fk_task = babel_projet_task.rowid
             WHERE babel_projet_task_actors.fk_projet_task = babel_projet_task.rowid
               AND babel_projet_task_actors.fk_user = ".$user->id."
               AND babel_projet.rowid = babel_projet_task.fk_projet
          GROUP BY babel_projet_task.fk_projet";
//    $table.= $sql;
    $sql .= $db->plimit(50);

    $result=$db->query($sql) ;
    if ($result)
    {
      $num = $db->num_rows($result);
      $i = 0;

      $var=true;
      while ($i < $num)
      {
          $objp = $db->fetch_object($result);
          $var=!$var;
          $table2 .= "<tr $bc[$var]>";
          $table2 .= '<td><a href="'.DOL_URL_ROOT.'/product/stats/fiche.php?id='.$objp->rowid.'">'.$objp->title."</a>";
          $table2 .= '<td>'.$projet->sec2time($objp->td);
          $i++;
        }
      $db->free();
    }
    $table2 .= "</table>";


    $table .="</p>";
    $table2 .="</p>";
      return array(
        'title' => 'Stats - Mon activit&eacute; pr&eacute;vue',
        'content' => utf8_encode($table),
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
        'fullscreen' => utf8_encode($table2),
        'fullscreenInitScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/statOfcProjetMonActivitePrevu_chart.js',
        'fullscreenScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/statOfcProjetMonActivitePrevu_chart.js',
      );
}

// The widget settings handler.
function widget_statOfcProjetMonActivitePrevu_settings() {
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

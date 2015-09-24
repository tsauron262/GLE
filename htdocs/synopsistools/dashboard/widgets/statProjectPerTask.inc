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
  * Name : statProjectPerTask.php
  * GLE-1.2
  */
require_once("../../main.inc.php");
// The get widget handler.
require_once(DOL_DOCUMENT_ROOT.'/projet/class/project.class.php');

function widget_statProjectPerTask() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf, $user, $langs,$bc;
    if ($user->rights->projet->lire)
    {
        $socid = 0; if ($user->societe_id > 0)
        {
          $socid = $user->societe_id;
        }

        $langs->load("projects");
        $langs->load("companies");
        $langs->load("bills");
        $langs->load("orders");
        $langs->load("commercial");

        $table = '<p>';
        $table .= '<table class="noborder" width="100%">';
        $table .= '<tr class="liste_titre">';
        $table .= "<td>".$langs->trans("Project");
        $table .= '<td align="center">'.$langs->trans("NbOpenTasks").'</td>';
        $table .= "</tr>\n";

        $sql = "SELECT p.title, p.rowid, count(t.rowid)";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
        $sql .= " FROM ".MAIN_DB_PREFIX."Synopsis_projet_view as p";
        $sql .= " , ".MAIN_DB_PREFIX."projet_task as t";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= ", llxsociete_commerciaux as sc";
        $sql .= " WHERE t.fk_projet = p.rowid";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;

        if ($socid)
        {
          $sql .= " AND s.rowid = ".$socid;
        }
        $sql .= " GROUP BY p.rowid";

        $var=true;
        $resql = $db->query($sql);
        if ( $resql )
        {
          $num = $db->num_rows($resql);
          $i = 0;

          while ($i < $num)
            {
              $row = $db->fetch_row( $resql);
              $var=!$var;
              $table .= "<tr $bc[$var]>";
              $table .= '<td><a href="'.DOL_URL_ROOT.'/projet/tasks/card.php?id='.$row[1].'">'.$row[0].'</a></td>';
              $table .= '<td align="center">'.$row[2].'</td>';
              $table .= "</tr>\n";

              $i++;
            }

          $db->free($resql);
        }

        $table .="</p>";

      return array(
        'title' => 'Projets - Statistiques par t&acirc;che',
        'content' => $table,
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );
    } else {
      return array(
        'title' => 'Projets - Statistiques par t&acirc;che',
        'content' => "Ce module ne vous est pas accessible",
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );
    }
}

// The widget settings handler.
function widget_statProjectPerTask_settings() {
    return(array('markup' => '<div>Aucune option pour ce module</div>'));
}

if (!function_exists('sec2time'))
{
    function sec2time($sec){
        if (!is_numeric($sec))
        {
            $sec = 0;
        }
        $returnstring = " ";
        $days = intval($sec/86400);
        $hours = intval ( ($sec/3600) - ($days*24));
        $minutes = intval( ($sec - (($days*86400)+ ($hours*3600)))/60);
        $seconds = $sec - ( ($days*86400)+($hours*3600)+($minutes * 60));

        $returnstring .= ($days)?(($days == 1)? "1 j":$days."j"):"";
        $returnstring .= ($days && $hours && !$minutes && !$seconds)?"":"";
        $returnstring .= ($hours)?( ($hours == 1)?" 1h":" " .$hours."h"):"";
        $returnstring .= (($days || $hours) && ($minutes && !$seconds))?"  ":" ";
        $returnstring .= ($minutes)?( ($minutes == 1)?" 1 min":" ".$minutes."min"):"";
        //$returnstring .= (($days || $hours || $minutes) && $seconds)?" et ":" ";
        //$returnstring .= ($seconds)?( ($seconds == 1)?"1 second":"$seconds seconds"):"";
        return ($returnstring);
    }
}
if (!function_exists('sec2hour'))
{
    function sec2hour($sec){
        if (!is_numeric($sec))
        {
            $sec = 0;
        }
        $days=false;
        $returnstring = " ";
        $hours = intval ( ($sec/3600) );
        $minutes = intval( ($sec - ( ($hours*3600)))/60);
        $seconds = $sec - ( ($hours*3600)+($minutes * 60));

        $returnstring .= ($days)?(($days == 1)? "1 j":$days."j"):"";
        $returnstring .= ($days && $hours && !$minutes && !$seconds)?"":"";
        $returnstring .= ($hours)?( ($hours == 1)?" 1h":" " .$hours."h"):"";
        $returnstring .= (($days || $hours) && ($minutes && !$seconds))?"  ":" ";
        $returnstring .= ($minutes)?( ($minutes == 1)?" 1 min":" ".$minutes."min"):"";
        //$returnstring .= (($days || $hours || $minutes) && $seconds)?" et ":" ";
        //$returnstring .= ($seconds)?( ($seconds == 1)?"1 second":"$seconds seconds"):"";
        return ($returnstring);
    }

}




?>

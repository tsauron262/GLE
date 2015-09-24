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
  * Name : statProjectWarning.php GLE-1.2
  */
require_once("../../main.inc.php");
// The get widget handler.
require_once(DOL_DOCUMENT_ROOT.'/projet/class/project.class.php');

function widget_statProjectWarning() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf, $user, $langs,$bc;
    $var = false;
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

        $requete = "SELECT *
                      FROM ".MAIN_DB_PREFIX."Synopsis_projet_view
                     WHERE fk_statut = 0";
        $sql = $db->query($requete);
        $table .= "<table border = 1 style='border-collapse: collapse; text-align: center; width: 100%;'>";
        $table .= "<tr class='liste_titre'><td>Nom du projet</td><td>Message</td><td>D&eacute;pas.</td>";


        while ($res = $db->fetch_object($sql))
        {
            $projet = new Project($db);
            $projet->fetch($res->rowid);
            $requete1 = "SELECT t.title,
                                ifnull((SELECT SUM(task_duration_effective) FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_time_effective WHERE fk_task = t.rowid),0) as sduration_effective,
                                ifnull((SELECT SUM(task_duration) FROM ".MAIN_DB_PREFIX."projet_task_time WHERE fk_task = t.rowid),0) as sduration,
                                t.progress, t.rowid
                           FROM ".MAIN_DB_PREFIX."projet_task as t
                          WHERE t.fk_projet = ".$res->rowid;
        //1 affiche les taches warning :> débordement , diff % avancement et % de temps important
            $sql1 = $db->query($requete1);
            $html = "";
            $cnt=0;
            while ($res1 = $db->fetch_object($sql1))
            {
                if ($res1->sduration_effective > $res1->sduration)
                {
                    $html .= "<tr class='ui-state-error' style='color: #FF0084; padding: 4px; font-weight: 900;'>";
                    $html .= "    <td nowrap>D&eacute;bordement ".$projet->getTaskNomUrl($res1->rowid,1)  . "</td>";
                    $html .= "    <td>". $projet->sec2time($res1->sduration_effective - $res1->sduration) . "</td>";
                    $html .= "</tr>" ;
                    $cnt ++;
                }
                //warning
                $percAccompTime=0;
                if ($res1->sduration != 0)
                {
                    $percAccompTime = $res1->sduration_effective / $res1->sduration;
                }
        //        $table .= '<tr><td>'.$percAccompTime.'<td>'.$res1->progress;
                $delta = abs($percAccompTime*100 - $res1->progress);
                if ($delta > 20)
                {
                    $html .= "<tr class='ui-state-highlight' style='padding: 4px;  font-weight: 900;'>";
                    $html .= "    <td style='padding: 4px;'>Warning ".$projet->getTaskNomUrl($res1->rowid,1) . "</td>";
                    $html .= "    <td style='padding: 4px;'>". round($delta) . "%</td>";
                    $html .= "</tr>" ;
                    $cnt ++;
                }



            }
            $cnt++;
            $var = !$var;
            if ($cnt > 1)
            {
                $table .= "<tr><td ".$bc[$var]." colspan=1 rowspan=$cnt>".$projet->getNomUrl(1);
                $table .= $html;
            }

        }
        $table .= "</table>";
        $table .="</p>";

      return array(
        'title' => 'Projets - T&acirc;che en d&eacute;passement',
        'content' => $table,
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );
    } else {
      return array(
        'title' => 'Projets - T&acirc;che en d&eacute;passement',
        'content' => "Ce module ne vous est pas accessible",
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );
    }
}

// The widget settings handler.
function widget_statProjectWarning_settings() {
    return(array('markup' => '<div>Aucune option pour ce module</div>'));
}

?>

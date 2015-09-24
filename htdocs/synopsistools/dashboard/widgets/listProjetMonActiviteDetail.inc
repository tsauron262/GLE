<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/projet/class/project.class.php');
// The get widget handler.
function widget_listProjetMonActiviteDetail() {
  // A widget object as per jquery.dashboard.js.
    global $db,$user,$langs,$bc;

    $projet = new Project($db);

    // Defini les bornes date debut et fin de semaines, mois et annee pour le jour courant
    $now=time();
    $info=dol_getdate($now);
    $daystartw=$now-(($info['wday'] - 1)*24*3600);
    $dayendw  =$now+((7 - $info['wday'])*24*3600);
    $infostartw=dol_getdate($daystartw);
    $infoendw  =dol_getdate($dayendw);
    $datestartw=dol_mktime(0,0,0,$infostartw["mon"],$infostartw["mday"],$infostartw["year"]);
    $dateendw=dol_mktime(23,59,59,$infoendw["mon"],$infoendw["mday"],$infoendw["year"]);
    $datestartm=dol_mktime(0,0,0,$info["mon"],1,$info["year"]);
    $dateendm=dol_mktime(23,59,59,$info["mon"],30,$info["year"]);
    $datestarty=dol_mktime(0,0,0,1,1,$info["year"]);
    $dateendy=dol_mktime(23,59,59,12,31,$info["year"]);

    $socid = 0; if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
    $langs->load('projects');
  $table = '
<p>
<table width="100%" class="noborder"><tbody>';

    if ($user->rights->projet->lire)
    {

        /* Affichage de la liste des projets d'aujourd'hui */
        $table .= '<br /><table class="noborder" width="100%">';
        $table .= '<tr class="liste_titre">';
        $table .= '<td width="50%">'.$langs->trans('Today').'</td>';
        $table .= '<td width="50%" align="center">Temps</td>';
        $table .= "</tr>\n";

        $sql = "SELECT p.title, p.rowid, sum(tt.task_duration)";
        $sql .= " FROM ".MAIN_DB_PREFIX."Synopsis_projet_view as p";
        $sql .= " , ".MAIN_DB_PREFIX."projet_task as t";
        $sql .= " , ".MAIN_DB_PREFIX."projet_task_time as tt";
        $sql .= " WHERE t.fk_projet = p.rowid";
        $sql .= " AND tt.fk_task = t.rowid";
        $sql .= " AND tt.fk_user = ".$user->id;
        $sql .= " AND date_format(task_date,'%d%m%y') = ".strftime("%d%m%y",time());
        $sql .= " GROUP BY p.rowid";

        $var=true;
        $total=0;
        $resql = $db->query($sql);
        if ( $resql )
        {
          while ($row = $db->fetch_row($resql))
            {
              $var=!$var;
              $table .= "<tr $bc[$var]>";
              $table .= '<td><a href="'.DOL_URL_ROOT.'/projet/tasks/card.php?id='.$row[1].'">'.$row[0].'</a></td>';
              $table .= '<td align="center">'.$projet->sec2hour($row[2]).'</td>';
              $table .= "</tr>\n";
              $total += $row[2];
            }

          $db->free($resql);
        }
        else
        {
          dol_print_error($db);
        }
        $table .= '<tr class="liste_total">';
        $table .= '<td>'.$langs->trans('Total').'</td>';
        $table .= '<td align="center">'.$total.'</td>';
        $table .= "</tr>\n";
        $table .= "</table>";

        /* Affichage de la liste des projets d'hier */
        $table .= '<br /><table class="noborder" width="100%">';
        $table .= '<tr class="liste_titre">';
        $table .= '<td width="50%">'.$langs->trans('Yesterday').'</td>';
        $table .= '<td width="50%" align="center">Temps</td>';
        $table .= "</tr>\n";

        $sql = "SELECT p.title, p.rowid, sum(tt.task_duration)";
        $sql .= " FROM ".MAIN_DB_PREFIX."Synopsis_projet_view as p";
        $sql .= " , ".MAIN_DB_PREFIX."projet_task as t";
        $sql .= " , ".MAIN_DB_PREFIX."projet_task_time as tt";
        $sql .= " WHERE t.fk_projet = p.rowid";
        $sql .= " AND tt.fk_task = t.rowid";
        $sql .= " AND tt.fk_user = ".$user->id;
        $sql .= " AND date_format(date_add(task_date, INTERVAL 1 DAY),'%d%m%y') = ".strftime("%d%m%y",time());
        $sql .= " GROUP BY p.rowid";

        $var=true;
        $total=0;
        $resql = $db->query($sql);
        if ( $resql )
        {
          while ($row = $db->fetch_row($resql))
            {
              $var=!$var;
                $table .= "<tr $bc[$var]>";
              $table .= '<td><a href="'.DOL_URL_ROOT.'/projet/tasks/card.php?id='.$row[1].'">'.$row[0].'</a></td>';
              $table .= '<td align="center">'.$projet->sec2hour($row[2]).'</td>';
              $table .= "</tr>\n";
              $total += $row[2];
            }

          $db->free($resql);
        }
        $table .= "</table>";
            $table .= '</tbody></table></p>';

      return array(
        'title' => 'Projets - Mon activit&eacute; journali&egrave;re',
        'content' => $table,
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );
    } else {
      return array(
        'title' => 'Projets - Mon activit&eacute; journali&egrave;re',
        'content' => "Ce module ne vous est pas accessible",
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );

    }
}

// The widget settings handler.
function widget_listProjetMonActiviteDetail_settings() {
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

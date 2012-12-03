<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
require_once (DOL_DOCUMENT_ROOT."/Babel_GMAO/contratSAV.class.php");

// The get widget handler.
function widget_listContratSAVParDateFin() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user,$bc;
    if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
    $langs->load("contracts");

    $staticcontrat=new Contrat($db);
    $staticcompany = new Societe($db);
    $staticcontratligne=new ContratLigne($db);

      $table =  '<p>';
      $table2 = '</p>';



    if ($conf->contrat->enabled && $user->rights->contrat->lire)
    {
        $sql = "SELECT date_format(date_add(g.DateDeb,INTERVAL g.durValid MONTH),'%Y-%m-%d') as DateFin,
                       c.fk_contrat
                  FROM llx_contratdet as c LEFT JOIN babel_product as p ON c.fk_product = p.rowid,
                       Babel_GMAO_contratdet_prop as g LEFT JOIN Babel_product_serial_cont as s ON s.element_id = g.contratdet_refid AND s.element_type='contratSAV'
                 WHERE c.rowid = g.contratdet_refid
                   AND c.statut <> 5";
        $sql.= " ORDER BY DateFin ASC";
        $sql.= " LIMIT 50";
//print $sql;

        $result=$db->query($sql);
        if ($result)
        {
            $num = $db->num_rows($result);
            $i = 0;

            $table .=  '<table class="noborder" width="100%">';

            $table .=  '<tr class="liste_titre"><td colspan="2">Les 10 prochains contrats &agrave; &eacute;ch&eacute;ance</td>';
            $table .=  '<td align="center">'.$langs->trans("Date Fin.").'</td>';
            $table .=  "</tr>\n";

            $table2 .=  '<table class="noborder" width="100%">';
            $table2 .=  '<tr class="liste_titre"><td colspan="2">Les 50 prochains contrats &agrave; &eacute;ch&eacute;ance</td>';
            $table2 .=  '<td align="center">'.$langs->trans("Date Fin.").'</td>';
            $table2 .=  "</tr>\n";


            $var=true;
            while ($i < $num)
            {
                $obj = $db->fetch_object($result);
                $var=!$var;

                if ($i < 10)
                {
                    $table .=  "<tr ".$bc[$var].">";
                    $table .=  "<td>";
                    $staticcontrat->fetch($obj->fk_contrat);
                    $table .=  $staticcontrat->getNomUrl(1,16);
                    if (strtotime($obj->DateFin) < time()) $table .=  img_warning($langs->trans("Late"),false);
                    $table .=  '</td>';
                    $table .=  '<td>';
                    $staticcompany->fetch($staticcontrat->societe->id);
                    $table .=  $staticcompany->getNomUrl(1,'',20);
                    $table .=  '</td>';
                    $table .=  '<td align="center">'.dolibarr_print_date($obj->DateFin,'day').'</td>';
                    //$table .=  '<td align="left">'.$staticcontrat->LibStatut($obj->statut,2).'</td>';
                    $table .=  "</tr>\n";
                }

                $table2 .=  "<tr ".$bc[$var].">";
                $table2 .=  "<td>";
                $staticcontrat->fetch($obj->fk_contrat);
                $table2 .=  $staticcontrat->getNomUrl(1,16);
                if (strtotime($obj->DateFin)< time()) $table2 .=  img_warning($langs->trans("Late"));
                $table2 .=  '</td>';
                $table2 .=  '<td>';
                $staticcompany->id=$obj->socid;
                $staticcompany->fetch($obj->socid);
                $staticcompany->nom=$obj->nom;
                $table2 .=  $staticcompany->getNomUrl(1,'',20);
                $table2 .=  '</td>';
                $table2 .=  '<td align="center">'.dolibarr_print_date($obj->DateFin,'day').'</td>';
                $table2 .=  "</tr>\n";
                $i++;
            }
            $db->free($result);

            $table .=  "</table>";
            $table2 .=  "</table>";
        }

        $table .="</p>";
        $table2 .="</p>";
      return array(
        'title' => 'GMAO - 10 prochains contrats SAV &agrave &eacute;ch&eacute;ance',
        'content' => utf8_encode($table),
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
        'fullscreen' => utf8_encode($table2),
        'fullscreenScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/fullscreen.js',
        'fullscreenInitScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/initFullscreen.js',
      );

    } else {
      return array(
        'title' => 'GMAO - 10 derniers contrats SAV modifi&eacute;s',
        'content' => 'Vous ne possedez pas les droits requis pour ce module',
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );

    }
}

// The widget settings handler.
function widget_listContratSAVParDateFin_settings() {
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

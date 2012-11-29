<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
// The get widget handler.
function widget_listSAVAttenteClient() {
  // A widget object as per jquery.dashboard.js.
    global $db;
  $table = '

<p><table width="100%" class="noborder"><tbody>
    <tr class="liste_titre">
        <td colspan="3">Les 10 derniers produits en attente client</td>
    </tr>
</tr>';
    $requete = "SELECT UNIX_TIMESTAMP(date_create) as date_createF,
                       id,
                       descriptif_produit,
                       societe_refid
                  FROM Babel_GMAO_SAV_client
                 WHERE statut = 80
              ORDER BY date_create
                 LIMIT 10";
    $sql = $db->query($requete);
    $cssClass[true]='pair';
    $cssClass[false]='impair';
    $bool = false;
    while ($res =$db->fetch_object($sql))
    {
        $bool = !$bool;


        $table .= '<tr class="'.$cssClass[$bool].'">';
        $table .= '   <td style="overflow: hidden; white-space: nowrap;">';
        $soc = new Societe($db);
        $soc->fetch($res->societe_refid);
        $socname = $soc->getNomUrl(4);
        $table .= '       <a href="'.DOL_URL_ROOT.'/Babel_GMAO/SAV/fiche.php?id='.$res->id.'">'.$socname.'</a>';
        $table .= '   </td><td style="overflow: hidden; white-space: nowrap;"><a href="'.DOL_URL_ROOT.'/Babel_GMAO/SAV/fiche.php?id='.$res->id.'">';
        $table .= '        '.img_object("SAV",'sav');
        $table .= '            '.$res->descriptif_produit;
        $table .= '            </a>';
        $table .= '   </td><td style="overflow: hidden; white-space: nowrap;">'.date('d/m/Y',$res->date_createF);
        $table .= '    </td>';
        $table .= '</tr>';
    }
    $table .= '</tbody></table>';

    $table2 ='<p><table width="100%" class="noborder"><tbody><tr class="liste_titre"><td colspan="3">Les 50 derniers produits en attente du client</td></tr>';
    $requete = "SELECT UNIX_TIMESTAMP(date_create) as date_createF,
                       id,
                       descriptif_produit,
                       societe_refid
                  FROM Babel_GMAO_SAV_client
                 WHERE statut = 80
              ORDER BY date_create
                 LIMIT 50";
    $sql = $db->query($requete);
    $cssClass[true]='pair';
    $cssClass[false]='impair';
    $bool = false;
    while ($res =$db->fetch_object($sql))
    {
        $bool = !$bool;


        $table2 .= '<tr class="'.$cssClass[$bool].'">';
        $table2 .= '   <td style="overflow: hidden; white-space: nowrap;">';
        $soc = new Societe($db);
        $soc->fetch($res->societe_refid);
        $socname = $soc->getNomUrl(4);
        $table2 .= '       <a href="'.DOL_URL_ROOT.'/Babel_GMAO/SAV/fiche.php?id='.$res->id.'">'.$socname.'</a>';
        $table2 .= '   </td><td style="overflow: hidden; white-space: nowrap;"><a href="'.DOL_URL_ROOT.'/Babel_GMAO/SAV/fiche.php?id='.$res->id.'">';
        $table2 .= '        '.img_object('SAV','sav');
        $table2 .= '            '.$res->descriptif_produit;
        $table2 .= '            </a>';
        $table2 .= '   </td><td style="overflow: hidden; white-space: nowrap;">'.date('d/m/Y',$res->date_createF);
        $table2 .= '    </td>';
        $table2 .= '</tr>';
    }
    $table2.="</tbody></table>";
    $table .="</p>";
    $table2 .="</p>";


  return array(
    'title' => 'SAV - En attente du client',
    'content' => utf8_encode($table),
    'initScript' => "",
    'classes' => 'ui-state-default ui-widget-header',
    'settings' => false,
    'fullscreen' => utf8_encode($table2),
    'fullscreenScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/fullscreen.js',
    'fullscreenInitScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/initFullscreen.js',
  );
}

// The widget settings handler.
function widget_listSAVAttenteClient_settings() {
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

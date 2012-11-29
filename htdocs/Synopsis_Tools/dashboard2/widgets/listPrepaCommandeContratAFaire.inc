<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/commande/commande.class.php');
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");

// The get widget handler.
function widget_listPrepaCommandeContratAFaire() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user;
    if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
    $langs->load("orders");
    $formfile = new FormFile($db);
    $langs->load("compta");
    $langs->load("bills");

    $commandestatic=new Commande($db);


      $table =  '<p>';
      $table2 = '</p>';

    if ($conf->commande->enabled && $user->rights->commande->lire)
    {
        $sql = "SELECT distinct dt.fk_commande
                  FROM babel_product AS p,
                       llx_commandedet AS dt,
                       llx_commande as c
                 WHERE p.rowid = dt.fk_product
                   AND p.`fk_product_type` = 2
                   AND c.rowid = dt.fk_commande
                   AND dt.rowid NOT IN (SELECT cd.`fk_commande_ligne`
                                          FROM Babel_GMAO_contratdet_prop AS cdp,
                                               llx_contratdet  AS cd
                                         WHERE cd.rowid = cdp.contratdet_refid
                                           AND cd.`fk_commande_ligne` IS NOT NULL)";
        $sql .= " ORDER BY c.date_creation DESC LIMIT 50";

//$table .= $sql;

        $resql=$db->query($sql);
        if ($resql)
        {
            $total = 0;
            $total2 = 0;
            $ftotal = 0;
            $ftotal2 = 0;
            $num = $db->num_rows($resql);
            if ($num > 0)
            {

                $cssClass[true]='pair';
                $cssClass[false]='impair';
                $bool = false;
                $iter = 0;
                $table .= '<table class="noborder" width="100%">';
                $table .= "<tr class=\"liste_titre\">";
                $table .= "<td colspan=\"2\">".$langs->trans("Orders")."</td>";

                $table2 .= '<table class="noborder" width="100%">';
                $table2 .= "<tr class=\"liste_titre\">";
                $table2 .= "<td colspan=\"2\">".$langs->trans("Orders")."</td>";

                $bool = false;
                $arrCss[true]='pair';
                $arrCss[false]='impair';
                while ($res =$db->fetch_object($resql))
                {

                    $commandestatic->fetch($res->fk_commande);

                    $bool = !$bool;
                    if ($iter < 10)
                    {
                            $var=!$var;
                            $table .= '<tr class='.$arrCss[$bool].'>
                                    <td  nowrap="nowrap">';
                            $table .= $commandestatic->getNomUrl(1,5);
                            $table .= $formfile->html;

                            $table .=  '<td nowrap="nowrap">'.($commandestatic->societe?$commandestatic->societe->getNomUrl(1):'');
                    }
                    $var=!$var;
                    $table2 .= '<tr class='.$arrCss[$bool].'>
                            <td  nowrap="nowrap">';
                    $table2 .= $commandestatic->getNomUrl(1,5);

                    $table2 .=  '<td nowrap="nowrap">'.($commandestatic->societe?$commandestatic->societe->getNomUrl(1):'');
                    $iter++;
                }
                $table2 .=  "</table><br>";
            }
        }
        $table .="</p>";
        $table2 .="</p>";
      return array(
        'title' => 'Pr&eacute;paration commande - Derniers contrats &agrave; saisir',
        'content' => utf8_encode($table),
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
        'fullscreen' => utf8_encode($table2),
        'fullscreenScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/fullscreen.js',
        'fullscreenInitScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/initFullscreen.js',
      );

    }else {
      return array(
        'title' => 'Pr&eacute;paration commande - Derniers contrats &agrave; saisir',
        'content' => 'Vous ne possedez pas les droits requis pour ce module',
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );

    }
}

// The widget settings handler.
function widget_listPrepaCommandeContratAFaire_settings() {
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

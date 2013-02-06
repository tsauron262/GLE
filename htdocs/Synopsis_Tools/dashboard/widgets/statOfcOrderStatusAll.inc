<?php
/**
 * @file
 *    Defines the statOfcOrderStatusAll widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");

// The get widget handler.
function widget_statOfcOrderStatusAll() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user,$bc;

    if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }

    $langs->load("order");

    $table ="<p>";
    $table2 ="<p>";

    $jsMainpath = DOL_URL_ROOT."/Synopsis_Tools/dashboard";

    $js = ' <script src="'.$jsMainpath.'/swfobject.js" type="text/javascript"></script>';



    $js .= '<script type="text/javascript">';

    $js .= 'swfobject.embedSWF(';
    $js .= '"'.DOL_URL_ROOT.'/Babel_Common/open-flash-chart/open-flash-chart.swf", "statOfcOrderStatusAll_chart", "100%", "350px",';
    $js .= '"9.0.0", "expressInstall.swf",';
    $js .= '{"data-file":"'.DOL_URL_ROOT.'/Synopsis_Tools/dashboard/ajax/stat_statOfcOrderStatusAll_json.php"} );';

    $js .= "var DOL_URL_ROOT='".DOL_URL_ROOT."'";
    $js .= '</script>';


    $table .=  $js;

    $table .=  '    <div id="statOfcOrderStatusAll_chart"></div>';
    $table2 .=  '    <table width=100% cellpadding=15><tr><td class="ui-widget-content" valign=top width=400><div id="statOfcOrderStatusAll_chart1"></div>';
    $table2 .= '       <td valign=top class="ui-widget-content"><table width=100%><thead>';
    $table2 .= '       <tr><th style="padding: 15px; font-size: 16px;" colspan=4 class="ui-widget-header ui-state-default">Mes 20 meilleures commandes</thead><tbody><tr><td colspan=2>&nbsp</tbody>';

//    $requete = "SELECT *
//                FROM ".MAIN_DB_PREFIX."c_order_statut
//                WHERE active = 1";
//    $presql = $db->query($requete);
//    while ($resql = $db->fetch_object($presql))
    $tabStat = array(-1 => "StatusOrderCanceled", 0 => "StatusOrderDraft", 1 => "StatusOrderValidated", 2 => "StatusOrderOnProcess", 3 => "StatusOrderToBill", 4 => "StatusOrderProcessed");
    foreach($tabStat as $id => $label)
    {
        $trans = $label;
//        if ($trans == $langs->trans($trans))
//        $trans = $resql->label;
        $sql = "SELECT *
                FROM ".MAIN_DB_PREFIX."commande
                WHERE date_commande > date_sub(now(),interval 6 month)
                AND fk_statut = ".$id."
            ORDER BY total_ht DESC ";
/*                  AND fk_user_author = ".$user->id."
*/
        $sql .= $db->plimit(20);

        $result=$db->query($sql) ;
        if ($result)
        {
        $num = $db->num_rows($result);
        $i = 0;
        if ($num> 0)
            {
            $table2 .= "<thead><tr><th class='liste_titre' style='color: white;' colspan=4>" .$trans;
            $table2 .= "</thead><tbody>";
        }

        $var=true;
        while ($i < $num)
        {
            $objp = $db->fetch_object($result);
            $var=!$var;
            $table2 .= "<tr $bc[$var]>";
            $table2 .= '<td><a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$objp->rowid.'">';
            $table2 .= img_object($langs->trans("Order"),"order");
            $table2 .= " ";
            $table2 .= $objp->ref.'</a></td>';
            $table2 .= '<td>'.$objp->label.'</td>';
            $table2 .= '<td align="right">'.price($objp->total_ht).'&euro;</td>';
            $table2 .= "</tr>\n";
            $i++;
            }
        $db->free();
        }
    }
        $table2 .= "</table>";
        $table2 .= "</table>";

    $table .="</p>";
    $table2 .="</p>";
      return array(
        'title' => 'Stats - Toutes les commandes par status',
        'content' => $table,
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
        'fullscreen' => $table2,
        'fullscreenInitScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/statOfcOrderStatusAll_chart.js',
        'fullscreenScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/statOfcOrderStatusAll_chart.js',
      );
}

// The widget settings handler.
function widget_statOfcOrderStatusAll_settings() {
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

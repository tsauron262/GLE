<?php

/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");

// The get widget handler.
function widget_statOfcProduitContrat() {
    // A widget object as per jquery.dashboard.js.
    global $db, $conf, $langs, $user, $bc;

    $socid = 0; if ($user->societe_id > 0) {
        $socid = $user->societe_id;
    }

    $langs->load("products");

    $table = "<p>";
    $table2 = "<p>";

    $jsMainpath = DOL_URL_ROOT . "/Synopsis_Tools/dashboard";

    $js = ' <script src="' . $jsMainpath . '/swfobject.js" type="text/javascript"></script>';



    $js .= '<script type="text/javascript">';

    $js .= 'swfobject.embedSWF(';
    $js .= '"' . DOL_URL_ROOT . '/Synopsis_Common/open-flash-chart/open-flash-chart.swf", "productContrat_chart", "100%", "350px",';
    $js .= '"9.0.0", "expressInstall.swf",';
    $js .= '{"data-file":"' . DOL_URL_ROOT . '/Synopsis_Tools/dashboard/ajax/stat_produit_json.php?type=2"} );';

    $js .= "var DOL_URL_ROOT='" . DOL_URL_ROOT . "'";
    $js .= '</script>';


    $table .= $js;

    $table .= '    <div id="productContrat_chart"></div>';
    $table2 .= '    <table width=100% cellpadding=15><tr><td class="ui-widget-content" valign=top width=400><div id="productContrat_chart1"></div>';
    $table2 .= '       <td valign=top class="ui-widget-content"><table width=100%>';
    $table2 .= '       <tr><th style="padding: 15px; font-size: 16px;" colspan=4 class="ui-widget-header ui-state-default">Les 20 meilleurs ventes';


    $sql = "SELECT p.rowid, p.label, p.ref, fk_product_type, SUM(pd.qty)  c";
    $sql .= " FROM " . MAIN_DB_PREFIX . "commandedet as pd, " . MAIN_DB_PREFIX . "commande as pl, " . MAIN_DB_PREFIX . "product as p";
    $sql .= " WHERE p.rowid = pd.fk_product";
    $sql.= ' AND pl.rowid = pd.fk_commande';
    $sql.= ' AND pl.date_commande > date_sub(now(), interval 12 month)';
    $sql.= ' AND p.fk_product_type=2
            AND pl.fk_statut in (2,4)';
    $sql .= " GROUP BY (p.rowid)";
    $sql .= " ORDER BY c DESC ";
//    $table.= $sql;
    $sql .= $db->plimit(20);
    
    $result = $db->query($sql);
    if ($result) {
        $var = true;
        while ($objp = $db->fetch_object($result)) {
            $var = !$var;
            $table2 .= "<tr $bc[$var]>";
            $table2 .= '<td><a href="' . DOL_URL_ROOT . '/product/stats/fiche.php?id=' . $objp->rowid . '">';
            if ($objp->fk_product_type == 1)
                $table2 .= img_object($langs->trans("ShowService"), "service");
            else
                $table2 .= img_object($langs->trans("ShowProduct"), "product");
            $table2 .= " ";
            $table2 .= $objp->ref . '</a></td>';
            $table2 .= '<td>';
            if ($objp->type == 0)
                $table2 .= $langs->trans("ShowProduct");
            else
                $table2 .= $langs->trans("ShowService");
            $table2 .= '</td>';
            $table2 .= '<td>' . $objp->label . '</td>';
            $table2 .= '<td align="right">' . $objp->c . '</td>';
            $table2 .= "</tr>\n";
        }
        $db->free();
    }
    $table2 .= "</table>";
    $table2 .= "</table>";


    $table .="</p>";
    $table2 .="</p>";
    return array(
        'title' => 'Stats - 5 meilleures ventes de contrat',
        'content' => $table,
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
        'fullscreen' => $table2,
        'fullscreenInitScript' => DOL_URL_ROOT . '/Synopsis_Tools/dashboard/widgets/scripts/productContrat_chart.js',
        'fullscreenScript' => DOL_URL_ROOT . '/Synopsis_Tools/dashboard/widgets/scripts/productContrat_chart.js',
    );
}

// The widget settings handler.
function widget_statOfcProduitContrat_settings() {
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

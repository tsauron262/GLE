<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php');

// The get widget handler.
function widget_listService() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user,$bc;
    $socid = 0; if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
    $langs->load("products");

    $table ="<p>";
    $table2 ="<p>";
    require_once(DOL_DOCUMENT_ROOT.'/product/class/product.class.php');

    $staticproduct=new Product($db);


    $max=50;
    $sql = "SELECT p.rowid, p.label, p.price, p.ref, p.fk_product_type, p.tobuy,";
    $sql.= " tms as datem";
    $sql.= " FROM ".MAIN_DB_PREFIX."product as p";
    if ($conf->categorie->enabled && !$user->rights->categorie->voir)
    {
      $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_product as cp ON cp.fk_product = p.rowid";
      $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie as c ON cp.fk_categorie = c.rowid";
    }
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_association as sp ON p.rowid = sp.fk_product_fils";
    $sql.= " WHERE sp.fk_product_fils IS NULL AND p.fk_product_type = 1";
    if ($conf->categorie->enabled && !$user->rights->categorie->voir) $sql.= " AND IFNULL(c.visible,1)=1 ";
//    if (isset($_GET["type"])) $sql.= " AND p.fk_product_type = ".$_GET["type"];
    $sql.= " GROUP BY p.rowid ";
    $sql.= " ORDER BY p.tms DESC ";
    $sql.= $db->plimit($max,0);
    $result = $db->query($sql) ;

    if ($result)
    {
      $num = $db->num_rows($result);

      $i = 0;

      if ($num > 0)
      {
        $transRecordedType = $langs->trans("LastModifiedProductsAndServices",10);
        $transRecordedType2 = $langs->trans("LastModifiedProductsAndServices",$max);

        $table .= '<table class="noborder" width="100%">';
        $table .= '<tr class="liste_titre"><td colspan="4">'.$transRecordedType.'</td></tr>';

        $table2 .= '<table class="noborder" width="100%">';
        $table2 .= '<tr class="liste_titre"><td colspan="4">'.$transRecordedType2.'</td></tr>';

        $var=True;

          while ($i < $num)
        {
        $objp = $db->fetch_object($result);

        //Multilangs
        if ($conf->global->MAIN_MULTILANGS)
        {
            $sql = "SELECT label FROM ".MAIN_DB_PREFIX."product_det";
            $sql.= " WHERE fk_product=".$objp->rowid." AND lang='". $langs->getDefaultLang() ."'";
            $resultd = $db->query($sql);
            if ($resultd)
            {
                $objtp = $db->fetch_object($resultd);
                if ($objtp->label != '') $objp->label = $objtp->label;
            }
        }

        $var=!$var;
        if ($i < 10)
        {
            $table .= "<tr $bc[$var]>";
            $table .= '<td nowrap="nowrap">';
            $staticproduct->id=$objp->rowid;
            $staticproduct->ref=$objp->ref;
            $staticproduct->type=$objp->fk_product_type;
            $table .= $staticproduct->getNomUrl(1,'',16);
            $table .= "</td>\n";
            $table .= '<td>'.dol_trunc($objp->label,32).'</td>';
            $table .= "<td>";
            $table .= dol_print_date($objp->datem,'day');
            $table .= "</td>";
            $table .= '<td align="right" nowrap="nowrap">';
            $table .= $staticproduct->LibStatut($objp->tobuy,5);
            $table .= "</td>";
            $table .= "</tr>\n";
        }
        $table2 .= "<tr $bc[$var]>";
        $table2 .= '<td nowrap="nowrap">';
        $staticproduct->id=$objp->rowid;
        $staticproduct->ref=$objp->ref;
        $staticproduct->type=$objp->fk_product_type;
        $table2 .= $staticproduct->getNomUrl(1,'',16);
        $table2 .= "</td>\n";
        $table2 .= '<td>'.dol_trunc($objp->label,32).'</td>';
        $table2 .= "<td>";
        $table2 .= dol_print_date($objp->datem,'day');
        $table2 .= "</td>";
        $table2 .= '<td align="right" nowrap="nowrap">';
        $table2 .= $staticproduct->LibStatut($objp->tobuy,5);
        $table2 .= "</td>";
        $table2 .= "</tr>\n";

        $i++;
        }

          $db->free();

          $table .= "</table>";
        }
    }
    else
    {
      dol_print_error($db);
    }


    $table .="</p>";
    $table2 .="</p>";
      return array(
        'title' => 'Services - 10 derniers ajouts',
        'content' => $table,
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
        'fullscreen' => $table2,
        'fullscreenScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/fullscreen.js',
        'fullscreenInitScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/initFullscreen.js',
      );
}

// The widget settings handler.
function widget_listService_settings() {
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

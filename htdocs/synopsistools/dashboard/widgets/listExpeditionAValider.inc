<?php

/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
require_once (DOL_DOCUMENT_ROOT . "/expedition/class/expedition.class.php");

// The get widget handler.
function widget_listExpeditionAValider() {
    // A widget object as per jquery.dashboard.js.
    global $db, $conf, $langs, $user, $bc;
    if ($user->societe_id > 0) {
        $socid = $user->societe_id;
    }
    $langs->load("sendings");

    $table = '<p>';
    $table2 = '</p>';

    if ($conf->expedition->enabled && $user->rights->expedition->lire) {
        $clause = " WHERE ";
        /* $sql = "SELECT e.rowid, e.ref";
          $sql.= ", s.nom, s.rowid as socid";
          $sql.= ", c.ref as commande_ref, c.rowid as commande_id";
          $sql.= " FROM ".MAIN_DB_PREFIX."expedition as e";
          $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."co_exp as ce ON e.rowid = ce.fk_expedition";
          $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."commande as c ON ce.fk_commande = c.rowid";
          $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = e.fk_soc"; */
        $sql = "SELECT *, comm.rowid as rowid";
        $sql.= " FROM " . MAIN_DB_PREFIX . "commande comm, " . MAIN_DB_PREFIX . "element_element el, " . MAIN_DB_PREFIX . "Synopsis_commande comm2 ";
        $sql.= $clause . " el.fk_target > 0 AND el.fk_target != 17 AND comm.rowid = el.fk_source AND comm.rowid = comm2.rowid AND el.sourcetype = 'commande' AND el.targettype LIKE 'entrepot%'";
        $sql.= " AND fk_statut IN (1) AND comm.rowid NOT IN (SELECT fk_source 
FROM  `" . MAIN_DB_PREFIX . "element_element` 
WHERE  `sourcetype` LIKE  'commande'
AND  `targettype` LIKE  'shipping')";
        //$sql .= " AND fk_soc = ".$socid;
        $sql.= " ORDER BY date_valid desc";
        $sql.= " LIMIT 50";
        /* $sql.= "MINUS";
          $sql = "SELECT *";
          $sql.= " FROM ".MAIN_DB_PREFIX."co_exp"; */
        /* if (!$user->rights->societe->client->voir && !$socid)
          {
          $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON e.fk_soc = sc.fk_soc";
          $sql.= $clause." sc.fk_user = " .$user->id;
          $clause = " AND ";
          }
          if ($socid)
          {
          $sql .= " AND fk_soc = ".$socid;
          } */

        $result = $db->query($sql);
        if ($result) {
            $num = $db->num_rows($result);
            $i = 0;

            $table .= '<table class="noborder" width="100%">';

            //$table .=  '<tr class="liste_titre"><td colspan="1">'.$langs->trans("SendingsToValidate",30).'</td>';
            //$table .=  '<tr class="liste_titre"><td align="right" width="80" colspan="">'.$langs->trans("Soci&eacute;t&eacute;").'</td>';
            $table .= '<tr class="liste_titre"><td align="left" width="20" colspan="1">' . $langs->trans("Cli.") . '</td>';
            $table .= '<td align="left" width="20" colspan="1">' . $langs->trans("Comm.") . '</td>';
            $table .= '<td align="left" width="20" colspan="">' . $langs->trans("date livrais.") . '</td>';
            $table .= '<td align="left" width="40" colspan="">' . $langs->trans("Logist.") . '</td>';
            $table .= '<td align="left" width="20" colspan="">' . $langs->trans("Financ.") . '</td>';
            $table .= "</tr>\n";

            $table2 .= '<table class="noborder" width="100%">';

            //$table2 .=  '<tr class="liste_titre"><td colspan="1">'.$langs->trans("SendingsToValidate",30).'</td>';
            //$table2 .=  '<tr class="liste_titre"><td align="right" width="80" colspan="">'.$langs->trans("Soci&eacute;t&eacute;").'</td>';
            $table2 .= '<tr class="liste_titre"><td align="left" width="20" colspan="1">' . $langs->trans("Cli.") . '</td>';
            $table2 .= '<td align="left" width="20" colspan="1">' . $langs->trans("Comm.") . '</td>';
            $table2 .= '<td align="left" width="20" colspan="">' . $langs->trans("date livrais.") . '</td>';
            $table2 .= '<td align="left" width="40" colspan="">' . $langs->trans("Logist.") . '</td>';
            $table2 .= '<td align="left" width="20" colspan="">' . $langs->trans("Financ.") . '</td>';
            $table2 .= "</tr>\n";


            $var = true;
            while ($i < $num) {
                $obj = $db->fetch_object($result);
                $var = !$var;

                if ($obj->logistique_ok == 1) {
                    $log_ok = "OK";
                }
                if ($obj->logistique_ok == 0) {
                    $log_ok = "KO";
                }
                if ($obj->logistique_ok == 2) {
                    $log_ok = "Partiel";
                }
                if ($obj->logistique_statut == 1) {
                    $log_stat = "Valide";
                }
                if ($obj->logistique_statut == 0) {
                    $log_stat = "Temporaire";
                }
                if ($obj->finance_statut == 1) {
                    $fin_stat = "Valide";
                } else {
                    $fin_stat = "Temporaire";
                }
                if ($obj->finance_ok == 1) {
                    $fin_ok = "OK";
                } else {
                    $fin_ok = "KO";
                }


                $socStr = "";
                if ($obj->fk_soc) {
                    $soc = new Societe($db);
                    $soc->fetch($obj->fk_soc);
                    $socStr = $soc->getNomUrl(1, '', 20);
                }
                if ($i < 10) {
                    //$table .= "<tr $bc[$var]><td nowrap=\"nowrap\"><a href=\"card.php?id=".$obj->rowid."\">".$obj->ref."</a></td>";
                    //$table .= '<tr $bc[$var]><td nowrap=\"nowrap\"><a href="'.DOL_URL_ROOT.'/Synopsis_PrepaCommande/prepacommande.php?id='.$obj->commande_id.'">'.$obj->ref.'</a></td>';
                    //$table .= '<td><a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$obj->socid.'">'.$obj->nom.'</a></td>';
                    //$table .= '<td><a href="'.DOL_URL_ROOT.'/commande/card.php?id='.$obj->commande_id.'">'.$obj->commande_ref.'</a></td></tr>';
                    $table .= '<tr ' . $bc[$var] . '><td>' . $socStr . '</td>';
                    $table .= '<td><a href="' . DOL_URL_ROOT . '/Synopsis_PrepaCommande/prepacommande.php?id=' . $obj->rowid . '">' . $obj->ref . '</a></td>';
                    $table .= '<td>' . $obj->date_livraison . '</td>';
                    $table .= '<td>' . $log_ok . ' , ' . $log_stat . '</td>';
                    $table .= '<td>' . $fin_ok . ' , ' . $fin_stat . '</td></tr>';
                }
                //$table2 .= "<tr $bc[$var]><td nowrap=\"nowrap\"><a href=\"card.php?id=".$obj->rowid."\">".$obj->ref."</a></td>";
                //$table .= '<tr $bc[$var]><td nowrap=\"nowrap\"><a href="'.DOL_URL_ROOT.'/Synopsis_PrepaCommande/prepacommande.php?id='.$obj->commande_id.'">'.$obj->ref.'</a></td>';
                //$table2 .= '<td><a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$obj->socid.'">'.$obj->nom.'</a></td>';
                //$table2 .= '<td><a href="'.DOL_URL_ROOT.'/commande/card.php?id='.$obj->commande_id.'">'.$obj->commande_ref.'</a></td></tr>';

                $table2 .= '<tr ' . $bc[$var] . '><td>' . $socStr . '</td>';
                $table2 .= '<td><a href="' . DOL_URL_ROOT . '/Synopsis_PrepaCommande/prepacommande.php?id=' . $obj->rowid . '">' . $obj->ref . '</a></td>';
                $table2 .= '<td>' . $obj->date_livraison . '</td>';
                $table2 .= '<td>' . $log_ok . ', ' . $log_stat . '</td>';
                $table2 .= '<td>' . $fin_ok . ' , ' . $fin_stat . '</td></tr>';

                $i++;
            }
            $db->free($result);

            $table .= "</table>";
            $table2 .= "</table>";
        }

        $table .="</p>";
        $table2 .="</p>";
        return array(
            'title' => 'Exp&eacute;dition - 10 derni&egrave;res exp&eacute;ditions &agrave; valider',
            'content' => utf8_encode($table),
            'initScript' => "",
            'classes' => 'ui-state-default ui-widget-header',
            'settings' => false,
            'fullscreen' => utf8_encode($table2),
            'fullscreenScript' => DOL_URL_ROOT . '/Babel_Common/jquery/dashboard/widgets/scripts/fullscreen.js',
            'fullscreenInitScript' => DOL_URL_ROOT . '/Babel_Common/jquery/dashboard/widgets/scripts/initFullscreen.js',
        );
    } else {
        return array(
            'title' => 'Exp&eacute;dition - 10 derni&egrave;res exp&eacute;ditions &agrave; valider',
            'content' => 'Vous ne possedez pas les droits requis pour ce module',
            'initScript' => "",
            'classes' => 'ui-state-default ui-widget-header',
            'settings' => false,
        );
    }
}

// The widget settings handler.
function widget_listExpeditionAValider_settings() {
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

/*
// The get widget handler.
function widget_listExpeditionAValider() {
    // A widget object as per jquery.dashboard.js.
    global $db, $conf, $langs, $user, $bc;
    $socid = 0;
    if ($user->societe_id > 0) {
        $socid = $user->societe_id;
    }
    $langs->load("sendings");

    $table = '<p>';
    $table2 = '</p>';

    if ($conf->expedition->enabled && $user->rights->expedition->lire) {
        $staticcontrat = new Synopsis_Commande($db);
        $sql = "SELECT *, c.rowid as idContrat, e.rowid as idExpe";
        $sql.= " FROM " . MAIN_DB_PREFIX . "expedition as e";
        $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "element_element as ce ON e.rowid = ce.fk_target AND ce.sourcetype = 'commande' AND ce.targettype = 'shipping'";
        $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "commande as c ON ce.fk_source = c.rowid";
        $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_commande as sc ON ce.fk_source = sc.rowid";
        if (!$user->rights->societe->client->voir && !$socid) {
            $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON e.fk_soc = sc.fk_soc";
            $sql.= $clause . " sc.fk_user = " . $user->id;
            $clause = " AND ";
        }
        if ($socid) {
            $sql .= $clause . " c.fk_soc = " . $socid;
        }


        $sql.= " WHERE e.fk_statut = 0";
        $sql.= " GROUP BY e.rowid";
        $sql.= " ORDER BY e.rowid desc";
        $sql.= " LIMIT 50";

        $result = $db->query($sql);
        if ($result) {
            $num = $db->num_rows($result);
            $i = 0;

            $table .= '<table class="noborder" width="100%">';

            //$table .=  '<tr class="liste_titre"><td colspan="1">'.$langs->trans("SendingsToValidate",30).'</td>';
            //$table .=  '<tr class="liste_titre"><td align="right" width="80" colspan="">'.$langs->trans("Soci&eacute;t&eacute;").'</td>';
            $table .= '<tr class="liste_titre"><td align="left" width="20" colspan="1">' . $langs->trans("Comm.") . '</td>';
            $table .= '<td align="left" width="20" colspan="1">' . $langs->trans("Client") . '</td>';
            $table .= '<td align="left" width="20" colspan="">' . $langs->trans("date livrais.") . '</td>';
            $table .= '<td align="left" width="40" colspan="">' . $langs->trans("Logist.") . '</td>';
            $table .= '<td align="left" width="20" colspan="">' . $langs->trans("Financ.") . '</td>';
            $table .= "</tr>\n";

            $table2 .= '<table class="noborder" width="100%">';

            //$table2 .=  '<tr class="liste_titre"><td colspan="1">'.$langs->trans("SendingsToValidate",30).'</td>';
            //$table2 .=  '<tr class="liste_titre"><td align="right" width="80" colspan="">'.$langs->trans("Soci&eacute;t&eacute;").'</td>';
            $table2 .= '<tr class="liste_titre"><td align="left" width="20" colspan="1">' . $langs->trans("Expe.") . '</td>';
            $table2 .= '<td align="left" width="20" colspan="1">' . $langs->trans("Comm.") . '</td>';
            $table2 .= '<td align="left" width="20" colspan="1">' . $langs->trans("Client") . '</td>';
            $table2 .= '<td align="left" width="20" colspan="">' . $langs->trans("date livrais.") . '</td>';
            $table2 .= '<td align="left" width="40" colspan="">' . $langs->trans("Logist.") . '</td>';
            $table2 .= '<td align="left" width="20" colspan="">' . $langs->trans("Financ.") . '</td>';
            $table2 .= "</tr>\n";


            $var = true;
            while ($i < $num) {
                $obj = $db->fetch_object($result);
                $var = !$var;

                if ($obj->logistique_ok == 1) {
                    $log_ok = "OK";
                }
                if ($obj->logistique_ok == 0) {
                    $log_ok = "KO";
                }
                if ($obj->logistique_ok == 2) {
                    $log_ok = "Partiel";
                }
                if ($obj->logistique_statut == 1) {
                    $log_stat = "Valide";
                }
                if ($obj->logistique_statut == 0) {
                    $log_stat = "Temporaire";
                }
                if ($obj->finance_statut == 1) {
                    $fin_stat = "Valide";
                } else {
                    $fin_stat = "Temporaire";
                }
                if ($obj->finance_ok == 1) {
                    $fin_ok = "OK";
                } else {
                    $fin_ok = "KO";
                }
                $staticcontrat->fetch($obj->idContrat);
                $societe = new Societe($db);
                $societe->fetch($staticcontrat->socid);
                $expedition = new Expedition($db);
                $expedition->fetch($obj->idExpe);
//                    $staticcontrat->ref=$obj->ref;
//                    $staticcontrat->id=$obj->idContrat;

                if ($i < 10) {
                    //$table .= "<tr $bc[$var]><td nowrap=\"nowrap\"><a href=\"card.php?id=".$obj->rowid."\">".$obj->ref."</a></td>";
                    //$table .= '<tr $bc[$var]><td nowrap=\"nowrap\"><a href="'.DOL_URL_ROOT.'/Synopsis_PrepaCommande/prepacommande.php?id='.$obj->commande_id.'">'.$obj->ref.'</a></td>';
                    //$table .= '<td><a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$obj->socid.'">'.$obj->nom.'</a></td>';
                    //$table .= '<td><a href="'.DOL_URL_ROOT.'/commande/card.php?id='.$obj->commande_id.'">'.$obj->commande_ref.'</a></td></tr>';
                    $table .= '<tr ' . $bc[$var] . '' . '><td>' . $staticcontrat->getNomUrl(1, '', 15) . '</td>';
                    $table .= '<td>' . $societe->getNomUrl(1, '', 12) . '</td>';
                    $table .= '<td>' . $obj->date_livraison . '</td>';
                    $table .= '<td>' . $log_ok . ' , ' . $log_stat . '</td>';
                    $table .= '<td>' . $fin_ok . ' , ' . $fin_stat . '</td></tr>';
                }
                //$table2 .= "<tr $bc[$var]><td nowrap=\"nowrap\"><a href=\"card.php?id=".$obj->rowid."\">".$obj->ref."</a></td>";
                //$table .= '<tr $bc[$var]><td nowrap=\"nowrap\"><a href="'.DOL_URL_ROOT.'/Synopsis_PrepaCommande/prepacommande.php?id='.$obj->commande_id.'">'.$obj->ref.'</a></td>';
                //$table2 .= '<td><a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$obj->socid.'">'.$obj->nom.'</a></td>';
                //$table2 .= '<td><a href="'.DOL_URL_ROOT.'/commande/card.php?id='.$obj->commande_id.'">'.$obj->commande_ref.'</a></td></tr>';
                $table2 .= '<tr ' . $bc[$var] . '' . '><td>' . $expedition->getNomUrl(1, '') . '</td>';
                $table2 .= '<td>' . $staticcontrat->getNomUrl(1, '') . '</td>';
                $table2 .= '<td>' . $societe->getNomUrl(1) . '</td>';
                $table2 .= '<td>' . $obj->date_livraison . '</td>';
                $table2 .= '<td>' . $log_ok . ', ' . $log_stat . '</td>';
                $table2 .= '<td>' . $fin_ok . ' , ' . $fin_stat . '</td></tr>';

                $i++;
            }
            $db->free($result);

            $table .= "</table>";
            $table2 .= "</table>";
        }

        $table .="</p>";
        $table2 .="</p>";
        return array(
            'title' => 'Exp&eacute;dition - 10 derni&egrave;res exp&eacute;ditions &agrave; valider LLLL',
            'content' => $table,
            'initScript' => "",
            'classes' => 'ui-state-default ui-widget-header',
            'settings' => false,
            'fullscreen' => $table2,
            'fullscreenScript' => DOL_URL_ROOT . '/synopsistools/dashboard/widgets/scripts/fullscreen.js',
            'fullscreenInitScript' => DOL_URL_ROOT . '/synopsistools/dashboard/widgets/scripts/initFullscreen.js',
        );
    } else {
        return array(
            'title' => 'Exp&eacute;dition - 10 derni&egrave;res exp&eacute;ditions &agrave; valider',
            'content' => 'Vous ne possedez pas les droits requis pour ce module',
            'initScript' => "",
            'classes' => 'ui-state-default ui-widget-header',
            'settings' => false,
        );
    }
}

// The widget settings handler.
function widget_listExpeditionAValider_settings() {
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
*/
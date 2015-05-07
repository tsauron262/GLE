<?php
/*
 ** GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
require_once('../../main.inc.php');


 $action = $_REQUEST['action'];
 $campagne_id = $_REQUEST['campagneId'];

$offset =($_REQUEST['offset']?$_REQUEST['offset']:0);
$id = ($_REQUEST['id']?$_REQUEST['id']:"data_grid");
$page_size = ($_REQUEST['page_size']?$_REQUEST['page_size']:-1);

 switch ($action)
 {
    case 'add';
        require_once(DOL_DOCUMENT_ROOT.'/BabelProspect/Campagne.class.php');
        $socid = $_REQUEST['socid'];
        $listed = $_REQUEST['listed'];
        $obj = new CampagneSoc($db);
        if ($listed == 'unlisted')
        {
            $obj->create($socid,$campagne_id);
        } else {
            $obj->delete($socid,$campagne_id);
        }
    break;
    case 'listed':
    $requete = "SELECT ".MAIN_DB_PREFIX."societe.rowid,
                       ".MAIN_DB_PREFIX."societe.client,
                       ".MAIN_DB_PREFIX."societe.nom,
                       ".MAIN_DB_PREFIX."societe.ville,
                       ".MAIN_DB_PREFIX."societe.fk_effectif,
                       ".MAIN_DB_PREFIX."c_effectif.libelle as effectifStr,
                       ".MAIN_DB_PREFIX."societe.fk_departement,
                       CONCAT(".MAIN_DB_PREFIX."c_departements.code_departement,' ',".MAIN_DB_PREFIX."c_departements.nom)  as departmentStr,
                       ".MAIN_DB_PREFIX."societe.fk_secteur,
                       ".MAIN_DB_PREFIX."c_secteur.libelle AS secteurStr
                      FROM ".MAIN_DB_PREFIX."societe
                 LEFT JOIN ".MAIN_DB_PREFIX."c_country on ".MAIN_DB_PREFIX."c_country.rowid=".MAIN_DB_PREFIX."societe.fk_pays
                 LEFT JOIN ".MAIN_DB_PREFIX."c_typent on ".MAIN_DB_PREFIX."c_typent.id=".MAIN_DB_PREFIX."societe.fk_typent
                 LEFT JOIN ".MAIN_DB_PREFIX."c_forme_juridique on ".MAIN_DB_PREFIX."c_forme_juridique.rowid = ".MAIN_DB_PREFIX."societe.fk_forme_juridique
                 LEFT JOIN ".MAIN_DB_PREFIX."c_departements on ".MAIN_DB_PREFIX."c_departements.rowid = ".MAIN_DB_PREFIX."societe.fk_departement
                 LEFT JOIN ".MAIN_DB_PREFIX."c_effectif on ".MAIN_DB_PREFIX."c_effectif.id = ".MAIN_DB_PREFIX."societe.fk_effectif AND ".MAIN_DB_PREFIX."c_effectif.active = 1
                 LEFT JOIN ".MAIN_DB_PREFIX."c_prospectlevel on ".MAIN_DB_PREFIX."c_prospectlevel.sortorder = ".MAIN_DB_PREFIX."societe.fk_prospectlevel
                 LEFT JOIN ".MAIN_DB_PREFIX."c_stcomm on ".MAIN_DB_PREFIX."c_stcomm.id = ".MAIN_DB_PREFIX."societe.fk_stcomm
                 LEFT JOIN ".MAIN_DB_PREFIX."c_secteur on ".MAIN_DB_PREFIX."c_secteur.id = ".MAIN_DB_PREFIX."societe.fk_secteur  AND ".MAIN_DB_PREFIX."c_secteur.active = 1
                     WHERE client > 0
                       AND ".MAIN_DB_PREFIX."societe.rowid not in (SELECT societe_refid FROM Babel_campagne_societe WHERE id = ".$campagne_id.")
                  ORDER BY ".MAIN_DB_PREFIX."societe.nom ,
                           ".MAIN_DB_PREFIX."societe.client,
                           ".MAIN_DB_PREFIX."societe.ville,
                           ".MAIN_DB_PREFIX."societe.fk_departement,
                           ".MAIN_DB_PREFIX."societe.fk_effectif,
                           ".MAIN_DB_PREFIX."societe.fk_secteur ";
//                           print $requete;
//        print "<table>";
        header("Content-Type: text/xml");
        $xmlStr = '<'.'?xml version="1.0" encoding="UTF-8"?'.'>';

        print "<ajax-response>\n";
        print "\t<response type='object' id='".$id."_updater'>\n";
        print "\t\t<rows update_ui='true' offset='".$offset."'>\n";
        if ($resql = $db->query($requete))
        {
            while ($res=$db->fetch_object($resql))
            {
                print "\t\t\t<tr>
                           \t\t\t\t<td>".$res->rowid."</td>\n
                           \t\t\t\t<td>".$res->rowid."</td>\n
                           \t\t\t\t<td>".$res->client."</td>\n
                           \t\t\t\t<td>". utf8_encode($res->nom)."</td>\n
                           \t\t\t\t<td>". utf8_encode($res->ville)."</td>\n
                           \t\t\t\t<td>". utf8_encode($res->departmentStr)."</td>\n
                           \t\t\t\t<td>".$res->fk_effectif."</td>\n
                           \t\t\t\t<td>".$res->effectifStr."</td>\n
                           \t\t\t\t<td>".$res->fk_secteur."</td>\n
                           \t\t\t\t<td>".$res->secteurStr."</td></tr>\n";
            }
        }
        print "\t\t</rows>\n";
        print "\t</response>\n";
        print "</ajax-response>\n";

    break;
    case 'unlisted':
    $requete = "SELECT ".MAIN_DB_PREFIX."societe.rowid,
                       ".MAIN_DB_PREFIX."societe.client,
                       ".MAIN_DB_PREFIX."societe.nom,
                       ".MAIN_DB_PREFIX."societe.ville,
                       ".MAIN_DB_PREFIX."societe.fk_effectif,
                       ".MAIN_DB_PREFIX."c_effectif.libelle as effectifStr,
                       ".MAIN_DB_PREFIX."societe.fk_departement,
                       CONCAT(".MAIN_DB_PREFIX."c_departements.code_departement,' ',".MAIN_DB_PREFIX."c_departements.nom)  as departmentStr,
                       ".MAIN_DB_PREFIX."societe.fk_secteur
                       ".MAIN_DB_PREFIX."c_secteur.libelle AS secteurStr
                      FROM ".MAIN_DB_PREFIX."societe
                 LEFT JOIN ".MAIN_DB_PREFIX."c_country on ".MAIN_DB_PREFIX."c_country.rowid=".MAIN_DB_PREFIX."societe.fk_pays
                 LEFT JOIN ".MAIN_DB_PREFIX."c_typent on ".MAIN_DB_PREFIX."c_typent.id=".MAIN_DB_PREFIX."societe.fk_typent
                 LEFT JOIN ".MAIN_DB_PREFIX."c_forme_juridique on ".MAIN_DB_PREFIX."c_forme_juridique.rowid = ".MAIN_DB_PREFIX."societe.fk_forme_juridique
                 LEFT JOIN ".MAIN_DB_PREFIX."c_departements on ".MAIN_DB_PREFIX."c_departements.rowid = ".MAIN_DB_PREFIX."societe.fk_departement
                 LEFT JOIN ".MAIN_DB_PREFIX."c_effectif on ".MAIN_DB_PREFIX."c_effectif.id = ".MAIN_DB_PREFIX."societe.fk_effectif AND ".MAIN_DB_PREFIX."c_effectif.active = 1
                 LEFT JOIN ".MAIN_DB_PREFIX."c_prospectlevel on ".MAIN_DB_PREFIX."c_prospectlevel.sortorder = ".MAIN_DB_PREFIX."societe.fk_prospectlevel
                 LEFT JOIN ".MAIN_DB_PREFIX."c_stcomm on ".MAIN_DB_PREFIX."c_stcomm.id = ".MAIN_DB_PREFIX."societe.fk_stcomm
                 LEFT JOIN ".MAIN_DB_PREFIX."c_secteur on ".MAIN_DB_PREFIX."c_secteur.id = ".MAIN_DB_PREFIX."societe.fk_secteur  AND ".MAIN_DB_PREFIX."c_secteur.active = 1
                     WHERE client > 0
                       AND ".MAIN_DB_PREFIX."societe.rowid not in (SELECT societe_refid FROM Babel_campagne_societe WHERE id = ".$campagne_id.")
                  ORDER BY ".MAIN_DB_PREFIX."societe.nom ,
                           ".MAIN_DB_PREFIX."societe.client,
                           ".MAIN_DB_PREFIX."societe.ville,
                           ".MAIN_DB_PREFIX."societe.fk_departement,
                           ".MAIN_DB_PREFIX."societe.fk_effectif,
                           ".MAIN_DB_PREFIX."societe.fk_secteur ";
//                           print $requete;
//        print "<table>";
        header("Content-Type: text/xml");
        $xmlStr = '<'.'?xml version="1.0" encoding="UTF-8"?'.'>';

        print "<ajax-response>\n";
        print "\t<response type='object' id='".$id."_updater'>\n";
        print "\t\t<rows update_ui='true' offset='".$offset."'>\n";
        if ($resql = $db->query($requete))
        {
            while ($res=$db->fetch_object($resql))
            {
                print "\t\t\t<tr>
                           \t\t\t\t<td>".$res->rowid."</td>\n
                           \t\t\t\t<td>".$res->rowid."</td>\n
                           \t\t\t\t<td>".$res->client."</td>\n
                           \t\t\t\t<td>". utf8_encode($res->nom)."</td>\n
                           \t\t\t\t<td>". utf8_encode($res->ville)."</td>\n
                           \t\t\t\t<td>". utf8_encode($res->departmentStr)."</td>\n
                           \t\t\t\t<td>".$res->fk_effectif."</td>\n
                           \t\t\t\t<td>".$res->effectifStr."</td>\n
                           \t\t\t\t<td>".$res->fk_secteur."</td>\n
                           \t\t\t\t<td>".$res->secteurStr."</td></tr>\n";
            }
        }
        print "\t\t</rows>\n";
        print "\t</response>\n";
        print "</ajax-response>\n";

    break;

 }

?>

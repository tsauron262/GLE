<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 7-22-2009
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : ajax.php
  * GLE-1.0
  */

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
 $campagne_id = 1;

$offset =($_REQUEST['offset']?$_REQUEST['offset']:0);
$id = ($_REQUEST['id']?$_REQUEST['id']:"data_grid");
$page_size = ($_REQUEST['page_size']?$_REQUEST['page_size']:-1);

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
        print "<ajax>";

                print "\t\t\t                <tr>
                    <td></td>
                    <th>food</th>
                    <th>auto</th>
                    <th>household</th>
                    <th>furniture</th>
                    <th>kitchen</th>
                    <th>bath</th>
                </tr>
               <tr>
                    <th>Mary</th>
                    <td>190</td>
                    <td>160</td>
                    <td>40</td>
                    <td>120</td>
                    <td>30</td>
                    <td>70</td>
                </tr>
                <tr>
                    <th>Tom</th>
                    <td>3</td>
                    <td>40</td>
                    <td>30</td>
                    <td>45</td>
                    <td>35</td>
                    <td>49</td>
                </tr>
                <tr>
                    <th>Brad</th>
                    <td>10</td>
                    <td>180</td>
                    <td>10</td>
                    <td>85</td>
                    <td>25</td>
                    <td>79</td>
                </tr>
                <tr>
                    <th>Kate</th>
                    <td>40</td>
                    <td>80</td>
                    <td>90</td>
                    <td>25</td>
                    <td>15</td>
                    <td>119</td>
                </tr>       \n";
        print "</ajax>";

?>
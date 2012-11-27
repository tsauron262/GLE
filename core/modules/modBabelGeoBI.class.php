<?php
/*
 * GLE by Synopsis et DRSI
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

/**     \defgroup   JasperBabel     Module GeoBI
        \brief      Module pour inclure GeoBI dans GLE
*/

/**
        \file       htdocs/core/modules/modBabelGeoBI.class.php
        \ingroup    JasperBabel
        \brief      Fichier de description et activation du module GeoBI - Babel
*/

include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");

/**     \class      modProspectBabel
        \brief      Classe de description et activation du module de GeoBI
*/

class modBabelGeoBI extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'acces base
    */
    function modBabelGeoBI($DB)
    {
        $this->db = $DB ;
        $this->numero = 22235;

        $this->family = "OldGleModule";
        $this->name = "BabelGeoBI";
        $this->description = "GeoBI Synopsis et DRSI";
        $this->version = '0.1';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_BABELGEOBI';
        $this->special = 0;
        $this->picto='GEOBIBABEL';

        // Dir
        $this->dirs = array();

        // Config pages
        $this->config_page_url = "BabelGeoBI.php";

        // Dependences
        $this->depends = array("modBabelJasper");
        $this->requiredby = array();

        // Constantes
        $this->const = array();

        // Boites
        $this->boxes = array();

        // Permissions
        $this->rights = array();
        $this->rights_class = 'GeoBI'; //Max 12 lettres


        $r = 0;
        $this->rights[$r][0] = $this->numero."1";// this->numero ."". 1
        $this->rights[$r][1] = 'Affichage de BabelGeoBI';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'GeoBI'; // Famille
        $this->rights[$r][5] = 'Affiche'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."4";// this->numero ."". 2
        $this->rights[$r][1] = 'Administration du module BabelGeoBI';
        $this->rights[$r][2] = 'e'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'GeoBI'; // Famille
        $this->rights[$r][5] = 'Modifier'; // Droit
        $r ++;

        // Menus
        //------
        $r=0;

        $requete ="SELECT * FROM ".MAIN_DB_PREFIX."menu WHERE type='top' AND fk_menu=0 AND module='JasperBabel'";
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        if ($res->rowid > 0)
        {
            $this->menu[$r]=array('fk_menu'=>$res->rowid,
                                  'type'=>'left',
                                  'titre'=>'Geo - BI',
                                  'mainmenu'=>'jasperbabel',
                                  'leftmenu'=>'1',
                                  'url'=>'/Babel_GeoBI/index.php',
                                  'langs'=>'other',
                                  'position'=>99,
                                  'perms'=>'$user->rights->GeoBI->GeoBI->Affiche',
                                  'target'=>'',
                                  'user'=>0);
            $r++;
        }


  }

   /**
    *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
    *               Definit egalement les repertoires de donnees a creer pour ce module.
    */
  function init()
  {
    $address = MAIN_INFO_SOCIETE_ADRESSE;
    $ville = MAIN_INFO_SOCIETE_VILLE;
    $cp = MAIN_INFO_SOCIETE_CP;
    $pays = MAIN_INFO_SOCIETE_PAYS;

    $url = 'http://maps.google.com/maps/api/geocode/xml';

    $add = urlencode($address." ".$cp." ".$ville .",".$pays);
    $param = '?address='.$add.'&sensor=true';
//print $url.$param;
    $_curl = curl_init();
    curl_setopt($_curl, CURLOPT_URL,$url.$param);
    curl_setopt($_curl, CURLOPT_POST,           false);
    curl_setopt($_curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($_curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($_curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($_curl, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($_curl);
    $xml = new DOMDocument('1.0', 'utf-8');
    $xml->loadXML($response);

    if ($xml->getElementsByTagName('status')->item(0)->nodeValue!='ZERO_RESULTS')
    {
        $locNode = $xml->getElementsByTagName('result')->item(0)->getElementsByTagName('location')->item(0);
        $lat = $locNode->getElementsByTagName('lat')->item(0)->nodeValue;
        $lng = $locNode->getElementsByTagName('lng')->item(0)->nodeValue;

    } else {
        // nous voulons un joli affichage
        $lat=43.5269449;
        $lng=5.4412472;
    }


    dolibarr_set_const($this->db,'MAIN_MODULE_GEOBI_LATDEFAULT',$lat);
    dolibarr_set_const($this->db,'MAIN_MODULE_GEOBI_LNGDEFAULT',$lng);
    $sql = array();
    return $this->_init($sql);
  }

  /**
   *    \brief      Fonction appelee lors de la desactivation d'un module.
   *                Supprime de la base les constantes, boites et permissions du module.
   */
  function remove()
  {
    $sql = array();

    return $this->_remove($sql);
  }
}
?>

<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2008 Regis Houssin        <regis.houssin@capnetworks.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.*//*
  * GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Create on : 4-1-2009
  *
  * Infos on http://www.synopsis-erp.com
  *
  *//*
 *
 * $Id: interface_modOrangeHrm_BabelGeoBISync.class.php,
 * v 1.1 2008/01/06 20:33:49
 * hregis Exp $
 */

/**
        \file       htdocs/includes/triggers/interface_modPhenix_BabelGeoBISync.class.php
        \ingroup    phenix
        \brief      Fichier de gestion des triggers OrangeHrm
*/


/**
        \class      InterfaceBabelGeoBISync
        \brief      Classe des fonctions triggers des actions OrangeHrm
*/

class InterfaceGeoBIsync
{
    public $db;
    public $error;

    public $date;
    public $duree;
    public $texte;
    public $desc;

    /**
     *   \brief      Constructeur.
     *   \param      DB      Handler d'acces base
     */
    function InterfaceGeoBIsync($DB)
    {
        $this->db = $DB ;

        $this->name = "BabelGeoBISync";
        $this->family = "OldGleModule";
        $this->description = "Les triggers de ce composant permettent de recherche les positions GPS des clients";
        $this->version = '0.1';                        // 'experimental' or 'dolibarr' or version
    }

    /**
     *   \brief      Renvoi nom du lot de triggers
     *   \return     string      Nom du lot de triggers
     */
    function getName()
    {
        return $this->name;
    }

    /**
     *   \brief      Renvoi descriptif du lot de triggers
     *   \return     string      Descriptif du lot de triggers
     */
    function getDesc()
    {
        return $this->description;
    }

    /**
     *   \brief      Renvoi version du lot de triggers
     *   \return     string      Version du lot de triggers
     */
    function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'experimental') return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return GLE_VERSION;
        elseif ($this->version) return $this->version;
        else return $langs->trans("Unknown");
    }

    /**
     *      \brief      Fonction appelee lors du declenchement d'un evenement Dolibarr.
     *                  D'autres fonctions run_trigger peuvent etre presentes dans includes/triggers
     *      \param      action      Code de l'evenement
     *      \param      object      Objet concerne
     *      \param      user        Objet user
     *      \param      lang        Objet lang
     *      \param      conf        Objet conf
     *      \return     int         <0 si ko, 0 si aucune action faite, >0 si ok
     */
    function run_trigger($action,$object,$user,$langs,$conf)
    {

        //init nécéssaire à l'activation et à la descativaton de OrangeHrm
        //dans init, faire un populate du calendar

        // Mettre ici le code e executer en reaction de l'action
        if (!$langs) global $langs;
        $langs->load("synopsisGene@Synopsis_Tools");
        $db=$this->db;
        require_once(DOL_DOCUMENT_ROOT.'/hrm/hrm.class.php');
        $hrm = new hrm($db);

        switch ($action)
        {
            case 'COMPANY_CREATE':
                $iter = 0;
                $url = 'http://maps.google.com/maps/api/geocode/xml';
                $add = urlencode($soc->adresse_full .",".$soc->pays);
                $param = '?address='.$add.'&sensor=true';
                $_curl = curl_init();
                curl_setopt($_curl, CURLOPT_URL,$url.$param);
                curl_setopt($_curl, CURLOPT_POST,           false);
                curl_setopt($_curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($_curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($_curl, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($_curl, CURLOPT_TIMEOUT, 15);
                $response = curl_exec($_curl);
                $xml = new DOMDocument('1.0', 'utf-8');
                $xml->loadXML($response);
                while ($iter < 3)
               {

                if ($xml->getElementsByTagName('status')->item(0)->nodeValue!='ZERO_RESULTS'
                    && $xml->getElementsByTagName('status')->item(0)->nodeValue!='INVALID_REQUEST'
                    && !$xml->getElementsByTagName('result')->item(0)
                    && $xml->getElementsByTagName('GeocodeResponse')->length > 0)
                {
                    //var_dump($xml->getElementsByTagName('result')->item(0));
                    sleep(3);
                    $response = curl_exec($_curl);
                    $xml->loadXML($response);
                }
                $iter ++;
            }
            if ($xml->getElementsByTagName('status')->item(0)->nodeValue!='ZERO_RESULTS')
            {
                $add = urlencode($soc->ville .",".$soc->pays);
                $param = '?address='.$add.'&sensor=true';
            //print $url.$param;
                $_curl = curl_init();
                curl_setopt($_curl, CURLOPT_URL,$url.$param);
                curl_setopt($_curl, CURLOPT_POST,           false);
                curl_setopt($_curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($_curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($_curl, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($_curl, CURLOPT_TIMEOUT, 15);
                $response = curl_exec($_curl);
                $xml = new DOMDocument('1.0', 'utf-8');
                $xml->loadXML($response);
                while ($iter < 3)
               {

                if ($xml->getElementsByTagName('status')->item(0)->nodeValue!='ZERO_RESULTS'
                    && $xml->getElementsByTagName('status')->item(0)->nodeValue!='INVALID_REQUEST'
                    && !$xml->getElementsByTagName('result')->item(0)
                    && $xml->getElementsByTagName('GeocodeResponse')->length > 0)
                {
                    //var_dump($xml->getElementsByTagName('result')->item(0));
                    sleep(3);
                    $response = curl_exec($_curl);
                    $xml->loadXML($response);
                }
                $iter ++;

                }
            }

             //var_dump($xml);

            if ($xml->getElementsByTagName('status')->item(0)->nodeValue!='ZERO_RESULTS' && $xml->getElementsByTagName('status')->item(0)->nodeValue!='INVALID_REQUEST' && $xml->getElementsByTagName('result')->length>0)
            {
                $locNode = $xml->getElementsByTagName('result')->item(0)->getElementsByTagName('location')->item(0);
                $lat = $locNode->getElementsByTagName('lat')->item(0)->nodeValue;
                $lng = $locNode->getElementsByTagName('lng')->item(0)->nodeValue;
                $codeCountry='FRANCE';
                foreach( $xml->getElementsByTagName('result')->item(0)->getElementsByTagName('address_component') as $key=>$val)
                {
                    if ($val->getElementsByTagName('type')->item(0)->nodeValue == 'country' || $val->getElementsByTagName('type')->item(1)->nodeValue == 'country')
                    {
                        $codeCountry = $val->getElementsByTagName('short_name')->item(0)->nodeValue;
                    }
                }

                //if option save pour import global
                $requete = "INSERT INTO `Babel_GeoBI` (`lat`,`lng`,`socid`,`countryCode`,`label`)
                                 VALUES ('".$lat."','".$lng."',".$soc->id.", '".$codeCountry."', '".$soc->name."')";
                $sql =$db->query($requete);
            }
            break;
            case 'COMPANY_DELETE'://faux !! passe en deleted
            $requete = "DELETE FROM Babel_GeoBI WHERE socid = ".$object->id;
            $sql = $this->db->query($requete);
            break;
        }
        return 0;
    }
}
?>

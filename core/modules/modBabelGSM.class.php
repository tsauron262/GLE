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
/**     \defgroup   BabelGSM     Module BabelGSM
        \brief      Module pour inclure BabelGSM dans Dolibarr
*/

/**
        \file       htdocs/core/modules/modBabelGSM.class.php
        \ingroup    BabelGSM
        \brief      Fichier de description et activation du module   GSM - Babel
*/

include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");

/**     \class      modProspectBabel
        \brief      Classe de description et activation du module de Prospection Babel
*/

class modBabelGSM extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'acces base
    */
    function modBabelGSM($DB)
    {
        $this->db = $DB ;
        $this->numero = 22221;

        $this->family = "OldGleModule";
        $this->name = "BabelGSM";
        $this->description = "Module GSM par Synopsis et DRSI - beta<br/>Compatible S60 - iphone(partiel)";
        $this->version = '0.1';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_GSMBABEL';
        $this->special = 0;
        $this->picto='GSMBABEL';

        // Dir
        $this->dirs = array();

        // Config pages
        $this->config_page_url = "";

        // Dependences
        $this->depends = array("");
        $this->requiredby = array();

        // Constantes
        $this->const = array();

        // Boites
        $this->boxes = array();

        // Permissions
        $this->rights = array();
        $this->rights_class = 'BabelGSM'; //Max 12 lettres


        $r = 0;
        $s=1;
        $this->rights[$r][0] = $this->numero.$s;// this->numero ."". 1
        $this->rights[$r][1] = 'Affichage de BabelGSM';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'BabelGSM'; // Famille
        $this->rights[$r][5] = 'Affiche'; // Droit
        $r ++;
        $s ++;

        $this->rights[$r][0] = $this->numero.$s;// this->numero ."". 1
        $this->rights[$r][1] = 'Acces aux propales';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'BabelGSM_com'; // Famille
        $this->rights[$r][5] = 'AffichePropal'; // Droit
        $r ++;
        $s ++;

        $this->rights[$r][0] = $this->numero.$s;// this->numero ."". 1
        $this->rights[$r][1] = 'Acces aux factures';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'BabelGSM_ctrlGest'; // Famille
        $this->rights[$r][5] = 'AfficheFacture'; // Droit
        $r ++;
        $s ++;

        $this->rights[$r][0] = $this->numero.$s;// this->numero ."". 1
        $this->rights[$r][1] = 'Acces aux commandes';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'BabelGSM_com'; // Famille
        $this->rights[$r][5] = 'AfficheCommande'; // Droit
        $r ++;
        $s ++;

        $this->rights[$r][0] = $this->numero.$s;// this->numero ."". 1
        $this->rights[$r][1] = 'Acces aux commandes fournisseurs';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'BabelGSM_fourn'; // Famille
        $this->rights[$r][5] = 'AfficheCommandeFourn'; // Droit
        $r ++;
        $s ++;

        $this->rights[$r][0] = $this->numero.$s;// this->numero ."". 1
        $this->rights[$r][1] = 'Acces aux contacts';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'BabelGSM_com'; // Famille
        $this->rights[$r][5] = 'AfficheContact'; // Droit
        $r ++;
        $s ++;

        $this->rights[$r][0] = $this->numero.$s;// this->numero ."". 1
        $this->rights[$r][1] = 'Acces aux contrats';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'BabelGSM_com'; // Famille
        $this->rights[$r][5] = 'AfficheContrat'; // Droit
        $r ++;
        $s ++;

        $this->rights[$r][0] = $this->numero.$s;// this->numero ."". 1
        $this->rights[$r][1] = 'Acces aux documents';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'BabelGSM'; // Famille
        $this->rights[$r][5] = 'AfficheDocuments'; // Droit
        $r ++;
        $s ++;

        $this->rights[$r][0] = $this->numero.$s;// this->numero ."". 1
        $this->rights[$r][1] = 'Acces aux expedition';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'BabelGSM_com'; // Famille
        $this->rights[$r][5] = 'AfficheExpedition'; // Droit
        $r ++;
        $s ++;

        $this->rights[$r][0] = $this->numero.$s;// this->numero ."". 1
        $this->rights[$r][1] = 'Acces aux fournisseurs';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'BabelGSM_fourn'; // Famille
        $this->rights[$r][5] = 'AfficheFourn'; // Droit
        $r ++;
        $s ++;

        $this->rights[$r][0] = $this->numero.$s;// this->numero ."". 1
        $this->rights[$r][1] = 'Acces aux interventions';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'BabelGSM_tech'; // Famille
        $this->rights[$r][5] = 'AfficheIntervention'; // Droit
        $r ++;
        $s ++;

        $this->rights[$r][0] = $this->numero.$s;// this->numero ."". 1
        $this->rights[$r][1] = 'Acces aux livraisons';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'BabelGSM_com'; // Famille
        $this->rights[$r][5] = 'AfficheLivraison'; // Droit
        $r ++;
        $s ++;

        $this->rights[$r][0] = $this->numero.$s;// this->numero ."". 1
        $this->rights[$r][1] = 'Acces aux paiements';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'BabelGSM_ctrlGest'; // Famille
        $this->rights[$r][5] = 'AffichePaiement'; // Droit
        $r ++;
        $s ++;

        $this->rights[$r][0] = $this->numero.$s;// this->numero ."". 1
        $this->rights[$r][1] = 'Acces aux produits';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'BabelGSM_com'; // Famille
        $this->rights[$r][5] = 'AfficheProduit'; // Droit
        $r ++;
        $s ++;

        $this->rights[$r][0] = $this->numero.$s;// this->numero ."". 1
        $this->rights[$r][1] = 'Acces aux projets';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'BabelGSM_com'; // Famille
        $this->rights[$r][5] = 'AfficheProjet'; // Droit
        $r ++;
        $s ++;

        $this->rights[$r][0] = $this->numero.$s;// this->numero ."". 1
        $this->rights[$r][1] = 'Acces aux prospects';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'BabelGSM_com'; // Famille
        $this->rights[$r][5] = 'AfficheProspect'; // Droit
        $r ++;
        $s ++;

        $this->rights[$r][0] = $this->numero.$s;// this->numero ."". 1
        $this->rights[$r][1] = 'Acces aux clients';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'BabelGSM_com'; // Famille
        $this->rights[$r][5] = 'AfficheClient'; // Droit
        $r ++;
        $s ++;

        $this->rights[$r][0] = $this->numero.$s;// this->numero ."". 1
        $this->rights[$r][1] = 'Acces aux services';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'BabelGSM_com'; // Famille
        $this->rights[$r][5] = 'AfficheServices'; // Droit
        $r ++;
        $s ++;

        $this->rights[$r][0] = $this->numero.$s;// this->numero ."". 1
        $this->rights[$r][1] = 'Acces aux stocks';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'BabelGSM_com'; // Famille
        $this->rights[$r][5] = 'AfficheStock'; // Droit
        $r ++;
        $s ++;

        $this->rights[$r][0] = $this->numero.$s;// this->numero ."". 1
        $this->rights[$r][1] = 'Acces aux t&acirc;ches';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'BabelGSM_com'; // Famille
        $this->rights[$r][5] = 'AfficheTache'; // Droit
        $r ++;
        $s ++;

        $this->rights[$r][0] = $this->numero.$s;// this->numero ."". 1
        $this->rights[$r][1] = 'Acces aux marque-pages';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'BabelGSM'; // Famille
        $this->rights[$r][5] = 'AfficheBookmark'; // Droit
        $r ++;
        $s ++;

        $this->rights[$r][0] = $this->numero.$s;// this->numero ."". 1
        $this->rights[$r][1] = 'Acces aux modules BI';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'BabelGSM_ctrlGest'; // Famille
        $this->rights[$r][5] = 'AfficheBI'; // Droit
        $r ++;
        $s ++;


        // Menus
        //------
        $r=0;

//        $this->menu[$r]=array('fk_menu'=>0,'type'=>'top','titre'=>'BI','mainmenu'=>'jasperbabel','leftmenu'=>'0','url'=>'/BabelJasper/index.php','langs'=>'other','position'=>100,'perms'=>'','target'=>'','user'=>0);
//        $r++;

  }

   /**
    *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
    *               Definit egalement les repertoires de donnees a creer pour ce module.
    */
  function init()
  {



    // Create Dir
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

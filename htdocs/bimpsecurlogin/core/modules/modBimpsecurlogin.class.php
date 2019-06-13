<?php
/*
 * BIMP-ERP by Synopsis et DRSI
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

/**     \defgroup   ProspectBabel     Module ProspectBabel
  \brief      Module pour inclure ProspectBabel dans Dolibarr
 */
/**
  \file       htdocs/core/modules/modProspectBabel.class.php
  \ingroup    ProspectBabel
  \brief      Fichier de description et activation du module de Prospection Babel
 */
include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");

/**     \class      modProspectBabel
  \brief      Classe de description et activation du module de Prospection Babel
 */
class modBimpsecurlogin extends DolibarrModules {

    /**
     *   \brief      Constructeur. Definit les noms, constantes et boites
     *   \param      DB      handler d'acces base
     */
    function modBimpsecurlogin($DB) {
        $this->db = $DB;
        $this->numero = 8000;

        $this->family = "BIMP";
        $this->name = "bimpsecurlogin";
        $this->description = "Double sécurité login";
        $this->version = '0.1';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_BIMPSECURLOGIN';
        $this->special = 0;
        $this->picto = 'key';

        // Dir
        $this->dirs = array();

        // Config pages
        //$this->config_page_url = "";
        // Dependences
        $this->depends = array();
        $this->requiredby = array();

        // Constantes
        $this->const = array();
        
        
        $this->module_parts = array(
            'hooks' => array('main'),
            'triggers' => 1
        );
        
        $r = 0;


        $r++;

//        // Boites
//        $this->boxes = array();
        // Boites
//    $this->boxes = array();
//    $r=0;
//    $this->boxes[$r][1] = "box_deplacement.php";
//    $r++;
        // Permissions
        $this->rights = array();
        $this->rights_class = 'bimpsecurlogin';

//        $this->rights[1][0] = 161881;
//        $this->rights[1][1] = 'Generer les PDF contrats';
//        $this->rights[1][2] = 'r';
//        $this->rights[1][3] = 1;
//        $this->rights[1][4] = 'generate';
//
//        $this->rights[2][0] = 161882;
//        $this->rights[2][1] = 'Lire les PDF contrats';
//        $this->rights[2][2] = 'r';
//        $this->rights[2][3] = 1;
//        $this->rights[2][4] = 'read';
//
//        $this->rights[2][0] = 161883;
//        $this->rights[2][1] = 'Renouveller les contrats';
//        $this->rights[2][2] = 'r';
//        $this->rights[2][3] = 1;
//        $this->rights[2][4] = 'renouveller';
//
//        $this->rights[3][0] = 161884;
//        $this->rights[3][1] = 'Gérer les annexe';
//        $this->rights[3][2] = 'r';
//        $this->rights[3][3] = 1;
//        $this->rights[3][4] = 'annexe';
//
//
//        $r = 0;
//        $this->tabs = array('contract:+annexe:Annexe PDF:@monmodule:$user->rights->synopsiscontrat->annexe:/Synopsis_Contrat/annexes.php?id=__ID__',
//            'contract:+interv:Interventions:@monmodule:$user->rights->synopsiscontrat->generate:/Synopsis_Contrat/intervByContrat.php?id=__ID__',
//                /* 'contract:+tickets:Tickets:@monmodule:/Synopsis_Contrat/annexes.php?id=__ID__',
//                  'contract:+sav:SAV:@monmodule:/Babel_GMAO/savByContrat.php?id=__ID__' */                );
    }

    /**
     *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
     *               Definit egalement les repertoires de donnees e creer pour ce module.
     */
    function init() {
        global $conf;
        $sql = array(
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "bimp_secure_log` (
  `rowid` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `crypt` varchar(50) NOT NULL,
  `tms` datetime NOT NULL DEFAULT current_timestamp,
  `ip` varchar(50) NOT NULL
) ;");

        

        $extrafields = new ExtraFields($this->db);
        
        $extrafields->addExtraField('echec_auth', 'Echec d\'authentification consecutif', 'int', 900, 10, 'user');
        $extrafields->addExtraField('code_sms', 'Code SMS', 'varchar', 901, 10, 'user');
        $extrafields->addExtraField('phone_perso', 'Téléphone portable perso', 'password', 902, 20, 'user');
        $extrafields->addExtraField('mail_sec', 'Mail de secours', 'password', 903, 40, 'user');

        
        
        return $this->_init($sql);
    }

    /**
     *    \brief      Fonction appelee lors de la desactivation d'un module.
     *                Supprime de la base les constantes, boites et permissions du module.
     */
    function remove() {
        global $conf;
//         $extrafields = new ExtraFields($this->db);
//         $extrafields->delete('syntec', 'contrat');
//         $extrafields->delete('tacite', 'contrat');
//         $extrafields->delete('denounce', 'contrat');
//         $extrafields->delete('date_start', 'contrat');
//         $extrafields->delete('duree_mois', 'contrat');
//         $extrafields->delete('periodicity', 'contrat');
        $sql = array("DELETE FROM " . MAIN_DB_PREFIX . "document_model WHERE nom = '" . $this->const[0][2] . "' AND entity = " . $conf->entity);
        return $this->_remove($sql);
    }

}
?>
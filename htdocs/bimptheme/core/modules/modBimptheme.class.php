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
include_once(DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php");

/**     \class      modProspectBabel
  \brief      Classe de description et activation du module de Prospection Babel
 */
class modBimptheme extends DolibarrModules
{

    /**
     *   \brief      Constructeur. Definit les noms, constantes et boites
     *   \param      DB      handler d'acces base
     */
    function __construct($DB)
    {
        $this->db = $DB;
        $this->numero = 47477;

        $this->family = "Bimp";
        $this->name = "bimptheme";
        $this->description = "Theme BIMP";
        $this->version = '0.1';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_BIMPTHEME';
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
            'hooks' => array('main', 'initBimpLayout')
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
//    
//    
//    
        $this->menu = array();

        $r = 0;

        $this->menu[$r] = array(
            'fk_menu'  => '', // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode of parent menu
            'type'     => 'top', // This is a Left menu entry
            'titre'    => 'Changer de thème',
            'mainmenu' => '',
            'leftmenu' => '',
            'url'      => '/bimptheme/switch.php',
            'langs'    => '', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position' => 1000,
            'enabled'  => '1', // Define condition to show or hide menu entry. Use '$conf->monmodule->enabled' if entry must be visible if module is enabled.
            'perms'    => '1', // Use 'perms'=>'$user->rights->monmodule->level1->level2' if you want your menu with a permission rules
            'target'   => '',
            'user'     => 2);    // 0=Menu for internal users,1=external users, 2=both

        $r++;
        // Permissions
        $this->rights = array();
        $this->rights_class = 'bimptheme';

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
    function init($options = '')
    {
        global $conf;
        $sql = array();

        require_once(DOL_DOCUMENT_ROOT . "/bimpcore/Bimp_Lib.php");
        $name = 'module_version_' . strtolower($this->name);
        if (BimpCore::getConf($name, '') == "") {
            BimpCore::setConf($name, floatval($this->version));
        }

        if (!file_exists(DOL_DATA_ROOT . '/bimptheme')) {
            $error = BimpTools::makeDirectories('bimptheme', DOL_DATA_ROOT);
            if ($error) {
                setEventMessage($error, 'errors');
            }
        }

        return $this->_init($sql, $options);
    }

    /**
     *    \brief      Fonction appelee lors de la desactivation d'un module.
     *                Supprime de la base les constantes, boites et permissions du module.
     */
    function remove($options = '')
    {
        global $conf;
        return $this->_remove(array(), $options);
    }
}

?>
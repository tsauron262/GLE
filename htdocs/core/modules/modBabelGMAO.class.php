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

/**     \defgroup   ProspectBabel     Module GMAO
        \brief      Module pour inclure GMAO dans GLE
*/

/**
        \file       htdocs/core/modules/modProspectBabel.class.php
        \ingroup    ProspectBabel
        \brief      Fichier de description et activation du module de GMAO Babel
*/

include_once "DolibarrModules.class.php";

/**     \class      modBabelGMAO
        \brief      Classe de description et activation du module de GMAO Babel
*/

class modBabelGMAO extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'acces base
    */
    function modBabelGMAO($DB)
    {
        $this->db = $DB ;
        $this->numero = 222338;

        $this->family = "OldGleModule";
        $this->name = "BabelGMAO";
        $this->description = "GMAO";
        $this->version = '0.1';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_BABELGMAO';
        $this->special = 0;
        $this->picto='GMAO';

        // Dir
        $this->dirs = array();

        // Config pages
        $this->config_page_url = "Babel_GMAO.php";
//        $this->config_page_url = "";

        // Dependences
        $this->depends = array();
        $this->requiredby = array();

        // Constantes
        $this->const = array();

        // Permissions
        $this->rights = array();
        $this->rights_class = 'GMAO';

        $r = 0;
$this->tabs = array('contract:+annexe:Annexe PDF:@monmodule:/Synopsis_Contrat/annexes.php?id=__ID__',
			'contract:+interv:Interventions:@monmodule:/Synopsis_Contrat/intervByContrat.php?id=__ID__',
			'contract:+tickets:Tickets:@monmodule:/Synopsis_Contrat/annexes.php?id=__ID__',
			'contract:+sav:SAV:@monmodule:/Babel_GMAO/savByContrat.php?id=__ID__'); 

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Acc&egrave;s aux menus GMAO';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'all'; // Famille
        $this->rights[$r][5] = 'Affiche'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Acc&egrave;s au sous module SAV';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'SAV'; // Famille
        $this->rights[$r][5] = 'Affiche'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Creer une fiche SAV';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'SAV'; // Famille
        $this->rights[$r][5] = 'Creer'; // Droit
        $r ++;


        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Modifier une fiche SAV';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'SAV'; // Famille
        $this->rights[$r][5] = 'Modifie'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Cloture une fiche SAV';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'SAV'; // Famille
        $this->rights[$r][5] = 'Cloture'; // Droit
        $r ++;


        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Acc&egrave;s au sous module Ticket';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'Tkt'; // Famille
        $this->rights[$r][5] = 'Affiche'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Acc&egrave;s aux statistiques Ticket';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'Tkt'; // Famille
        $this->rights[$r][5] = 'Stats'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Acc&egrave;s au sous module Retour';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'Retour'; // Famille
        $this->rights[$r][5] = 'Affiche'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Creer un retour';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'Retour'; // Famille
        $this->rights[$r][5] = 'Creer'; // Droit
        $r ++;


        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Acc&egrave;s au sous module Litige';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'Litige'; // Famille
        $this->rights[$r][5] = 'Affiche'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Telecharger un contrat';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'contrat'; // Famille
        $this->rights[$r][5] = 'Telecharger'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Generer un contrat';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'contrat'; // Famille
        $this->rights[$r][5] = 'generate'; // Droit
        $r ++;




    // Menus
        //------
        $this->menus = array();            // List of menus to add
        $r=0;
//TODO menu
//         Top menu
        $this->menu[$r]=array('fk_menu'=>0,
                            'type'=>'top',
                            'titre'=>'GMAO',
                            'mainmenu'=>'GMAO',
                            'leftmenu'=>'1',        // To say if we can overwrite leftmenu
                            'url'=>'/Babel_GMAO/index.php',
                            'langs' => 'synopsisGene@Synopsis_Tools',
                            'position'=>20,
                            'perms'=>'$user->rights->GMAO->all->Affiche',
                            'target'=>'',
                            'user'=>0);
        $r++;

//
        $this->menu[$r]=array('fk_menu'=>'r=0',
                            'type'=>'left',
                            'titre'=>'GMAO',
                            'mainmenu'=>'GMAO',
                            'url'=>'/Babel_GMAO/index.php',
                            'langs' => 'synopsisGene@Synopsis_Tools',
                            'position'=>1,
                            'perms'=>'$user->rights->GMAO->all->Affiche',
                            'target'=>'',
                            'user'=>0);
        $s0 = $r;
        $r++;

        $this->menu[$r]=array('fk_menu'=>'r=0',
                            'type'=>'left',
                            'titre'=>'Tickets',
                            'mainmenu'=>'GMAO',
                            'url'=>'/Babel_GMAO/iframe.php',
                            'langs' => 'synopsisGene@Synopsis_Tools',
                            'position'=>2,
                            'perms'=>'$user->rights->GMAO->Tkt->Affiche',
                            'target'=>'',
                            'user'=>0);
        $s =$r;
        $r++;
        $this->menu[$r]=array('fk_menu'=>'r=0',
                            'type'=>'left',
                            'titre'=>'Litiges',
                            'mainmenu'=>'GMAO',
                            'url'=>'/Babel_GMAO/Litiges/index.php',
                            'langs' => 'synopsisGene@Synopsis_Tools',
                            'position'=>3,
                            'perms'=>'$user->rights->GMAO->Litige->Affiche',
                            'target'=>'',
                            'user'=>0);
        $s1 = $r;
        $r++;




        $this->menu[$r]=array('fk_menu'=>'r='.$s0,
                            'type'=>'left',
                            'titre'=>'CreateFiche',
                            'mainmenu'=>'GMAO',
                            'url'=>'/Babel_GMAO/SAV/create.php',
                            'langs' => 'synopsisGene@Synopsis_Tools',
                            'position'=>1,
                            'perms'=>'$user->rights->GMAO->SAV->Creer',
                            'target'=>'',
                            'user'=>0);
        $r++;

        $this->menu[$r]=array('fk_menu'=>'r='.$s0,
                            'type'=>'left',
                            'titre'=>'SAV',
                            'mainmenu'=>'GMAO',
                            'url'=>'/Babel_GMAO/SAV/index.php',
                            'langs' => 'synopsisGene@Synopsis_Tools',
                            'position'=>2,
                            'perms'=>'$user->rights->GMAO->SAV->Affiche',
                            'target'=>'',
                            'user'=>0);
        $r++;

        $this->menu[$r]=array('fk_menu'=>'r='.$s,
                            'type'=>'left',
                            'titre'=>'Int. Tickets',
                            'mainmenu'=>'GMAO',
                            'url'=>'/Babel_GMAO/iframe.php',
                            'langs' => 'synopsisGene@Synopsis_Tools',
                            'position'=>1,
                            'perms'=>'$user->rights->GMAO->Tkt->Affiche',
                            'target'=>'',
                            'user'=>0);
        $r++;

        $this->menu[$r]=array('fk_menu'=>'r='.$s,
                            'type'=>'left',
                            'titre'=>'Stats',
                            'mainmenu'=>'GMAO',
                            'url'=>'/Babel_GMAO/statsTicket.php',
                            'langs' => 'synopsisGene@Synopsis_Tools',
                            'position'=>2,
                            'perms'=>'$user->rights->GMAO->Tkt->Stats',
                            'target'=>'',
                            'user'=>0);
        $r++;

        $this->menu[$r]=array('fk_menu'=>'r='.$s1,
                            'type'=>'left',
                            'titre'=>'Liste',
                            'mainmenu'=>'GMAO',
                            'url'=>'/Babel_GMAO/litiges/index.php?action=list',
                            'langs' => 'synopsisGene@Synopsis_Tools',
                            'position'=>1,
                            'perms'=>'$user->rights->GMAO->Litige->Affiche',
                            'target'=>'',
                            'user'=>0);
        $r++;

        $this->menu[$r]=array('fk_menu'=>'r=0',
                            'type'=>'left',
                            'titre'=>'Retours',
                            'mainmenu'=>'GMAO',
                            'url'=>'/Babel_GMAO/Retours/index.php',
                            'langs' => 'synopsisGene@Synopsis_Tools',
                            'position'=>4,
                            'perms'=>'$user->rights->GMAO->Retour->Affiche',
                            'target'=>'',
                            'user'=>0);
        $s2 = $r;
        $r++;
        $this->menu[$r]=array('fk_menu'=>'r='.$s2,
                            'type'=>'left',
                            'titre'=>'Nouveau',
                            'mainmenu'=>'GMAO',
                            'url'=>'/Babel_GMAO/Retours/index.php?action=list',
                            'langs' => 'synopsisGene@Synopsis_Tools',
                            'position'=>1,
                            'perms'=>'$user->rights->GMAO->Retour->Affiche',
                            'target'=>'',
                            'user'=>0);
        $r++;
        $this->menu[$r]=array('fk_menu'=>'r='.$s2,
                            'type'=>'left',
                            'titre'=>'Liste',
                            'mainmenu'=>'GMAO',
                            'url'=>'/Babel_GMAO/Retours/nouveau.php',
                            'langs' => 'synopsisGene@Synopsis_Tools',
                            'position'=>2,
                            'perms'=>'$user->rights->GMAO->Retour->Creer',
                            'target'=>'',
                            'user'=>0);
        $r++;

    }
   /**
    *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
    *               Definit egalement les repertoires de donnees e creer pour ce module.
    */
  function init()
  {
    $sql = array();
$requete = "INSERT INTO `".MAIN_DB_PREFIX."menu` (`rowid`,`menu_handler`,`module`,`type`,`mainmenu`,`fk_menu`,`position`,`url`,`target`,`titre`,`langs`,`level`,`leftmenu`,`perms`,`user`)
VALUES
    (2223587, 'auguria', NULL, 'left', 'GMAO', 5046, 1, '/product/index.php?leftmenu=service&type=2', '', 'Contrat', 'products', 0, '', '$user->rights->produit->lire', 2),
    (2223588, 'auguria', NULL, 'left', 'GMAO', 2223587, 0, '/product/fiche.php?leftmenu=service&action=create&type=2', '', 'NewContrat', 'products', 1, '', '$user->rights->produit->creer', 2),
    (2223589, 'auguria', NULL, 'left', 'GMAO', 2223587, 1, '/product/liste.php?leftmenu=service&type=2', '', 'List', 'products', 1, '', '$user->rights->GMAO->lire', 2);
";
    $res = $this->db->query($requete);
    return $this->_init($sql);
  }

  /**
   *    \brief      Fonction appelee lors de la desactivation d'un module.
   *                Supprime de la base les constantes, boites et permissions du module.
   */
  function remove()
  {
    $sql = array();
    $requete = "DELETE FROM ".MAIN_DB_PREFIX."menu WHERE module='GMAO'";
    $this->db->query($requete);
    return $this->_remove($sql);
  }
}
?>

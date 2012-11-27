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

/**     \defgroup   ProspectBabel     Module ProspectBabel
        \brief      Module pour inclure ProspectBabel dans Dolibarr
*/

/**
        \file       htdocs/core/modules/modProspectBabel.class.php
        \ingroup    ProspectBabel
        \brief      Fichier de description et activation du module de Prospection Babel
*/

include_once "DolibarrModules.class.php";

/**     \class      modProspectBabel
        \brief      Classe de description et activation du module de Prospection Babel
*/

class modBabelTechPeople extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'acces base
    */
    function modBabelTechPeople($DB)
    {
        $this->db = $DB ;
        $this->numero = 22228 ;

        $this->family = "OldGleModule";
        $this->name = "BabelTechPeople";
        $this->description = "Menu pour les non commerciaux";
        $this->version = '0.1';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_BABELTECHPEOPLE';
        $this->special = 0;
        $this->picto='TECHPEOPLE';

        // Dir
        $this->dirs = array();

        // Config pages
        $this->config_page_url = "Babel_TechPeople.php";
//        $this->config_page_url = "";

        // Dependences
        $this->depends = array("modDeplacement");
        $this->requiredby = array();

        // Constantes
        $this->const = array();

//        // Boites
//        $this->boxes = array();

    // Boites
    $this->boxes = array();
    $r=0;
    $this->boxes[$r][1] = "box_deplacement.php";
    $r++;


        // Permissions
        $this->rights = array();
        $this->rights_class = 'TechPeople';

        $r = 0;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Affichage  menu TechPeople';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'all'; // Famille
        $this->rights[$r][5] = 'AfficheMenu'; // Droit
        $r ++;


        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Affichage des ndf';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'ndf'; // Famille
        $this->rights[$r][5] = 'AfficheMien'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Affiche toutes les ndf';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'ndf'; // Famille
        $this->rights[$r][5] = 'Affiche'; // Droit
        $r ++;



        $this->rights[$r][0] = $this->numero."$r";// this->numero ."". 1
        $this->rights[$r][1] = 'Valide des ndf';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'ndf'; // Famille
        $this->rights[$r][5] = 'Valide'; // Droit
        $r ++;



    // Menus
        //------
        $this->menus = array();            // List of menus to add
        $r=0;
//TODO position
        // Top menu
        $this->menu[$r]=array('fk_menu'=>0,
                            'type'=>'top',
                            'titre'=>'MenuTechPeople',
                            'mainmenu'=>'techpeople',
                            'leftmenu'=>'1',        // To say if we can overwrite leftmenu
                            'url'=>'/Babel_TechPeople/index.php',
                            'langs' => 'synopsisGene@Synopsis_Tools',
                            'position'=>110,
                            'perms'=>'$user->rights->TechPeople->all->AfficheMenu',
                            'target'=>'',
                            'user'=>0);
        $r++;

        // Left menu linked to top menu

        //index d module deplacement

        $this->menu[$r]=array('fk_menu'=>'r=0',
                            'type'=>'left',
                            'titre'=>'Deplacements',
                            'mainmenu'=>'techpeople',
                            'url'=>'/Babel_TechPeople/deplacements/index.php?leftmenu=deplacement',
                            'langs' => 'synopsisGene@Synopsis_Tools',
                            'position'=>1,
                            'perms'=>'$user->rights->TechPeople->ndf->AfficheMien || $user->rights->TechPeople->ndf->Affiche',
                            'target'=>'',
                            'user'=>0);
        $r++;

//        1er lien
        $this->menu[$r]=array('fk_menu'=>'r=1',
                            'type'=>'left',
                            'titre'=>'Faire une NDF',
                            'mainmenu'=>'techpeople',
                            'url'=>'/Babel_TechPeople/deplacements/fiche.php?action=create&leftmenu=deplacement',
                            'langs' => 'synopsisGene@Synopsis_Tools',
                            'position'=>1,
                            'perms'=>'$user->rights->TechPeople->ndf->AfficheMien',
                            'target'=>'',
                            'user'=>0,
                            'constraints' => array( 0 => '$leftmenu==deplacement' ));
        $r++;
        $this->menu[$r]=array('fk_menu'=>'r=1',
                            'type'=>'left',
                            'titre'=>'Voir les NDF',
                            'mainmenu'=>'techpeople',
                            'url'=>'/Babel_TechPeople/deplacements/index.php?showall=1&leftmenu=deplacement',
                            'langs' => 'synopsisGene@Synopsis_Tools',
                            'position'=>2,
                            'perms'=>'$user->rights->TechPeople->ndf->Affiche',
                            'target'=>'',
                            'user'=>0,
                            'constraints' => array( 0 => '$leftmenu==deplacement' ));
        $r++;


        //index menu intervention


        // Left menu linked to top menu
        $this->menu[$r]=array('fk_menu'=>'r=0',
                            'type'=>'left',
                            'titre'=>'Interventions',
                            'mainmenu'=>'techpeople',
                            'url'=>'/fichinter/index.php?leftmenu=ficheinter',
                            'langs'=>'interventions',
                            'position'=>1,
                            'perms'=>'$user->rights->ficheinter->lire',
                            'target'=>'',
                            'user'=>0);
        $rem = $r;
        $r++;
        //1er lien

        $this->menu[$r]=array('fk_menu'=>'r='.$rem,
                            'type'=>'left',
                            'titre'=>'NewIntervention',
                            'mainmenu'=>'techpeople',
                            'url'=>'/fichinter/fiche.php?action=create&leftmenu=ficheinter',
                            'langs'=>'interventions',
                            'position'=>1,
                            'perms'=>'$user->rights->ficheinter->creer',
                            'target'=>'',
                            'user'=>0,
                            'constraints' => array( 0 => '$leftmenu==ficheinter' ));
        $r++;
        //2eme lien

        $this->menu[$r]=array('fk_menu'=>'r='.$rem,
                            'type'=>'left',
                            'titre'=>'ListOfInterventions',
                            'mainmenu'=>'techpeople',
                            'url'=>'/fichinter/index.php?leftmenu=ficheinter',
                            'langs'=>'interventions',
                            'position'=>2,
                            'perms'=>'$user->rights->ficheinter->lire',
                            'target'=>'',
                            'user'=>0,
                            'constraints' => array( 0 => '$leftmenu==ficheinter' ));
        $r++;

        $this->menu[$r]=array('fk_menu'=>'r='.$rem ,
                            'type'=>'left',
                            'titre'=>'AllDI',
                            'mainmenu'=>'techpeople',
                            'url'=>'/Synopsis_DemandeInterv/index.php?leftmenu=ficheinter',
                            'langs'=>'interventions',
                            'position'=>3,
                            'perms'=>'$user->rights->synopsisdemandeinterv->lire',
                            'target'=>'',
                            'user'=>0,
                            'constraints' => array( 0 => '$leftmenu==ficheinter' ));
        $r++;

        $this->menu[$r]=array('fk_menu'=>'r='.$rem ,
                            'type'=>'left',
                            'titre'=>'NewDI',
                            'mainmenu'=>'techpeople',
                            'url'=>'/Synopsis_DemandeInterv/fiche.php?action=create&leftmenu=ficheinter',
                            'langs'=>'interventions',
                            'position'=>4,
                            'perms'=>'$user->rights->synopsisdemandeinterv->creer',
                            'target'=>'',
                            'user'=>0,
                            'constraints' => array( 0 => '$leftmenu==ficheinter' ));
        $r++;


//        // Left menu linked to top menu
//        $this->menu[$r]=array('fk_menu'=>'r=0',
//                            'type'=>'left',
//                            'titre'=>'Note de frais',
//                            'mainmenu'=>'techpeople',
//                            'url'=>'/Babel_TechPeople/deplacement/index.php',
//                            'langs' => 'synopsisGene@Synopsis_Tools',
//                            'position'=>1,
//                            'perms'=>'$user->rights->ficheinter->lire',
//                            'target'=>'',
//                            'user'=>0);
        // Left menu linked to top menu
//        $this->menu[$r]=array('fk_menu'=>'r=1',
//                            'type'=>'left',
//                            'titre'=>'Note de frais',
//                            'mainmenu'=>'techpeople',
//                            'url'=>'/Babel_TechPeople/deplacement/index.php',
//                            'langs' => 'synopsisGene@Synopsis_Tools',
//                            'position'=>1,
//                            'perms'=>'$user->rights->TechPeople->AfficheMien',
//                            'target'=>'',
//                            'user'=>0);
        $rem = $r;
        $r++;
//        $this->menu[$r]=array('fk_menu'=>'r=2',
//                            'type'=>'left',
//                            'titre'=>'Faire une NDF',
//                            'mainmenu'=>'techpeople',
//                            'url'=>'/Babel_TechPeople/deplacement/index.php',
//                            'langs' => 'synopsisGene@Synopsis_Tools',
//                            'position'=>2,
//                            'perms'=>'$user->rights->TechPeople->AfficheMien',
//                            'target'=>'',
//                            'user'=>0);
//        $r++;
//        $this->menu[$r]=array('fk_menu'=>'r=3',
//                            'type'=>'left',
//                            'titre'=>'Voir les NDF',
//                            'mainmenu'=>'techpeople',
//                            'url'=>'/Babel_TechPeople/deplacement/index.php',
//                            'langs' => 'synopsisGene@Synopsis_Tools',
//                            'position'=>3,
//                            'perms'=>'$user->rights->TechPeople->Affiche',
//                            'target'=>'',
//                            'user'=>0);
        $r++;

    }
   /**
    *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
    *               Definit egalement les repertoires de donnees e creer pour ce module.
    */
  function init()
  {
    $sql = array();
 //Get the value of the parent menu:
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."menu where mainmenu = 'commercial' and fk_menu = 0 and level = -1 ";
        $resql = $this->db->query($requete);
        $parentId = "";
        if ($resql)
        {
            $res = $this->db->fetch_object($resql);
            $parentId = $res->rowid;
        }

        $requete = "SELECT max(position) + 1 as res1 FROM ".MAIN_DB_PREFIX."menu WHERE mainmenu = 'commercial' AND level = 1 ";
        $resql = $this->db->query($requete);
        $pos = "";
        if ($resql)
        {
            $res = $this->db->fetch_object($resql);
            $pos = $res->res1;
        }

        $r=0;
        // Left menu linked to top menu
        $menu[$r]=array('fk_menu'=> $parentId,
                            'type'=>'left',
                            'rowid'=>$this->numero . '1',
                            'titre'=>'Calendar',
                            'mainmenu'=>'commercial',
                            'url'=>'/comm/index.php?mainmenu=commercial&idmenu='.$parentId,
                            'langs'=>'BabelCal',
                            'position'=>$pos,
                            'perms'=>'$user->rights->SynopsisZimbra->AfficheCal || $user->rights->SynopsisZimbra->AfficheMyUserCal|| $user->rights->SynopsisZimbra->AfficheAllUserCal',
                            'target'=>'',
                            'user'=>0);
        $r++;
        $menu[$r]=array('fk_menu'=> $this->numero . '1',
                            'type'=>'left',
                            'titre'=>'CalendarComm',
                            'rowid'=>$this->numero . '2',
                            'mainmenu'=>'commercial',
                            'url'=>'/comm/calendar.php',
                            'langs'=>'BabelCal',
                            'position'=>1,
                            'perms'=>'$user->rights->SynopsisZimbra->AfficheCal || $user->rights->SynopsisZimbra->AfficheMyUserCal|| $user->rights->SynopsisZimbra->AfficheAllUserCal',
                            'target'=>'',
                            'user'=>0);
        $r++;

        $menu[$r]=array('fk_menu'=>$this->numero . '1',
                            'type'=>'left',
                            'titre'=>'CalendarProspect',
                            'mainmenu'=>'commercial',
                            'rowid'=>$this->numero . '3',
                            'url'=>'/comm/prospect/prospects.php?leftmenu=prospects&idmenu='.$parentId,
                            'langs'=>'BabelCal',
                            'position'=>2,
                            'perms'=>'$user->rights->SynopsisZimbra->AfficheCal',
                            'target'=>'',
                            'user'=>0);
        $r++;

        $menu[$r]=array('fk_menu'=>$this->numero . '1',
                            'type'=>'left',
                            'rowid'=>$this->numero . '4',
                            'titre'=>'CalendarClient',
                            'mainmenu'=>'commercial',
                            'url'=>'/comm/clients.php?leftmenu=customers&idmenu='.$parentId,
                            'langs'=>'BabelCal',
                            'position'=>3,
                            'perms'=>'$user->rights->SynopsisZimbra->AfficheCal',
                            'target'=>'',
                            'user'=>0);
        $r++;
        foreach($menu as $key=>$val)
        {
            $requete = "INSERT INTO ".MAIN_DB_PREFIX."menu
                                    (module, fk_menu, type, rowid, titre, mainmenu, url, langs, position, perms, target, user)
                             VALUES ('BabelCalendar', '".$val['fk_menu']."', '".$val['type']."', '".$val['rowid']."', '".$val['titre']."', '".$val['mainmenu']."', '".$val['url']."', '".$val['langs']."', '".$val['position']."', '".$val['perms']."', '".$val['target']."', '".$val['user']."')";
//            print $requete;
            $this->db->query($requete);
        }

    return $this->_init($sql);
  }

  /**
   *    \brief      Fonction appelee lors de la desactivation d'un module.
   *                Supprime de la base les constantes, boites et permissions du module.
   */
  function remove()
  {
    $sql = array();
    $sql = array();
    $requete = "DELETE FROM ".MAIN_DB_PREFIX."menu WHERE mainmenu='commercial' and (titre = 'Calendar'  or titre='CalendarClient' or titre = 'CalendarComm' or titre = 'CalendarProspect')";
    $this->db->query($requete);
//    print $requete;

    return $this->_remove($sql);
  }
}
?>
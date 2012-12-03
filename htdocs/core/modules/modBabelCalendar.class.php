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

/**     \defgroup   JasperBabel     Module JasperBabel
        \brief      Module pour inclure JasperBabel dans Dolibarr
*/

/**
        \file       htdocs/core/modules/modJasperBabel.class.php
        \ingroup    JasperBabel
        \brief      Fichier de description et activation du module d'interface JasperBI - Babel
*/

include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");

/**     \class      modProspectBabel
        \brief      Classe de description et activation du module de Prospection Babel
*/

class modBabelCalendar extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'acces base
    */
    function modBabelCalendar($DB)
    {
        $this->db = $DB ;
        $this->numero = 22220;

        $this->family = "OldGleModule";
        $this->name = "BabelCalendar";
        $this->description = "Calendrier des activit&eacute; par Synopsis et DRSI";
        $this->version = '0.1';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_BABELCALENDAR';
        $this->special = 0;
        $this->picto='calendar';

        // Dir
        $this->dirs = array();

        // Config pages
        //$this->config_page_url = "BabelCalendar.php";
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
        $this->rights_class = 'BabelCal'; //Max 12 lettres



        $r = 0;
        $this->rights[$r][0] = $this->numero."0";// this->numero ."". 1
        $this->rights[$r][1] = 'Affichage du calendrier ';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'BabelCal'; // Famille
        $this->rights[$r][5] = 'Affiche'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."2";// this->numero ."". 1
        $this->rights[$r][1] = 'Affichage du calendrier soci&eacute;t&eacute;';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'BabelCal'; // Famille
        $this->rights[$r][5] = 'AfficheSoc'; // Droit
        $r ++;
        $this->rights[$r][0] = $this->numero."3";// this->numero ."". 1
        $this->rights[$r][1] = 'Affichage du calendrier commercial';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'BabelCal'; // Famille
        $this->rights[$r][5] = 'AfficheCom'; // Droit
        $r ++;


  }

   /**
    *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
    *               Definit egalement les repertoires de donnees a creer pour ce module.
    */
  function init()
  {
    $sql = array();


 /*
 */

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
                            'perms'=>'$user->rights->BabelCal->BabelCal->Affiche',
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
                            'perms'=>'$user->rights->BabelCal->BabelCal->Affiche && $user->rights->BabelCal->BabelCal->AfficheCom',
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
                            'perms'=>'$user->rights->BabelCal->BabelCal->Affiche && $user->rights->BabelCal->BabelCal->AfficheSoc',
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
                            'perms'=>'$user->rights->BabelCal->BabelCal->Affiche && $user->rights->BabelCal->BabelCal->AfficheSoc',
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
    $requete = "DELETE FROM ".MAIN_DB_PREFIX."menu WHERE mainmenu='commercial' and (titre = 'Calendar'  or titre='CalendarClient' or titre = 'CalendarComm' or titre = 'CalendarProspect')";
    $this->db->query($requete);
//    print $requete;
    return $this->_remove($sql);
  }
}
?>
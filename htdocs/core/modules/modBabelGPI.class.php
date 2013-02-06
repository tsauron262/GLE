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

class modBabelGPI extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'acces base
    */
    function modBabelGPI($DB)
    {
        $this->db = $DB ;
        $this->numero = 22254;

        $this->family = "OldGleModule";
        $this->name = "Babel - External access";
        $this->description = "Acc&egrave;s externe";
        $this->version = '0.1';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_BABELGPI';
        $this->special = 0;
        $this->picto='GPI';

        // Dir
        $this->dirs = array();

        // Config pages
        //$this->config_page_url = "";
        $this->config_page_url = "Babel_GPI.php";

        // Dependences
        $this->depends = array();
        $this->requiredby = array();

        // Constantes
        $this->const = array();

//        // Boites
//        $this->boxes = array();

    // Boites
//    $this->boxes = array();
//    $r=0;
//    $this->boxes[$r][1] = "box_deplacement.php";
//    $r++;


        // Permissions
        $this->rights = array();
        $this->rights_class = 'GPI';

        $r = 0;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Acc&egrave;s au module d\'acc&egrave;s externe';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'Global'; // Famille
        $this->rights[$r][5] = 'Afficher'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Modifier les comptes d\'acc&egrave;s externe';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'Global'; // Famille
        $this->rights[$r][5] = 'Modifier'; // Droit
        $r ++;



    // Menus
        //------
        $this->menus = array();            // List of menus to add
        $r=0;

    }
   /**
    *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
    *               Definit egalement les repertoires de donnees e creer pour ce module.
    */
  function init()
  {
    $sql = array();

//    $requete = "ALTER TABLE ".MAIN_DB_PREFIX."propal ADD column isFinancement tinyint(1) default 0";
//    $this->db->query($requete);
//    $requete = "ALTER TABLE ".MAIN_DB_PREFIX."societe ADD column cessionnaire tinyint(1) default 0";
//    $this->db->query($requete);

    $menu=array();
 //Get the value of the parent menu:
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."menu where mainmenu = 'home' AND position = 0 AND level=0";
        $resql = $this->db->query($requete);
        $parentId = "";
        if ($resql)
        {
            $res = $this->db->fetch_object($resql);
            $parentId = $res->rowid;
        }

        $requete = "SELECT max(position) + 1 as res1 FROM ".MAIN_DB_PREFIX."menu WHERE mainmenu = 'home' AND level = 1 ";
        $resql = $this->db->query($requete);
        $pos = "";
        if ($resql)
        {
            $res = $this->db->fetch_object($resql);
            $pos = $res->res1;
        }
//
        $r=1;
//        // Left menu linked to top menu

        $menu[$r]=array('fk_menu'=>$parentId,
                            'type'=>'left',
                            'titre'=>'Acc&egrave;s externe',
                            'mainmenu'=>'home',
                            'url'=>'/Babel_GPI/pilotage.php',
                            'langs' => 'synopsisGene@Synopsis_Tools',
                            'rowid'=>$this->numero . $r,
                            'position'=>$pos,
                            'perms'=>'$user->rights->GPI->Global->Afficher || $user->rights->GPI->Global->Modifier ',
                            'target'=>'',
                            'level'=>1,
                            'user'=>0);

        foreach($menu as $key=>$val)
        {
            $requete = "INSERT INTO ".MAIN_DB_PREFIX."menu
                                    (module, fk_menu, type, rowid, titre, mainmenu, url, langs, position, perms, target, user,level)
                             VALUES ('BabelGPI', '".$val['fk_menu']."', '".$val['type']."', '".$val['rowid']."', '".$val['titre']."', '".$val['mainmenu']."', '".$val['url']."', '".$val['langs']."', '".$val['position']."', '".$val['perms']."', '".$val['target']."', '".$val['user']."',".$val['level'].")";
            $sql = $this->db->query($requete);
            if (!$sql) print $this->db->error;
            $newId = $this->db->last_insert_id("".MAIN_DB_PREFIX."menu");
            $requete = "INSERT INTO ".MAIN_DB_PREFIX."menu_const (fk_menu, fk_constraint) VALUES (".$newId.",51) ";
            $sql = $this->db->query($requete);
            if (!$sql) print $this->db->error;
        }
    $sql = array();
    return $this->_init($sql);
  }

  /**
   *    \brief      Fonction appelee lors de la desactivation d'un module.
   *                Supprime de la base les constantes, boites et permissions du module.
   */
  function remove()
  {
            $requete = "DELETE FROM ".MAIN_DB_PREFIX."menu WHERE module = 'BabelGPI' ";
            $sql = $this->db->query($requete);
            if (!$sql) print $this->db->error;

    $sql = array();
    return $this->_remove($sql);
  }
}
?>
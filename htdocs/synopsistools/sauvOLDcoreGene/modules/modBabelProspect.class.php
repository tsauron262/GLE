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

include_once "DolibarrModules.class.php";

/**     \class      modProspectBabel
        \brief      Classe de description et activation du module de Prospection Babel
*/

class modBabelProspect extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'acces base
    */
    function modbabelprospect($DB)
    {
        $this->db = $DB ;
        $this->numero = 22225;

        $this->family = "OldGleModule";
        $this->name = "BabelProspect";
        $this->description = "Prospection Babel";
        $this->version = '1.0';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_BABELPROSPECTION';
        $this->special = 0;
        $this->picto='PROSPECTIONBABEL';

        // Dir
        $this->dirs = array();

        // Config pages
        $this->config_page_url = "BabelProspection.php";

        // Dependences
        $this->depends = array("modPropale");
        $this->requiredby = array();

        // Constantes
        $this->const = array();

        // Boites
        $this->boxes = array();

        // Permissions
        $this->rights = array();
        $this->rights_class = 'prospectbabe'; //Max 12 lettres

        $r = 0;
        $this->rights[$r][0] = $this->numero."1";// this->numero ."". 1
        $this->rights[$r][1] = 'Affichage de l\'&eacute;cran de prospection';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'Prospection'; // Famille
        $this->rights[$r][5] = 'Affiche'; // Droit
        $r ++;
        $this->rights[$r][0] = $this->numero."2";// this->numero ."". 2
        $this->rights[$r][1] = '&Eacute;dition de la campagne';
        $this->rights[$r][2] = 'w'; //useless
        $this->rights[$r][3] = 0;// Default
        $this->rights[$r][4] = 'Prospection';// Famille
        $this->rights[$r][5] = 'Ecrire';// Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."3";// this->numero ."". 3
        $this->rights[$r][1] = 'Efface la campagne';
        $this->rights[$r][2] = 'w'; //useless
        $this->rights[$r][3] = 0;// Default
        $this->rights[$r][4] = 'Prospection';// Famille
        $this->rights[$r][5] = 'Effacer';// Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."4";// this->numero ."". 3
        $this->rights[$r][1] = 'Acc&egrave;s permanent aux propections';
        $this->rights[$r][2] = 'w'; //useless
        $this->rights[$r][3] = 0;// Default
        $this->rights[$r][4] = 'Prospection';// Famille
        $this->rights[$r][5] = 'permAccess';// Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."5";// this->numero ."". 3
        $this->rights[$r][1] = 'Acc&egrave;s aux statistiques';
        $this->rights[$r][2] = 'w'; //useless
        $this->rights[$r][3] = 0;// Default
        $this->rights[$r][4] = 'Prospection';// Famille
        $this->rights[$r][5] = 'stats';// Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."6";// this->numero ."". 3
        $this->rights[$r][1] = 'Acc&egrave;s aux r&eacute;capitulatifs de la campagne';
        $this->rights[$r][2] = 'w'; //useless
        $this->rights[$r][3] = 0;// Default
        $this->rights[$r][4] = 'Prospection';// Famille
        $this->rights[$r][5] = 'recap';// Droit
        $r ++;

        //box_a_init
//       $this->boxes = array();
//        $this->boxes[0][0] = "Derniers clients";
//        $this->boxes[0][1] = "box_clients.php";
//        $this->boxes[0][2] = "A01";//Default order
//
//        $this->boxes[1][0] = "Derniers prospects enregistres";
//        $this->boxes[1][1] = "box_prospect.php";
//        $this->boxes[1][2] = "A02";//Default order
//
//        $this->boxes[2][0] = "Dernieres actions";
//        $this->boxes[2][1] = "box_actions.php";
//        $this->boxes[2][2] = "B01";//Default order

    //Menu
            $r=0;
//  $menu->add(DOL_URL_ROOT."", $langs->trans("Campagne de prospection"));
////  if ($user->rights->societe->creer)
////    {
//      $menu->add_submenu(DOL_URL_ROOT."/BabelProspect/nouvelleProspection.php?action=add", $langs->trans("Nouvelle campagne"));
////    }

//  $menu->add_submenu(DOL_URL_ROOT."/BabelProspect/nouvelleProspection.php?action=list", $langs->trans("Liste"));


        $this->menu[$r]=array('fk_menu'=>0,
                              'type'=>'top',
                              'titre'=>'Prospection',
                              'mainmenu'=>'prospection',
                              'leftmenu'=>'0',
                              'url'=>'/BabelProspect/nouvelleProspection.php',
                              'langs'=>'other',
                              'position'=>100,
                              'perms'=>'',
                              'target'=>'',
                              'user'=>0);
        $r++;
        $this->menu[$r]=array('fk_menu'=>'r=0',
                            'type'=>'left',
                            'titre'=>'Campagne de prospection',
                            'mainmenu'=>'prospection',
                            'url'=>'/BabelProspect/nouvelleProspection.php',
                            'langs' => 'synopsisGene@synopsistools',
                            'position'=>100,
                            'perms'=>'$user->rights->prospectbabe->Prospection->Affiche',
                            'target'=>'',
                            'user'=>0);
        $r++;

        $this->menu[$r]=array('fk_menu'=>'r=1',
                            'type'=>'left',
                            'titre'=>'Nouvelle campagne',
                            'mainmenu'=>'prospection',
                            'url'=>'/BabelProspect/nouvelleProspection.php?action=add',
                            'langs' => 'synopsisGene@synopsistools',
                            'position'=>100,
                            'perms'=>'$user->rights->prospectbabe->Prospection->Ecrire',
                            'target'=>'',
                            'user'=>0);
        $r++;
        $this->menu[$r]=array('fk_menu'=>'r=1',
                            'type'=>'left',
                            'titre'=>'Liste',
                            'mainmenu'=>'prospection',
                            'url'=>'/BabelProspect/nouvelleProspection.php?action=list',
                            'langs' => 'synopsisGene@synopsistools',
                            'position'=>100,
                            'perms'=>'$user->rights->prospectbabeProspection->->Effacer',
                            'target'=>'',
                            'user'=>0);
        $r++;

  }

   /**
    *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
    *               Definit egalement les repertoires de donnees a creer pour ce module.
    */
  function init()
  {
        $sql = array();


        //box_a_init

        $arr = array();
        $arr[0][0] = "Derniers prospects enregistr&eacute;s";
        $arr[0][1] = "box_clients.php";
        $arr[0][2] = "A01";//Default order

        $arr[1][0] = "Derniers prospects enregistr&eacute;s";
        $arr[1][1] = "box_prospect.php";
        $arr[1][2] = "A02";//Default order

        $arr[2][0] = "Derni&egrave;res actions";
        $arr[2][1] = "box_actions.php";
        $arr[2][2] = "B01";//Default order

        $db=$this->db;

        for($i=0;$i<sizeof($arr);$i++)
        {
            //ajoute les boites


            //1 trouve les id des boites
            $requete = "SELECT b.rowid FROM  ".MAIN_DB_PREFIX."boxes_def AS b WHERE file ='".$arr[$i][1]."'";
            $resql = $db->query($requete);

            if ($resql)
            {
                    $objp = $db->fetch_object();
                    //2 ajoute les boites pour tous les utlisateurs
                    $requete1 = "INSERT into ".MAIN_DB_PREFIX."boxes (rowid,box_id,position,box_order,fk_user) VALUES (".$this->numero.$i.", $objp->rowid, $this->numero, '".$arr[$i][2]."', 0)";
                    $resql1 = $db->query($requete1);
                    //$sql = "SELECT f.ref, f.total,f.datef as df, f.rowid as facid, f.fk_user_author, f.paye";
                    //$sql .= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."fa_pr as fp WHERE fp.fk_facture = f.rowid AND f.fk_soc = 1";
            }
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
//supprime les boites
    $db=$this->db;
        //1 remove tout ceux dont la position est $this->numero
        $requete = "DELETE FROM  ".MAIN_DB_PREFIX."boxes WHERE position =".$this->numero;

        $resql = $db->query($requete);
    return $this->_remove($sql);
  }
}
?>

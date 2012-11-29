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

class modBabelPlaquette extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'acces base
    */
    function modBabelPlaquette($DB)
    {
        $this->db = $DB ;
        $this->numero = 24423;

        $this->family = "OldGleModule";
        $this->name = "BabelPlaquette";
        $this->description = "Envoi de plaquettes";
        $this->version = '0.1';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_BABELPLAQUETTE';
        $this->special = 0;
        $this->picto='PLAQUETTE';

        // Dir
        $this->dirs = array();

        // Config pages
        //$this->config_page_url = "Babel_GA.php";
//        $this->config_page_url = "";

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
        $this->rights_class = 'Plaquette';

        $r = 0;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Voir les plaquettes';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'Global'; // Famille
        $this->rights[$r][5] = 'AffichePlaquette'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Administrer le module plaquette';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'Global'; // Famille
        $this->rights[$r][5] = 'Modifier'; // Droit
        $r ++;


    // Menus
        //------
        $this->menus = array();            // List of menus to add
        $r=0;
//TODO menu
//         Top menu
        $this->menu[$r]=array('fk_menu'=>0,
                            'type'=>'top',
                            'titre'=>'Plaquettes',
                            'mainmenu'=>'Plaquettes',
                            'leftmenu'=>'1',        // To say if we can overwrite leftmenu
                            'url'=>'/Babel_plaquette/index.php',
                            'langs' => 'synopsisGene@Synopsis_Tools',
                            'position'=>130,
                            'perms'=>'$user->rights->Plaquette->Global->AffichePlaquette',
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
    $sql = array();
        $relativepath="Plaquettes";
        require_once(DOL_DOCUMENT_ROOT . "/ecm/class/ecmdirectory.class.php");
        $cat = new ECMDirectory($this->db);
        $cate_arbo = $cat->get_full_arbo(1);
        $pathfound=0;
        foreach ($cate_arbo as $key => $categ)
        {
            $path=preg_replace('/[ -><\/]+/i','/',$categ['fulllabel']);
            //print $path.'<br>';
            if ($path == $relativepath)
            {
                $pathfound=1;
                break;
            }
        }

        if ($pathfound)
        {
                $requete = "SELECT code FROM ".MAIN_DB_PREFIX."c_actioncomm WHERE code = 'AC_PLAQ'";
                $sql1 = $this->db->query($requete);
                if (! $this->db->num_rows($sql1) > 0)
                {
                    $requete2 = "SELECT max(id) + 1 as newId FROM ".MAIN_DB_PREFIX."c_actioncomm";
                    $sql2 = $this->db->query($requete2);
                    $res2 = $this->db->fetch_object($sql2);
                    $newId = $res2->newId;
                    $requete = "INSERT INTO `".MAIN_DB_PREFIX."c_actioncomm`
                                            (`id`,`code`,`type`,`libelle`,`module`,`active`,`todo`)
                                    VALUES
                                            (".$newId.",'AC_PLAQ', 'babel', 'Envoi de plaquette', NULL, 1, NULL)";
                    $sql1 = $this->db->query($requete);
                    if ($sql1)
                    {
                        dolibarr_set_const($this->db,"BABEL_PLAQUETTE_AC",$newId);
                    }

                } else {
                    $requete = "UPDATE ".MAIN_DB_PREFIX."c_actioncomm SET active = 1 WHERE code = 'AC_PLAQ' ";
                    $sql1= $this->db->query($requete);
                }

            return $this->_init($sql);

        } else {
            require_once(DOL_DOCUMENT_ROOT . "/user/class/user.class.php");
            $user_admin = new User ($this->db,1);
            $cat->fk_parent = 0;
            $cat->label              = "Plaquettes";
            $cat->description        = "Plaquettes produits";

            $id = $cat->create($user_admin);
            if ($id > 0)
            {
                //Create Dir BI
                //mkdir(DOL_DATA_ROOT."/ecm/Plaquettes",'0777',true);
                Header("Location: ".$_SERVER["PHP_SELF"]);
                $requete = "SELECT code FROM ".MAIN_DB_PREFIX."c_actioncomm WHERE code = 'AC_PLAQ'";
                $sql1 = $this->db->query($requete);
                if (! $sql1->num_rows > 0)
                {
                    $requete2 = "SELECT max(id) + 1 as newId FROM ".MAIN_DB_PREFIX."c_actioncomm";
                    $sql2 = $this->db->query($requete2);
                    $res2 = $this->db->fetch_object($sql2);
                    $newId = $res2->newId;
                    $requete = "INSERT INTO `".MAIN_DB_PREFIX."c_actioncomm`
                                            (`id`,`code`,`type`,`libelle`,`module`,`active`,`todo`)
                                    VALUES
                                            (".$newId.",'AC_PLAQ', 'babel', 'Envoi de plaquette', NULL, 1, NULL)";
                    $sql1 = $this->db->query($requete);
                    if ($sql1)
                    {
                        dolibarr_set_const($this->db,"BABEL_PLAQUETTE_AC",$newId);
                    }

                }else {
                    $requete = "UPDATE ".MAIN_DB_PREFIX."c_actioncomm SET active = 1 WHERE code = 'AC_PLAQ' ";
                    $sql1= $this->db->query($requete);
                }

                return $this->_init($sql);
                exit;
            } else {
                global $langs;
                $mesg='<div class="error ui-state-error">Error '.$langs->trans($ecmdir->error).'</div>';
                Header("Location: ".$_SERVER["PHP_SELF"]);
            }
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

    $requete = "UPDATE ".MAIN_DB_PREFIX."c_actioncomm SET active = 0 WHERE code = 'AC_PLAQ' ";
    $sql1= $this->db->query($requete);

    $sql = array();
    return $this->_remove($sql);
  }
}
?>
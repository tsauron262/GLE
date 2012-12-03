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

class modBabelJasper extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'acces base
    */
    function modBabelJasper($DB)
    {
        $this->db = $DB ;
        $this->numero = 22223;

        $this->family = "OldGleModule";
        $this->name = "BabelJasper";
        $this->description = "Jasper Synopsis et DRSI";
        $this->version = '0.1';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_JASPERBABEL';
        $this->special = 0;
        $this->picto='JASPERBABEL';

        // Dir
        $this->dirs = array();

        // Config pages
        $this->config_page_url = "BabelJasper.php";

        // Dependences
        $this->depends = array("modECM");
        $this->requiredby = array();

        // Constantes
        $this->const = array();

        // Boites
        $this->boxes = array();

        // Permissions
        $this->rights = array();
        $this->rights_class = 'JasperBabel'; //Max 12 lettres


        $r = 0;
        $this->rights[$r][0] = $this->numero."1";// this->numero ."". 1
        $this->rights[$r][1] = 'Affichage de BabelJasper';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'JasperBabel'; // Famille
        $this->rights[$r][5] = 'Affiche'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."2";// this->numero ."". 2
        $this->rights[$r][1] = 'G&eacute;n&eacute;ration dans BabelJasper';
        $this->rights[$r][2] = 'g'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'JasperBabel'; // Famille
        $this->rights[$r][5] = 'Generate'; // Droit
        $r ++;
        $this->rights[$r][0] = $this->numero."3";// this->numero ."". 2
        $this->rights[$r][1] = 'Pousser dans Zimbra';
        $this->rights[$r][2] = 'z'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'JasperBabel'; // Famille
        $this->rights[$r][5] = 'PushZimbra'; // Droit
        $r ++;
        $this->rights[$r][0] = $this->numero."4";// this->numero ."". 2
        $this->rights[$r][1] = 'Pousser dans ECM';
        $this->rights[$r][2] = 'e'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'JasperBabel'; // Famille
        $this->rights[$r][5] = 'PushECM'; // Droit
        $r ++;

        // Menus
        //------
        $r=0;

        $this->menu[$r]=array('fk_menu'=>0,'type'=>'top','titre'=>'BI','mainmenu'=>'jasperbabel','leftmenu'=>'0','url'=>'/BabelJasper/index.php','langs'=>'other','position'=>100,'perms'=>'$user->rights->JasperBabel->JasperBabel->Affiche','target'=>'','user'=>0);
        $r++;
        $this->menu[$r]=array('fk_menu'=>"r=0",'type'=>'left','titre'=>'Reporting','mainmenu'=>'jasperbabel','leftmenu'=>'1','url'=>'/BabelJasper/index.php','langs'=>'other','position'=>100,'perms'=>'$user->rights->JasperBabel->JasperBabel->Affiche','target'=>'','user'=>0);
        $r++;

  }

   /**
    *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
    *               Definit egalement les repertoires de donnees a creer pour ce module.
    */
  function init()
  {
      global $langs;


    // Create Dir
    $sql = array();
        $relativepath="BI";
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
//            $this->error="ErrorDirAlreadyExists";
//            dol_syslog("EcmDirectories::create ".$this->error, LOG_WARNING);
            return $this->_init($sql);

        } else {
            // si y'a pas de repertoire BI, on le crée dans l'ECM
            require_once(DOL_DOCUMENT_ROOT . "/user/class/user.class.php");
            $user_admin = new User ($this->db,1);
            $cat->fk_parent = 0;
            $cat->label              = "BI";
            $cat->description        = "JasperBI";

            $id = $cat->create($user_admin);
            if ($id > 0)
            {
                //Create Dir BI
                mkdir(DOL_DATA_ROOT."/ecm/BI",'0777',true);
                Header("Location: ".$_SERVER["PHP_SELF"]);
                return $this->_init($sql);
                exit;
            } else {
                $mesg='<div class="error ui-state-error">Error '.$langs->trans($ecmdir->error).'</div>';
                Header("Location: ".$_SERVER["PHP_SELF"]);
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

    return $this->_remove($sql);
  }
}
?>

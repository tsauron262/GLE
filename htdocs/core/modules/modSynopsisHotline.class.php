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

include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");

/**     \class      modProspectBabel
        \brief      Classe de description et activation du module de Prospection Babel
*/

class modSynopsisHotline extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'acces base
    */
    function modSynopsisHotline($DB)
    {
        $this->db = $DB ;
        $this->numero = 29900;

        $this->family = "Synopsis";
        $this->name = "Module Hotline";
        $this->description = "Gestion des appel";
        $this->version = '1';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_SYNOPSISHOTLINE';
        $this->special = 0;
        $this->picto='propal';

        // Dir
        $this->dirs = array();

        // Config pages
        $this->config_page_url = preg_replace('/^mod/i', '', get_class($this)).".php";

        // Dependences
        $this->depends = array("modSynopsisTools");
        $this->requiredby = array();
        // Constantes
        $this->const = array();

        // Boites
        $this->boxes = array();

        // Permissions
        $this->rights = array();
        $this->rights_class = 'SynopsisHotline'; //Max 12 lettres


        $r = 0;
        $this->rights[$r][0] = $this->numero."1";// this->numero ."". 1
        $this->rights[$r][1] = 'Affichage des Revision';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'SynopsisHotline'; // Famille
        $this->rights[$r][5] = 'Affiche'; // Droit
        $r ++;
        $this->rights[$r][0] = $this->numero."2";// this->numero ."". 1
        $this->rights[$r][1] = 'Reviser des proposition';
        $this->rights[$r][2] = 'p'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'SynopsisHotline'; // Famille
        $this->rights[$r][5] = 'Push'; // Droit
        $r ++;
        
        // Menus
        //------

        
        //Ajout onglet dans fiche utilisateur
        $this->tabs = array('thirdparty:+hotline:Hotline:@monmodule:/Synopsis_Hotline/list.php?obj=soc&id=__ID__',
            'contract:+hotline:Hotline:@monmodule:/Synopsis_Hotline/list.php?obj=ctr&id=__ID__',
            'thirdparty:+productCli:Parc:@monmodule:/Synopsis_Hotline/listProd.php?obj=soc&id=__ID__');
  }

   /**
    *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
    *               Definit egalement les repertoires de donnees a creer pour ce module.
    */
  function init()
  {
        $this->remove();
    $sql = array("INSERT IGNORE INTO `llx_Synopsis_Chrono_conf` (`id`, `titre`, `description`, `hasFile`, `hasContact`, `hasSociete`, `hasRevision`, `revision_model_refid`, `modele`, `date_create`, `tms`, `active`) VALUES
(100, 'Appel', 'Fiche Hotline', 0, 0, 0, 0, NULL, 'Hot{yy}{mm}{000}', '2013-10-15', '2013-10-15 20:05:01', 1);
", "
INSERT IGNORE INTO `llx_Synopsis_Chrono_key` (`id`, `nom`, `description`, `model_refid`, `type_valeur`, `type_subvaleur`, `extraCss`, `inDetList`) VALUES

(1000, 'Objet', '', 100, 1, NULL, 'required', 1),
(1001, 'Date / Heure', '', 100, 3, NULL, 'required', 1),
(1004, 'Contrat', '', 100, 6, 1000, '', 1),
(1005, 'Société', '', 100, 6, 1001, '', 1);",
            "INSERT IGNORE INTO `llx_Synopsis_Process_form_requete` (`id`, `requete`, `requeteValue`, `params`, `limite`, `label`, `description`, `showFields`, `indexField`, `tableName`, `groupBy`, `orderBy`, `filter`, `OptGroup`, `OptGroupLabel`, `postTraitement`) VALUES

(1000, 'SELECT c.rowid as \"c.rowid\", c.ref, fk_soc, s.nom FROM llx_contrat c, llx_societe s WHERE c.fk_soc = s.rowid\r\nORDER BY s.nom', 'SELECT c.rowid as \"c.rowid\", c.ref, fk_soc, s.nom FROM llx_contrat c, llx_societe s WHERE c.fk_soc = s.rowid AND [[indexField]] \r\nORDER BY s.nom', 'a:1:{i:0;s:0:\"\";}', 10000, 'requete Contrat/Soc', 'contrat + soc', 'a:1:{i:0;s:3:\"ref\";}', 'c.rowid', 'llx_contrat', NULL, NULL, NULL, 'fk_soc', 'nom', 'a:1:{s:3:\"ref\";s:0:\"\";}'),

(1001, 'SELECT rowid, nom FROM llx_societe', '            ', 'a:1:{i:0;s:0:\"\";}', 10000, 'Sociétés', 'Liste des sociétés', 'a:1:{i:0;s:3:\"nom\";}', 'rowid', '', NULL, NULL, NULL, '', '', 'a:1:{s:3:\"nom\";s:0:\"\";}'),
(1002, 'Select rowid, ref, Label FROM llx_product WHERE fk_product_type=0', '', 'a:1:{i:0;s:0:\"\";}', 100, 'Produits', 'Liste des produits', 'a:3:{i:0;s:5:\"rowid\";i:1;s:3:\"ref\";i:2;s:5:\"Label\";}', 'rowid', '', NULL, NULL, NULL, '', '', 'a:3:{s:5:\"rowid\";s:32:\"lien(product/fiche.php?id=[VAL])\";s:3:\"ref\";s:0:\"\";s:5:\"Label\";s:13:\"finLien([VAL]\";}');",
        
        "INSERT IGNORE INTO `llx_Synopsis_Chrono_conf` (`id`, `titre`, `description`, `hasFile`, `hasContact`, `hasSociete`, `hasPropal`, `hasProjet`, `hasRevision`, `revision_model_refid`, `modele`, `date_create`, `tms`, `active`) VALUES
   (101, 'Produit', 'Info sur un produit sous contrat ou garentie', 0, 0, 1, 0, 0, 0, NULL, 'PROD{000000}', '2013-10-28', '2013-10-28 20:16:18', 1);",
        
        "INSERT IGNORE INTO `llx_Synopsis_Chrono_key` (`id`, `nom`, `description`, `model_refid`, `type_valeur`, `type_subvaleur`, `extraCss`, `inDetList`) VALUES       
   (1010, 'Produit', '', 101, 6, 1002, '', 1),
   (1011, 'N° Serie', '', 101, 1, NULL, '', 1),
   (1012, 'Nom', 'Facultatif (ex: Serveur PAO)', 101, 1, NULL, '', 1),
   (1013, 'Note', '', 101, 9, NULL, '', 1),
   (1014, 'Date Achat', '', 101, 2, NULL, '', 1),
   (1015, 'Date fin SAV', '', 101, 2, NULL, '', 1),
   (1015, 'Ligne contrat', '', 101, 10, 1, '', 1);",
        
        "CREATE TABLE IF NOT EXISTS `llx_Synopsis_Chrono_lien` (
  `rowid` int(11) NOT NULL,
  `label` varchar(50) NOT NULL,
  `description` varchar(500) NOT NULL,
  `table` varchar(30) NOT NULL,
  `nomElem` varchar(30) NOT NULL,
  `ordre` tinyint(4) NOT NULL,
  `champId` varchar(30) NOT NULL,
  `champVueSelect` varchar(30) NOT NULL,
  `sqlFiltreSoc` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;",
        
        "INSERT IGNORE INTO `llx_Synopsis_Chrono_lien` (`rowid`, `label`, `description`, `table`, `nomElem`, `ordre`, `champId`, `champVueSelect`, `sqlFiltreSoc`) VALUES
(1, 'ContratLigne (N)', '', 'llx_contratdet', 'contratdet', 1, 'rowid', 'description', 'fk_contrat IN (SELECT `rowid` FROM `llx_contrat` WHERE `fk_soc` = [id])');",
        
        "INSERT IGORE INTO `llx_Synopsis_Chrono_key_type_valeur` (`id`, `nom`, `hasSubValeur`, `subValeur_table`, `subValeur_idx`, `subValeur_text`, `phpClass`, `htmlTag`, `htmlEndTag`, `endNeeded`, `cssClass`, `cssScript`, `jsCode`, `valueIsChecked`, `valueIsSelected`, `valueInTag`, `valueInValueField`, `sourceIsOption`) VALUES
(10, 'Lien', 1, 'llx_Synopsis_Chrono_lien', 'rowid', 'label', 'lien', '<SELECT>', '</SELECT>', 1, NULL, NULL, NULL, NULL, 1, NULL, NULL, 1);");
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

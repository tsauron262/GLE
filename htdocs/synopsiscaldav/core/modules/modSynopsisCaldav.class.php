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

class modSynopsisCaldav extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'acces base
    */
    function modSynopsisCaldav($DB)
    {
        $this->db = $DB ;
        $this->numero = 8596;

        $this->family = "Synopsis";
        $this->name = "Synopsis CalDav";
        $this->description = utf8_decode("Gestion des Synchro Agenda en CalDav");
        $this->version = '0.1';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_SYNOPSISCALDAV';
        $this->special = 0;
        $this->picto='tools@synopsistools';

        // Dir
        $this->dirs = array("synopsiscaldav");

        // Config pages
        //$this->config_page_url = "";
        
        
        $this->module_parts = array('triggers' => 1);

        // Dependences
//        $this->depends = array('');
        $this->requiredby = array();

        // Constantes
        $this->const = array();



        // Permissions
        $this->rights = array();
        $this->rights_class = 'synopsiscaldav';

        $r = 0;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Admin CalDav';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'read'; // Famille
//        $this->rights[$r][5] = 'read'; // Droit
        $r ++;
        
        
//        $this->const[$r][0] = "synopsispanier_ADDON_PDF";
//        $this->const[$r][1] = "chaine";
//        $this->const[$r][2] = "PANIER";
        
        
$this->menus = array();			// List of menus to add
		$r=0;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=tools',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
					'type'=>'left',			// This is a Left menu entry
					'titre'=>'Admin CalDav',
					'mainmenu'=>'tools',
					'leftmenu'=>'caldav',
					'url'=>'/synopsiscaldav/html/admin/index.php',
					'langs'=>'global@synopsistools',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
					'position'=>200,
					'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
					'perms'=>'1',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
					'target'=>'',
					'user'=>0);				// 0=Menu for internal users,1=external users, 2=both
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=tools,fk_leftmenu=caldav',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
					'type'=>'left',			// This is a Left menu entry
					'titre'=>'Admin CalDav',
					'mainmenu'=>'caldav',
					'leftmenu'=>'caldav',
					'url'=>'/synopsiscaldav/html/admin/index.php',
					'langs'=>'global@synopsistools',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
					'position'=>201,
					'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
					'perms'=>'1',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
					'target'=>'',
					'user'=>0);	
        
        $this->tabs = array('agenda:+caldav:Export CalDav:@synopsiscaldav:$user->rights->agenda->myactions->read:/synopsiscaldav/index.php');
    }
   /**
    *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
    *               Definit egalement les repertoires de donnees e creer pour ce module.
    */
  function init()
  {
    $sql = array("CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "synopsiscaldav_event` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_object` int(11) NOT NULL,
  `sequence` int(30) NOT NULL DEFAULT 0,
  `etag` varchar(100) NOT NULL,
  `uri` varchar(100) NOT NULL,
  `agendaplus` text NOT NULL,
  `participentExt` text NOT NULL,
  `dtstamp` varchar(100) NOT NULL,
  PRIMARY KEY (`rowid`)
);",

"CREATE OR REPLACE VIEW calendars as (SELECT u.`rowid` as id, LOWER(login) as login, CONCAT('principals/', LOWER(`login`)) as principaluri, REPLACE(CONCAT(`lastname`, CONCAT('_', `firstname`)), ' ', '_') as displayname, 'Calendar' as uri, 
IF(fk_target, fk_target, '0') as ctag, 'Calendrier BIMP-ERP' as description, 0 as calendarorder, '' as calendarcolor, 
'' as timezone, 'VEVENT,VTODO' as components, 
0 as transparent FROM " . MAIN_DB_PREFIX . "user u LEFT JOIN " . MAIN_DB_PREFIX . "element_element ON sourcetype = 'user' AND targettype = 'idCaldav' AND fk_source = u.rowid)",
        
        "CREATE OR REPLACE VIEW users AS (SELECT `rowid` as id, `login` as username, `pass` as digesta1 FROM " . MAIN_DB_PREFIX . "user);",

        " CREATE OR REPLACE VIEW principals AS (SELECT `rowid` as id, LOWER(login) as login, CONCAT('principals/', LOWER(`login`)) as uri, `email`, `login` as displayname, 'null' as vcardurl FROM `" . MAIN_DB_PREFIX . "user`);",

        " "
//        . "CREATE OR REPLACE VIEW calendarobjects AS (SELECT e.`id`, etag, uri, agendaplus, e.`fk_user_action` as calendarid, '3715' as size, UNIX_TIMESTAMP(e.`tms`) as lastmodified, 'VEVENT' as componenttype, UNIX_TIMESTAMP(e.`datep`) as firstoccurence, UNIX_TIMESTAMP(e.`datep2`) as lastoccurence FROM `" . MAIN_DB_PREFIX . "actioncomm` e, " . MAIN_DB_PREFIX . "synopsiscaldav_event WHERE fk_object = e.id AND e.`datep2` + INTERVAL 3 MONTH > now());");
          . "CREATE OR REPLACE VIEW calendarobjects AS (SELECT e.`id`, dtstamp, sequence, etag, uri, agendaplus, participentExt, organisateur, IF(ar.fk_element, ar.fk_element, e.`fk_user_action`) as calendarid, '97150' as size, UNIX_TIMESTAMP(e.`tms`) as lastmodified, UNIX_TIMESTAMP(e.`datec`) as CREATED, 'VEVENT' as componenttype, UNIX_TIMESTAMP(e.`datep`) as firstoccurence, UNIX_TIMESTAMP(e.`datep2`) as lastoccurence FROM `" . MAIN_DB_PREFIX . "actioncomm` e LEFT JOIN " . MAIN_DB_PREFIX . "actioncomm_resources ar ON ar.fk_actioncomm = e.id AND ar.element_type = 'user', " . MAIN_DB_PREFIX . "synopsiscaldav_event WHERE fk_object = e.id AND e.`datep2` + INTERVAL 24 MONTH > now()  AND fk_action NOT IN (3,8,9,10,30,31,40));");
    
    global $db, $conf;
    $sql2 = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."actioncomm WHERE id NOT IN (SELECT `fk_object` FROM `".MAIN_DB_PREFIX."synopsiscaldav_event`) ");
    if($sql2){
        while($result = $db->fetch_object($sql2)){
                $sql[] ="INSERT INTO ".MAIN_DB_PREFIX."synopsiscaldav_event (etag, uri, fk_object) VALUES ('".random2(15)."', '".$conf->global->MAIN_APPLICATION_TITLE.(defined("CHAINE_CALDAV")? "-".CHAINE_CALDAV : "")."-".$result->id.".ics', '".$result->id."')";
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


function random2($car) {
$string = "";
$chaine = "abcdefghijklmnpqrstuvwxy";
srand((double)microtime()*1000000);
for($i=0; $i<$car; $i++) {
$string .= $chaine[rand()%strlen($chaine)];
}
return $string;
}
?>
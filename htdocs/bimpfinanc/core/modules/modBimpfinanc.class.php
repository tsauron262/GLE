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
include_once(DOL_DOCUMENT_ROOT ."/bimpcore/core/BimpModules.class.php");

/**     \class      modProspectBabel
  \brief      Classe de description et activation du module de Prospection Babel
 */
class modBimpfinanc extends BimpModules {
    var $numero = 8599;
    var $description = "Bimp Financier";
    var $picto = 'margin';


}
?>
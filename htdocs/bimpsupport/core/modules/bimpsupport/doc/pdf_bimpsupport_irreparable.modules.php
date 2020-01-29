<?php
/* Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2008      Raphael Bertrand     <raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2015 Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel   <christophe.battarel@altairis.fr>
 * Copyright (C) 2012      Cedric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2017      Ferran Marcet        <fmarcet@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/propale/doc/pdf_azur.modules.php
 *	\ingroup    propale
 *	\brief      Fichier de la classe permettant de generer les propales au modele Azur
 */
require_once DOL_DOCUMENT_ROOT.'/bimpcore/pdf/classes/OrderPDF.php';


/**
 *	Class to generate PDF proposal Azur
 */
class pdf_bimpsupport_irreparable extends BimpDocumentPDF
{
    public function initData() {
        parent::initData();
        static::$use_cgv = false;
        $this->pdf->addCgvPages = false;
    }
    
    protected function initHeader()
    {
        parent::initHeader();
        
        
        $this->header_vars['doc_name'] = 'Dossier n° :';
    }
    
    public function renderBottom(){
        
    }
    
    public function renderLines(){
        $equipment = $this->object->getChildObject('equipment');
        
        $text = "<h1>ATTESTATION DE NON-REPARABILITE</h1>

<p>Nous soussigné, ".($this->fromCompany->name == "OLYS" ? "Bimp OLYS SAS" : $this->fromCompany->name).", ".$this->fromCompany->address." - ".$this->fromCompany->zip." ".$this->fromCompany->town.", 
agissant en qualité de revendeur informatique, 

attestons que le produit :

".$equipment->getData('product_label')."
N° de série : ".$equipment->getData('serial')."

a fait l’objet d’un diagnostic par notre service technique et est considéré comme non réparable. 

Il est classé obsolète par la marque Apple et ne peut être pris en charge par nos services de réparation.

Cette attestation est délivrée à la demande du destinataire, pour servir et faire valoir ce que de droit.

Le Service Après-Vente</p>";
        
        
        
        
        $this->writeContent('<div>'.str_replace("\n", "<br/>", $text).'</div>');
    }
    
    public function getFilePath(){
        return DOL_DATA_ROOT."/bimpcore/sav/".$this->object->id."/";
    }
    
    public function getFileName(){
        return "Obsolete-".parent::getFileName();
    }
}



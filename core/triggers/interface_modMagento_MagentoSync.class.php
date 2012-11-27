<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2008 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.*//*
  * GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Create on : 4-1-2009
  *
  * Infos on http://www.synopsis-erp.com
  *
  *//*
 *
 * $Id: interface_modMagento_MagentoSync.class.php,
 * v 1.1 2008/01/06 20:33:49
 * hregis Exp $
 */

/**
        \file       htdocs/includes/triggers/interface_modPhenix_MagentoSync.class.php
        \ingroup    phenix
        \brief      Fichier de gestion des triggers magento
*/


/**
        \class      InterfaceMagentoSync
        \brief      Classe des fonctions triggers des actions magento
*/

class InterfaceMagentoSync
{
    var $db;
    var $error;

    var $date;
    var $duree;
    var $texte;
    var $desc;

    /**
     *   \brief      Constructeur.
     *   \param      DB      Handler d'acces base
     */
    function InterfaceMagentoSync($DB)
    {
        $this->db = $DB ;

        $this->name = "MagentoSync";
        $this->family = "OldGleModule";
        $this->description = "Les triggers de ce composant permettent de synchroniser Magento et GLE";
        $this->version = '0.1';                        // 'experimental' or 'dolibarr' or version
    }

    /**
     *   \brief      Renvoi nom du lot de triggers
     *   \return     string      Nom du lot de triggers
     */
    function getName()
    {
        return $this->name;
    }

    /**
     *   \brief      Renvoi descriptif du lot de triggers
     *   \return     string      Descriptif du lot de triggers
     */
    function getDesc()
    {
        return $this->description;
    }

    /**
     *   \brief      Renvoi version du lot de triggers
     *   \return     string      Version du lot de triggers
     */
    function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'experimental') return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return GLE_VERSION;
        elseif ($this->version) return $this->version;
        else return $langs->trans("Unknown");
    }

    /**
     *      \brief      Fonction appelee lors du declenchement d'un evenement Dolibarr.
     *                  D'autres fonctions run_trigger peuvent etre presentes dans includes/triggers
     *      \param      action      Code de l'evenement
     *      \param      object      Objet concerne
     *      \param      user        Objet user
     *      \param      lang        Objet lang
     *      \param      conf        Objet conf
     *      \return     int         <0 si ko, 0 si aucune action faite, >0 si ok
     */
    function run_trigger($action,$object,$user,$langs,$conf)
    {

        //init nécéssaire à l'activation et à la descativaton de Magento
        //dans init, faire un populate du calendar

        // Mettre ici le code e executer en reaction de l'action

        require_once(DOL_DOCUMENT_ROOT . '/Magento/magento_product.class.php');
        $langs->load("synopsisGene@Synopsis_Tools");
        $db=$this->db;

        $mag_Product= new magento_product($conf);


        switch ($action)
        {
            case 'BILL_CANCEL':
                //si from magento => Cancel order on magento
            break;
            case 'BILL_UNPAYED':
                //si from magento => Cancel order on magento
            break;
            case 'BILL_CREATE':
            break;
            case 'BILL_DELETE':
            break;
            case 'BILL_PAYED':
                //si from magento => Update sale on magento
            break;
            case 'BILL_SENTBYMAIL':
            break;
            case 'BILL_MODIFY':
            case 'BILL_VALIDATE':
                //si from magento => Update sale on magento
            break;
            case 'BILL_SUPPLIER_VALIDATE':
            break;
            case 'COMPANY_CREATE':
            //Si creer compte magneto = oui (conf ou formulaire)
            break;
            case 'COMPANY_DELETE':
            //Si la societe à un compte magneto => desactive /efface le compte
            break;
            case 'COMPANY_MODIFY':
            //Si compte magneto modifier
            break;
            case 'CONTACT_MODIFY':
            //Si compte magneto modifier
            case 'CONTACT_CREATE':
            //Si creer compte magento = oui (conf / formulaire)
            break;
            case 'CONTACT_DELETE':
            //on efface/desactive le contact dans magento si le contact a un compte
            break;
//            case 'CONTRAT_LIGNE_MODIFY':

            case 'ORDER_CREATE':
            break;
            case 'ORDER_DELETE':
            //si dans magento, on desactive/ efface dans magento
            break;
            case 'ORDER_SENTBYMAIL':
            break;
            case 'ORDER_VALIDATE':
            //si dans magento, on valide dans magento
            break;

            case 'ORDER_SUPPLIER_CREATE':
            break;
            case 'ORDER_SUPPLIER_VALIDATE':
            break;

            case 'PAYMENT_CUSTOMER_CREATE':
            break;
            case 'PAYMENT_SUPPLIER_CREATE':
            break;

            case 'PRODUCT_CREATE':
            //TODO test
            //si option "dans magento" => create prod in magento
                $mag_Product->connect();
                $mag_Product->createProductMagento($attrSet,$ref,$name,$shortDesc,$desc,$price,$weight,$status);
            break;
            case 'PRODUCT_DELETE':
            //TODO test
                $mag_Product->connect();
                $mag_Product->deleteProductMagento($ref);
            //si dans magento => efface / deactivate product
            break;
            case 'PRODUCT_MODIFY':
            //TODO test
                $mag_Product->connect();
                $mag_Product->upadteProductMagento($ref,$name,$shortDesc,$desc,$price,$weight,$status);
            //si dans magento => modify product
            break;

            case 'PRODUCT_CAT_CREATE':
            //TODO
            //si option "dans magento" => create prod in magento
                $mag_Product->connect();
            break;
            case 'PRODUCT_CAT_DELETE':
            //TODO
                $mag_Product->connect();
            //si dans magento => efface / deactivate product
            break;
            case 'PRODUCT_CAT_MODIFY':
            //TODO
            //si dans magento => modify product cat
            break;

            case 'PRODUCT_IMG_CREATE':
                $mag_Product->connect();
                $mag_Product->createProductImage($sku,$label,$imgFile,"image/jpeg","image",1);//1 = position
            //si dans magento => modify product
            break;
            case 'PRODUCT_IMG_DELETE':
            //TODO
            //si dans magento => modify product
            break;

//nouveau trigger
            case 'EXPEDITION_CREATE':
            //si dans magento => modify expedition/status commande
            break;
            case 'EXPEDITION_DELETE':
            //si dans magento => modify expedition/status commande
            break;
            case 'EXPEDITION_VALIDATE':
            //si dans magento => modify expedition/status commande
            break;
            case 'LIVRAISON_CREATE':
            //si dans magento => modify expedition/status commande
            break;
            case 'EXPEDITION_VALID_FROM_DELIVERY':
            case 'EXPEDITION_CREATE_FROM_DELIVERY':
            break;
            case 'LIVRAISON_VALID':
            //si dans magento => modify expedition/status commande
            break;
            case 'LIVRAISON_DELETE':
            //si dans magento => modify expedition/status commande
            break;

/***** Admin *******/
            case 'USER_CREATE':
            //si dans magento => ajoute un utilisateur magento (back end)
            break;
            case 'USER_DELETE':
            //si dans magento => efface/desactive un utilisateur magento (back end)
            break;
            case 'USER_DISABLE':
            //si dans magento => desactive un utilisateur magento (back end)
            break;
            case 'USER_ENABLE':
            //si dans magento => active un utilisateur magento (back end)
            break;
            case 'USER_MODIFY':
            //si dans magento => modify l utilisateur magento (back end)
            break;
            case 'USER_ENABLEDISABLE':
                // si dans magnto && utilisateur enable => enbale sinon disabled
            break;
            case 'USER_LOGIN':
            break;
            case 'USER_LOGIN_FAILED':
            break;
            case 'USER_CHANGERIGHT':
                //si change droit magento => ???
            break;
            case 'USER_NEW_PASSWORD':
                // on modifie le password Magento
            break;
        }
        return 0;
    }
}
?>

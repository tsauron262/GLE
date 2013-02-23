<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2008 Regis Houssin        <regis.houssin@capnetworks.com>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/
/*
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
  */
/*
 *
 * $Id: interface_modPhenix_SynchroAffaire.class.php,v 1.1 2008/01/06 20:33:49 hregis Exp $
 */

/**
        \file       htdocs/includes/triggers/interface_modZimbra_SynchroAffaire.class.php
        \ingroup    phenix
        \brief      Fichier de gestion des triggers Zimbra
*/



/**
        \class      InterfaceSynchroAffaire
        \brief      Classe des fonctions triggers des actions Zimbra
*/

class InterfaceSynchroAffaire
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
    function InterfaceSynchroAffaire($DB)
    {
        $this->db = $DB ;

        $this->name = "SynchroAffaire";
        $this->family = "OldGleModule";
        $this->description = "Les triggers de ce composant permettent d'ins&eacute;rer un &eacute;v&egrave;nement dans le calendrier Zimbra pour chaque &eacute;v&egrave;nement Dolibarr.";
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

        //init nécéssaire à l'activation et à la descativaton de zimbra
        //dans init, faire un populate du calendar

        // Mettre ici le code a executer en reaction de l'action
global $langs;
        $langs->load("synopsisGene@Synopsis_Tools");
        switch ($action)
        {

            case 'ACTION_CREATE':
            break;
            case 'ACTION_DELETE':
            break;
            case 'ACTION_UPDATE':
            break;
            case 'BILL_CANCEL':
                delAffaireElement('facture',$object->id);
            break;
            case 'BILL_UNPAYED':
            break;
            case 'BILL_CREATE':
                addAffaireElement('facture',$object);
            break;
            case 'BILL_DELETE':
                delAffaireElement('facture',$object->id);
            break;
            case 'BILL_PAYED':
            break;
            case 'BILL_SENTBYMAIL':
            break;
            case 'BILL_MODIFY':
            case 'BILL_VALIDATE':
            break;
            case 'BILL_SUPPLIER_VALIDATE':
            break;
            case 'COMPANY_CREATE':
            break;
            case 'COMPANY_DELETE':
            break;
            case 'COMPANY_MODIFY':
            break;
            case 'CONTACT_MODIFY':
            case 'CONTACT_CREATE':
            break;
            case 'CONTACT_DELETE':
            break;
            case 'CONTRAT_LIGNE_MODIFY':
            case 'CONTRACT_SERVICE_ACTIVATE':
            break;
            case 'CONTRACT_SERVICE_CLOSE':
            break;
            case 'CONTRACT_CANCEL':
            break;
            case 'CONTRACT_CLOSE':
            break;
            case 'CONTRACT_CREATE':
                addAffaireElement('contrat',$object);
            break;
            case 'CONTRACT_DELETE':
                delAffaireElement('contrat',$object->id);
            break;
            case 'CONTRACT_VALIDATE':
            break;
            case 'ORDER_CREATE':
                addAffaireElement('commande',$object);
            break;
            case 'ORDER_DELETE':
                delAffaireElement('commande',$object->id);
            break;
            case 'ORDER_SENTBYMAIL':
            break;
            case 'ORDER_VALIDATE':
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
            break;
            case 'PRODUCT_DELETE':
            break;
            case 'PRODUCT_MODIFY':
            break;
            case 'PROPAL_CLOSE_REFUSED':
            break;
            case 'PROPAL_CLOSE_SIGNED':
            break;
            case 'PROPAL_DELETE':
                delAffaireElement('propale',$object->id);
            break;
            case 'PROPAL_CREATE':
                addAffaireElement('propale',$object);
            break;
            case 'PROPAL_MODIFY':
            break;
            case 'PROPAL_SENTBYMAIL':
            break;
            case 'PROPAL_VALIDATE':
            break;
            case 'EXPEDITION_CREATE':
                addAffaireElement('expedition',$object);
            break;
            case 'EXPEDITION_DELETE':
                delAffaireElement('expedition',$object->id);
            break;
            case 'EXPEDITION_VALIDATE':
            break;
            case 'LIVRAISON_CREATE':
                addAffaireElement('livraison',$object);
            break;
            case 'EXPEDITION_VALID_FROM_DELIVERY':
            break;
            case 'EXPEDITION_CREATE_FROM_DELIVERY':
                addAffaireElement('livraison',$object);
            break;
            case 'LIVRAISON_VALID':
            break;
            case 'LIVRAISON_DELETE':
                delAffaireElement('livraison',$object->id);
            break;
            case 'PROJECT_CREATE':
            break;
            case 'PROJECT_UPDATE':
            break;
            case 'PROJECT_DELETE':
                delAffaireElement('projet',$object->id);
            break;
            case 'PROJECT_CREATE_TASK_ACTORS':
            break;
            case 'PROJECT_CREATE_TASK':
            break;
            case 'PROJECT_CREATE_TASK_TIME':
            break;
            case 'PROJECT_CREATE_TASK_TIME_EFFECTIVE':
            break;
            case 'PROJECT_UPDATE_TASK':
            break;
            case 'PROJECT_DEL_TASK':
            break;
            case 'FICHEINTER_VALIDATE':
            break;
            case 'FICHEINTER_CREATE':
                addAffaireElement('FI',$object);
            break;
            case 'FICHEINTER_UPDATE':
            break;
            case 'FICHEINTER_DELETE':
                delAffaireElement('FI',$object->id);
            break;
            case 'DEMANDEINTERV_CREATE':
                addAffaireElement('DI',$object);
            break;
            case 'DEMANDEINTERV_UPDATE':
            break;
            case 'DEMANDEINTERV_VALIDATE':
            break;
            case 'DEMANDEINTERV_PRISENCHARGE':
            break;
            case 'DEMANDEINTERV_CLOTURE':
            break;
            case 'DEMANDEINTERV_DELETE':
                delAffaireElement('DI',$object->id);
            break;
            case 'DEMANDEINTERV_SETDELIVERY':
            break;
        //new
            case 'CAMPAGNEPROSPECT_CREATE':
            break;
            case 'CAMPAGNEPROSPECT_UPDATE':
            break;
            case 'CAMPAGNEPROSPECT_VALIDATE':
            break;
            case 'CAMPAGNEPROSPECT_LANCER':
            break;
            case 'CAMPAGNEPROSPECT_CLOTURE':
            break;
            case 'CAMPAGNEPROSPECT_NEWACTION': //V2
            break;
            case 'CAMPAGNEPROSPECT_CLOSE':
            break;
            case 'CAMPAGNEPROSPECT_NEWPRISECHARGE':
            break;

/***** Admin *******/
            case 'USER_CREATE':
            break;
            case 'USER_DELETE':
            break;
            case 'USER_DISABLE':
            break;
            case 'USER_ENABLE':
            break;
            case 'USER_MODIFY':
            break;
            case 'USER_ENABLEDISABLE':
            break;
            case 'USER_LOGIN':
            break;
            case 'USER_LOGIN_FAILED':
            break;
            case 'USER_CHANGERIGHT':
            break;
            case 'USER_NEW_PASSWORD':
            break;




        }

        return 0;
    }

}
function delAffaireElement($type,$id)
{
    global $db;
    $requete = "DELETE FROM Babel_Affaire_Element WHERE type='".$type."' AND element_id = ".$id;
    $db->query($requete);
}

function addSqlAffaireElement($type,$eid,$affaireId)
{
    global $user;
    global $db;
    $requete ="INSERT INTO `Babel_Affaire_Element`
                           (`type`,`element_id`,`datea`,`fk_author`,`affaire_refid`)
                    VALUES
                           ('".$type."', ".$eid.", 'now();' , ".$user->id.", ".$affaireId.")";
    $sql = $db->query($requete);
}

function addAffaireElement($type,$obj)
{
    global $user;
    //Det Affaire ID

    switch ($type)
    {
        case "commande":
        {
            //Si une propal liee a une commande est dans une affaire, on insert dans la meme affaire
            $requete ="SELECT affaire_refid
                         FROM ".MAIN_DB_PREFIX."co_pr, Babel_Affaire_Element
                        WHERE fk_commande = ".$obj->id ."
                          AND fk_propale = element_id
                          AND type='propale'";
            $sql = $obj->db->query($requete);
            while ($res=$obj->db->fetch_object($sql))
            {
                addSqlAffaireElement($type,$obj->id,$res->affaire_refid);
            }
        }
        break;
        case "facture":
        {
            $requete ="SELECT affaire_refid
                         FROM ".MAIN_DB_PREFIX."co_fa, Babel_Affaire_Element
                        WHERE fk_facture = ".$obj->id ."
                          AND fk_commande = element_id
                          AND type='commande'";
            $sql = $obj->db->query($requete);
            while ($res=$obj->db->fetch_object($sql))
            {
                addSqlAffaireElement($type,$obj->id,$res->affaire_refid);
            }
        }
        break;
        case "livraison":
        {
            $requete ="SELECT affaire_refid
                         FROM ".MAIN_DB_PREFIX."co_liv, Babel_Affaire_Element
                        WHERE fk_livraison = ".$obj->id ."
                          AND fk_commande = element_id
                          AND type='commande'";
            $sql = $obj->db->query($requete);
            while ($res=$obj->db->fetch_object($sql))
            {
                addSqlAffaireElement($type,$obj->id,$res->affaire_refid);
            }
            $requete ="SELECT affaire_refid
                         FROM ".MAIN_DB_PREFIX."co_exp, Babel_Affaire_Element
                        WHERE fk_livraison = ".$obj->id ."
                          AND fk_expedition = element_id
                          AND type='expedition'";
            $sql = $obj->db->query($requete);
            while ($res=$obj->db->fetch_object($sql))
            {
                addSqlAffaireElement($type,$obj->id,$res->affaire_refid);
            }
        }
        break;
        case "expedition":
        {
            $requete ="SELECT affaire_refid
                         FROM ".MAIN_DB_PREFIX."co_exp, Babel_Affaire_Element
                        WHERE fk_expedition = ".$obj->id ."
                          AND fk_commande = element_id
                          AND type='commande'";
            $sql = $obj->db->query($requete);
            while ($res=$obj->db->fetch_object($sql))
            {
                addSqlAffaireElement($type,$obj->id,$res->affaire_refid);
            }
        }
        break;
        case "facture fournisseur":
        {
            $requete ="SELECT affaire_refid
                         FROM Babel_li_fourn_co_fa, Babel_Affaire_Element
                        WHERE fk_facture = ".$obj->id ."
                          AND fk_commande = element_id
                          AND type='commande'";
            $sql = $obj->db->query($requete);
            while ($res=$obj->db->fetch_object($sql))
            {
                addSqlAffaireElement($type,$obj->id,$res->affaire_refid);
            }
        }
        break;
        case "FI":
        {
            
                    $tabDI = $obj->getDI();
            $requete ="SELECT affaire_refid
                         FROM Babel_Affaire_Element
                        WHERE element_id IN (".  implode(",",$tabDI).")
                          AND type='DI'";
            $sql = $obj->db->query($requete);
            while ($res=$obj->db->fetch_object($sql))
            {
                addSqlAffaireElement($type,$obj->id,$res->affaire_refid);
            }
        }
        break;
    }

}

?>

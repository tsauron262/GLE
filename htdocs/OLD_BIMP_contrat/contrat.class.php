<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Destailleur Laurent  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2008      Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
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
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/
/*
  * GLE by Synopsis & DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Create on : 4-1-2009
  *
  * Infos on http://www.synopsis-erp.com
  *
  */
/*
 */

/**
        \file       htdocs/contrat/class/contrat.class.php
        \ingroup    contrat
        \brief      Fichier de la classe des contrats
        \version    $Id: contrat.class.php,v 1.106 2008/07/12 10:31:59 eldy Exp $
*/

require_once(DOL_DOCUMENT_ROOT."/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/price.lib.php");


/**
        \class      Contrat
        \brief      Classe permettant la gestion des contrats
*/
class Contrat extends CommonObject
{
    public $db;
    public $error;
    public $element='contrat';
    public $table_element='contrat';
    public $table_element_line='contratdet';
    public $fk_element='fk_contrat';

    public $id;
    public $ref;
    public $socid;
    public $societe;        // Objet societe
    public $statut=0;        // 0=Draft,
    public $product;

    public $user_author;
    public $user_service;
    public $user_cloture;
    public $date_creation;
    public $date_validation;

    public $date_contrat;
    public $date_cloture;

    public $commercial_signature_id;
    public $commercial_suivi_id;

    public $note;
    public $note_public;

    public $fk_projet;

    public $lignes=array();
    public $linkedArray = array();

    //Modif post GLE 1.0
    public $newContractLigneId = "";
    //fin Modif


    /**
     *    \brief      Constructeur de la classe
     *    \param      DB          handler acces base de donnees
     */
    public function Contrat($DB)
    {
        global $langs;

        $this->db = $DB ;
        $this->product = new Product($DB);
        $this->societe = new Societe($DB);
        $this->user_service = new User($DB);
        $this->user_cloture = new User($DB);
    }

    /**
     *      \brief      Active une ligne detail d'un contrat
     *      \param      user        Objet User qui avtice le contrat
     *      \param      line_id     Id de la ligne de detail e activer
     *      \param      date        Date d'ouverture
     *      \param      date_end    Date fin prevue
     *      \return     int         < 0 si erreur, > 0 si ok
     */
    public function active_line($user, $line_id, $date, $date_end='')
    {
        global $langs,$conf;

        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."contratdet SET statut = 4,";
        $sql.= " date_ouverture = '".$this->db->idate($date)."',";
        if ($date_end) $sql.= " date_fin_validite = '".$this->db->idate($date_end)."',";
        $sql.= " fk_user_ouverture = ".$user->id.",";
        $sql.= " date_cloture = null";
        $sql.= " WHERE rowid = ".$line_id;// . " AND (statut = 0 OR statut = 3 OR statut = 5)";
//print $sql;
        dolibarr_syslog("Contrat::active_line sql=".$sql);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('CONTRACT_SERVICE_ACTIVATE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // Fin appel triggers

            $this->db->commit();
            return 1;
        } else {
            $this->error=$this->db->lasterror();
            dolibarr_syslog("Contrat::active_line error ".$this->error);
            $this->db->rollback();
            return -1;
        }
    }


    /**
     *      \brief      Active une ligne detail d'un contrat
     *      \param      user        Objet User qui avtice le contrat
     *      \param      line_id     Id de la ligne de detail e activer
     *      \param      date_end     Date fin
     *      \return     int         <0 si erreur, >0 si ok
     */
    public function close_line($user, $line_id, $date_end)
    {
        global $langs,$conf;

        // statut actif : 4

        $sql = "UPDATE ".MAIN_DB_PREFIX."contratdet SET statut = 5,";
        $sql.= " date_cloture = '".$this->db->idate($date_end)."',";
        $sql.= " fk_user_cloture = ".$user->id;
        $sql.= " WHERE rowid = ".$line_id . " AND statut = 4";

        $resql = $this->db->query($sql) ;
        if ($resql)
        {
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('CONTRACT_SERVICE_CLOSE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // Fin appel triggers

            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }

    public function cancel_line($user, $line_id)
    {
        global $langs,$conf;

        // statut actif : 4

        $sql = "UPDATE ".MAIN_DB_PREFIX."contratdet SET statut = 0 ";
        $sql.= " WHERE rowid = ".$line_id . " AND statut = 4";
        $resql = $this->db->query($sql) ;
        if ($resql)
        {
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('CONTRACT_SERVICE_CLOSE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // Fin appel triggers

            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }

    /**
     *    \brief      Cloture un contrat
     *    \param      user      Objet User qui cloture
     *    \param      langs     Environnement langue de l'utilisateur
     *    \param      conf      Environnement de configuration lors de l'operation
     *
     */
    public function cloture($user,$langs='',$conf='')
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."contrat SET statut = 2";
        $sql .= " , date_cloture = now(), fk_user_cloture = ".$user->id;
        $sql .= " WHERE rowid = ".$this->id . " AND statut = 1";

        $resql = $this->db->query($sql) ;
        if ($resql)
        {
            $this->use_webcal=($conf->global->PHPWEBCALENDAR_CONTRACTSTATUS=='always'?1:0);

            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('CONTRACT_CLOSE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // Fin appel triggers

            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }

    /**
     *    \brief      Valide un contrat
     *    \param      user      Objet User qui valide
     *    \param      langs     Environnement langue de l'utilisateur
     *    \param      conf      Environnement de configuration lors de l'operation
     */
    public function validate($user,$langs,$conf)
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."contrat SET statut = 1 ,date_valid=now()";
        $sql .= " WHERE rowid = ".$this->id. " AND statut = 0";
        $resql = $this->db->query($sql) ;
        if ($resql)
        {
            $this->use_webcal=($conf->global->PHPWEBCALENDAR_CONTRACTSTATUS=='always'?1:0);

            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('CONTRACT_VALIDATE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // Fin appel triggers

            return 1;
        } else {
            $this->error=$this->db->error();
            return -1;
        }
    }


    public function devalidate($user,$langs,$conf)
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."contrat SET statut = 0 ,date_valid=now()";
        $sql .= " WHERE rowid = ".$this->id. " AND statut != 0";
        $resql = $this->db->query($sql) ;
        if ($resql)
        {

            return 1;
        } else {
            $this->error=$this->db->error();
            return -1;
        }
    }

    /**
     *    \brief      Annule un contrat
     *    \param      user      Objet User qui annule
     *    \param      langs     Environnement langue de l'utilisateur
     *    \param      conf      Environnement de configuration lors de l'operation
     */
    public function annule($user,$langs='',$conf='')
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."contrat SET statut = 0";
        $sql .= " , date_cloture = now(), fk_user_cloture = ".$user->id;
        $sql .= " WHERE rowid = ".$this->id . " AND statut = 1";

        $resql = $this->db->query($sql) ;
        if ($resql)
        {
            //$this->use_webcal=($conf->global->PHPWEBCALENDAR_CONTRACTSTATUS=='always'?1:0);

            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('CONTRACT_CANCEL',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // Fin appel triggers

            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }

    /**
     *    \brief      Chargement depuis la base des donnees du contrat
     *    \param      id      Id du contrat a charger
     *    \return     int     <0 si ko, id du contrat charge si ok
     */
    public function fetch($id)
    {
        $sql = "SELECT rowid, statut, ref, fk_soc, mise_en_service as datemise,";
        $sql.= " fk_user_mise_en_service, date_contrat as datecontrat,modelPdf,";
        $sql.= " UNIX_TIMESTAMP(date_valid) as dateValidation,  fin_validite as datefin,";
        $sql.= " fk_user_author,";
        $sql.= " fk_projet,";
        $sql.= " linkedTo,";
        $sql.= " is_financement,";
        $sql.= " fk_commercial_signature, fk_commercial_suivi,";
        $sql.= " note, note_public, type, condReg_refid, modeReg_refid";
        $sql.= " FROM ".MAIN_DB_PREFIX."contrat WHERE rowid = ".$id;

        dolibarr_syslog("Contrat::fetch sql=".$sql);
        $resql = $this->db->query($sql) ;
        if ($resql)
        {
            $result = $this->db->fetch_array($resql);

            if ($result)
            {
                $this->id                = $result["rowid"];
                $this->ref               = (!isset($result["ref"]) || !$result["ref"]) ? $result["rowid"] : $result["ref"];
                $this->statut            = $result["statut"];
                $this->factureid         = $result["fk_facture"];
                $this->facturedetid      = $result["fk_facturedet"];
                $this->mise_en_service   = $result["datemise"];
                $this->condReg_refid     = $result['condReg_refid'];
                $this->modeReg_refid     = $result['modeReg_refid'];
                $this->date_fin_validite = $result["datefin"];
                $this->date_validation   = $result["dateValidation"];
                $this->linkedTo          = $result["linkedTo"];
                $this->modelPdf          = $result['modelPdf'];

                $this->date_contrat      = $result["datecontrat"];

                $this->user_author_id    = $result["fk_user_author"];

                $this->commercial_signature_id = $result["fk_commercial_signature"];
                $this->commercial_suivi_id = $result["fk_commercial_suivi"];

                $this->user_service->id  = $result["fk_user_mise_en_service"];
                $this->user_cloture->id  = $result["fk_user_cloture"];

                $this->note              = $result["note"];
                $this->note_public       = $result["note_public"];

                $this->is_financement = $result['is_financement'];
                $this->fk_projet         = $result["fk_projet"];

                $this->socid            = $result["fk_soc"];
                $this->type            = $result["type"];
                $this->typeContrat     = $result["type"];
                $this->societe->fetch($result["fk_soc"]);

                $this->db->free($resql);

                return $this->id;
            } else {
                dolibarr_syslog("Contrat::Fetch Erreur contrat non trouve");
                $this->error="Contrat non trouve";
                return -2;
            }
        } else {
            dolibarr_syslog("Contrat::Fetch Erreur lecture contrat");
            $this->error=$this->db->error();
            return -1;
        }

    }
    public function getExtraHeadTab($head)
    {
        return ($head);
    }
    /**
     *      \brief      Reinitialise le tableau lignes
     */
    public function fetch_lignes()
    {
        $this->nbofserviceswait=0;
        $this->nbofservicesopened=0;
        $this->nbofservicesclosed=0;
        $this->lignes=array();
        // Selectionne les lignes contrats liees a un produit
        $sql = "SELECT p.label,
                       p.description as product_desc,
                       p.ref,
                       d.rowid,
                       d.statut,
                       d.description,
                       d.price_ht,
                       d.tva_tx,
                       d.line_order,
                       d.total_ht,
                       d.qty,
                       d.remise_percent,
                       d.subprice,
                       d.info_bits,
                       d.fk_product,
                       date_format(d.date_ouverture_prevue,'%d/%m/%Y') as date_ouverture_prevue,
                       date_format(d.date_ouverture ,'%d/%m/%Y') as date_ouverture,
                       date_format(d.date_fin_validite,'%d/%m/%Y') as date_fin_validite,
                       date_format(d.date_cloture,'%d/%m/%Y') as date_cloture
                  FROM ".MAIN_DB_PREFIX."contratdet as d,
                       ".MAIN_DB_PREFIX."product as p
                 WHERE d.fk_contrat = ".$this->id ." AND d.fk_product = p.rowid
              ORDER BY d.line_order, d.rowid ASC";

        dolibarr_syslog("Contrat::fetch_lignes sql=".$sql);
        $result = $this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);
            $i = 0;

            while ($i < $num)
            {
                $objp                  = $this->db->fetch_object($result);

                $ligne                 = new ContratLigne($this->db);
                $ligne->id             = $objp->rowid;
                $ligne->desc           = $objp->description;  // Description ligne
                $ligne->description    = $objp->description;  // Description ligne
                $ligne->qty            = $objp->qty;
                $ligne->tva_tx         = $objp->tva_tx;
                $ligne->subprice       = $objp->subprice;
                $ligne->statut            = $objp->statut;
                $ligne->remise_percent = $objp->remise_percent;
                $ligne->price          = $objp->total_ht;
                $ligne->total_ht          = $objp->total_ht;
                $ligne->fk_product     = $objp->fk_product;

                if ($objp->fk_product > 0)
                {
                    $product = new Product($this->db);
                    $product->id =$objp->fk_product;
                    $product->fetch($objp->fk_product);
                    $ligne->product=$product;
                } else {
                    $ligne->product=false;
                }

                $ligne->info_bits      = $objp->info_bits;

                $ligne->ref            = $objp->ref;
                $ligne->libelle        = $objp->label;        // Label produit
                $ligne->product_desc   = $objp->product_desc; // Description produit

                $ligne->date_debut_prevue = $objp->date_ouverture_prevue;
                $ligne->date_debut_reel   = $objp->date_ouverture;
                $ligne->date_fin_prevue   = $objp->date_fin_validite;
                $ligne->date_fin_reel     = $objp->date_cloture;
                if ($objp->line_order != 0)
                {
                    $this->lignes[$objp->line_order]        = $ligne;
                } else {
                    $this->lignes[]        = $ligne;
                }
                //dolibarr_syslog("1 ".$ligne->desc);
                //dolibarr_syslog("2 ".$ligne->product_desc);

                if ($ligne->statut == 0) $this->nbofserviceswait++;
                if ($ligne->statut == 4) $this->nbofservicesopened++;
                if ($ligne->statut == 5) $this->nbofservicesclosed++;

                $i++;
            }
            $this->db->free($result);
        } else {
            dolibarr_syslog("Contrat::Fetch Erreur lecture des lignes de contrats liees aux produits");
            return -3;
        }

        // Selectionne les lignes contrat liees a aucun produit
        $sql = "SELECT d.rowid,
                       d.statut,
                       d.qty,
                       d.description,
                       d.price_ht,
                       d.total_ht,
                       d.subprice,
                       d.tva_tx,
                       d.line_order,
                       d.rowid,
                       d.remise_percent,
                       date_format(d.date_ouverture_prevue,'%d/%m/%Y') as date_ouverture_prevue,
                       date_format(d.date_ouverture ,'%d/%m/%Y') as date_ouverture,
                       date_format(d.date_fin_validite,'%d/%m/%Y') as date_fin_validite,
                       date_format(d.date_cloture,'%d/%m/%Y') as date_cloture
                 FROM ".MAIN_DB_PREFIX."contratdet as d
                WHERE d.fk_contrat = ".$this->id ."
                  AND (d.fk_product IS NULL OR d.fk_product = 0)";   // fk_product = 0 garde pour compatibilite

        $result = $this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);
            $i = 0;

            while ($i < $num)
            {
                $objp                  = $this->db->fetch_object($result);
                $ligne                 = new ContratLigne($this->db);
                $ligne->id                = $objp->rowid;
                $ligne->libelle        = stripslashes($objp->description);
                $ligne->desc           = stripslashes($objp->description);
                $ligne->qty            = $objp->qty;
                $ligne->statut            = $objp->statut;
                $ligne->ref            = $objp->ref;
                $ligne->tva_tx         = $objp->tva_tx;
                $ligne->subprice       = $objp->subprice;
                $ligne->remise_percent = $objp->remise_percent;
                $ligne->price          = $objp->total_ht;
                $ligne->total_ht          = $objp->total_ht;
                $ligne->fk_product     = 0;

                $ligne->date_debut_prevue = $objp->date_ouverture_prevue;
                $ligne->date_debut_reel   = $objp->date_ouverture;
                $ligne->date_fin_prevue   = $objp->date_fin_validite;
                $ligne->date_fin_reel     = $objp->date_cloture;

                if ($ligne->statut == 0) $this->nbofserviceswait++;
                if ($ligne->statut == 4) $this->nbofservicesopened++;
                if ($ligne->statut == 5) $this->nbofservicesclosed++;
                if ($objp->line_order != 0)
                {
                    $this->lignes[$objp->line_order]        = $ligne;
                } else {
                    $this->lignes[]        = $ligne;
                }

                $i++;
            }

            $this->db->free($result);
        } else {
            dolibarr_syslog("Contrat::Fetch Erreur lecture des lignes de contrat non liees aux produits");
            $this->error=$this->db->error();
            return -2;
        }

        $this->nbofservices=sizeof($this->lignes);

        ksort($this->lignes);
        return $this->lignes;
    }

    /**
     *      \brief      Cree un contrat vierge en base
     *      \param      user        Utilisateur qui cree
     *      \param      langs       Environnement langue de l'utilisateur
     *      \param      conf        Environnement de configuration lors de l'operation
     *      \return     int         <0 si erreur, id contrat cre sinon
     */
     public $linkedTo;



    public function create($user,$langs='',$conf='')
    {
        // Check parameters
        $paramsok=1;
        if ($this->commercial_signature_id <= 0)
        {
            $langs->load("commercial");
            $this->error.=$langs->trans("ErrorFieldRequired",$langs->trans("SalesRepresentativeSignature"));
            $paramsok=0;

        }
        if ($this->commercial_suivi_id <= 0)
        {
            $langs->load("commercial");
            $this->error.=($this->error?"<br>":'');
            $this->error.=$langs->trans("ErrorFieldRequired",$langs->trans("SalesRepresentativeFollowUp"));
            $paramsok=0;
        }

        if (! $paramsok) return -5;

        $this->db->begin();
        $this->verifyNumRef();
        // Insert contract
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."contrat (type,datec, fk_soc, fk_user_author, date_contrat";
        $sql.= ", fk_commercial_signature, fk_commercial_suivi,ref, linkedTo";
        if ($this->fk_projet ."x" != "x")
        {
            $sql .= ", fk_projet";
        }
        if ($this->is_financement ."x" != "x")
        {
            $sql .= ", is_financement, cessionnaire_refid, fournisseur_refid ";
        }
        $sql.= " )";
        $sql.= " VALUES (".$this->typeContrat.",now(),".$this->socid.",".$user->id;
        $sql.= ",".$this->db->idate($this->date_contrat);
        $sql.= ",".($this->commercial_signature_id>0?$this->commercial_signature_id:"NULL");
        $sql.= ",".($this->commercial_suivi_id>0?$this->commercial_suivi_id:"NULL");
        $sql .= ", " . (strlen($this->ref)<=0 ? "null" : "'".$this->ref."'");
        $sql.= ", '".$this->linkedTo."'";
        if ($this->fk_projet ."x" != "x")
        {
            $sql .= " ,  ".$this->fk_projet;
        }
        if ($this->is_financement ."x" != "x")
        {
            $sql .= " ,  ".$this->is_financement;
            if ($this->cessionnaire_refid > 0)
            {
                $sql .= " ,  ".$this->cessionnaire_refid;
            } else {
                $sql .= " ,  null";
            }
            if ($this->fournisseur_refid > 0)
            {
                $sql .= " ,  ".$this->fournisseur_refid;
            } else {
                $sql .= " ,  null";
            }

        }
        $sql.= " )";
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $error=0;

            $this->id = $this->db->last_insert_id("".MAIN_DB_PREFIX."contrat");

            // Insere contacts commerciaux ('SALESREPSIGN','contrat')
            $result=$this->add_contact($this->commercial_signature_id,'SALESREPSIGN','internal');
            if ($result < 0) $error++;

            // Insere contacts commerciaux ('SALESREPFOLL','contrat')
            $result=$this->add_contact($this->commercial_suivi_id,'SALESREPFOLL','internal');
            if ($result < 0) $error++;


            if ($conf->global->MAIN_MODULE_BABELGA == 1)
            {
                $resultNoCount=$this->add_contact($this->client_signataire_refid,'BILLING','external');
                //no need if from propal
            }
            $requete = false;
            //Si type contrat = 2 3 4 7
            if ($this->typeContrat == 2){
                $requete = "INSERT INTO Babel_GMAO_contrat_prop
                                        (contrat_refid,tms,qte,hotline,telemaintenance,maintenance,isSAV)
                                 VALUES (".$this->id.",now(),-1,0,0,0,0)";
            } else if ($this->typeContrat == 3)
            {
                $requete = "INSERT INTO Babel_GMAO_contrat_prop
                                        (contrat_refid,tms,hotline,telemaintenance,maintenance,isSAV)
                                 VALUES (".$this->id.",now(),0,0,1,0)";
            }else if ($this->typeContrat == 4)
            {
                $requete = "INSERT INTO Babel_GMAO_contrat_prop
                                        (contrat_refid,tms, hotline,telemaintenance,maintenance,isSAV)
                                 VALUES (".$this->id.",now(),0,0,0,1)";
            }else if ($this->typeContrat == 7)
            {
                $requete = "INSERT INTO Babel_GMAO_contrat_prop
                                        (contrat_refid,tms, hotline,telemaintenance,maintenance,isSAV)
                                 VALUES (".$this->id.",now(),0,0,0,0)";
            }
            $sql = $this->db->query($requete);
            if (! $error || $error == 0)
            {
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('CONTRACT_CREATE',$this,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
                // Fin appel triggers

                if (! $error)
                {
                    $this->db->commit();
                    return $this->id;
                } else {
                    $this->error=$interface->error;
                    dolibarr_syslog("Contrat::create - 30 - ".$this->error);

                    $this->db->rollback();
                    return -3;
                }
            } else {
                $this->error="Failed to add contract extra data";
                dolibarr_syslog("Contrat::create - 20 - ".$this->error);
                $this->db->rollback();
                return -2;
            }
        } else {
            $this->error=$langs->trans("UnknownError: ".$this->db->error()." - sql=".$sql);
            dolibarr_syslog("Contrat::create - 10 - ".$this->error);
            $this->db->rollback();
            return -1;
        }
    }
    public function getTypeContrat()
    {
        $array[0]['type']="Simple";
        $array[0]['Nom']="Simple";
        $array[1]['type']="Service";
        $array[1]['Nom']="Service";
        $array[2]['type']="Ticket";
        $array[2]['Nom']="Au ticket";
        $array[3]['type']="Maintenance";
        $array[3]['Nom']="Maintenance";
        $array[4]['type']="SAV";
        $array[4]['Nom']="SAV";
        $array[5]['type']="Location";
        $array[5]['Nom']="Location de produits";
        $array[6]['type']="LocationFinanciere";
        $array[6]['Nom']="Location Financi&egrave;re";
        $array[7]['type']="Mixte";
        $array[7]['Nom']="Mixte";
        return ($array[$this->typeContrat]);

    }
    public function getTypeContrat_noLoad($id)
    {
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."contrat WHERE rowid = ".$id;
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        return($res->type);
    }

    /**
     *      \brief      Supprime l'objet de la base
     *      \param      user        Utilisateur qui supprime
     *      \param      langs       Environnement langue de l'utilisateur
     *      \param      conf        Environnement de configuration lors de l'operation
     *      \return     int         < 0 si erreur, > 0 si ok
     */
    public function delete($user,$langs='',$conf='')
    {
        $error=0;

        $this->db->begin();

        if (! $error)
        {
            // Delete element_contact
            /*
            $sql = "DELETE ec";
            $sql.= " FROM ".MAIN_DB_PREFIX."element_contact as ec, ".MAIN_DB_PREFIX."c_type_contact as tc";
            $sql.= " WHERE ec.fk_c_type_contact = tc.rowid";
            $sql.= " AND tc.element='".$this->element."'";
            $sql.= " AND ec.element_id=".$this->id;
            */

            $sql = "SELECT ec.rowid as ecrowid";
            $sql.= " FROM ".MAIN_DB_PREFIX."element_contact as ec, ".MAIN_DB_PREFIX."c_type_contact as tc";
            $sql.= " WHERE ec.fk_c_type_contact = tc.rowid";
            $sql.= " AND tc.element='".$this->element."'";
            $sql.= " AND ec.element_id=".$this->id;

            dolibarr_syslog("Contrat::delete element_contact sql=".$sql,LOG_DEBUG);
            $resql=$this->db->query($sql);
            if (! $resql)
            {
                $this->error=$this->db->error();
                $error++;
            }
            $numressql=$this->db->num_rows($resql);
            if (! $error && $numressql )
            {
                $tab_resql=array();
                for($i=0;$i<$numressql;$i++)
                {
                    $objresql=$this->db->fetch_object($resql);
                    $tab_resql[]= $objresql->ecrowid;
                }
                $this->db->free($resql);

                $sql= "DELETE FROM ".MAIN_DB_PREFIX."element_contact ";
                $sql.= " WHERE ".MAIN_DB_PREFIX."element_contact.rowid IN (".implode(",",$tab_resql).")";

                dolibarr_syslog("Contrat::delete element_contact sql=".$sql,LOG_DEBUG);
                $resql=$this->db->query($sql);
                if (! $resql)
                {
                    $this->error=$this->db->error();
                    $error++;
                }
            }
        }

        if (! $error)
        {
            // Delete contratdet_log
            /*
            $sql = "DELETE cdl";
            $sql.= " FROM ".MAIN_DB_PREFIX."contratdet_log as cdl, ".MAIN_DB_PREFIX."contratdet as cd";
            $sql.= " WHERE cdl.fk_contratdet=cd.rowid AND cd.fk_contrat=".$this->id;
            */
            $sql = "SELECT cdl.rowid as cdlrowid ";
            $sql.= " FROM ".MAIN_DB_PREFIX."contratdet_log as cdl, ".MAIN_DB_PREFIX."contratdet as cd";
            $sql.= " WHERE cdl.fk_contratdet=cd.rowid AND cd.fk_contrat=".$this->id;

            dolibarr_syslog("Contrat::delete contratdet_log sql=".$sql, LOG_DEBUG);
            $resql=$this->db->query($sql);
            if (! $resql)
            {
                $this->error=$this->db->error();
                $error++;
            }
            $numressql=$this->db->num_rows($resql);
            if (! $error && $numressql )
            {
                $tab_resql=array();
                for($i=0;$i<$numressql;$i++)
                {
                    $objresql=$this->db->fetch_object($resql);
                    $tab_resql[]= $objresql->cdlrowid;
                }
                $this->db->free($resql);

                $sql= "DELETE FROM ".MAIN_DB_PREFIX."contratdet_log ";
                $sql.= " WHERE ".MAIN_DB_PREFIX."contratdet_log.rowid IN (".implode(",",$tab_resql).")";

                dolibarr_syslog("Contrat::delete contratdet_log sql=".$sql, LOG_DEBUG);
                $resql=$this->db->query($sql);
                if (! $resql)
                {
                    $this->error=$this->db->error();
                    $error++;
                }
            }
        }

        if (! $error)
        {
            // Delete contratdet
            $sql = "DELETE FROM ".MAIN_DB_PREFIX."contratdet";
            $sql.= " WHERE fk_contrat=".$this->id;

            dolibarr_syslog("Contrat::delete contratdet sql=".$sql, LOG_DEBUG);
            $resql=$this->db->query($sql);
            if (! $resql)
            {
                $this->error=$this->db->error();
                $error++;
            }
        }

        if (! $error)
        {
            // Delete contrat
            $sql = "DELETE FROM ".MAIN_DB_PREFIX."contrat";
            $sql.= " WHERE rowid=".$this->id;

            dolibarr_syslog("Contrat::delete contrat sql=".$sql);
            $resql=$this->db->query($sql);
            if (! $resql)
            {
                $this->error=$this->db->error();
                $error++;
            }
        }

        if (! $error)
        {
//            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('CONTRACT_DELETE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // Fin appel triggers

            $this->db->commit();
            return 1;
        } else {
            $this->error=$this->db->error();
            dolibarr_syslog("Contrat::delete ERROR ".$this->error);
            $this->db->rollback();
            return -1;
        }
    }


    /**
     *      \brief      Ajoute une ligne de contrat en base
     *      \param      desc                Description de la ligne
     *      \param      pu_ht                  Prix unitaire HT
     *      \param      qty                 Quantite
     *      \param      txtva               Taux tva
     *      \param      fk_product          Id produit
     *      \param      remise_percent      Pourcentage de remise de la ligne
     *      \param      date_start          Date de debut prevue
     *      \param      date_end            Date de fin prevue
    *        \param        price_base_type        HT ou TTC
     *         \param        pu_ttc                 Prix unitaire TTC
     *         \param        info_bits            Bits de type de lignes
     *      \return     int                 <0 si erreur, >0 si ok
     */
    public function addline($desc, $pu_ht, $qty, $txtva, $fk_product=0, $remise_percent=0, $date_start, $date_end, $price_base_type='HT', $pu_ttc=0, $info_bits=0,$commandeDet=false)
    {
        global $langs, $conf, $user;

        dolibarr_syslog("Contrat::addline $desc, $pu_ht, $qty, $txtva, $fk_product, $remise_percent, $date_start, $date_end, $price_base_type, $pu_ttc, $info_bits");
        if ($this->statut == 0 || ($this->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED))
        {
            $this->db->begin();

            // Clean parameters
            $remise_percent=price2num($remise_percent);
            $qty=price2num($qty);
            if (! $qty) $qty=1;
            if (! $ventil) $ventil=0;
            if (! $info_bits) $info_bits=0;
            $pu_ht=price2num($pu_ht);
            $pu_ttc=price2num($pu_ttc);
            $txtva=price2num($txtva);

            if ($price_base_type=='HT'||$price_base_type."x"=='x')
            {
                $pu=$pu_ht;
            } else {
                $pu=$pu_ttc;
            }
            // Calcul du total TTC et de la TVA pour la ligne a partir de
            // qty, pu, remise_percent et txtva
            // TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
            // la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.
            $tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva, 0, $price_base_type, $info_bits);
            $total_ht  = $tabprice[0];
            $total_tva = $tabprice[1];
            $total_ttc = $tabprice[2];
            // \TODO A virer
            // Anciens indicateurs: $price, $remise (a ne plus utiliser)
            $remise = 0;
            $price = price2num(round($pu, 2));
            if (strlen($remise_percent) > 0)
            {
                $remise = round(($pu * $remise_percent / 100), 2);
                $price = $pu - $remise;
            }
            $hasDateEnd = false;
            $dateEndHasInterval = false;
            if (preg_match('/\+([0-9]*)/',$date_end,$arr)){
                $hasDateEnd=true;
                $dateEndHasInterval=$arr[1];
            }else if ($date_end > 0){
                $hasDateEnd=true;
            }

            //rang
            $requete = "SELECT max(line_order) as mx FROM ".MAIN_DB_PREFIX."contratdet WHERE fk_contrat = ".$this->id;
            $sql = $this->db->query($requete);
            $res = $this->db->fetch_object($sql);
            $cnt = $res->mx + 1;
            $lineOrder = ($res->mx."x"=="x"?1:$cnt);
            // Insertion dans la base
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."contratdet";
            $sql.= " (line_order, fk_contrat, label, description, fk_product, qty, tva_tx,";
            $sql.= " remise_percent, subprice,fk_user_author, ";
            $sql.= " total_ht, total_tva, total_ttc,";
            $sql.= " info_bits,";
            $sql.= " price_ht, remise";                                // \TODO A virer
            if ($commandeDet) { $sql .= ",fk_commande_ligne ";}
            if ($date_start > 0) { $sql.= ",date_ouverture_prevue"; }
            if ($hasDateEnd)  { $sql.= ",date_fin_validite"; }
            $sql.= ") VALUES ($lineOrder, $this->id, '" . addslashes($label) . "','" . addslashes($desc) . "',";
            $sql.= ($fk_product>0 ? $fk_product : "null").",";
            $sql.= " '".$qty."',";
            $sql.= " '".$txtva."',";
            $sql.= " ".price2num($remise_percent).",".price2num($pu).",".$user->id .",";
            $sql.= " ".price2num($total_ht).",".price2num($total_tva).",".price2num($total_ttc).",";
            $sql.= " '".$info_bits."',";
            $sql.= " ".price2num($price).",".price2num( $remise);    // \TODO A virer
            if ($commandeDet){ $sql .= ",".$commandeDet; }
            if ($date_start > 0) { $sql.= ",".$this->db->idate($date_start); }
            if ($hasDateEnd) {
                if($dateEndHasInterval){
                     $sql.= ", date_add(date_ouverture_prevue,INTERVAL ".$dateEndHasInterval." MONTH)";
                } else {
                     $sql.= ",".$this->db->idate($date_end);
                }
                 }
            $sql.= ")";
            dolibarr_syslog("Contrat::addline sql=".$sql);
            $resql=$this->db->query($sql);
            if ($resql)
            {
                $lastid = $this->db->last_insert_id("".MAIN_DB_PREFIX."contratdet");
                $result=$this->update_total_contrat();
                if ($result > 0)
                {

                    $label="cont-".trim($this->id).'-opt'.$lastid;
                    $requet = "UPDATE ".MAIN_DB_PREFIX."contratdet SET label ='".$label."' WHERE rowid = ".$lastid;
                    $this->db->query($requet);


                    $this->db->commit();
                    //Modif post GLE 1.0
                    $this->newContractLigneId =  $lastid;
                    //fin Modif
                    return $lastid;
                } else {
                    dolibarr_syslog("Error sql=$sql, error=".$this->error,LOG_ERR);
                    $this->db->rollback();
                    return -1;
                }
            } else {
                $this->db->rollback();
                $this->error=$this->db->error()." sql=".$sql;
                dolibarr_syslog("Contrat::addline ".$this->error,LOG_ERR);
                return -2;
            }
        } else {
            dolibarr_syslog("Contrat::addline ErrorTryToAddLineOnValidatedContract", LOG_ERR);
            return -3;
        }
    }

    /**
     *      \brief     Mets a jour une ligne de contrat
     *      \param     rowid            Id de la ligne de facture
     *      \param     desc             Description de la ligne
     *      \param     pu               Prix unitaire
     *      \param     qty              Quantite
     *      \param     remise_percent   Pourcentage de remise de la ligne
     *      \param     date_start       Date de debut prevue
     *      \param     date_end         Date de fin prevue
     *      \param     tvatx            Taux TVA
     *      \param     date_debut_reel  Date de debut reelle
     *      \param     date_fin_reel    Date de fin reelle
     *      \return    int              < 0 si erreur, > 0 si ok
     */
    public function updateline($rowid, $desc, $pu, $qty, $remise_percent=0,
         $date_start='', $date_end='', $tvatx,
         $date_debut_reel='', $date_fin_reel='',$fk_prod=false,$fk_commandedet=false)
    {
        // Nettoyage parametres
        $qty=trim($qty);
        $desc=trim($desc);
        $desc=trim($desc);
        $price = price2num($pu);
        $tvatx = price2num($tvatx);
        $subprice = $price;
        $remise = 0;
        if (strlen($remise_percent) > 0)
        {
            $remise = round(($pu * $remise_percent / 100), 2);
            $price = $pu - $remise;
        } else {
            $remise_percent=0;
        }
            if (! $info_bits) $info_bits=0;
            $pu_ht=price2num($pu);
            $pu_ttc=price2num($pu_ttc);
            $txtva=price2num($tvatx);

            // Calcul du total TTC et de la TVA pour la ligne a partir de
            // qty, pu, remise_percent et txtva
            // TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
            // la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.
            $tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva, 0, "HT", $info_bits);
            $total_ht  = $tabprice[0];
            $total_tva = $tabprice[1];
            $total_ttc = $tabprice[2];

            // \TODO A virer
            // Anciens indicateurs: $price, $remise (a ne plus utiliser)
            $remise = 0;


        dolibarr_syslog("Contrat::UpdateLine $rowid, $desc, $pu, $qty, $remise_percent, $date_start, $date_end, $date_debut_reel, $date_fin_reel, $tvatx");

        $this->db->begin();
        $sql = "UPDATE ".MAIN_DB_PREFIX."contratdet set description='".addslashes($desc)."'";
        $sql .= ",price_ht='" .     price2num($price)."'";
        $sql .= ",subprice='" .     price2num($price)."'";
        $sql .= ",total_ht='" .     price2num($total_ht)."'";
        $sql .= ",total_tva='" .     price2num($total_tva)."'";
        $sql .= ",total_ttc='" .     price2num($total_ttc)."'";
        $sql .= ",remise='" .       price2num($remise)."'";
        $sql .= ",remise_percent='".price2num($remise_percent)."'";
        $sql .= ",qty='$qty'";
        if($fk_commandedet) { $sql .= ",fk_commande_ligne=".$fk_commandedet." "; }
        $sql .= ",tva_tx='".        price2num($tvatx)."'";
        if ($fk_prod && $fk_prod != 0) { $sql .= ", fk_product=".$fk_prod; }
        else if ($fk_prod == 0){ $sql .= ", fk_product = NULL ";}
        if ($date_start > 0) { $sql.= ",date_ouverture_prevue=".$this->db->idate($date_start); }
        else { $sql.=",date_ouverture_prevue=null"; }
        if ($date_end > 0) { $sql.= ",date_fin_validite=".$this->db->idate($date_end); }
        else { $sql.=",date_fin_validite=null"; }
        if ($date_debut_reel > 0) { $sql.= ",date_ouverture=".$this->db->idate($date_debut_reel); }
        else { $sql.=",date_ouverture=null"; }
        if ($date_fin_reel > 0) { $sql.= ",date_cloture=".$this->db->idate($date_fin_reel); }
        else { $sql.=",date_cloture=null"; }
        $sql .= " WHERE rowid = ".$rowid;
//print $sql;
        dolibarr_syslog("Contrat::UpdateLine sql=".$sql);
        $result = $this->db->query($sql);
        if ($result)
        {
            $result=$this->update_total_contrat();
            if ($result >= 0)
            {
                $this->db->commit();
                return 1;
            } else {
                $this->db->rollback();
                dolibarr_syslog("Contrat::UpdateLigne Erreur -2");
                return -2;
            }
        } else {
            $this->db->rollback();
            $this->error=$this->db->error();
            dolibarr_syslog("Contrat::UpdateLigne Erreur -1");
            return -1;
        }
    }

    /**
     *      \brief      Delete a contract line
     *      \param      idline        Id of line to delete
     *        \param      user        User that delete
     *      \return     int         >0 if OK, <0 if KO
     */
    public function delete_line($idline,$user)
    {
        global $conf, $langs;

        if ($contrat->statut == 0 ||
            ($contrat->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED) )
        {
            $sql = "DELETE FROM ".MAIN_DB_PREFIX."contratdet";
            $sql.= " WHERE rowid=".$idline;

            dolibarr_syslog("Contratdet::delete sql=".$sql);
            $resql = $this->db->query($sql);
            if (! $resql)
            {
                $this->error="Error ".$this->db->lasterror();
                dolibarr_syslog("Contratdet::delete ".$this->error, LOG_ERR);
                return -1;
            }

            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('CONTRACTLINE_DELETE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // Fin appel triggers

            return 1;
        } else {
            return -2;
        }
    }


    /**
     *      \brief      Update statut of contract according to services
     *        \return     int     <0 si ko, >0 si ok
     */
    public function update_total_contrat()
    {
        //Change le montant du contrat , retourn le total HT
        $requete = "SELECT sum(total_ht) as totHT, sum(total_ttc) as totTTC, sum(total_tva) as totTVA FROM ".MAIN_DB_PREFIX."contratdet WHERE fk_contrat = ".$this->id;
        //print $requete;
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        $tot = $res->totHT;
        $totttc = $res->totTTC;
        $tottva = $res->totTVA;
        $this->total_ht = $res->totHT;
        $this->total_tva = $totttc;
        $this->total_ttc = $tottva;

//        $requete ="UPDATE ".MAIN_DB_PREFIX."contrat set total_ht = ".$tot . " , total_ttc = ".$totttc." , tva = ".$tottva." WHERE id = ".$this->id;
//        $sql = $this->db->query($requete);
        return ($tot);

    }

    public function datediff($datefrom, $dateto) {

    $difference = $dateto - $datefrom; // Difference in seconds
    $bool=true;
    $nbMonth = 0;
    while ($bool){
        $nbMonth++;
        // 2010-(12+1)-01 => 139000000
        if (strtotime((date("m",$datefrom) + 1> 12?date("Y",$datefrom)+1:date("Y",$datefrom))."-".(date("m",$datefrom) + 1>12?1:date("m",$datefrom) + 1) ."-".date('d',$datefrom)) > $dateto){
            $bool=false;
        } else {
            $datefrom = strtotime((date("m",$datefrom) + 1> 12?date("Y",$datefrom)+1:date("Y",$datefrom))."-".(date("m",$datefrom) + 1>12?1:date("m",$datefrom) + 1) ."-".date('d',$datefrom));
        }
    }
    return $nbMonth;

}


    /**
    *        \brief      Retourne le libelle du statut du contrat
    *        \param      mode              0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
        *        \return     string          Label
        */
    public function getLibStatut($mode)
    {
        return $this->LibStatut($this->statut,$mode);
    }

    /**
        *        \brief      Renvoi le libelle d'un statut donne
        *        \param      statut          id statut
    *        \param      mode              0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
        *        \return     string          Libelle
        */
    public function LibStatut($statut,$mode)
    {
        global $langs;
        $langs->load("contracts");
        if ($mode == 0)
        {
            if ($statut == 0) { return $langs->trans("ContractStatusDraft").$text; }
            if ($statut == 1) { return $langs->trans("ContractStatusValidated").$text; }
            if ($statut == 2) { return $langs->trans("ContractStatusClosed").$text; }
        }
        if ($mode == 1)
        {
            if ($statut == 0) { return $langs->trans("ContractStatusDraft"); }
            if ($statut == 1) { return $langs->trans("ContractStatusValidated"); }
            if ($statut == 2) { return $langs->trans("ContractStatusClosed"); }
        }
        if ($mode == 2)
        {
            if ($statut == 0) { return img_picto($langs->trans('ContractStatusDraft'),'statut0').' '.$langs->trans("ContractStatusDraft"); }
            if ($statut == 1) { return img_picto($langs->trans('ContractStatusValidated'),'statut4').' '.$langs->trans("ContractStatusValidated"); }
            if ($statut == 2) { return img_picto($langs->trans('ContractStatusClosed'),'statut6').' '.$langs->trans("ContractStatusClosed"); }
        }
        if ($mode == 3)
        {
            if ($statut == 0) { return img_picto($langs->trans('ContractStatusDraft'),'statut0'); }
            if ($statut == 1) { return img_picto($langs->trans('ContractStatusValidated'),'statut4'); }
            if ($statut == 2) { return img_picto($langs->trans('ContractStatusClosed'),'statut6'); }
        }
        if ($mode == 4)
        {
            //Modif Babel Post 1.0
            $line=new ContratLigne($this->db);
            $text=($this->nbofserviceswait+$this->nbofservicesopened+$this->nbofservicesclosed);
            $text.=' '.$langs->trans("Services");
            $text.=': &nbsp; &nbsp; ';
            $srvWait = 0;
            if ("x".$this->nbofserviceswait != "x")
            {
                $srvWait= $this->nbofserviceswait;
            }
            $srvOpen = 0;
            if ("x".$this->nbofservicesopened != "x")
            {
                $srvOpen= $this->nbofservicesopened;
            }
            $srvClose = 0;
            if ("x".$this->nbofservicesclosed != "x")
            {
                $srvClose= $this->nbofservicesclosed;
            }

            $text.=$srvWait.' '.$line->LibStatut(0,3).' &nbsp; ';
            $text.=$srvOpen.' '.$line->LibStatut(4,3).' &nbsp; ';
            $text.=$srvClose.' '.$line->LibStatut(5,3);
//            return $text;

            if ($statut == 0) { return $text . "<br>Contrat : ".img_picto($langs->trans('ContractStatusDraft'),'statut0').' '.$langs->trans("ContractStatusDraft"); }
            if ($statut == 1) { return $text . "<br>Contrat : " . img_picto($langs->trans('ContractStatusValidated'),'statut4').' '.$langs->trans("ContractStatusValidated"); }
            if ($statut == 2) { return $text . "<br>Contrat : " . img_picto($langs->trans('ContractStatusClosed'),'statut6').' '.$langs->trans("ContractStatusClosed"); }
        }
        if ($mode == 5)
        {
            if ($statut == 0) { return $langs->trans("ContractStatusDraft").' '.img_picto($langs->trans('ContractStatusDraft'),'statut0'); }
            if ($statut == 1) { return $langs->trans("ContractStatusValidated").' '.img_picto($langs->trans('ContractStatusValidated'),'statut4'); }
            if ($statut == 2) { return $langs->trans("ContractStatusClosed").' '.img_picto($langs->trans('ContractStatusClosed'),'statut6'); }
        }
        //fin modif
    }


    /**
        \brief      Renvoie nom clicable (avec eventuellement le picto)
        \param        withpicto        0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
        \param        maxlength        Max length of ref
        \return        string            Chaine avec URL
    */
    public function getNomUrl($withpicto=0,$maxlength=0,$option=false)
    {
        global $langs;

        $result='';

        $lien = '<a href="'.DOL_URL_ROOT.'/contrat/fiche.php?id='.$this->id.'">';
        if ($option == 6){
            $lien = '<a href="'.GLE_FULL_ROOT.'/contrat/fiche.php?id='.$this->id.'">';
        }
        $lienfin='</a>';

        $picto='contract';

        $label=$langs->trans("ShowContract").': '.$this->ref;

//        if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
        if ($option == 6){
            if ($withpicto) $result.=($lien.img_object($label,$picto,false,false,false,true).$lienfin);
        } else {
            if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
        }

        if ($withpicto && $withpicto != 2) $result.=' ';
        if ($withpicto != 2) $result.=$lien.($maxlength?dol_trunc($this->ref,$maxlength):$this->ref).$lienfin;
        return $result;
    }

  /*
    *       \brief     Charge les informations d'ordre info dans l'objet contrat
    *       \param     id     id du contrat a charger
    */
    public function info($id=false)
    {
        if(!$id)$id = $this->id;
        $sql = "SELECT c.rowid, c.ref, datec as datec, date_cloture as date_cloture,";
        $sql.= "c.tms as date_modification,";
        $sql.= " fk_user_author, fk_user_cloture";
        $sql.= " fk_commercial_signature, fk_commercial_suivi";
        $sql.= " fk_user_mise_en_service";
        $sql.= " FROM ".MAIN_DB_PREFIX."contrat as c";
        $sql.= " WHERE c.rowid = ".$id;

        $result=$this->db->query($sql);
        if ($result)
        {
            if ($this->db->num_rows($result))
            {
                $obj = $this->db->fetch_object($result);

                $this->id = $obj->rowid;

                if ($obj->fk_user_author) {
                    $cuser = new User($this->db);
                    $cuser->fetch($obj->fk_user_author);
                    $this->user_creation     = $cuser;
                }

                if ($obj->fk_user_cloture) {
                    $cuser = new User($this->db);
                    $cuser->fetch($obj->fk_user_cloture);
                    $this->user_cloture = $cuser;
                }
                if ($obj->fk_commercial_signature) {
                    $cuser = new User($this->db);
                    $cuser->fetch($obj->fk_commercial_signature);
                    $this->commercial_signature     = $cuser;
                }
                if ($obj->fk_commercial_suivi) {
                    $cuser = new User($this->db);
                    $cuser->fetch($obj->fk_commercial_suivi);
                    $this->commercial_suivi     = $cuser;
                }
                if ($obj->fk_user_mise_en_service) {
                    $cuser = new User($this->db);
                    $cuser->fetch($obj->fk_user_mise_en_service);
                    $this->user_mise_en_service     = $cuser;
                }


                $this->ref                 = (! $obj->ref) ? $obj->rowid : $obj->ref;
                $this->date_creation     = $obj->datec;
                $this->date_modification = $obj->date_modification;
                $this->date_cloture      = $obj->date_cloture;
            }

            $this->db->free($result);

        }
        else
        {
            dol_print_error($this->db);
        }
    }


    /**
     *    \brief      Recupere les lignes de detail du contrat
     *    \param      statut      Statut des lignes detail e recuperer
     *    \return     array       Tableau des lignes de details
     */
    public function array_detail($statut=-1)
    {
        $tab=array();

        $sql = "SELECT cd.rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."contratdet as cd";
        $sql.= " WHERE fk_contrat =".$this->id;
        if ($statut >= 0) $sql.= " AND statut = '$statut'";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num=$this->db->num_rows($resql);
            $i=0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($resql);
                $tab[$i]=$obj->rowid;
                $i++;
            }
            return $tab;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }


    /**
     *      \brief      Charge indicateurs this->nbtodo et this->nbtodolate de tableau de bord
     *      \param      user        Objet user
     *      \param      mode        "inactive" pour services e activer, "expired" pour services expires
     *      \return     int         <0 si ko, >0 si ok
     */
    public function load_board($user,$mode)
    {
        global $conf, $user;

        $this->nbtodo=$this->nbtodolate=0;
        if ($mode == 'inactives')
        {
            $sql = "SELECT cd.rowid,cd.date_ouverture_prevue as datefin";
            if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", sc.fk_soc, sc.fk_user";
            $sql.= " FROM ".MAIN_DB_PREFIX."contrat as c, ".MAIN_DB_PREFIX."contratdet as cd";
            if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
            $sql.= " WHERE c.statut = 1 AND c.rowid = cd.fk_contrat";
            $sql.= " AND cd.statut = 0";
        }
        if ($mode == 'expired')
        {
            $sql = "SELECT cd.rowid,cd.date_fin_validite as datefin";
            if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", sc.fk_soc, sc.fk_user";
            $sql.= " FROM ".MAIN_DB_PREFIX."contrat as c, ".MAIN_DB_PREFIX."contratdet as cd";
            if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
            $sql.= " WHERE c.statut = 1 AND c.rowid = cd.fk_contrat";
            $sql.= " AND cd.statut = 4";
            $sql.= " AND cd.date_fin_validite < '".$this->db->idate(time())."'";
        }
        if ($user->societe_id) $sql.=" AND c.fk_soc = ".$user->societe_id;
        if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= " AND c.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
        $resql=$this->db->query($sql);
        if ($resql)
        {
            while ($obj=$this->db->fetch_object($resql))
            {
                $this->nbtodo++;
                if ($mode == 'inactives')
                    if ($obj->datefin && $obj->datefin < (time() - $conf->contrat->services->inactifs->warning_delay)) $this->nbtodolate++;
                if ($mode == 'expired')
                    if ($obj->datefin && $obj->datefin < (time() - $conf->contrat->services->expires->warning_delay)) $this->nbtodolate++;
            }
            return 1;
        }
        else
        {
            dol_print_error($this->db);
            $this->error=$this->db->error();
            return -1;
        }
    }

    public function isGA($id)
    {
        global $conf;
        if ($conf->global->MAIN_MODULE_BABELGA == 1)
        {
            $requete = "SELECT count(*) as cnt FROM ".MAIN_DB_PREFIX."contrat WHERE is_financement=1 AND rowid = ".$id;
//            print $requete;
            $sql= $this->db->query($requete);
            $res=$this->db->fetch_object($sql);
            if ($res->cnt > 0)
            {
                return (true);
            } else {
                return(false);
            }

        } else {
            return(false);
        }


    }

    /* gestion des contacts d'un contrat */

    /**
     *      \brief      Retourne id des contacts clients de facturation
     *      \return     array       Liste des id contacts facturation
     */
    public function getIdBillingContact()
    {
        return $this->getIdContact('external','BILLING');
    }

    /**
     *      \brief      Retourne id des contacts clients de prestation
     *      \return     array       Liste des id contacts prestation
     */
    public function getIdServiceContact()
    {
        return $this->getIdContact('external','SERVICE');
    }
    public function contratCheck_link()
    {
        $this->linkedArray['co'] = array();
        $this->linkedArray['pr'] = array();
        $this->linkedArray['fa'] = array();
        $db=$this->db;
        //check si commande ou propale ou facture
        if (preg_match('/^([c|p|f]{1})([0-9]*)/',$this->linkedTo,$arr))
        {
            //si commande check si propal lie a la commande / facture etc ...
            switch($arr[1])
            {
                case "p":
                    //test si commande facture
                    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."co_pr WHERE fk_propale = ".$arr[2];
                    if ($resql = $db->query($requete))
                    {
                        while ($res = $db->fetch_object($resql))
                        {
                            array_push($this->linkedArray['co'],$res->fk_commande);
                        }
                    }
                    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."fa_pr WHERE fk_propale = ".$arr[2];
                    if ($resql = $db->query($requete))
                    {
                        while ($res = $db->fetch_object($resql))
                        {
                            array_push($this->linkedArray['fa'],$res->fk_facture);
                        }
                    }
                break;
                case "c":
                    //test si commande propal ...
                    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."co_pr WHERE fk_commande = ".$arr[2];
                    if ($resql = $db->query($requete))
                    {
                        while ($res = $db->fetch_object($resql))
                        {
                            array_push($this->linkedArray['pr'],$res->fk_propale);
                        }
                    }
                    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."co_fa WHERE fk_commande = ".$arr[2];
                    if ($resql = $db->query($requete))
                    {
                        while ($res = $db->fetch_object($resql))
                        {
                            array_push($this->linkedArray['fa'],$res->fk_facture);
                        }
                    }
                break;
                case "f":
                    //test si propal facture ...
                    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."co_fa WHERE fk_facture = ".$arr[2];
                    if ($resql = $db->query($requete))
                    {
                        while ($res = $db->fetch_object($resql))
                        {
                            array_push($this->linkedArray['co'],$res->fk_commande);
                        }
                    }
                    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."fa_pr WHERE fk_facture = ".$arr[2];
                    if ($resql = $db->query($requete))
                    {
                        while ($res = $db->fetch_object($resql))
                        {
                            array_push($this->linkedArray['pr'],$res->fk_propal);
                        }
                    }
                break;
            }
        }
        //ajoute donnees dans les tables
//        var_dump($this->linkedArray);
    }
    function convDur($duration)
    {

        // Initialisation
        $duration = abs($duration);
        $converted_duration = array();

        // Conversion en semaines
        $converted_duration['weeks']['abs'] = floor($duration / (60*60*24*7));
        $modulus = $duration % (60*60*24*7);

        // Conversion en jours
        $converted_duration['days']['abs'] = floor($duration / (60*60*24));
        $converted_duration['days']['rel'] = floor($modulus / (60*60*24));
        $modulus = $modulus % (60*60*24);

        // Conversion en heures
        $converted_duration['hours']['abs'] = floor($duration / (60*60));
        $converted_duration['hours']['rel'] = floor($modulus / (60*60));
        $modulus = $modulus % (60*60);

        // Conversion en minutes
        $converted_duration['minutes']['abs'] = floor($duration / 60);
        $converted_duration['minutes']['rel'] = floor($modulus / 60);
        if ($converted_duration['minutes']['rel'] <10){$converted_duration['minutes']['rel'] ="0".$converted_duration['minutes']['rel']; } ;
        $modulus = $modulus % 60;

        // Conversion en secondes
        $converted_duration['seconds']['abs'] = $duration;
        $converted_duration['seconds']['rel'] = $modulus;

        // Affichage
        return( $converted_duration);
    }
    public function info_contratdet($id)
    {
        $sql = "SELECT DISTINCT fk_user_author";
        $sql.= " FROM ".MAIN_DB_PREFIX."contratdet as c";
        $sql.= " WHERE c.fk_contrat = ".$id;

        $sql1 = "SELECT DISTINCT fk_user_ouverture";
        $sql1.= " FROM ".MAIN_DB_PREFIX."contratdet as c";
        $sql1.= " WHERE c.fk_contrat = ".$id;

        $sql2 = "SELECT DISTINCT fk_user_cloture";
        $sql2.= " FROM ".MAIN_DB_PREFIX."contratdet as c";
        $sql2.= " WHERE c.fk_contrat = ".$id;

        $result=$this->db->query($sql);
        $this->user_creation=array();

        $result1=$this->db->query($sql1);
        $this->user_ouverture=array();

        $result2=$this->db->query($sql2);
        $this->user_cloture=array();
        if ($result && $result1 && $result2)
        {
            while ($obj = $this->db->fetch_object($result))
            {
                if ($obj->fk_user_author) {
                    $cuser = new User($this->db);
                    $cuser->fetch($obj->fk_user_author);
                    array_push($this->user_creation, $cuser);
                }
            }
            while ($obj = $this->db->fetch_object($result1))
            {
                if ($obj->fk_user_ouverture) {
                    $cuser = new User($this->db);
                    $cuser->fetch($obj->fk_user_ouverture);
                    array_push($this->user_ouverture, $cuser);
                }
            }
            while ($obj = $this->db->fetch_object($result2))
            {
                if ($obj->fk_user_cloture) {
                    $cuser = new User($this->db);
                    $cuser->fetch($obj->fk_user_cloture);
                    array_push($this->user_cloture, $cuser);
                }
            }


            $this->db->free($result);

        }
        else
        {
            dol_print_error($this->db);
        }
    }
    public function verifyNumRef()
    {
        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."contrat";
        $sql.= " WHERE ref = '".$this->ref."'";

        $result = $this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);
            if ($num > 0)
            {
                $this->ref = $this->getNextNumRef("");
            }
        }
    }
    public function getNextNumRef($soc)
    {
        global $db, $langs;
        $langs->load("contracts");
        $langs->load("babel");

        $dir = DOL_DOCUMENT_ROOT . "/includes/modules/contrat/";
        if (defined("CONTRAT_ADDON") && CONTRAT_ADDON)
        {
            $file = CONTRAT_ADDON.".php";

            // Chargement de la classe de numerotation
            $classname = CONTRAT_ADDON;
            require_once($dir.$file);

            $obj = new $classname();

            $numref = "";
            $numref = $obj->getNextValue($soc,$this);

            if ( $numref ."x" != "x")
            {
            return $numref;
            }
            else
            {
            dol_print_error($db,"Contrat::getNextNumRef ".$obj->error);
            return "";
            }
         } else {
            print $langs->trans("Error")." ".$langs->trans("Error_CONTRAT_ADDON_NotDefined");
            return "";
         }
  }

    public function calculateMonthlyAmortizingCost($totalLoan, $month, $interest )
    {
        $years = $month / 12;
        $tmp = pow((1 + ($interest / 1200)), ($years * 12));
        return round(($totalLoan * $tmp) * ($interest / 1200) / ($tmp - 1),2);
    }
    public function calculateTotalAmortizingCost($totalLoan, $month, $interest )
    {
        $years = $month / 12;
        $tmp = pow((1 + ($interest / 1200)), ($years * 12));
        return round(($years*12*(($totalLoan * $tmp) * ($interest / 1200) / ($tmp - 1))-$totalLoan),2);
    }
    public function displayPreLine()
    {
        //Do nothing
    }
    public function displayLine()
    {
        global $user, $conf, $lang;
            $html = "";
            $html .= "<ul id='sortable' style='list-style: none; padding-left: 0px; padding-top:0; margin-top: 0px;'>";
            $html .= '<li class="titre ui-state-default ui-widget-header"><table width=100%><tr><td><td>Description<td align=center style="width: 50px;">TVA
                                                <td align=center style="width: 50px;">PU HT
                                                <td align=center style="width: 50px;">Qt&eacute;
                                                <td align=center style="width: 50px;">Rem.
                                                <td align=center style="width: 50px;">Total
                                                <td align=center style="width: 50px;">Action</tr></table></li>';
            foreach($this->lignes as $key => $val)
            {
                $html .= $this->display1line($val);
            }
            $html .= "</ul>";
            return ($html);

    }
    public function displayAddLine($mysoc,$objp)
    {
        $html = '<right>';
        $html .= "     <span class='butAction' id='AddLineBut' style='margin: 25px;'>Ajouter une ligne</span>";
        $html .= '</right>';
        return($html);
    }
    public function initDialog($mysoc,$objp)
    {
        global $user;
        $html = "";
        if ($user->rights->contrat->creer && $this->statut ==0)
        {
            $html .= '<div id="addDialog" class="ui-state-default ui-corner-all" style="">';
            $html .= $this->displayDialog('add',$mysoc,$objp);
            $html .= '</div>';
        }
        if ($user->rights->contrat->supprimer)
        {
            $html .=  '<div id="delDialog"><span id="delDialog-content"></span></div>';
        }
        if ($user->rights->contrat->creer && ($this->statut ==0   || ($this->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED) ) )
        {
            $html .=  '<div id="modDialog"><span id="modDialog-content">';
            $html .=  $this->displayDialog('mod',$mysoc,$objp);
            $html .=  '</span></div>';
        }

        if ($user->rights->contrat->activer && $this->statut !=0)
        {
            $html .=  '<div id="activateDialog" class="ui-state-default ui-corner-all" style="">';
            $html .=  "<table width=450><tr><td>Date de d&eacute;but effective du service<td>";
            $html .=  "<input type='text' name='dateDebEff' id='dateDebEff'>";
            $html .=  "<tr><td>Date de fin effective du service<td>";
            $html .=  "<input type='text' name='dateFinEff' id='dateFinEff'>";
            $html .=  "</table>";
            $html .=  '</div>';
        }
        if ($user->rights->contrat->desactiver && $this->statut !=0)
        {
            $html .=  '<div id="unactivateDialog" class="ui-state-default ui-corner-all" style="">';
            $html .=  "<p>&Ecirc;tes vous sur de vouloir d&eacute;sactiver cette ligne&nbsp;?</p>";
            $html .=  '</div>';
        }
        //var_dump($user->rights->contrat);
        if ($user->rights->contrat->activer && $this->statut != 0)
        {
            $html .=  '<div id="closeLineDialog" class="ui-state-default ui-corner-all" style="">';
            $html .=  "<p>&Ecirc;tes vous sur de vouloir cl&ocirc;turer cette ligne&nbsp;?</p>";
            $html .=  '</div>';
        }


        return($html);
    }
    public function displayExtraInfoCartouche()
    {
        return "";
    }

    public function displayDialog($type='add',$mysoc,$objp)
    {
        global $conf, $form, $db;
        $html .=  '<div id="'.$type.'Line" class="ui-state-default ui-corner-all" style="">';
        $html .= "<form id='".$type."Form' method='POST' onsubmit='return(false);'>";

//        $html .=  '<span class="ui-state-default ui-widget-header ui-corner-all" style="margin-top: -4px; padding: 5px 35px 5px 35px;">Ajout d\'une ligne</span>';
        $html .=  '<table style="width: 900px; border: 1px Solid; border-collapse: collapse;margin-top: 20px;" cellpadding=10 >';
        $html .=  '<tr style="border-bottom: 1px Solid #0073EA !important">';
        $html .=  '<th style="border-bottom: 1px Solid #0073EA !important" colspan="4"  class="ui-widget-header">Recherche de produits & financement</th></tr>';
        $html .=  '<tr style="border-top: 1px Solid #0073EA !important">';
        $html .=  '<td style="width: 300px; padding-top: 5px; padding-bottom: 3px;">Produits</td>
                   <td style=" padding-top: 5px; padding-bottom: 3px;">';
            // multiprix
            $filter = "";
            switch ($this->type)
            {
                case 1:
                    //SAV
                    $filter="1";
                break;
            }
            if($conf->global->PRODUIT_MULTIPRICES == 1)
                $html .= $form->select_produits('','p_idprod_'.$type,$filter,$conf->produit->limit_size,$this->societe->price_level,1,true,false,false);
            else
                $html .= $form->select_produits('','p_idprod_'.$type,$filter,$conf->produit->limit_size,false,1,true,true,false);
            if (! $conf->global->PRODUIT_USE_SEARCH_TO_SELECT) $html .=  '<br>';



        $html .=  '</td><td  style=" padding-top: 5px; padding-bottom: 3px;border-right: 1px Solid #0073EA;">&nbsp;</td>';
        $html .=  '<tr>';
        $html .=  ' <td style="width: 300px; padding-top: 5px; padding-bottom: 3px;">Financement ? ';
        $html .=  ' </td>
                    <td style="width: 30px;">
                        <input type="checkbox" id="addFinancement'.$type.'"  name="addFinancement'.$type.'" /></td>
                    <td style="border-right: 1px Solid #0073EA; padding-top: 5px; padding-bottom: 3px;">&nbsp;</td>';
        $html .=  '</tr>';
        $html .=  "</table>";
        $html .=  '<table style="width: 900px; border: 1px Solid; border-collapse: collapse; margin-top: 5px; " cellpadding=10>';
        $html .=  '<tr>';
        $html .=  '<th style="border-bottom: 1px Solid #0073EA !important" colspan="4"  class="ui-widget-header">Description ligne / produit</th></tr>';
        $html .=  '<tr class="ui-state-default ui-widget-content" style="border: 1px Solid #0073EA;">';
        $html .=  '<td style="border-right: 1px Solid #0073EA;">';
        $html .=  'Description libre<br/>';
        $html .=  '<div class="nocellnopadd" id="ajdynfieldp_idprod_'.$type.'"></div>';
        $html .=  "<textarea style='width: 600px; height: 3em' name='".$type."Desc' id='".$type."Desc'></textarea>";
        $html .=  '</td>';
        $html .=  '</tr>';
        $html .=  "</table>";

        $html .=  '<table style=" width: 900px; border: 1px Solid; border-collapse: collapse; margin-top: 5px; " cellpadding=10>';
        $html .=  '<tr>';
        $html .=  '<th style="border-bottom: 1px Solid #0073EA !important; " colspan="8"  class="ui-widget-header">Prix & Quantit&eacute;</th></tr><tr style="padding: 10px; ">';
        $html .=  '<td align=right>Prix (&euro;)</td><td align=left>';
        $html .=  "<input id='".$type."Price' name='".$type."Price' style='width: 100px; text-align: center;'/>";
        $html .=  '</td>';
        $html .=  '<td align=right>TVA<td align=left width=180>';
        $html .= $form->select_tva($type."Linetva_tx","19.6",$mysoc,$this->societe,"",0,false);

        $html .=  '</td>';
        $html .=  '<td align=right>Qt&eacute;</td><td align=left>';
        $html .=  "<input id='".$type."Qty' value=1 name='".$type."Qty' style='width: 20px;  text-align: center;'/>";
        $html .=  '</td>';
        $html .=  '<td align=right>Remise (%)</td><td align=left>';
        $html .=  "<input id='".$type."Remise' value=0 name='".$type."Remise' style='width: 20px; text-align: center;'/>";
        $html .=  '</td>';
        $html .=  '</tr>';

        $html .=  '</table>';

        $html .=  '<table style="width: 900px;  border-collapse: collapse; margin-top: 5px;"  cellpadding=10>';
        $html .=  '<tr style="border-bottom: 1px Solid #0073EA; ">';
        $html .=  '<th colspan=10" class="ui-widget-header" style=" border-bottom: 1px Solid #0073EA;" >Chronologie</th>';
        $html .=  '</tr>';
        $html .=  "<tr  class='ui-state-default' style='border: 1px Solid #0073EA; '>";
        $html .=  '<td>Date de d&eacute;but pr&eacute;vue</td>';
        $html .=  '<td>
                        <input value="'. date('d').'/'.date('m').'/'.date('Y') .'" style="text-align: center;" type="text" name="dateDeb'.$type.'" id="dateDeb'.$type.'"/>'.img_picto('calendrier','calendar.png','style="float: left;margin-right: 3px; margin-top: 1px;"').'</td>';
        $html .=  '<td>Date de fin pr&eacute;vue</td>';
//        calendar.png
        $html .=  '<td style="border-right: 1px Solid #0073EA;">
                        <input style="text-align: center;" type="text" name="dateFin'.$type.'" id="dateFin'.$type.'"/>'.img_picto('calendrier','calendar.png','style="float: left; margin-right: 3px; margin-top: 1px;"').'</td>';
        $html .=  '</tr>';
        $html .=  "</table>";

        $html .= '<div id="financementLigne'.$type.'" style="display: none; margin-top: 5px;">';
        $html .=  '<table style="width: 900px;  border-collapse: collapse; "  cellpadding=10>';
        $html .=  '<tr style="border-bottom: 1px Solid #0073EA; ">';
        $html .=  '<th colspan=10" class="ui-widget-header" style=" border-bottom: 1px Solid #0073EA;" >Financement</th>';
        $html .=  '</tr>';
        $html .=  "<tr  class='ui-state-default' style='border: 1px Solid #0073EA; border-top: 1px Solid #0073EA;'>";
        $html .=  '<td align=right>Nombre de p&eacute;riode</td>';
        //TODO ds conf
        $html .=  '<td align=left><input style="text-align: center;width: 35px;" type="text" name="nbPeriode'.$type.'" id="nbPeriode'.$type.'"/></td>';
        $html .=  '<td align=right>Type de p&eacute;riode</td>';
        $html .=  '<td align=left><select id="typePeriod'.$type.'">';
        $requete = "SELECT * FROM Babel_financement_period ORDER BY id";
        $sqlPeriod = $db->query($requete);
        while ($res = $db->fetch_object($sqlPeriod))
        {
            $html .=  "<option value='".$res->id."'>".$res->Description."</option>";
        }
        $html .=  '</select>';
        $html .=  '</td>';
        //TODO dans conf taux par défaut configurable selon droit ++ droit de choisir le taux
        $html .=  '<td align=right>Taux achat</td>';
        $html .=  '<td align=left><input style="text-align: center; width: 35px;" name="'.$type.'TauxAchat" id="'.$type.'TauxAchat"/></td>';
        //TODO dans conf taux par défaut configurable selon droit + droit de choisir le taux
        $html .=  '<td align=right>Taux vente</td>';
        $html .=  '<td align=left style="border-right: 1px Solid #0073EA;">
                        <input style="text-align: center;width: 35px;" name="'.$type.'TauxVente" id="'.$type.'TauxVente"/></td>';
        $html .=  '</tr>';
        $html .=  "</table>";
        $html .=  '</div>';

        $html .=  '</form>';
        $html .=  '</div>';
        return ($html);

    }
    public function displayButton($nbofservices)
    {
        /*************************************************************
         * Boutons Actions
         *************************************************************/
        global $langs, $conf, $user;
        $html = "";
        if ($user->societe_id == 0)
        {
            $html .=  '<div class="tabsAction">';

            if ($this->statut == 0 && $nbofservices)
            {
                if ($user->rights->contrat->creer) $html .=  '<a class="butAction" href="fiche.php?id='.$this->id.'&amp;action=valid">'.$langs->trans("Validate").'</a>';
                else $html .=  '<a class="butActionRefused" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans("Validate").'</a>';
            }

            if ($conf->facture->enabled && $this->statut > 0)
            {
                $langs->load("bills");
                if ($user->rights->facture->creer) $html .=  '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&amp;contratid='.$this->id.'&amp;socid='.$this->societe->id.'">'.$langs->trans("CreateBill").'</a>';
                else $html .=  '<a class="butActionRefused" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans("CreateBill").'</a>';
            }

            if ($this->nbofservicesclosed < $nbofservices)
            {
                    $html .=  '<a class="butAction" href="fiche.php?id='.$this->id.'&amp;action=close">'.$langs->trans("CloseAllContracts").'</a>';
            }

             $html .=  "<a class='butAction' href=".$_SERVER['PHP_SELF']."?id=".$this->id."&action=generatePdf>G&eacute;n&eacute;rer</a>";
            // On peut supprimer entite si
            // - Droit de creer + mode brouillon (erreur creation)
            // - Droit de supprimer
            if (($user->rights->contrat->creer && $this->statut == 0) || $user->rights->contrat->supprimer)
            {
                $html .=  '<a class="butActionDelete" href="fiche.php?id='.$this->id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
            }

            $html .=  "</div>";
            $html .=  '<br>';
        }
        return($html);
    }
    public function displayExtraStyle()
    {
        $html .= "<style>";
        $html .= ".ui-placeHolder { background-color: #eee05d; opacity: 0.9; border:1px Dashed #999; min-height: 2em;}
               #ui-datepicker-div { z-index: 2000;  }
               #sortable li span img{ cursor: pointer; } ";
        if (($contrat->statut == 0 || ($contrat->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED)) && $user->rights->contrat->creer)
        {
            $html .= "  #sortable li { cursor: move; }";
        }
        $html .= "</style>";
        return($html);

    }
    public function displayStatusIco($val)
    {
        global $conf, $user, $langs;
        $html .= '<td nowrap=1  align="center" valign=top  nowrap="nowrap" style="width:50px;padding-top: 0.5em;">';

        if ($this->statut < 2  && $user->rights->contrat->creer && ($val->statut < 4 || $val->statut == 4 && $conf->global->CONTRAT_EDITWHENVALIDATED ))
        {
            $html .= '<div style="width: 48px;">';
        } else if ($val->statut == 4 && $this->statut == 1 && $user->rights->contrat->activer)
        {
            $html .= '<div style="width: 32px;">';
        } else {
            $html .= '<div style="width: 16px;">';
        }
            //Si $contrat->statut==1 => edition possible de la date effective et la date de fin effective + commentaire + activer
            //var_dump($user->rights->contrat);
            if ($this->statut == 1 && $user->rights->contrat->activer )
            {
                if ($val->statut == 0)
                {
                    $html .= '<span onclick="activateLine(this,'.$this->id.','.$val->id.');" >';
                    $html .= img_tick('Activer');
                    $html .= '&nbsp;</span>';
                } else if ($val->statut == 4)
                {
                    $html .= '<span title="D&eacute;sactiver" class="ui-icon ui-icon-arrowrefresh-1-n" style="float: left; width:16px; cursor: pointer;" onclick="unactivateLine(this,'.$this->id.','.$val->id.');" >';
                    $html .= '</span>';

                    $html .= '<span class="ui-icon ui-icon-arrowreturnthick-1-e" title="Cloturer" style="float: left; width:16px;cursor: pointer;" onclick="closeLine(this,'.$this->id.','.$val->id.');" >';
                    $html .= '</span>';
                }
            }
            if ($this->statut==1 &&  !$conf->global->CONTRAT_EDITWHENVALIDATED){
                $html .= '&nbsp;';
            } else  if ($this->statut < 2  && $user->rights->contrat->creer && ($val->statut < 4 || $val->statut == 4 && $conf->global->CONTRAT_EDITWHENVALIDATED ))
            {
                if ($this->statut == 0 || ($this->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED)
                    && $user->rights->contrat->creer )
                {
                    $html .= '<span onclick="editLine(this,'.$this->id.','.$val->id.');" >';
                    $html .= img_edit();
                    $html .= '</span>';
                } else {
                    $html .= '&nbsp;';
                }
            } else {
                $html .= '&nbsp;';
            }
            if ( ($this->statut == 0 || ($this->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED))
                && $user->rights->contrat->creer&& $val->statut < 4)
            {
                $html .= '&nbsp;';
                $html .= '<span onclick="deleteLine(this,'.$this->id.','.$val->id.');" >';
                $html .= img_delete();
                $html .= '</span>';
            }
            $html .= '</div></td>';
        return ($html);
    }
    public function display1Line($val)
    {

        global $user, $conf, $lang;
        $html = "<li id='".$val->id."' class='ui-state-default'>";
        $html .= "<table  width=100%><tr class='ui-widget-content'><td width=15 rowspan=3>";
        if ( ($this->statut == 0 || ($this->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED)) && $user->rights->contrat->creer)
        {
            $html .= "<span class='ui-icon ui-icon-carat-2-n-s'></span>";
        }
        if ($val->product)
        {
            $html .= "<td align=left>".$val->product->getNomUrl(1) ."<br/><br/><font style='font-weight: normal;'>" .$val->product->description .' '.$val->desc."</font>
                   <td nowrap=1  valign=top align=center style='white-space: nowrap; padding-top: 0.5em; width: 50px; border: none;'>".price($val->tva_tx,2) ."%
                   <td nowrap=1  valign=top align=center style='white-space: nowrap; width: 50px;padding-top: 0.5em;'>" . price($val->product->price,2) . "&euro;
                   <td nowrap=1  valign=top align=center style='white-space: nowrap; width: 50px;padding-top: 0.5em;'>".$val->qty. "
                   <td nowrap=1  valign=top align=center style='white-space: nowrap; width: 50px;padding-top: 0.5em;'>".$val->remise_percent. "%
                   <td nowrap=1  valign=top align=center style='white-space: nowrap; width: 50px;padding-top: 0.5em;'>".price($val->product->price * $val->qty,2)  ."&euro;";
            $html .= $this->displayStatusIco($val);
        } else {
            $html .= "<td align=left style='text-align: left;'><font style='font-weight: normal;'>" .$val->desc."</font>
                   <td nowrap=1  valign=top align=center style='white-space: nowrap; padding-top: 0.5em; width: 50px; border: none;'>".price($val->tva_tx,2) ."%
                   <td nowrap=1  valign=top align=center style='white-space: nowrap; width: 50px;padding-top: 0.5em;'>" . price($val->subprice,2) . "&euro;
                   <td nowrap=1  valign=top align=center style='white-space: nowrap; width: 50px;padding-top: 0.5em;'>".$val->qty. "
                   <td nowrap=1  valign=top align=center style='white-space: nowrap; width: 50px;padding-top: 0.5em;'>".$val->remise_percent. "%
                   <td nowrap=1  valign=top align=center style='white-space: nowrap;  width: 50px;padding-top: 0.5em;'>".price($val->total_ht)  ."&euro;";
            $html .= $this->displayStatusIco($val);

        }
            //Si financement ...
            $requete = "SELECT Babel_financement.id as fid,
                            Babel_financement_period.Description2,
                            Babel_financement.taux,
                            Babel_financement.tauxAchat,
                            Babel_financement.duree,
                            Babel_financement.financement_period_refid as fpid
                       FROM Babel_financement,
                            Babel_financement_period
                      WHERE Babel_financement.financement_period_refid = Babel_financement_period.id AND fk_contratdet =  ".$val->id;
            $sql = $this->db->query($requete);
            $res = $this->db->fetch_object($sql);
            if ($res->fid ."x" != "x")
            {
                $html .= "<tr><td colspan=7><table width=100%><tr>";
                $html .= "    <td class='ui-state-default ui-widget-header' style='border: 0; width: 150px;' >Taux de vente<td class='ui-state-default ui-widget-content' style='border: 0; color: #333; font-weight: normal; width: 150px;'>".round($res->taux*100)/100 ." %";
                $html .= "    <td class='ui-state-default ui-widget-header' style='border: 0; width: 150px;'>Taux d'achat <td class='ui-widget-content ui-state-default' style='border: 0; color: #333; font-weight: normal; width: 150px;'>".round($res->tauxAchat*100)/100 . "%";
                $html .= "    <td class='ui-state-default ui-widget-header' style='border: 0; width: 150px;'>Dur&eacute;e<td class='ui-widget-content ui-state-default' style='border: 0; color: #333; font-weight: normal; width: 250px;'>".$res->duree;
                $html .= " ".$res->Description2;
                $html .= "<td></table></td></tr>";
            }
            //cartouche date de mise en service => date début, date fin, date prévu
            $dateDebutLigne = ($val->date_debut_reel ."x" != 'x'? $val->date_debut_reel :$val->date_debut_prevue);
            $dateFinLigne = ($val->date_fin_reel ."x" != 'x'? $val->date_fin_reel :$val->date_fin_prevue);
            if ($dateDebutLigne ."x" != "x" || $dateFinLigne ."x" != "x")
            {
                $html .= "<tr><td colspan=7><table width=100% style='border-collapse: collapse;' cellpadding=4><tr>";
                    if ($val->statut == 0)
                    {
                        $html .= "<td class='ui-state-hover ui-widget-header' style='border: 0; width: 150px;' >Date de d&eacute;but pr&eacute;vue<td class='ui-state-hover ui-widget-content' style='border: 0; color: #333; font-weight: normal; width: 150px;'>".$dateDebutLigne ." ";
                        $html .= "<td class='ui-state-hover ui-widget-header' style='border: 0; width: 150px;'>Date de fin pr&eacute;vue <td class='ui-widget-content ui-state-hover' style='border: 0; color: #333; font-weight: normal; width: 150px;'>".$dateFinLigne . "";
                        $html .= "<td class='ui-state-hover ui-widget-header' style='border: 0;'></table></td></tr>";
                    } else if ($val->statut == 1)
                    {
                        $html .= "<td class='ui-state-hover ui-widget-header' style='border: 0; width: 150px;' >Date de d&eacute;but pr&eacute;vue<td class='ui-state-hover ui-widget-content' style='border: 0; color: #333; font-weight: normal; width: 150px;'>".$dateDebutLigne ." ";
                        $html .= "<td class='ui-state-hover ui-widget-header' style='border: 0; width: 150px;'>Date de fin pr&eacute;vue<td class='ui-widget-content ui-state-hover' style='border: 0; color: #333; font-weight: normal; width: 150px;'>".$dateFinLigne . "";
                        $html .= "<td class='ui-state-hover ui-widget-header' style='border: 0;'></table></td></tr>";
                    } else if ($val->statut == 5)
                    {
                        $html .= "    <td class='ui-state-hover ui-widget-header' style='border: 0; width: 150px;' >Date de d&eacute;but<td class='ui-state-hover ui-widget-content' style='border: 0; color: #333; font-weight: normal; width: 150px;'>".$dateDebutLigne ." ";
                        $html .= "    <td class='ui-state-hover ui-widget-header' style='border: 0; width: 150px;'>Date de cloture <td class='ui-widget-content ui-state-hover' style='border: 0; color: #333; font-weight: normal; width: 150px;'>".$dateFinLigne . "";
                        $html .= "<td class='ui-state-hover ui-widget-header' style='border: 0;'></table></td></tr>";
                    } else if ($val->statut == 4)
                    {
                        $html .= "    <td class='ui-state-hover ui-widget-header' style='border: 0; width: 150px;' >Date de d&eacute;but<td class='ui-state-hover ui-widget-content' style='border: 0; color: #333; font-weight: normal; width: 150px;'>".$dateDebutLigne ." ";
                        $html .= "    <td class='ui-state-hover ui-widget-header' style='border: 0; width: 150px;'>Date de fin <td class='ui-widget-content ui-state-hover' style='border: 0; color: #333; font-weight: normal; width: 150px;'>".$dateFinLigne . "";
                        $html .= "<td class='ui-state-hover ui-widget-header' style='border: 0;'></table></td></tr>";
                    }
            }

        $html .= "</table>";
        $html .= "</li>";
        return($html);
    }

}


/**
        \class      ContratLigne
        \brief      Classe permettant la gestion des lignes de contrats
*/

class ContratLigne
{
    public $db;                            //!< To store db handler
    public $error;                            //!< To return error code (or message)
    public $errors=array();                //!< To return several error codes (or messages)
    //public $element='contratdet';            //!< Id that identify managed objects
    //public $table_element='contratdet';    //!< Name of table without prefix where object is stored

    public $id;

    public $tms;
    public $fk_contrat;
    public $fk_product;
    public $statut;                    // 0 inactive, 4 active, 5 closed
    public $label;
    public $description;
    public $date_commande;
    public $date_ouverture_prevue;        // date start planned
    public $date_ouverture;            // date start real
    public $date_fin_validite;            // date end planned
    public $date_cloture;                // date end real
    public $tva_tx;
    public $qty;
    public $remise_percent;
    public $remise;
    public $fk_remise_except;
    public $subprice;
    public $price_ht;
    public $total_ht;
    public $total_tva;
    public $total_ttc;
    public $info_bits;
    public $fk_user_author;
    public $fk_user_ouverture;
    public $fk_user_cloture;
    public $commentaire;


    /**
    *      \brief     Constructeur d'objets ligne de contrat
    *      \param     DB      handler d'acces base de donnee
    */
    public function ContratLigne($DB)
    {
        $this->db = $DB;
    }


    /**
    *        \brief      Retourne le libelle du statut de la ligne de contrat
    *        \param      mode            0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
        *        \return     string          Libelle
        */
    public function getLibStatut($mode)
    {
        return $this->LibStatut($this->statut,$mode);
    }

    /**
        *        \brief      Renvoi le libelle d'un statut donne
        *        \param      statut      id statut
    *        \param      mode        0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
        *        \return     string      Libelle
        */
    public function LibStatut($statut,$mode)
    {
        global $langs;
        $langs->load("contracts");
        if ($mode == 0)
        {
            if ($statut == 0) { return $langs->trans("ServiceStatusInitial"); }
            if ($statut == 4) { return $langs->trans("ServiceStatusRunning"); }
            if ($statut == 5) { return $langs->trans("ServiceStatusClosed");  }
        }
        if ($mode == 1)
        {
            if ($statut == 0) { return $langs->trans("ServiceStatusInitial"); }
            if ($statut == 4) { return $langs->trans("ServiceStatusRunning"); }
            if ($statut == 5) { return $langs->trans("ServiceStatusClosed");  }
        }
        if ($mode == 2)
        {
            if ($statut == 0) { return img_picto($langs->trans('ServiceStatusInitial'),'statut0').' '.$langs->trans("ServiceStatusInitial"); }
            if ($statut == 4) { return img_picto($langs->trans('ServiceStatusRunning'),'statut4').' '.$langs->trans("ServiceStatusRunning"); }
            if ($statut == 5) { return img_picto($langs->trans('ServiceStatusClosed'),'statut6') .' '.$langs->trans("ServiceStatusClosed"); }
        }
        if ($mode == 3)
        {
            if ($statut == 0) { return img_picto($langs->trans('ServiceStatusInitial'),'statut0'); }
            if ($statut == 4) { return img_picto($langs->trans('ServiceStatusRunning'),'statut4'); }
            if ($statut == 5) { return img_picto($langs->trans('ServiceStatusClosed'),'statut6'); }
        }
        if ($mode == 4)
        {
            if ($statut == 0) { return img_picto($langs->trans('ServiceStatusInitial'),'statut0').' '.$langs->trans("ServiceStatusInitial"); }
            if ($statut == 4) { return img_picto($langs->trans('ServiceStatusRunning'),'statut4').' '.$langs->trans("ServiceStatusRunning"); }
            if ($statut == 5) { return img_picto($langs->trans('ServiceStatusClosed'),'statut6') .' '.$langs->trans("ServiceStatusClosed"); }
        }
        if ($mode == 5)
        {
            if ($statut == 0) { return $langs->trans("ServiceStatusInitial").' '.img_picto($langs->trans('ServiceStatusInitial'),'statut0'); }
            if ($statut == 4) { return $langs->trans("ServiceStatusRunning").' '.img_picto($langs->trans('ServiceStatusRunning'),'statut4'); }
            if ($statut == 5) { return $langs->trans("ServiceStatusClosed").' '.img_picto($langs->trans('ServiceStatusClosed'),'statut6'); }
        }
    }

    /**
        \brief      Renvoie nom clicable (avec eventuellement le picto)
        \param        withpicto        0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
        \return        string            Chaine avec URL
    */
    public function getNomUrl($withpicto=0,$maxlength=0)
    {
        global $langs;

        $result='';

        $lien = '<a href="'.DOL_URL_ROOT.'/contrat/fiche.php?id='.$this->fk_contrat.'">';
        $lienfin='</a>';

        $picto='contract';

        $label=$langs->trans("ShowContractOfService").': '.$this->label;

        if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
        if ($withpicto && $withpicto != 2) $result.=' ';
        if ($withpicto != 2) $result.=$lien.$this->label.$lienfin;
        return $result;
    }

    /*
     *    \brief      Load object in memory from database
     *    \param      id          id object
     *    \param      user        User that load
     *    \return     int         <0 if KO, >0 if OK
     */
    public function fetch($id, $user=0)
    {
        global $langs;
        $sql = "SELECT";
        $sql.= " t.rowid,";

        $sql.= " t.tms as tms,";
        $sql.= " t.fk_contrat,";
        $sql.= " t.fk_product,";
        $sql.= " t.statut,";
        $sql.= " t.label,";
        $sql.= " t.description,";
        $sql.= " t.date_commande as date_commande,";
        $sql.= " date_format(t.date_ouverture_prevue,'%d/%m/%Y') as date_ouverture_prevue,";
        $sql.= " date_format(t.date_ouverture,'%d/%m/%Y') as date_ouverture,";
        $sql.= " date_format(t.date_fin_validite,'%d/%m/%Y') as date_fin_validite,";
        $sql.= " date_format(t.date_cloture,'%d/%m/%Y') as date_cloture,";
        $sql.= " t.tva_tx,";
        $sql.= " t.qty,";
        $sql.= " t.remise_percent,";
        $sql.= " t.remise,";
        $sql.= " t.fk_remise_except,";
        $sql.= " t.subprice,";
        $sql.= " t.price_ht,";
        $sql.= " t.total_ht,";
        $sql.= " t.total_tva,";
        $sql.= " t.total_ttc,";
        $sql.= " t.info_bits,";
        $sql.= " t.fk_user_author,";
        $sql.= " t.fk_user_ouverture,";
        $sql.= " t.fk_user_cloture,";
        $sql.= " t.commentaire";

        $sql.= " FROM ".MAIN_DB_PREFIX."contratdet as t";
        $sql.= " WHERE t.rowid = ".$id;
        dolibarr_syslog("Contratdet::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id    = $obj->rowid;

                $this->tms = $obj->tms;
                $this->fk_contrat = $obj->fk_contrat;
                $this->fk_product = $obj->fk_product;
                $this->fk_product     = $obj->fk_product;

                $this->price          = $obj->total_ht;
                $this->info_bits      = $obj->info_bits;

                $this->ref            = $obj->ref;
                $this->libelle        = $obj->label;        // Label produit
                $this->product_desc   = $obj->product_desc; // Description produit

                $this->date_debut_prevue = $obj->date_ouverture_prevue;
                $this->date_debut_reel   = $obj->date_ouverture;
                $this->date_fin_prevue   = $obj->date_fin_validite;
                $this->date_fin_reel     = $obj->date_cloture;



                if ($obj->fk_product > 0)
                {
                    $product = new Product($this->db);
                    $product->id =$obj->fk_product;
                    $product->fetch($obj->fk_product);
                    $this->product=$product;
                } else {
                    $this->product=false;
                }

                $this->statut = $obj->statut;
                $this->label = $obj->label;
                $this->description = $obj->description;
                $this->desc = $obj->description;
                $this->date_commande = $obj->date_commande;
                $this->date_debut_prevue = $obj->date_ouverture_prevue;
                $this->date_ouverture_prevue = $obj->date_ouverture_prevue;
                $this->date_ouverture = $obj->date_ouverture;
                $this->date_debut_reel = $obj->date_ouverture;
                $this->date_fin_validite = $obj->date_fin_validite;
                $this->date_fin_prevue = $obj->date_fin_validite;
                $this->date_cloture = $obj->date_cloture;
                $this->date_fin_reel = $obj->date_cloture;
                $this->tva_tx = $obj->tva_tx;
                $this->qty = $obj->qty;
                $this->remise_percent = $obj->remise_percent;
                $this->remise = $obj->remise;
                $this->fk_remise_except = $obj->fk_remise_except;
                $this->subprice = $obj->subprice;
                $this->price_ht = $obj->price_ht;
                $this->total_ht = $obj->total_ht;
                $this->total_tva = $obj->total_tva;
                $this->total_ttc = $obj->total_ttc;
                $this->info_bits = $obj->info_bits;
                $this->fk_user_author = $obj->fk_user_author;
                $this->fk_user_ouverture = $obj->fk_user_ouverture;
                $this->fk_user_cloture = $obj->fk_user_cloture;
                $this->commentaire = $obj->commentaire;


            }
            $this->db->free($resql);

            return 1;
        } else {
            $this->error="Error ".$this->db->lasterror();
            dolibarr_syslog("ContratLigne::fetch ".$this->error, LOG_ERR);
            return -1;
        }
    }


    /*
     *      \brief      Update database for contract line
     *      \param      user            User that modify
     *      \param      notrigger        0=no, 1=yes (no update trigger)
     *      \return     int             <0 if KO, >0 if OK
     */
    public function update($user, $notrigger=0)
    {
        global $conf, $langs;

        // Clean parameters
        $this->fk_contrat=trim($this->fk_contrat);
        $this->fk_product=trim($this->fk_product);
        $this->statut=trim($this->statut);
        $this->label=trim($this->label);
        $this->description=trim($this->description);
        $this->tva_tx=trim($this->tva_tx);
        $this->qty=trim($this->qty);
        $this->remise_percent=trim($this->remise_percent);
        $this->remise=trim($this->remise);
        $this->fk_remise_except=trim($this->fk_remise_except);
        $this->subprice=price2num($this->subprice);
        $this->price_ht=price2num($this->price_ht);
        $this->total_ht=trim($this->total_ht);

            $this->total_ht =  (1 + $this->remise_percent/100) * $this->qty * $this->price_ht;
            if ($this->remise) { $this->total_ht -= $this->remise; }
            if ($this->fk_remise_except)
            {
                //TODO
                //Get the remise
            }

        $this->total_tva=trim($this->total_tva);

            $this->total_tva = $this->tva_tx/100 * $this->total_ht;

        $this->total_ttc=trim($this->total_ttc);

            $this->total_ttc = $this->total_tva + $this->total_ht;

        $this->info_bits=trim($this->info_bits);
        $this->fk_user_author=trim($this->fk_user_author);
        $this->fk_user_ouverture=trim($this->fk_user_ouverture);
        $this->fk_user_cloture=trim($this->fk_user_cloture);
        $this->commentaire=trim($this->commentaire);

        $this->label="cont-".trim($this->fk_contrat).'-opt'.$this->id;

        // Check parameters
        // Put here code to add control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."contratdet SET";
        $sql.= " fk_contrat='".$this->fk_contrat."',";
        $sql.= " fk_product=".($this->fk_product?"'".$this->fk_product."'":'null').",";
        $sql.= " statut='".$this->statut."',";
        $sql.= " label='".addslashes($this->label)."',";
        $sql.= " description='".addslashes($this->description)."',";
        $sql.= " date_commande=".($this->date_commande!=''?$this->db->idate($this->date_commande):"null").",";
        $sql.= " date_ouverture_prevue=".($this->date_ouverture_prevue!=''?"'".$this->date_ouverture_prevue."'":"null").",";
        $sql.= " date_ouverture=".($this->date_ouverture!=''?"'".$this->date_ouverture."'":"null").",";
        $sql.= " date_fin_validite=".($this->date_fin_validite!=''?"'".$this->date_fin_validite."'":"null").",";
        $sql.= " date_cloture=".($this->date_cloture!=''?"'".$this->date_cloture."'":"null").",";
        $sql.= " tva_tx='".$this->tva_tx."',";
        $sql.= " qty='".$this->qty."',";
        $sql.= " remise_percent='".$this->remise_percent."',";
        $sql.= " remise='".$this->remise."',";
        $sql.= " fk_remise_except=".($this->fk_remise_except?"'".$this->fk_remise_except."'":"null").",";
        $sql.= " subprice='".$this->subprice."',";
        $sql.= " price_ht='".$this->price_ht."',";
        $sql.= " total_ht='".$this->total_ht."',";
        $sql.= " total_tva='".$this->total_tva."',";
        $sql.= " total_ttc='".$this->total_ttc."',";
        $sql.= " info_bits='".$this->info_bits."',";
        $sql.= " fk_user_author=".($this->fk_user_author >= 0?$this->fk_user_author:"NULL").",";
        $sql.= " fk_user_ouverture=".($this->fk_user_ouverture > 0?$this->fk_user_ouverture:"NULL").",";
        $sql.= " fk_user_cloture=".($this->fk_user_cloture > 0?$this->fk_user_cloture:"NULL").",";
        $sql.= " commentaire='".addslashes($this->commentaire)."'";
        $sql.= " WHERE rowid=".$this->id;

        dolibarr_syslog("ContratLigne::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $contrat=new Contrat($this->db);
            $contrat->fetch($this->fk_contrat);
            $result=$contrat->update_total_contrat();
        } else {
            $this->error="Error ".$this->db->lasterror();
            dolibarr_syslog("ContratLigne::update ".$this->error, LOG_ERR);
            return -1;
        }

        if (! $notrigger)
        {
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('CONTRAT_LIGNE_MODIFY',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // Fin appel triggers
        }

        return 1;
    }


    /**
    *      \brief         Mise a jour en base des champs total_xxx de ligne
    *        \remarks    Utilise par migration
    *        \return        int        <0 si ko, >0 si ok
    */
    public function update_total()
    {
        $this->db->begin();
        global $user,$langs,$conf;
        // Mise a jour ligne en base
        $sql = "UPDATE ".MAIN_DB_PREFIX."contratdet SET";
        $sql.= " total_ht=".price2num($this->total_ht,'MT')."";
        $sql.= ",total_tva=".price2num($this->total_tva,'MT')."";
        $sql.= ",total_ttc=".price2num($this->total_ttc,'MT')."";
        $sql.= " WHERE rowid = ".$this->rowid;

        dolibarr_syslog("ContratLigne::update_total sql=".$sql);

        $resql=$this->db->query($sql);
        if ($resql)
        {
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('CONTRAT_LIGNE_MODIFY_TOT',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            dolibarr_syslog("ContratLigne::update_total Error ".$this->error);
            $this->db->rollback();
            return -2;
        }
    }
    public function info_contratdet($id)
    {
        $sql = "SELECT DISTINCT fk_user_author";
        $sql.= " FROM ".MAIN_DB_PREFIX."contratdet as c";
        $sql.= " WHERE c.fk_contrat = ".$id;

        $sql1 = "SELECT DISTINCT fk_user_ouverture";
        $sql1.= " FROM ".MAIN_DB_PREFIX."contratdet as c";
        $sql1.= " WHERE c.fk_contrat = ".$id;

        $sql2 = "SELECT DISTINCT fk_user_cloture";
        $sql2.= " FROM ".MAIN_DB_PREFIX."contratdet as c";
        $sql2.= " WHERE c.fk_contrat = ".$id;

        $result=$this->db->query($sql);
        $result1=$this->db->query($sql1);
        $result2=$this->db->query($sql2);
        $this->user_creation=array();
        $this->user_ouverture=array();
        $this->user_cloture=array();
        if ($result && $result1 && $result2)
        {
            while ($obj = $this->db->fetch_object($result))
            {
                if ($obj->fk_user_author) {
                    $cuser = new User($this->db);
                    $cuser->fetch($obj->fk_user_author);
                    array_push($this->user_creation, $cuser);
                }
            }
            while ($obj = $this->db->fetch_object($result1))
            {
                if ($obj->fk_user_ouverture) {
                    $cuser = new User($this->db);
                    $cuser->fetch($obj->fk_user_ouverture);
                    array_push($this->user_ouverture, $cuser);
                }
            }
            while ($obj = $this->db->fetch_object($result2))
            {
                if ($obj->fk_user_cloture) {
                    $cuser = new User($this->db);
                    $cuser->fetch($obj->fk_user_cloture);
                    array_push($this->user_cloture, $cuser);
                }
            }


            $this->db->free($result);

        }
        else
        {
            dol_print_error($this->db);
        }
    }

    public function getNextRef($id)
    {

    }

}


?>

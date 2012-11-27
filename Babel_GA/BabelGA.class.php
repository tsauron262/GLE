<?php

class BabelGA {

    public $db;
    public $cessionnaire_id = false;
    public $type_taux = false;
    public $fournisseur_id = false;
    public $client_id = false;
    private $obj_id = false;

    public function BabelGA($db) {
        $this->db = $db;
    }

    public function fetch_taux_fin($entities_obj,$typeEnt="propal",$montant=0)
    {
        global $user,$conf;
        //client => fournisseur => cessionnaire =>  utiliisateur :> défault

        $taux = false;
        if ($typeEnt == "propal")
        {
            $client_id = $entities_obj->socid;
            if (!$taux) { $taux = $this->fetch_taux_fin_client($client_id,$montant); }
            $fourn_id = $entities_obj->fournisseur_refid;
            if (!$taux) { $taux = $this->fetch_taux_fin_fournisseur($fourn_id,$montant); }
            $cess_id = $entities_obj->cessionnaire_refid;
            if (!$taux) { $taux = $this->fetch_taux_fin_cessionnaire($cess_id,$montant); }
            $user_id = $entities_obj->user_author_id;
            if (!$taux) { $taux = $this->fetch_taux_fin_user($user_id,$montant); }
            if (!$taux) { $taux = $this->fetch_taux_fin_default($montant); }
        } else if ($typeEnt == "contrat") {
            $client_id = $entities_obj->socid;
//TODO
            if (!$taux) { $taux = $this->fetch_taux_fin_client($client_id,$montant); }
            $fourn_id = $entities_obj->fournisseur_refid;
//TODO
            if (!$taux) { $taux = $this->fetch_taux_fin_fournisseur($fourn_id,$montant); }
            $cess_id = $entities_obj->cessionnaire_refid;
//TODO
            if (!$taux) { $taux = $this->fetch_taux_fin_cessionnaire($cess_id,$montant); }
            $user_id = $entities_obj->user_author_id;
//TODO
            if (!$taux) { $taux = $this->fetch_taux_fin_user($user_id,$montant); }
            if (!$taux) { $taux = $this->fetch_taux_fin_default($montant); }

        }
        return($taux);
    }

    private function fetch_taux_fin_client($entities_id,$montant)
    {
        $requete = "SELECT tauxFinancement,
                           montantPlafond
                      FROM Babel_GA_taux_client
                     WHERE client_id = ".$entities_id."
                       AND dateValidite = (SELECT max(dateValidite)
                                       FROM Babel_GA_taux_client
                                      WHERE client_id = ".$entities_id."
                                        AND dateValidite < now()
                                    )
                    ORDER BY montantPlafond ASC";
        $sql = $this->db->query($requete);
        $tx="";
        $lastPlafond=false;
        while ($res = $this->db->fetch_object($sql))
        {
            $plafond = $res->montantPlafond;
            if ($plafond  == 0)
            {
                //c'est le plafond par default donc le taux par default
                $tx = $res->tauxFinancement;
                $lastPlafond=$plafond;
            } else if ($montant <= $plafond && $montant > $lastPlafond ||  ($montant <= $plafond && !$lastPlafond ))
            {
                $tx = $res->tauxFinancement;
                $lastPlafond=$plafond;
            } else {
                $lastPlafond=$plafond;
            }
        }
        if ($tx>0)
        {
            return($tx);
        } else {
            return (false);
        }
    }


    private function fetch_taux_fin_fournisseur($entities_id,$montant)
    {
        $requete = "SELECT tauxFinancement,
                           montantPlafond
                      FROM Babel_GA_taux_fournisseur
                     WHERE fournisseur_id = ".$entities_id."
                       AND dateValidite = (SELECT max(dateValidite)
                                       FROM Babel_GA_taux_fournisseur
                                      WHERE fournisseur_id = ".$entities_id."
                                        AND dateValidite < now()
                                    )
                    ORDER BY montantPlafond ASC";
        $sql = $this->db->query($requete);
        $tx="";
        $lastPlafond=false;
        while ($res = $this->db->fetch_object($sql))
        {
            $plafond = $res->montantPlafond;
            if ($plafond  == 0)
            {
                //c'est le plafond par default donc le taux par default
                $tx = $res->tauxFinancement;
                $lastPlafond=$plafond;
            } else if ($montant <= $plafond && $montant > $lastPlafond ||  ($montant <= $plafond && !$lastPlafond ))
            {
                $tx = $res->tauxFinancement;
                $lastPlafond=$plafond;
            } else {
                $lastPlafond=$plafond;
            }
        }
        if ($tx>0)
        {
            return($tx);
        } else {
            return (false);
        }
    }


    private function fetch_taux_fin_cessionnaire($entities_id,$montant)
    {
        $requete = "SELECT tauxFinancement,
                           montantPlafond
                      FROM Babel_GA_taux_cessionnaire
                     WHERE cessionnaire_id = ".$entities_id."
                       AND dateValidite = (SELECT max(dateValidite)
                                       FROM Babel_GA_taux_cessionnaire
                                      WHERE cessionnaire_id = ".$entities_id."
                                        AND dateValidite < now()
                                    )
                    ORDER BY montantPlafond ASC";
        $sql = $this->db->query($requete);
        $tx="";
        $lastPlafond=false;
        while ($res = $this->db->fetch_object($sql))
        {
            $plafond = $res->montantPlafond;
            if ($plafond  == 0)
            {
                //c'est le plafond par default donc le taux par default
                $tx = $res->tauxFinancement;
                $lastPlafond=$plafond;
            } else if ($montant <= $plafond && $montant > $lastPlafond ||  ($montant <= $plafond && !$lastPlafond ))
            {
                $tx = $res->tauxFinancement;
                $lastPlafond=$plafond;
            } else {
                $lastPlafond=$plafond;
            }
        }
        if ($tx>0)
        {
            return($tx);
        } else {
            return (false);
        }
    }


    private function fetch_taux_fin_user($userid,$montant)
    {
        $requete = "SELECT tauxFinancement, montantPlafond
                      FROM Babel_GA_taux_user
                     WHERE user_id = ".$userid."
                      AND dateValidite = (SELECT max(dateValidite)
                                       FROM Babel_GA_taux_user
                                      WHERE user_id = ".$userid."
                                        AND dateValidite < now()
                                    )
                    ORDER BY montantPlafond ASC";
        $sql = $this->db->query($requete);
        $tx="";
        $lastPlafond=false;
        while ($res = $this->db->fetch_object($sql))
        {
            $plafond = $res->montantPlafond;
            if ($plafond  == 0)
            {
                //c'est le plafond par default donc le taux par default
                $tx = $res->tauxFinancement;
                $lastPlafond=$plafond;
            } else if ($montant <= $plafond && $montant > $lastPlafond ||  ($montant <= $plafond && !$lastPlafond ))
            {
                $tx = $res->tauxFinancement;
                $lastPlafond=$plafond;
            } else {
                $lastPlafond=$plafond;
            }
        }
        if ($tx>0)
        {
            return($tx);
        } else {
            return (false);
        }
    }

    private function fetch_taux_fin_default($montant)
    {
        $requete = "SELECT tauxFinancement, montantPlafond
                      FROM Babel_GA_taux_default
                     WHERE dateValidite = (SELECT max(dateValidite)
                                       FROM Babel_GA_taux_default
                                      WHERE dateValidite < now()
                                    )
                    ORDER BY montantPlafond ASC";
        $sql = $this->db->query($requete);
        $tx="";
        $lastPlafond=false;
        while ($res = $this->db->fetch_object($sql))
        {
            $plafond = $res->montantPlafond;
            if ($plafond  == 0)
            {
                //c'est le plafond par default donc le taux par default
                $tx = $res->tauxFinancement;
                $lastPlafond=$plafond;
            } else if ($montant <= $plafond && $montant > $lastPlafond ||  ($montant <= $plafond && !$lastPlafond ))
            {
                $tx = $res->tauxFinancement;
                $lastPlafond=$plafond;
            } else {
                $lastPlafond=$plafond;
            }
        }
        if ($tx>0)
        {
            return($tx);
        } else {
            return (false);
        }
    }


    public function fetch_taux_marge($entities_obj,$typeEnt)
    {
        global $user,$conf;
        //client => fournisseur => cessionnaire =>  utiliisateur :> défault
        $taux = false;
        if ($typeEnt == "propal")
        {
            $client_id = $entities_obj->socid;
            if (!$taux) { $taux = $this->fetch_taux_marge_client($client_id); }
            $fourn_id = $entities_obj->fournisseur_refid;
            if (!$taux) { $taux = $this->fetch_taux_marge_fournisseur($fourn_id); }
            $cess_id = $entities_obj->cessionnaire_refid;
            if (!$taux) { $taux = $this->fetch_taux_marge_cessionnaire($cess_id); }
            $user_id = $entities_obj->user_author_id;
            if (!$taux) { $taux = $this->fetch_taux_marge_user($user_id); }
            if (!$taux) { $taux = $this->fetch_taux_marge_default(); }

        } else if ($typeEnt == "contrat")
        {
            $client_id = $entities_obj->socid;
            if (!$taux) { $taux = $this->fetch_taux_marge_client($client_id); }
//TODO
            $fourn_id = $entities_obj->fournisseur_refid;
            if (!$taux) { $taux = $this->fetch_taux_marge_fournisseur($fourn_id); }
//TODO
            $cess_id = $entities_obj->cessionnaire_refid;
            if (!$taux) { $taux = $this->fetch_taux_marge_cessionnaire($cess_id); }
//TODO
            $user_id = $entities_obj->user_author_id;
            if (!$taux) { $taux = $this->fetch_taux_marge_user($user_id); }
            if (!$taux) { $taux = $this->fetch_taux_marge_default(); }
        }


        return($taux);
    }
    private function fetch_taux_marge_fournisseur($entities_id)
    {
        $requete = "SELECT taux
                      FROM Babel_GA_taux_marge
                     WHERE obj_refid = ".$entities_id."
                       AND type_ref = 'fournisseur'
                       AND dateTx = (SELECT max(dateTx)
                                       FROM Babel_GA_taux_marge
                                      WHERE type_ref = 'fournisseur'
                                        AND obj_refid = ".$entities_id."
                                        AND dateTx < now()
                                    )";
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        if ($res->taux>0)
        {
            return($res->taux);
        } else {
            return(false);
        }
    }
    private function fetch_taux_marge_client($entities_id)
    {
        $requete = "SELECT taux
                      FROM Babel_GA_taux_marge
                     WHERE obj_refid = ".$entities_id."
                       AND type_ref = 'client'
                       AND dateTx = (SELECT max(dateTx)
                                       FROM Babel_GA_taux_marge
                                      WHERE type_ref = 'client'
                                        AND obj_refid = ".$entities_id."
                                        AND dateTx < now()
                                    )";
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        if ($res->taux>0)
        {
            return($res->taux);
        } else {
            return(false);
        }
    }
    private function fetch_taux_marge_cessionnaire($entities_id)
    {
        $requete = "SELECT taux
                      FROM Babel_GA_taux_marge
                     WHERE obj_refid = ".$entities_id."
                       AND type_ref = 'cessionnaire'
                       AND dateTx = (SELECT max(dateTx)
                                       FROM Babel_GA_taux_marge
                                      WHERE type_ref = 'cessionnaire'
                                        AND obj_refid = ".$entities_id."
                                        AND dateTx < now()
                                    )";
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        if ($res->taux>0)
        {
            return($res->taux);
        } else {
            return(false);
        }
    }
    private function fetch_taux_marge_user($userid)
    {
       $requete = "SELECT taux
                      FROM Babel_GA_taux_marge
                     WHERE obj_refid = ".$userid."
                       AND type_ref = 'user'
                       AND dateTx = (SELECT max(dateTx)
                                       FROM Babel_GA_taux_marge
                                      WHERE type_ref = 'user'
                                        AND obj_refid = ".$userid."
                                        AND dateTx < now()
                                    )";
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        if ($res->taux>0)
        {
            return($res->taux);
        } else {
            return(false);
        }

    }
    private function fetch_taux_marge_default()
    {
                $requete = "SELECT taux
                      FROM Babel_GA_taux_marge
                     WHERE obj_refid = -1
                       AND type_ref = 'dflt'
                       AND dateTx = (SELECT max(dateTx)
                                       FROM Babel_GA_taux_marge
                                      WHERE type_ref = 'dflt'
                                        AND obj_refid = -1
                                        AND dateTx < now()
                                    )";
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        return($res->taux);
    }

    public function fetch_taux($id,$type='cessionnaire')
    {
        $this->obj_id = $id;
        switch ($type)
        {
            case 'cessionnaire':
            {
                $this->type_taux = 'cessionnaire';
                $this->cessionnaire_id = $id;
            }
            break;
            case 'fournisseur':
            {
                $this->type_taux = 'fournisseur';
                $this->fournisseur_id = $id;
            }
            break;
            case 'client':
            {
                $this->type_taux = 'client';
                $this->client_id = $id;
            }
            break;
            case 'user':
            {
                $this->type_taux = 'client';
                $this->user_id = $id;
            }
            break;
            case 'dflt':
            {
                $this->type_taux = 'dflt';
            }
            break;
        }
    }
    public function drawFinanceTable($date=false,$displayMenu=true,$display=true)
    {
        global $user;
        $html = "";
        if ($user->rights->GA->Taux->Admin ||
                (($this->type_taux == "client" && ($user->rights->GA->Taux->VenteClientAffiche || $user->rights->GA->Taux->VenteClientModifier )) ||
                 ($this->type_taux == "cessionnaire" && ($user->rights->GA->Taux->VenteAffiche || $user->rights->GA->Taux->VenteClientModifier)) ||
                 ($this->type_taux == "fournisseur" && ($user->rights->GA->Taux->VenteFournAffiche || $user->rights->GA->Taux->VenteClientModifier)) ||
                 ($this->type_taux == "user" && ($user->rights->GA->Taux->VenteUserAffiche || $user->rights->GA->Taux->VenteClientModifier ))
                )
           ){
                switch ($this->type_taux)
                {
                    case 'cessionnaire':
                    {
                        $html = $this->drawFinanceTableCess($date,$displayMenu);
                    }
                    break;
                    case 'fournisseur':
                    {
                        $html = $this->drawFinanceTableFourn($date,$displayMenu);
                    }
                    break;
                    case 'client':
                    {
                        $html = $this->drawFinanceTableClient($date,$displayMenu);
                    }
                    break;
                    case 'user':
                    {
                        $html = $this->drawFinanceTableUser($date,$displayMenu);
                    }
                    break;
                    case 'dflt':
                    {
                        $html = $this->drawFinanceTableDflt($date,$displayMenu);
                    }
                    break;
                }
               }
        if ($display) print $html;
        return ($html);
    }
    private function drawFinanceTableCommon($displayMenu,$tmpHtml,$dateValid)
    {
        global $user;
        $html = "";
        if ($this->type_taux == 'dflt')
        {
            $html .= "<table width=100%><thead>";

        } else {
            $html .= "<table width=100%><thead>
                        <tr><th class='ui-widget-header ui-state-default' colspan=5 style='padding: 15px;'>Taux de financement</th>";

        }
        $html .= "<tr><td class='ui-widget-header ui-state-default black' align='right'>Montant plafond";
        $html .= "    <td class='ui-widget-header ui-state-default black' align='center'>Taux de financement";

        $html .= $tmpHtml;

        $html .= "</tbody><tfoot>";

        $html .= "<tr><td class='ui-widget-content' style='font-weight: 0 !important; font-size: 10px !important;'  colspan=5 align='right'>Valable &agrave; partir du : ".$dateValid;
        if ($displayMenu)
        {
            $html .= "<tr><td colspan=4>";
            if ($user->rights->GA->Taux->Admin ||
                    (($this->type_taux == "client" && $user->rights->GA->Taux->VenteClientModifier ) ||
                     ($this->type_taux == "cessionnaire" && $user->rights->GA->Taux->VenteModifier ) ||
                     ($this->type_taux == "fournisseur" && $user->rights->GA->Taux->VenteFournModifier ) ||
                     ($this->type_taux == "user" && $user->rights->GA->Taux->VenteUserModifier )
                    )
               ) {
                    $html .= "        <span id='addLigneFin' class='butAction'>Modifier</span>";
                 }
            $html .= "        <span id='histoLigneFin' class='butAction'>Historique des taux</span>";
            if ($user->rights->GA->Taux->Admin)
            {
                $html .= "        <span id='supprimerLigneFin' class='butAction'>Supprimer</span>";
            }

        }
        $html .= "</tfoot></table>";
        $html .= "<style>";
        $html .= "#addLigneFinForm input{ width: 80px; }";
        $html .= "</style>";
        $html .= "<script>";
        if ($this->type_taux == 'dflt')
        {
            $html .= "var cessionnaireID = -1 ;";
        } else {
            $html .= "var cessionnaireID = ".$this->obj_id." ;";
        }
        $html .=  "jQuery(document).ready(function(){

                    jQuery.datepicker.setDefaults(jQuery.extend({showMonthAfterYear: false,
                        dateFormat: 'dd/mm/yy',
                        changeMonth: true,
                        changeYear: true,
                        showButtonPanel: true,
                        buttonImage: 'cal.png',
                        buttonImageOnly: true,
                        showTime: true,
                        duration: '',
                        constrainInput: false,}, jQuery.datepicker.regional['fr']));

                jQuery(\"#ui-datepicker-div\").addClass(\"promoteZ\");


            jQuery('#addLigneFin').click(function(){
                jQuery('#addLigneFinDialog').dialog('open');
            });
        jQuery.extend(jQuery.validator.message,{
            messages: { required: \"Ce champ est requis\" }
        });
        jQuery.validator.addMethod(
            'FRDate',
            function(value, element) {
                return value.match(/^\d\d?\/\d\d?\/\d\d\d\d\W?\d?\d?\:?\d?\d?$/);
            },
            'La date doit &ecirc;tre au format dd/mm/yyyy'
        );
        jQuery.validator.addMethod(
            'percentdecimal',
            function(value, element) {
                return value.match(/^\d\d*?[,.]?\d*?%?$/);
            },
            'Le taux n\'est pas au bon format'
        );
        jQuery.validator.addMethod(
            'currency',
            function(value, element) {
                return value.match(/^\d*?[,.]?\d*?$/);
            },
            'La montant n\'est pas au bon format'
        );
        jQuery.validator.addMethod(
            'required',
            function(value, element) {
                return value.match(/^[\w\W\d]+$/);
            },
            '<br>Ce champ est requis'
        );
        jQuery('#histoLigneFin').click(function(){
            location.href='".DOL_URL_ROOT."/Babel_GA/historique-taux.php?socid='+cessionnaireID+'&type=".$this->type_taux."';
        });
        jQuery('#supprimerLigneFin').click(function(){
            //dialog etes vous sur de voiloir etre sur ?
            jQuery('#delLigneFinDialog').dialog('open');

        });
        jQuery('#delLigneFinDialog').dialog({
            autoOpen: false,
            minWidth: 560,
            maxWidth: 560,
            width: 560,
            buttons:{
                'Annuler': function(){ jQuery(this).dialog('close'); },
                'Supprimer': function(){
                    jQuery.ajax({
                        url: '".DOL_URL_ROOT."/Babel_GA/ajax/ficheTaux-xml_response.php',
                        datatype: 'xml',
                        data: 'id='+cessionnaireID+'&action=SupprCurrTableFin&type=".$this->type_taux."',
                        success: function(msg)
                        {
                            //console.log(msg);
                            jQuery(this).dialog('close');
                            location.reload();
                        }
                    });
                }
           }
        });

            jQuery('#addLigneFinDialog').dialog({
                modal: true,
                autoOpen: false,
                minWidth: 560,
                width: 560,
                title: 'Modifier les conditions',
                buttons: {
                           'Ajouter': function() {
                                if (jQuery('#addLigneFinForm').validate({}).form()){
                                    var data = jQuery('#addLigneFinForm').serialize();
                                    jQuery.ajax({
                                        url: '".DOL_URL_ROOT."/Babel_GA/ajax/ficheCessionnaire-xml_response.php',
                                        data: 'action=SetTaux&id='+cessionnaireID+'&'+data+'&type=".$this->type_taux."',
                                        datatype: 'xml',
                                        success: function(msg)
                                        {
                                            //console.log(msg);
                                            location.reload();
                                        }
                                });
                               }
                           },
                           'Annuler': function() { jQuery(this).dialog('close'); },
                          },
                open: function(){
                    jQuery('#addLigneFinForm')[0].reset();
                    jQuery('.ui-icon-trash').mouseover(function(){
                        jQuery(this).parent().addClass('ui-state-default');
                        jQuery(this).parent().removeClass('ui-widget-content');
                    });
                    jQuery('.ui-icon-trash').mouseout(function(){
                        jQuery(this).parent().removeClass('ui-state-default');
                        jQuery(this).parent().addClass('ui-widget-content');
                    });
                    jQuery('.ui-icon-trash').click(function(){
                        if (countElemntDialogTable() > 1)
                        {
                            jQuery(this).parent().parent().remove();
                        }

                    });

                    jQuery('.ui-icon-plus').mouseover(function(){
                        jQuery(this).parent().removeClass('ui-state-hover');
                        jQuery(this).parent().addClass('ui-state-default');
                        jQuery(this).css('backgroundImage','url(\"".DOL_URL_ROOT."/Synopsis_Common/css/flick/images/ui-icons_0073ea_256x240.png\")');
                        jQuery(this).css('backgroundPosition','-16px -128px');
                    });
                    jQuery('.ui-icon-plus').mouseout(function(){
                        jQuery(this).parent().removeClass('ui-state-default');
                        jQuery(this).parent().addClass('ui-state-hover');
                        jQuery(this).css('backgroundImage','url(\"".DOL_URL_ROOT."/Babel_GA/Synopsis_Common/css/flick/images/ui-icons_ffffff_256x240.png\")');
                        jQuery(this).css('backgroundPosition','-16px -128px');
                    });
                    jQuery('#addButLigneFinDialog').click(function(){
                        var date = new Date();
                        var newId = date.getTime();
                        var html = '<tr><td class=\"ui-widget-header ui-state-default\">Taux de Financement</td> \
                                        <td class=\"ui-widget-content\"><input class=\"percentdecimal required\"  name=\"txfinAdd'+newId+'\"></td> \
                                        <td class=\"ui-widget-header ui-state-default\">Plafond</td> \
                                        <td class=\"ui-widget-content\"><input class=\"currency\" name=\"plafondFinAdd'+newId+'\"></td> \
                                        <td class=\"ui-widget-content\"><span class=\"ui-icon ui-icon-trash\"></span></td> \
                                    </tr>';
                        jQuery('#tbodyAddDialog').append(html);
                        jQuery('.ui-icon-trash').mouseover(function(){
                            jQuery(this).parent().addClass('ui-state-default');
                            jQuery(this).parent().removeClass('ui-widget-content');
                        });
                        jQuery('.ui-icon-trash').mouseout(function(){
                            jQuery(this).parent().removeClass('ui-state-default');
                            jQuery(this).parent().addClass('ui-widget-content');
                        });
                        jQuery('.ui-icon-trash').click(function(){
                            if (countElemntDialogTable() > 1)
                            {
                                jQuery(this).parent().parent().remove();
                            }
                        });
                    });
                    jQuery('#dateFinAdd').datepicker();
                    jQuery('#ui-datepicker-div').addClass('promoteZ');
                }
            })

        });
        function countElemntDialogTable()
        {
            return jQuery('#tbodyAddDialog').find('tr').length;
        }

";

        $html .= "</script>";
        if ($displayMenu)
        {

            $html .= "<div id='addLigneFinDialog'>";
            $html .= "<form id='addLigneFinForm'>";
            $html .= "<table cellpadding=15 width=100%><thead>";
            $html .= "<tr><th class='ui-widget-header ui-state-hover' colspan=2>Date d'activation</td>
                       <td class='ui-widget-content ui-state-hover' colspan=3><input class='FRDate' name='dateFinAdd' id='dateFinAdd'></td></thead>";
            $html .= "<tbody id='tbodyAddDialog'><tr><td class='ui-widget-header ui-state-default'>Taux de Financement</td>
                        <td class='ui-widget-content'><input  class='percentdecimal required' name='txfinAdd'></td>";
            $html .= "     <td class='ui-widget-header ui-state-default'>Plafond</td>
                        <td class='ui-widget-content'><input class='currency' name='plafondFinAdd'></td>";
            $html .= "     <td class='ui-widget-content'><span class='ui-icon ui-icon-trash'></span></td></tbody>";
            $html .= "<tfoot><tr><td class='ui-widget-header ui-state-hover' colspan=5 align=right style='padding: 2px;'>
                            <span class='ui-icon ui-icon-circle-plus' id='addButLigneFinDialog'>
                            </span></td></tfoot>";

            $html .= "</table>";
            $html .= "</form>";
            $html .= "<div id='delLigneFinDialog'>";
            $html .= "&Ecirc;tes vous sur de vouloir supprimer cette table de taux ?";
            $html .= "</div>";
        }
        $html .= "</div>";
        return ($html);
    }
    private function drawFinanceTableCess($date=false,$displayMenu=true)
    {
        $html = "";
        if ("x".$this->cessionnaire_id =="x")
        {
            print "need to fetch first";
            return (false);
        }
        //Affiche table tel que

        $requete = "SELECT *
                      FROM Babel_GA_taux_cessionnaire a
                     WHERE cessionnaire_id = ".$this->cessionnaire_id."
                       AND dateValidite = (SELECT DISTINCT max(b.dateValidite)
                                             FROM Babel_GA_taux_cessionnaire b
                                            WHERE a.cessionnaire_id = b.cessionnaire_id
                                              AND b.dateValidite <= now()
                                          )
                  ORDER BY montantPlafond ";
                  //print $requete;
        if ($date && preg_match('/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/',$date,$arr))
        {

            $requete = "SELECT *
                          FROM Babel_GA_taux_cessionnaire a
                         WHERE cessionnaire_id = ".$this->cessionnaire_id."
                           AND day(dateValidite) = '".$arr[1]."'
                           AND month(dateValidite) = '".$arr[2]."'
                           AND year(dateValidite) = '".$arr[3]."'
                      ORDER BY montantPlafond ";
        }
        $sql = $this->db->query($requete);
        $tmpHtml = "</thead><tbody>";
        $dateValid = "";
        $num = $this->db->num_rows($sql);
        if ($num > 0)
        {
            while ($res = $this->db->fetch_object($sql))
            {
                $dateValid = date('d/m/Y',strtotime($res->dateValidite));
                $price = "-";
                if ($res->montantPlafond >0)
                {
                    $price = price(round($res->montantPlafond*100)/100) . " &euro;";
                }
                $tmpHtml .= "<tr><td class='ui-widget-header ui-state-default' align='right'>".$price;
                $tmpHtml .= "    <td class='ui-widget-header ui-state-default' align='center'>".round($res->tauxFinancement*100)/100 ." %";
                //$tmpHtml .= "    <td class='ui-widget-header ui-state-default' align='center'>".date('d/m/Y',strtotime($res->dateValidite));
            }
        } else {
            $tmpHtml .= "<tr><td colspan=5><span></span><div class='error ui-state-error'><span class='ui-icon ui-icon-alert' style='float: left; margin-right: 5px; margin-top: -1px;'></span>Pas de taux configur&eacute; pour la p&eacute;riode courante</div>";
        }
        $html .= $this->drawFinanceTableCommon($displayMenu,$tmpHtml,$dateValid);


        return ($html);
    }

    private function drawFinanceTableFourn($date=false,$displayMenu=true)
    {
        $html = "";
        if ("x".$this->fournisseur_id =="x")
        {
            print "need to fetch first";
            return (false);
        }
        //Affiche table tel que

        $requete = "SELECT *
                      FROM Babel_GA_taux_fournisseur a
                     WHERE fournisseur_id = ".$this->fournisseur_id."
                       AND dateValidite = (SELECT DISTINCT max(b.dateValidite)
                                             FROM Babel_GA_taux_fournisseur b
                                            WHERE a.fournisseur_id = b.fournisseur_id
                                              AND b.dateValidite <= now()
                                          )
                  ORDER BY montantPlafond ";
                  //print $requete;
        if ($date && preg_match('/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/',$date,$arr))
        {

            $requete = "SELECT *
                          FROM Babel_GA_taux_fournisseur a
                         WHERE fournisseur_id = ".$this->fournisseur_id."
                           AND day(dateValidite) = '".$arr[1]."'
                           AND month(dateValidite) = '".$arr[2]."'
                           AND year(dateValidite) = '".$arr[3]."'
                      ORDER BY montantPlafond ";
        }
        $sql = $this->db->query($requete);
        $tmpHtml = "</thead><tbody>";
        $dateValid = "";
        $num = $this->db->num_rows($sql);
        if ($num > 0)
        {
            while ($res = $this->db->fetch_object($sql))
            {
                $dateValid = date('d/m/Y',strtotime($res->dateValidite));
                $price = "-";
                if ($res->montantPlafond >0)
                {
                    $price = price(round($res->montantPlafond*100)/100) . " &euro;";
                }
                $tmpHtml .= "<tr><td class='ui-widget-header ui-state-default' align='right'>".$price;
                $tmpHtml .= "    <td class='ui-widget-header ui-state-default' align='center'>".round($res->tauxFinancement*100)/100 ." %";
                //$tmpHtml .= "    <td class='ui-widget-header ui-state-default' align='center'>".date('d/m/Y',strtotime($res->dateValidite));
            }
        } else {
            $tmpHtml .= "<tr><td colspan=5><span></span><div class='error ui-state-error'><span class='ui-icon ui-icon-alert' style='float: left; margin-right: 5px; margin-top: -1px;'></span>Pas de taux configur&eacute; pour la p&eacute;riode courante</div>";
        }
        $html .= $this->drawFinanceTableCommon($displayMenu,$tmpHtml,$dateValid);


        return ($html);
    }

    private function drawFinanceTableClient($date=false,$displayMenu=true)
    {
        $html = "";
        if ("x".$this->client_id =="x")
        {
            print "need to fetch first";
            return (false);
        }
        //Affiche table tel que

        $requete = "SELECT *
                      FROM Babel_GA_taux_client a
                     WHERE client_id = ".$this->client_id."
                       AND dateValidite = (SELECT DISTINCT max(b.dateValidite)
                                             FROM Babel_GA_taux_client b
                                            WHERE a.client_id = b.client_id
                                              AND b.dateValidite <= now()
                                          )
                  ORDER BY montantPlafond ";
                  //print $requete;
        if ($date && preg_match('/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/',$date,$arr))
        {

            $requete = "SELECT *
                          FROM Babel_GA_taux_client a
                         WHERE client_id = ".$this->client_id."
                           AND day(dateValidite) = '".$arr[1]."'
                           AND month(dateValidite) = '".$arr[2]."'
                           AND year(dateValidite) = '".$arr[3]."'
                      ORDER BY montantPlafond ";
        }
        $sql = $this->db->query($requete);
        $tmpHtml = "</thead><tbody>";
        $dateValid = "";
        $num = $this->db->num_rows($sql);
        if ($num > 0)
        {
            while ($res = $this->db->fetch_object($sql))
            {
                $dateValid = date('d/m/Y',strtotime($res->dateValidite));
                $price = "-";
                if ($res->montantPlafond >0)
                {
                    $price = price(round($res->montantPlafond*100)/100) . " &euro;";
                }
                $tmpHtml .= "<tr><td class='ui-widget-header ui-state-default' align='right'>".$price;
                $tmpHtml .= "    <td class='ui-widget-header ui-state-default' align='center'>".round($res->tauxFinancement*100)/100 ." %";
                //$tmpHtml .= "    <td class='ui-widget-header ui-state-default' align='center'>".date('d/m/Y',strtotime($res->dateValidite));
            }
        } else {
            $tmpHtml .= "<tr><td colspan=5><span></span><div class='error ui-state-error'><span class='ui-icon ui-icon-alert' style='float: left; margin-right: 5px; margin-top: -1px;'></span>Pas de taux configur&eacute; pour la p&eacute;riode courante</div>";
        }
        $html .= $this->drawFinanceTableCommon($displayMenu,$tmpHtml,$dateValid);


        return ($html);
    }
    private function drawFinanceTableDflt($date=false,$displayMenu=true)
    {
        $html = "";
        //Affiche table tel que

        $requete = "SELECT *
                      FROM Babel_GA_taux_default a
                     WHERE dateValidite = (SELECT DISTINCT max(b.dateValidite)
                                             FROM Babel_GA_taux_default b
                                            WHERE b.dateValidite <= now()
                                          )
                  ORDER BY montantPlafond ";
        if ($date && preg_match('/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/',$date,$arr))
        {

            $requete = "SELECT *
                          FROM Babel_GA_taux_default a
                         WHERE day(dateValidite) = '".$arr[1]."'
                           AND month(dateValidite) = '".$arr[2]."'
                           AND year(dateValidite) = '".$arr[3]."'
                      ORDER BY montantPlafond ";
        }
        $sql = $this->db->query($requete);
        $tmpHtml = "</thead><tbody>";
        $dateValid = "";
        $num = $this->db->num_rows($sql);
        if ($num > 0)
        {
            while ($res = $this->db->fetch_object($sql))
            {
                $dateValid = date('d/m/Y',strtotime($res->dateValidite));
                $price = "-";
                if ($res->montantPlafond >0)
                {
                    $price = price(round($res->montantPlafond*100)/100) . " &euro;";
                }
                $tmpHtml .= "<tr><td class='ui-widget-header ui-state-default' align='right'>".$price;
                $tmpHtml .= "    <td class='ui-widget-header ui-state-default' align='center'>".round($res->tauxFinancement*100)/100 ." %";
                //$tmpHtml .= "    <td class='ui-widget-header ui-state-default' align='center'>".date('d/m/Y',strtotime($res->dateValidite));
            }
        } else {
            $tmpHtml .= "<tr><td colspan=5><span></span><div class='error ui-state-error'><span class='ui-icon ui-icon-alert' style='float: left; margin-right: 5px; margin-top: -1px;'></span>Pas de taux configur&eacute; pour la p&eacute;riode courante</div>";
        }
        $html .= $this->drawFinanceTableCommon($displayMenu,$tmpHtml,$dateValid);


        return ($html);
    }



    public function drawMargeFinTable($date=false,$displayMenu=true)
    {
        global $user;
        $html = "";
        if ($user->rights->GA->Taux->Admin ||
                ($user->rights->GA->Taux->MargeAffiche || $user->rights->GA->Taux->MargeModifier )
           )
           {

            $requete = "SELECT * FROM Babel_GA_taux_marge
                         WHERE obj_refid = ".$this->obj_id."
                           AND type_ref ='".$this->type_taux."'
                           AND dateTx = (select max(dateTx) from Babel_GA_taux_marge  where dateTx < now() and obj_refid =  ".$this->obj_id." and  type_ref ='".$this->type_taux."')";
            $sql=$this->db->query($requete);
            $res=$this->db->fetch_object($sql);
            $tx = $res->taux;
            $html = "<table width=100%><thead>";
            //1 affiche le taux de marge
            $html .= "<tr><th style='padding: 15px;' class='ui-widget-header ui-state-default'>Taux de marge actuel</th>";
            $html .= "<td align=center class='ui-widget-content'>".$tx." %</td>";
            //2 affiche le bouton modifier
            $html .= "<tr><td colspan=2 class='ui-widget-content'>";
            if ($user->rights->GA->Taux->MargeModifier || $user->rights->GA->Taux->Admin)
            {
                $html .= "<span id='addTxMarge' class='butAction'>Modifier</span>";
            }
            $html .= "&nbsp;&nbsp;&nbsp;<span id='HistoTxMarge' class='butAction'>Historique du taux de marge</span>";
            if ($user->rights->GA->Taux->Admin)
            {
                $html .= "&nbsp;&nbsp;&nbsp;<span id='supprTxMarge' class='butAction'>Supprimer</span>";
            }
            $html .= "</table>";
            if ($displayMenu)
            {

                $html .= "<div id='addTxMargeDialog'>";
                $html .= "<form id='addTxMargeForm'>";
                $html .= "<table cellpadding=15 width=100%><thead>";
                $html .= "<tr><th class='ui-widget-header ui-state-hover'>Date d'activation</td>
                           <td class='ui-widget-content ui-state-hover'><input class='FRDate' name='dateTxMargeAdd' id='dateTxMargeAdd'></td></thead>";
                $html .= "<tbody id='tbodyTxMargeDialog'><tr><td class='ui-widget-header ui-state-default'>Taux de Marge</td>
                            <td class='ui-widget-content'><input  class='percentdecimal required' name='txMargeAdd'></td>";
                $html .= "</table>";
                $html .= "</form>";
                $html .= "<div id='delTxMargeDialog'>";
                $html .= "&Ecirc;tes vous sur de vouloir supprimer ce taux de marge?";
                $html .= "</div>";
            }
            $html .= "<script>";
            if ($this->type_taux == 'dflt')
            {
                $html .= "var cessionnaireID = -1 ;";
            } else {
                $html .= "var cessionnaireID = ".$this->obj_id." ;";
            }
            $html .=  "jQuery(document).ready(function(){

                        jQuery.datepicker.setDefaults(jQuery.extend({showMonthAfterYear: false,
                            dateFormat: 'dd/mm/yy',
                            changeMonth: true,
                            changeYear: true,
                            showButtonPanel: true,
                            buttonImage: 'cal.png',
                            buttonImageOnly: true,
                            showTime: true,
                            duration: '',
                            constrainInput: false,}, jQuery.datepicker.regional['fr']));

                        jQuery(\"#ui-datepicker-div\").addClass(\"promoteZ\");


                        jQuery('#addTxMarge').click(function(){
                            jQuery('#addTxMargeDialog').dialog('open');
                        });
                        jQuery('#supprTxMarge').click(function(){
                            jQuery('#delTxMargeDialog').dialog('open');
                        });
                        jQuery('#HistoTxMarge').click(function(){
                            location.href='".DOL_URL_ROOT."/Babel_GA/historique-tauxMarge.php?socid='+cessionnaireID+'&type=".$this->type_taux."';
                        });

                        jQuery.extend(jQuery.validator.message,{
                            messages: { required: \"Ce champ est requis\" }
                        });
                        jQuery.validator.addMethod(
                            'FRDate',
                            function(value, element) {
                                return value.match(/^\d\d?\/\d\d?\/\d\d\d\d\W?\d?\d?\:?\d?\d?$/);
                            },
                            'La date doit &ecirc;tre au format dd/mm/yyyy'
                        );
                        jQuery.validator.addMethod(
                            'percentdecimal',
                            function(value, element) {
                                return value.match(/^\d\d*?[,.]?\d*?%?$/);
                            },
                            'Le taux n\'est pas au bon format'
                        );

                        jQuery('#delTxMargeDialog').dialog({
                            autoOpen: false,
                            minWidth: 560,
                            maxWidth: 560,
                            width: 560,
                            buttons:{
                                'Annuler': function(){ jQuery(this).dialog('close'); },
                                'Supprimer': function(){
                                    jQuery.ajax({
                                        url: '".DOL_URL_ROOT."/Babel_GA/ajax/ficheMarge-xml_response.php',
                                        datatype: 'xml',
                                        data: 'id='+cessionnaireID+'&action=SupprCurrTableFin&type=".$this->type_taux."',
                                        success: function(msg)
                                        {
                                            //console.log(msg);
                                            jQuery(this).dialog('close');
                                            location.reload();
                                        }
                                    });
                                }
                           }
                        });


                    jQuery('#addTxMargeDialog').dialog({
                        modal: true,
                        autoOpen: false,
                        minWidth: 560,
                        width: 560,
                        title: 'Modifier les conditions',
                        buttons: {
                                   'Ajouter': function() {
                                        if (jQuery('#addTxMargeForm').validate({}).form()){
                                            var data = jQuery('#addTxMargeForm').serialize();
                                            jQuery.ajax({
                                                url: '".DOL_URL_ROOT."/Babel_GA/ajax/ficheCessionnaire-xml_response.php',
                                                data: 'action=SetTauxMarge&id='+cessionnaireID+'&'+data+'&type=".$this->type_taux."',
                                                datatype: 'xml',
                                                success: function(msg)
                                                {
                                                    //console.log(msg);
                                                    location.reload();
                                                }
                                        });
                                       }
                                   },
                                   'Annuler': function() { jQuery(this).dialog('close'); },
                        },
                        open: function(){
                            jQuery('#addLigneFinForm')[0].reset();
                            jQuery('#dateTxMargeAdd').datepicker();
                            jQuery('#ui-datepicker-div').addClass('promoteZ');
                        }
                    });

                });  ";


            $html .= "</script>";
           }

        return($html);
    }
}
?>
<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of synopsisWsShemaFixe
 *
 * @author tijean
 */
class synopsisWsShemaFixe {
    function getShema($nomShema){
              $tabShema = array();

        $tabShema['login'] = array(
            "champLabel" => array("login"),
            "labelTitre" => "Login",
            "champs" => array(
              "login" => array('label' => 'Login', 'required' => true),
              "password" => array('type' => 'password','label' => 'Password', 'required' => true)
            )
        );

        $tabShema['user_extrafields'] = array(
            'champClef' => array("rowid"),
            "champLabel" => array("nom"),
            "labelTitre" => "Utilisateur extra",
            "champs" => array(
                "rowid" => array("type" => "hidden", 'disabled' => true, 'label' => 'Id extra Utilisateur'),
                "apple_id" => array("type" => "text", 'label' => 'Id Apple', 'required' => true),
                "apple_service" => array("type" => "text"),
                "apple_shipTo" => array("type" => "text"),
                "apple_techid" => array("type" => "text"),
                "fk_object" => array("type" => "id", "label" => "Lien user", 'disabled' => true)
            )
        );


        $tabShema['user'] = array(
            'champClef' => array("rowid"),
            "champLabel" => array("lastname", "firstname"),
            "champDesc" => array("email", "login", "office_phone"),
            "labelTitre" => "Utilisateur",
            "champs" => array(
                "rowid" => array("type" => "hidden", 'disabled' => true, 'label' => 'Id Utilisateur'),
                "lastname" => array("type" => "text"),
                "firstname" => array("type" => "text", 'required' => true),
                "login" => array("type" => "text", 'required' => true),
                "email" => array("type" => "email"),
                "admin" => array("type" => "boolean"),
                "office_phone" => array("type" => "text", "label" => "Tel Pro"),
                "user_mobile" => array("type" => "text", "label" => "Tel Mobile"),
                "onsansfous" => array("type" => "relationN1", "label" => "Societes créer par l'utilisateur", "fils" => array("object" => "societe", "clefLien" => "fk_user_creat")),
                "fk_soc" => array("type" => "relation1N", "label" => "Societe de l'utilisateur", "fils" => array("object" => "societe", "clefLien" => "rowid")),
                "onsansfous2" => array("type" => "relation11", "label" => "Extra", "fils" => array("table" => "user_extrafields", "champ" => "fk_object", "shema" => $tabShema['user_extrafields'])),
                "onsansfous3" => array("type" => "relation11", "label" => "Extra", "fils" => array("table" => "user_extrafields", "champ" => "rowid", "shema" => $tabShema['user_extrafields']))
            )
        );

        $tabShema['societe'] = array(
            'champClef' => array("rowid"),
            "champLabel" => array("nom"),
            "champDesc" => array("address", "zip", "town"),
            "labelTitre" => "Societe",
            "champs" => array(
                "rowid" => array("type" => "id", 'label' => 'Id Client', 'disablead' => true),
                "nom" => array("type" => "text", "label" => "Nom", 'disablead' => true),
                'tms' => array('label' => "Dernière activité", 'type' => "date"),
                'phone' => array(),
                'email' => array(),
                'address' => array(),
                'zip' => array(),
                'town' => array(),
                "fk_user_creat" => array("type" => "relation1N", "label" => "Créateur", "fils" => array("object" => "user"))
            )
        );

        $tabShema['propal'] = array(
            'champClef' => array("rowid"),
            "champLabel" => array("ref"),
            "labelTitre" => "Propal/Devis",
            "champs" => array(
                "rowid" => array("type" => "id", 'label' => 'Id Client', 'disablead' => true),
                "ref" => array("type" => "text", "label" => "Ref"),
                'total' => array('label' => "Total", 'type' => "prix")
            )
        );

        $tabShema['contrat'] = array(
            'champClef' => array("rowid"),
            "champLabel" => array("ref"),
            "labelTitre" => "Contrat",
            "champs" => array(
                "rowid" => array("type" => "id", 'label' => 'Id Contrat', 'disablead' => true),
                "ref" => array("type" => "text", "label" => "Ref"),
                "socid" => array("type" => "relation1N", "label" => "Societe", "fils" => array("object" => "societe")),
                "commercial_signature_id" => array(),
                "commercial_suivi_id" => array(),
                "date_contrat" => array("type"=>"date"),
                "onsansfous2" => array("type" => "relationN1", "label" => "Lignes", "fils" => array("object" => "contratdet", "clefLien" => "fk_contrat")),
            )
        );

        $tabShema['contratdet'] = array(
            'champClef' => array("rowid"),
            "champLabel" => array("description"),
            "labelTitre" => "Ligne contrat",
            "champs" => array(
                "rowid" => array("type" => "id", 'label' => 'Id Contrat', 'disablead' => true),
                "description" => array("type" => "text", "label" => "Description"),
                "qty" => array("type" => "int", "label" => "Quantité"),
                "fk_contrat" => array("type"=> "relation1N", "label" => "Contrat", "fils" => array("object" => "contrat"))
            )
        );
        
        return $tabShema[$nomShema];
    }
}

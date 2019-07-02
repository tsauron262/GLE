<?php

function getTabTypeObject($typeFiltre = null) {
    $tabTypeObject = array('synopsischrono' => array("obj" => "Chrono", "tabMenu1" => "Process"),
        'propal' => array("path" => "/comm/propal/class/propal.class.php",
            "tabMenu1" => "commercial",
            "tabMenu2" => "propals",
            "urls" => array("comm/propal/card.php")),
        'facture' => array("path" => "/compta/facture/class/facture.class.php",
            "tabMenu1" => "accountancy",
            "urls" => array("compta/facture/card.php"),
            "nomIdUrl" => "facid"),
        'fichinter' => array(),
        'synopsisfichinter' => array("tabMenu1" => "synopsisficheinter",
            "urls" => array("synopsisfichinter/card.php", "synopsisfichinter/ficheFast.php")),
        'synopsisdemandeinterv' => array("tabMenu1" => "synopsisficheinter"),
        'contrat' => array("tabMenu1" => "commercial",
            "tabMenu2" => "contracts"),
        'expedition' => array(),
        'livraison' => array(),
        'commande' => array("tabMenu1" => "commercial",
            "tabMenu2" => "orders",
            "urls" => array("commande/card.php")),
        'synopsiscommande' => array("obj" => 'commande',
            "path" => "/commande/class/commande.class.php",
            "tabMenu1" => "commercial",
            "tabMenu2" => "orders",
            "urls" => array("Synopsis_PrepaCommande/prepacommande.php"),
            "changeNomUrl" => array("commande/card.php", "Synopsis_PrepaCommande/prepacommande.php"),
            "refPlus" => "-PrC"),
        'banque' => array("obj" => 'Account',
            "path" => "/compta/bank/class/account.class.php",
            "urls" => array("compta/bank/card.php")),
        'contact' => array("tabMenu1" => "companies"),
        'societe' => array("tabMenu1" => "companies",
            "urls" => array("comm/card.php"),
            "nomIdUrl" => "socid"),
        'projet' => array("obj" => 'project',
            "tabMenu1" => "synopsisprojet"),
        'synopsisprojet' => array("obj" => 'synopsisproject',
            "path" => "/synopsisprojet/class/synopsisproject.class.php",
            "tabMenu1" => "synopsisprojet"),
        'tache' => array("obj" => 'Task',
            "path" => "/projet/class/task.class.php",
            "tabMenu1" => "synopsisprojet"),
        'process' => array("obj" => 'processDet',
            "path" => "/Synopsis_Process/class/process.class.php",
            "tabMenu1" => "Process"),
        'product' => array("tabMenu1" => "products"),
        'user' => array("tabMenu1" => "home",
            "tabMenu2" => "users"),
        'ndfp' => array("tabMenu1" => "accountancy"),
        'synopsisholiday' => array("obj" => 'synopsisholiday',
            'path' => '/synopsisholiday/class/holiday.class.php',
            "tabMenu1" => "hrm"),
//            'UserGroup' => array("path" => "/user/class/usergroup.class.php",
//                "urls" => array("/group/card.php")),
        'synopsistasks' => array('urls' => array("synopsisprojet/tasks/task.php"),
            'path' => '/synopsisprojet/class/task.class.php',
            'obj' => 'Task'),
        'equipement' => array('urls' => array("equipement/card.php"),
            'path' => '/equipement/class/equipement.class.php',
            'obj' => 'Equipement'),
        'commandeFournisseur' => array('urls' => array("fourn/commande/card.php"),
            'path' => '/fourn/class/fournisseur.commande.class.php',
            'obj' => 'CommandeFournisseur'),
        'BS_SAV' => array('urls' => array("bimpsupport/index.php?fc=sav"),
            'path' => '/bimpsupport/objects/BS_SAV.class.php'),
        'Equipment' => array('urls' => array("bimpequipment/index.php?fc=equipment"),
            'path' => '/bimpequipment/objects/Equipment.class.php'),
        'Bimp_Propal' => array('urls' => array("bimpcommercial/index.php?fc=propal"),
            'module' => 'bimpcommercial'),
        'Bimp_Commande' => array('urls' => array("bimpcommercial/index.php?fc=commande"),
            'module' => 'bimpcommercial'),
        'Bimp_Facture' => array('urls' => array("bimpcommercial/index.php?fc=facture"),
            'module' => 'bimpcommercial'),
    );

    $tabTypeObject2 = array();
    foreach ($tabTypeObject as $typeT => $data) {
        if ($typeFiltre == null || $typeFiltre == $typeT) {
            if (!isset($data['type']))
                $data['type'] = $typeT;
            if (!isset($data['obj']))
                $data['obj'] = ucfirst($typeT);

            if (!isset($data['path'])){
                if(stripos($data['obj'], "bimp") !== false && isset($data['module']))
                     $data['path'] = $data['module']."/objects/".$data['obj'].".class.php";
                else
                    $data['path'] = "/" . $data['type'] . "/class/" . strtolower($data['obj']) . ".class.php";
            }
            if (!is_file(DOL_DOCUMENT_ROOT . $data['path'])) {
                $data['path1'] = $data['path'];
                $data['path'] = "/core/class/" . $data['obj'] . ".class.php";
            }
            if (!is_file(DOL_DOCUMENT_ROOT . $data['path'])) {
                if ($typeFiltre != null)
                    die("impossible de charger " . $data['path1'] . " ni " . $data['path']);
//                else
//                    dol_syslog("Impossible de charger " . DOL_DOCUMENT_ROOT . $data['path'], 3);
            }
            else {

                if (!isset($data['tabMenu1']))
                    $data['tabMenu1'] = "";
                if (!isset($data['tabMenu2']))
                    $data['tabMenu2'] = "";


                if (!isset($data['urls']))
                    $data['urls'] = array("/" . $data['type'] . "/card.php");
                if (!isset($data['nomIdUrl']))
                    $data['nomIdUrl'] = "id";

                global $conf;

                $version = isset($conf->global->MAIN_VERSION_LAST_UPGRADE) ? $conf->global->MAIN_VERSION_LAST_UPGRADE : $conf->global->MAIN_VERSION_LAST_INSTALL;
                if (substr($version, 0, 1) > 2 && substr($version, 2, 1) < 7)
                    foreach ($data['urls'] as $idT => $url)
                        $data['urls'][] = str_replace("card.php", "fiche.php", $url);
//                echo "<pre>";print_r($conf);



                $tabTypeObject2[$typeT] = $data;
            }
        }
    }
    return $tabTypeObject2;
}

function getTypeAndId($url = null, $request = null) {

    if ($url == NULL)
        $url = $_SERVER['REQUEST_URI'];
    if ($request == NULL)
        $request = $_REQUEST;
    if (stripos($url, "ajax") != false) {
        return null;
    }


    $tabTypeObject = getTabTypeObject();
    foreach ($tabTypeObject as $typeT => $dataT) {
        foreach ($dataT['urls'] as $filtreUrl) {
            if (stripos($url, $filtreUrl) !== false) {
                $element_type = $typeT;
                $element_id = $request[$dataT['nomIdUrl']];
            }
        }
    }
    return array($element_type, $element_id);
}

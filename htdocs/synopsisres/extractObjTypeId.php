<?php

function getTabTypeObject($typeFiltre = null) {
    $tabTypeObject = array('synopsischrono' => array("obj" => "Chrono", "tabMenu1" => "Process"),
        'propal' => array("path" => "/comm/propal/class/propal.class.php",
            "tabMenu1" => "commercial",
            "tabMenu2" => "propals",
            "urls" => array("comm/propal.php")),
        'facture' => array("path" => "/compta/facture/class/facture.class.php",
            "tabMenu1" => "accountancy",
            "urls" => array("compta/facture.php"),
            "nomIdUrl" => "facid"),
        'fichinter' => array(),
        'synopsisfichinter' => array("tabMenu1" => "synopsisficheinter"),
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
            'obj' => 'Task')
    );

    $tabTypeObject2 = array();
    foreach ($tabTypeObject as $typeT => $data) {
        if ($typeFiltre == null || $typeFiltre == $typeT) {
            if (!isset($data['type']))
                $data['type'] = $typeT;
            if (!isset($data['obj']))
                $data['obj'] = ucfirst($typeT);

            if (!isset($data['path']))
                $data['path'] = "/" . $data['type'] . "/class/" . strtolower($data['obj']) . ".class.php";
            if (!is_file(DOL_DOCUMENT_ROOT . $data['path'])) {
                $data['path1'] = $data['path'];
                $data['path'] = "/core/class/" . $data['obj'] . ".class.php";
            }
            if (!is_file(DOL_DOCUMENT_ROOT . $data['path'])) {
                if ($typeFiltre != null)
                    die("impossible de charger " . $data['path1'] . " ni " . $data['path']);
                else
                    dol_syslog("Impossible de charger " . DOL_DOCUMENT_ROOT . $data['path'], 3);
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
                        $data['urls'][$idT] = str_replace("card.php", "fiche.php", $url);
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
    /* if (stripos($url, "compta/facture") != false) {
      $element_type = 'facture';
      @$element_id = $request['facid'];
      } elseif (stripos($url, "societe/soc.php") || stripos($url, "comm/card.php?socid=") || stripos($url, "comm/prospect/fiche.php?socid=")) {
      $element_type = 'societe';
      @$element_id = $request['socid'];
      } elseif (stripos($url, "product/card.php") != false) {
      $element_type = 'product';
      @$element_id = $request['id'];
      } elseif (stripos($url, "projet/tasks/task.php") != false) {
      $element_type = 'tache';
      @$element_id = $request['id'];
      } elseif (stripos($url, "projet/") != false) {
      $element_type = 'projet';
      @$element_id = $request['id'];
      } elseif (stripos($url, "commande/") != false) {
      $element_type = 'commande';
      @$element_id = $request['id'];
      } elseif (stripos($url, "compta/bank/") != false) {
      $element_type = 'banque';
      @$element_id = $request['id'];
      } elseif (stripos($url, "fichinter/") != false) {
      $element_type = 'FI';
      @$element_id = $request['id'];
      } elseif (stripos($url, "synopsisdemandeinterv/") != false) {
      $element_type = 'DI';
      @$element_id = $request['id'];
      } elseif (stripos($url, "contrat/") != false) {
      $element_type = 'contrat';
      @$element_id = $request['id'];
      } elseif (stripos($url, "user/card.php") != false) {
      $element_type = 'user';
      @$element_id = $request['id'];
      } elseif (stripos($url, "comm/propal.php") != false) {
      $element_type = 'propal';
      @$element_id = $request['id'];
      } elseif (stripos($url, "/synopsischrono/admin/synopsischrono") != false) {
      $element_type = 'configChrono';
      @$element_id = $request['id'];
      } elseif (stripos($url, "synopsischrono") != false) {
      $element_type = 'chrono';
      @$element_id = $request['id'];
      } elseif (stripos($url, "Synopsis_Process") != false) {
      $element_type = 'process';
      @$element_id = $request['process_id'];
      } elseif (stripos($url, "ndfp") != false) {
      $element_type = 'ndfp';
      @$element_id = $request['id'];
      } elseif (stripos($url, "expedition") != false) {
      $element_type = 'expedition';
      @$element_id = $request['id'];
      } elseif (stripos($url, "synopsisholiday") != false) {
      $element_type = 'synopsisholiday';
      @$element_id = $request['id'];
      } else {
      return null;
      } */
}

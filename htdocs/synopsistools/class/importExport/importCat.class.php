<?php

require_once DOL_DOCUMENT_ROOT . "/synopsistools/class/importExport/import8sens.class.php";

class importCat extends import8sens {
    var $catDef = 2;
//    var $catDef = 12986;
    var $marqueOk = false;
    var $obsolescenceOk = false;
    var $tabConvert = array(
    //GAMME 8 SENS
        "Gamme" => array(
            "Accessoires" => array(
                array("", "A catégoriser")
            ),
            "Consommables" => array(
                array("", "Consommable")
            ),
            "Générique compta" => array(
                array("", "Générique"),
                array("Obsolescence", "Indisponible")
            ),
            "Generique compta" => array(
                array("", "Générique"),
                array("Obsolescence", "Indisponible")
            ),
            "Ope Marketing" => array(
                array("", "Opé Marketing"),
                array("Marque", "Apple")
            ),
             "Logiciels" => array(
                array("", "Logiciel")
            ),
              "Remise" => array(
                array("", "Remise"),
                array("Obsolescence", "Indisponible")
            ),
              "Matériel" => array(
                array("", "Matériel")
            ),
               "Prestations externes" => array(
                array("Marque", "A catégoriser"),
                array("", "Service")
            ),
               "Prestations internes" => array(
                array("Marque", "Bimp"),
                array("", "Service")
            ),
               "Livres" => array(
                array("", "Livre"),
                array("Obsolescence", "Indisponible")
            ),
               "Textile" => array(
                array("", "Textile")
            ),
               "Transport" => array(
                array("", "Transport")
            ),
               "ZZ" => array(
                array("", "A catégoriser")
            ),
            //    "Ope Apple" => array(
            //     array("", "")
            // ),

        ),
// FAMILLE 8SENS
        "Famille" => array(
            "Actuel" => array(
                array("Obsolescence", "Actuel")
            ),
            "Contrat Pro" => array(
                array("Gamme", "Déplacement"),
                array("Recurrence", "Support BtoB")
            ),
            "Deplacement Pro" => array(
                array("Gamme", "Déplacement"),
                array("Gamme", "Sur site client")
            ),
            "Développement" => array(
                array("Gamme", "Développement"),
                array("Gamme", "Logiciel")
            ),
            "Developpement" => array(
                array("Gamme", "Développement"),
                array("Gamme", "Logiciel")
            ),
            "Formation" => array(
                array("Gamme", "Formation"),
                array("Recurrence", "Unique")
            ),
            "Installation" => array(
                array("Gamme", "Installation"),
                array("Recurrence", "Unique")
            ),
            "Obsolète" => array(
                array("Obsolescence", "Obsolète")
            ),
            "Obsolete" => array(
                array("Obsolescence", "Obsolète")
            ),
            "Occa" => array(
                array("Gamme", "Occasion")
            ),
            "PRESTA PRO" => array(
                array("Gamme", "Sur site client"),
                array("Gamme", "Matériel & Logiciel")
            ),
            // "Stats" => array(
            //     array("", "")
            // ),
            // "Technicien" => array(
            //     array("", "")
            // ),
            "Texte formule" => array(
                array("Gamme", "Base de textes")
            ),
        ),
//NATURE 8SENS
        "Nature" => array(
            "ACCESSOIRES" => array(
                array("Gamme", "Accessoire")
            ),
            "ANTI VIRUS" => array(
                array("Gamme", "Antivirus")
            ),
            "APPLE WATCH" => array(
                array("Gamme", "Montre connectée"),
                array("Marque", "Apple"),
                array("Modèle", "Apple Watch")
            ),
            "APPLE WATCH DEMO" => array(
                array("Gamme", "Montre connectée"),
                array("Marque", "Apple"),
                array("Modèle", "Apple Watch Démo")
            ),
            "APPLE WATCH SPORT" => array(
                array("Gamme", "Montre connectée"),
                array("Marque", "Apple"),
                array("Modèle", "Apple Watch Sport")
            ),
            "APPLICATION" => array(
                array("Gamme", "Logiciel")
            ),
            "ARTS GRAPHIQUE" => array(
                array("Gamme", "Arts Graphiques")
            ),
            "ARTS GRAPHIQUES" => array(
                array("Gamme", "Arts Graphiques")
            ),
            "BUREAUTIQUE" => array(
                array("Gamme", "Bureautique")
            ),
            "CAMERA" => array(
                array("Gamme", "Caméra")
            ),
            "CAO" => array(
                array("Gamme", "CAO")
            ),
            "CASQUES" => array(
                array("Gamme", "Casque")
            ),
            "CLE" => array(
                array("Gamme", "Clef USB")
            ),
            "CLOUD SERVICES" => array(
                array("Gamme", "Cloud")
            ),
            "CONTRAT BOUTIQUE" => array(
                array("Gamme", "Sur site BIMP"),
                array("Gamme", "Dépannage"),
                array("Recurrence", "Maintenance Materiel")
            ),
            "CONTRATS RESERVES" => array(
                array("Gamme", "Service"),
                array("Recurrence", "Périodique")
            ),
            "CONTRATS" => array(
                array("Gamme", "Service"),
                array("Recurrence", "Périodique")
            ),
            "COUPEUSE" => array(
                array("Gamme", "Coupeuse")
            ),
            "CPL" => array(
                array("Gamme", "Boitier CPL")
            ),
            "DD BUREAU" => array(
                array("Gamme", "HDD externe")
            ),
            "DD INTERNE" => array(
                array("Gamme", "HDD interne")
            ),
            "DD MULTIMEDIA" => array(
                array("Gamme", "HDD multimédia")
            ),
            "DD NAS" => array(
                array("Gamme", "NAS")
            ),
            "DD NOMADE" => array(
                array("Gamme", "HDD externe")
            ),
            "DD RAID" => array(
                array("Gamme", "NAS")
            ),
            "DD RESEAU" => array(
                array("Gamme", "NAS")
            ),
            "DEPLACEMENT" => array(
                array("Gamme", "Déplacement"),
                array("Gamme", "Sur site client")
            ),
            "DEVELOPPEMENT" => array(
                array("Gamme", "Développement")
            ),
            "DICTAPHONE" => array(
                array("Gamme", "Dictaphone")
            ),
            "DOUCHETTE" => array(
                array("Gamme", "Douchette")
            ),
            "DRONE" => array(
                array("Gamme", "Drone")
            ),
            "EDUCATION" => array(
                array("Gamme", "Education")
            ),
            "ENCEINTES" => array(
                array("Gamme", "Enceintes")
            ),
            "ENCRES-TONERS" => array(
                array("Gamme", "Encre Toner")
            ),
            "EXTENSION DE GARANTIE" => array(
                array("Gamme", "Maintenance"),
                array("Marque", "A catégoriser"),
                array("Gamme", "Materiel")
            ),
            "FAX" => array(
                array("Gamme", "Fax")
            ),
            "FIREWALL" => array(
                array("Gamme", "Firewall")
            ),
            "FORMATIONS" => array(
                array("Gamme", "Formation")
            ),
            "GESTION" => array(
                array("Gamme", "Gestion")
            ),
            "GRAVEURS" => array(
                array("Gamme", "Graveur")
            ),
            "HOMEPOD" => array(
                array("Gamme", "Enceintes"),
                array("Marque", "Apple"),
                array("Modèle", "Apple Homepod")
            ),
            "HOUSSES" => array(
                array("Gamme", "Housse")
            ),
            "INDETERMINE" => array(
                array("", "A catégoriser")
            ),
            "IPHONE" => array(
                array("Gamme", "Iphone"),
                array("Marque", "Apple"),
                array("Modèle", "Apple Iphone")
            ),
            "IPOD" => array(
                array("Gamme", "Ipod"),
                array("Marque", "Apple"),
                array("Modèle", "Apple Ipod")
            ),
            "JET D'ENCRE" => array(
                array("Gamme", "Jet d'encre")
            ),
            "LASER" => array(
                array("Gamme", "Laser")
            ),
            "LIVRAISON" => array(
                array("Gamme", "Transport")
            ),
            // "LOGISTIQUE" => array(
            //     array("", "")
            // ),
            // "MAINTENANCE" => array(
            //     array("", "")
            // ),
            "MATRICIELLE" => array(
                array("Gamme", "Matricielle")
            ),
            "MATRICIELLES" => array(
                array("Gamme", "Matricielle")
            ),
            "MEMOIRES" => array(
                array("Gamme", "RAM")
            ),
            "MESSAGERIE" => array(
                array("Gamme", "Messagerie")
            ),
            "MULTIFONCTIONS" => array(
                array("Gamme", "Multifonctions")
            ),
            "ONDULEURS" => array(
                array("Gamme", "Onduleur")
            ),
            "PAGEPACK" => array(
                array("Gamme", "PagePack"),
                array("Marque", "Xerox"),
                array("Gamme", "Autre"),
                array("Gamme", "Matériel & Logiciel")
            ),
            "PAPIERS" => array(
                array("Gamme", "Papier")
            ),
            "PHOTO" => array(
                array("Gamme", "Appareil photo")
            ),
            "POCHETTES" => array(
                array("Gamme", "Pochette")
            ),
            "POCHETTE" => array(
                array("Gamme", "Pochette")
            ),
            "POLICES FONT" => array(
                array("Gamme", "Polices Font")
            ),
            "RESEAU" => array(
                array("Gamme", "Réseau")
            ),
            "ROUTEUR" => array(
                array("Gamme", "Routeur")
            ),
            "SACS" => array(
                array("Gamme", "Sac")
            ),
            "SANTE" => array(
                array("Gamme", "Sante")
            ),
            "SAUVEGARDE" => array(
                array("Gamme", "Sauvegarde")
            ),
            "SAV" => array(
                array("Gamme", "SAV Apple")
            ),
            "SCANNERS" => array(
                array("Gamme", "Scanner")
            ),
            "SON" => array(
                array("Gamme", "Son")
            ),
            // "SOUS-TRAITANCE" => array(
            //     array("", "")
            // ),
            "SYSTEME" => array(
                array("Gamme", "A catégoriser")
            ),
            "TABLETTES" => array(
                array("Gamme", "Tablette")
            ),
            "TAPIS" => array(
                array("Gamme", "Tapis")
            ),
            "TELEMAINTENANCE" => array(
                array("Gamme", "Telemaintenance")
            ),
            "TELEPHONE" => array(
                array("Gamme", "Téléphone bureau")
            ),
            "THERMIQUE" => array(
                array("Gamme", "Thermique")
            ),
            "TRACEUR" => array(
                array("Gamme", "Traceur")
            ),
            "UTILITAIRES" => array(
                array("Gamme", "Utilitaire")
            ),
            "VIDEO" => array(
                array("Gamme", "Vidéo")
            ),
            "VIRTUALISATION" => array(
                array("Gamme", "Virtualisation")
            ),
            "WIFI" => array(
                array("Gamme", "Wifi")
            ),
        ),
// CATEGORIE 8SENS
        "Categorie" => array(
            "ACCESSOIRES" => array(
                array("Gamme", "Accessoire")
            ),
            "COMPOSANTS" => array(
                array("Gamme", "Composant")
            ),
            "CONSOMMABLES" => array(
                array("Gamme", "Consommable")
            ),
            "CONTRAT PRO" => array(
                array("Gamme", "Service"),
                array("Marque", "BIMP")
            ),
            "ECRANS" => array(
                array("Gamme", "Ecran")
            ),
            "HOMEPOD" => array(
                array("Gamme", "Enceintes"),
                array("Marque", "Apple"),
                array("Modèle", "Apple HomePod")
            ),
            "IMPRIMANTES" => array(
                array("Gamme", "Imprimante")
            ),
            "IPAD" => array(
                array("Gamme", "Tablette"),
                array("Marque", "Apple"),
                array("Modèle", "Apple Ipad")
            ),
            "IPAD MINI" => array(
                array("Gamme", "Tablette"),
                array("Marque", "Apple"),
                array("Modèle", "Apple Ipad Mini")
            ),
            "IPAD PRO" => array(
                array("Gamme", "Tablette"),
                array("Marque", "Apple"),
                array("Modèle", "Apple Ipad Pro")
            ),
            "IPHONE" => array(
                array("Gamme", "Smartphone"),
                array("Marque","Apple"),
                array("Modèle", "Apple Iphone")
            ),
            "IPOD" => array(
                array("Gamme", "Ipod"),
                array("Marque", "Apple"),
                array("Modèle", "Apple Ipod")
            ),
            "LIVRES" => array(
                array("Gamme", "Livre"),
                array("Obsolescence", "Indisponible")
            ),
            "LOGICIELS" => array(
                array("Gamme", "Logiciel")
            ),
            "MAINTENANCE CONSTRUCTEUR" => array(
                array("Marque", "A catégoriser"),
                array("Gamme", "Maintenance"),
                array("Gamme", "Matériel"),
                array("Gamme", "Autre")
            ),
            "MONTRES CONNECTEES" => array(
                array("Gamme", "Montre connectée")
            ),
            "NAS" => array(
                array("Gamme", "NAS")
            ),
            "PERIPHERIQUES" => array(
                array("Gamme", "Accessoire")
            ),
            "PORTABLES APPLE" => array(
                array("Gamme", "Ordinateur portable"),
                array("Marque", "Apple"),
                array("Modèle", "MacBook")
            ),
            "PORTABLES WINDOWS" => array(
                array("Gamme", "Ordinateur portable")
            ),
            "RESEAU" => array(
                array("Gamme", "Réseau")
            ),
            "SERVEUR" => array(
                array("Gamme", "Serveur")
            ),
             "SERVICE CONSTRUCTEUR" => array(
                 array("", "")
             ),
            "SMARTPHONE" => array(
                array("Gamme", "Smartphone")
            ),
            "SON" => array(
                array("Gamme", "Son")
            ),
            "TABLETTES" => array(
                array("Gamme", "Tablette")
            ),
            "TEXTILE" => array(
                array("Gamme", "Textile")
            ),
            "UC APPLE" => array(
                array("Gamme", "Unité centrale"),
                array("Marque", "Apple")
            ),
            "UC WINDOWS" => array(
                array("Gamme", "Unité centrale")
            ),
        ),
//COLLECTION 8SENS
        "Collection" => array(
            "BIMP" => array(
                array("Marque", "BIMP")
            ),
            // "BIMP K12" => array(
            //     array("", "")
            // ),
            "LDLC PRO" => array(
                array("Marque", "LDLC PRO")
            ),
            "OCCASION" => array(
                array("Gamme", "Occasion")
            ),
            "Remises Volumes CRT" => array(
                array("Gamme", "Remise CRT")
            ),
            "JAM SOFTWARE" => array(
                array("Marque", "JAM SOFTWARE")
            ),
            "FINAPRO" => array(
                array("Marque", "FINAPRO")
            ),
            "IMMOBILISATION" => array(
                array("Gamme", "IMMOBILISATION")
            ),
            "POCHETTES" => array(
                array("Gamme", "Pochette")
            ),
            "xxxxxxx" => array(
                array("Marque", "XXXXXXX")
            ),
        )
    );

    public function __construct($db) {
        parent::__construct($db);
        $this->path .= "cat/";
        $this->sepCollone = ";";
    }

    
    function traiteLn($ln){
        $tabCat = explode("->", $ln['CAT']);
        $fk_parent = $this->catDef;
        foreach($tabCat as $cat){
            $catId = $this->getCatIDByNom($cat, $fk_parent);
            if($catId < 1)
                $catId = $this->createCat ($cat, $fk_parent);
            $fk_parent = $catId;
            echo "<br/>Cat : ".$cat." avec pour id ".$catId;
        }
    }

    function getCatIDByNom($nom, $parent = null, $lien = "parent") {
        if(is_null($parent))
            $parent = $this->catDef;
        if (isset($this->cache['catIdByNom'][$parent][$lien][$nom]))
            return $this->cache['catIdByNom'][$parent][$lien][$nom];
        else {
            if($lien == "parent")
                $sql = $this->db->query("SELECT rowid FROM `llx_categorie` WHERE `type` = 0 AND `label` LIKE '" . addslashes ($nom) . "' AND fk_parent = ".$parent);
            else
                $sql = $this->db->query("SELECT rowid  FROM `" . MAIN_DB_PREFIX . "view_categorie_all` WHERE `leaf` LIKE  '" . addslashes($nom) . "' AND (id_subroot = " . $parent." OR fk_parent = " . $parent.")"); //TODO rajout de type
            if ($this->db->num_rows($sql) < 1)
                return 0;
            else {
                $ln = $this->db->fetch_object($sql);
                $this->cache['catIdByNom'][$parent][$lien][$nom] = $ln->rowid;
                return $ln->rowid;
            }
        }
        
    }
    
    
    function traiteCat1($grandeCat, $cat) {
        $converti = false;
        $catDef = null;
        
        
        foreach ($this->tabConvert as $grandeCatTest => $tabT) {
            if($grandeCat == $grandeCatTest){
                foreach($tabT as $catTest => $tabT2){
                    if(strtoupper($catTest) == strtoupper($cat)){
                        $converti = true;
                        foreach($tabT2 as $tabConverssion){
                            if($tabConverssion[0] == "")
                                $tabConverssion[0] = $grandeCatTest;
                            $this->traiteCat($tabConverssion[0], $tabConverssion[1]);
                        }
                    }
                }
            }
            
        }
        
        if(!$converti && $grandeCat == "Collection"){
            $grandeCat = "Externe";
            $catDef = $this->getCatIDByNom("Marque");
            $catDef = $this->getCatIDByNom("Nature", $catDef);
        }
        

        if(!$converti)
            $this->traiteCat ($grandeCat, $cat, $catDef);
    }

    function traiteCat($grandeCat, $cat, $catDef = null, $unique = true) {
        if(in_array($grandeCat, array("Gamme", "Externe", "Marque")))
                $unique = false;
        
        
        if($grandeCat == "Obsolescence"){
            if($this->obsolescenceOk)
                return 0;
            else
                $this->obsolescenceOk = true;
        }
        
        
        $grCatId = $this->getCatIDByNom($grandeCat, $catDef);
        if ($grCatId < 1)
            die("Grande Famille " . $grandeCat . " introuvable dans cat d'id ".$catDef);
        else {
            $catId = array();
            if ($cat == "" || $cat == "  " || $cat == " " || $cat == "  "){
                if($grandeCat == "Marque" && $this->marqueOk)
                    return 0;
                $cat = "A catégoriser";
            }
            $catTmp = $this->getCatIDByNom($cat, $grCatId, "racine");
            if ($catTmp < 1) {
                $catId[] = $this->createCat($cat, $grCatId);
            } else {
                $catId[] = $catTmp;
                while ($catMere = $this->getCatMere($catTmp) AND $catMere != $grCatId) {
                    if ($catMere != $grCatId)
                        $catId[] = $catMere;
                    $catTmp = $catMere;
                }
            }
            if($grandeCat == "Marque")
                $this->marqueOk = true;
            
            if(!$this->testCat($catId, $grCatId, $unique))
                $this->updateProdCat($catId, $grCatId, $unique);
        }
    }
    

    function updateProdCat($catId, $fk_parent, $unique) {
        echo "update cat : ".$fk_parent." prod :".$this->object->id." unique : ".($unique ? "oui":"non")."<br/>";
        if($unique)
            $this->db->query("DELETE FROM " . MAIN_DB_PREFIX . "categorie_product WHERE  fk_categorie IN (SELECT rowid FROM `" . MAIN_DB_PREFIX . "view_categorie_all` WHERE `id_subroot` = " . $fk_parent . " OR id_level_1 = " . $fk_parent . " OR id_level_2 = " . $fk_parent . " OR id_level_3 = " . $fk_parent . " OR id_level_4 = " . $fk_parent . " OR id_level_5 = " . $fk_parent . ") AND fk_product = " . $this->object->id);
        foreach ($catId as $cat) {
            if($unique || !in_array($cat, $this->allCatProd)){
                $this->db->query("INSERT INTO " . MAIN_DB_PREFIX . "categorie_product (fk_categorie, fk_product) VALUES (" . $cat . "," . $this->object->id . ")");
                $this->allCatProd[] = $cat;
            }
        }
    }
    
    function getAllCat(){
        $this->marqueOk = false;
        $this->obsolescenceOk = false;
        $this->allCatProd = array();
        $sql = $this->db->query("SELECT fk_categorie FROM " . MAIN_DB_PREFIX . "categorie_product WHERE fk_product = ".$this->object->id);
        while($result = $this->db->fetch_object($sql))
                $this->allCatProd[] = $result->fk_categorie;
    }
    
    function getSouCat($fk_parent){
        if(!isset($this->cache['listSousCat'][$fk_parent])){
            $sql100 = $this->db->query("SELECT distinct(rowid) FROM `" . MAIN_DB_PREFIX . "view_categorie` WHERE `id_subroot` = " . $fk_parent . " OR id_level_1 = " . $fk_parent . " OR id_level_2 = " . $fk_parent . " OR id_level_3 = " . $fk_parent . " OR id_level_4 = " . $fk_parent . " OR id_level_5 = " . $fk_parent . "");
            if($this->db->num_rows($sql100) < 1){
                dol_syslog("erreur 5345346464534",3);
                return 0;
            }
            while($result = $this->db->fetch_object($sql100))
                $this->cache['listSousCat'][$fk_parent][] = $result->rowid;
        }
        return $this->cache['listSousCat'][$fk_parent];
    }
    
    function testCat($catId, $fk_parent, $unique){
        if($unique){
            $sql = $this->db->query("SELECT * FROM " . MAIN_DB_PREFIX . "categorie_product WHERE  fk_categorie IN (".implode(",", $this->getSouCat($fk_parent)).") AND fk_product = " . $this->object->id. " AND fk_categorie NOT IN (".implode(", ", array_merge($catId, $this->getSouCat($catId[0]))).")");
            if($this->db->num_rows($sql) > 0)
                return 0;//Cat a suppr
        }
        
        
        foreach($catId as $cat)
            if(!in_array($cat, $this->allCatProd))
                    return 0; //Cat a ajouter
        return 1;
    }

    function getCatMere($id) {
        $sql = $this->db->query("SELECT `fk_parent` FROM `llx_categorie` WHERE `rowid` = " . $id);
        if ($this->db->num_rows($sql) > 0) {
            $ln = $this->db->fetch_object($sql);
            return $ln->fk_parent;
        }
    }

    function createCat($cat, $fk_parent) {
        echo '<br/>Création de la cat '.$cat.' dans '.$fk_parent;
        $sql = $this->db->query("INSERT INTO " . MAIN_DB_PREFIX . "categorie (label, type, fk_parent) VALUES ('" . addslashes($cat) . "', 0, " . $fk_parent . ") ");
        $id = $this->db->last_insert_id($sql);
        if(isset($this->cache['listSousCat'][$fk_parent]))
            $this->cache['listSousCat'][$fk_parent][] = $id;
        return $id;
    }



}

<?php

class fileInfo {

    private $ok = true;

    function fileInfo($db) {
        $this->pathFileInfo = DOL_DOCUMENT_ROOT . "/synopsistools/fileInfo/";
        $this->db = $db;
        $this->fileVue = array();
        $requete = "SELECT rowid, file, date FROM " . MAIN_DB_PREFIX . "synopsistools_fileInfo";
        $sql = $this->db->query($requete);
        if ($sql) {
            while ($result = $db->fetch_object($sql))
                $this->fileVue[$result->rowid] = array("nom" => $result->file, "date" => $result->date);
        } else {
            $this->ok = false;
            echo "Il y a un probleme avec la table synopsistools_fileInfo";
        }
    }

    function showNewFile() {
        if (isset($_REQUEST['appli'])) {
            if ($_REQUEST['appli'] == "Oui")
                $this->appliFile($_REQUEST['file']);
            else
                $this->marquFileVue($_REQUEST['file']);
unset($_REQUEST['appli']);
            echo "<h3>Process</h3>";
        }

        if ($this->ok) {
            $Directory = $this->pathFileInfo;
            $MyDirectory = opendir($Directory) or die('Erreur');
            while ($Entry = @readdir($MyDirectory)) {
                if ($Entry != '.' && $Entry != '..' && stripos($Entry, "hide") === false) {
                    if (is_dir($Directory . '/' . $Entry)) {
                        //Dossier
                    } else { //if (!in_array($Entry, $this->fileVue)) {
                        $vue = false;
                        foreach ($this->fileVue as $file)
                            if ($file['nom'] == $Entry)
                                $vue = true;
                        if ($vue == false) {
//                            $this->marquFileVue($Entry);
                            $this->showFile($Entry);
                        } else {
                            //Cest un fichier deja vue;
                        }
                    }
                }
            }
            closedir($MyDirectory);
        }
    }

    private function getFile($nom) {
        if (stripos($nom, ".php")) {
            include($this->pathFileInfo . $nom);
            $this->tabSql = $tabSql;
            $this->tabActiveModule = $activeModule;
            $this->php = $php;
            return $text;
        } else
            return str_replace("\n", "<br/>", file_get_contents($this->pathFileInfo . $nom));
    }

    public function getFiles() {
        $return = '';
        $tabFile = $this->fileVue;
        ksort($tabFile);
        $tabFile = array_reverse($tabFile);
//        for($i=count(); $i--; $i>0){
//            $file = $this->fileVue[$i];
        foreach ($tabFile as $file) {
            echo $i;
            $return .= "Fichier : " . $file['nom'] . "<br/>Vue : " . $file['date'] . "<br/><br/>";
            if (is_file($this->pathFileInfo . $file['nom']))
                $return .= $this->getFile($file['nom']);
            else
                $return .= "Le fichier n'existe plus";
            $return .= "<br/>- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - <br/>";
        }
        return $return;
    }

    public function appliFile($nom) {
        global $db;
        $this->getFile($nom);
        if (isset($this->tabSql) && is_array($this->tabSql))
            foreach ($this->tabSql as $req)
                if (!$db->query($req))
                    echo "Erreur SQl : " . $req . "<br/>";
        
        if(isset($this->tabActiveModule) && is_array($this->tabActiveModule)){
            require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
            foreach($this->tabActiveModule as $module){
                unActivateModule($module, 0);
                activateModule($module);
            }
        }
                
                
        if (isset($this->php))
            eval($this->php);
        $this->marquFileVue($nom);
    }

    private function showFile($nom) {
        global $db;
        $info = $this->getFile($nom);
        if (isset($_REQUEST['appli']) || (!isset($this->php) && !isset($this->tabSql))) {
            $message = "Attention ceci est et message due a une mise a jour de GLE.
            <br/>Ce n'est pas un beug mais des instruction pour le bon déroulement de cette mise a jour.<br/><br/>";
            $messafe .= $info;
            $message .= '<br/><br/>Attention ce message ne s\'affichera que une foix.
            <br/>Vous pourez le retrouver dans Tools -> Fichier Info Maj
                            <br/><br/>
                            <input type="button" OnClick="javascript:window.location.reload()" value="OK">';
            $this->marquFileVue($nom);
        } else {
            $message .= "Info : <br/><br/>" . $info . "<br/><br/>";
            if (count($this->tabSql) > 0) {
                $message .= "Appli Sql : <br/><br/>";
                $message .= implode("<br/>", $this->tabSql)."<br/><br/>";
            }
            if (count($this->tabActiveModule) > 0) {
                $message .= "Activation de module : <br/><br/>";
                $message .= implode("<br/>", $this->tabActiveModule)."<br/><br/>";
            }
            if ($this->php != "")
                $message .= "Appli php : <br/><br/>" . $this->php;
            $message .= "<br/><br/><form action='' name='form' method='post'><input type='hidden' name='file' value='" . $nom . "'/><input type='submit' name='appli' value='Oui'/><input type='submit' name='appli' value='Non'/></form>";
        }
        die($message);
    }

    private function marquFileVue($nom) {
        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "synopsistools_fileInfo (file) VALUES ('" . $nom . "')";
        $sql = $this->db->query($requete);
        $this->fileVue[9999999999] = array("nom" => $nom, "date" => '01/01/2000');
    }

}

?>

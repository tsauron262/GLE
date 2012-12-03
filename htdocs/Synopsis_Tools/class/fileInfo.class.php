<?php

class fileInfo {

    private $ok = true;

    function fileInfo($db) {
        $this->pathFileInfo = DOL_DOCUMENT_ROOT . "/Synopsis_Tools/fileInfo/";
        $this->db = $db;
        $this->fileVue = array();
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Tools_fileInfo";
        $sql = $this->db->query($requete);
        if ($sql) {
            while ($result = $db->fetch_object($sql))
                $this->fileVue[$result->rowid] = array("nom" => $result->file, "date" => $result->date);
        } else {
            $this->ok = false;
            echo "Il y a un probleme avec la table Synopsis_Tools_fileInfo";
        }
    }

    function showNewFile() {
        if ($this->ok) {
            $Directory = $this->pathFileInfo;
            $MyDirectory = opendir($Directory) or die('Erreur');
            while ($Entry = @readdir($MyDirectory)) {
                if ($Entry != '.' && $Entry != '..') {
                    if (is_dir($Directory . '/' . $Entry)) {
                        //Dossier
                    } else { //if (!in_array($Entry, $this->fileVue)) {
                        $vue = false;
                        foreach ($this->fileVue as $file)
                            if ($file['nom'] == $Entry)
                                $vue = true;
                        if ($vue == false) {
                            $this->marquFileVue($Entry);
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

    private function showFile($nom) {
        $message = "Attention ceci est et message due a une mise a jour de GLE.
            <br/>Ce n'est pas un beug mais des instruction pour le bon déroulement de cette mise a jour.<br/><br/>";
        $message .= $this->getFile($nom);
        $message .= '<br/><br/>Attention ce message ne s\'affichera que une foix.
            <br/>Vous pourez le retrouver dans Tools -> Fichier Info Maj
                            <br/><br/>
                            <input type="button" OnClick="javascript:window.location.reload()" value="OK">';
        die($message);
    }

    private function marquFileVue($nom) {
        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Tools_fileInfo (file) VALUES ('" . $nom . "')";
        $sql = $this->db->query($requete);
    }

}

?>

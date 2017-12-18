<?php
        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

class BimpFile extends BimpObject
{
    
    private $dontRemove = false;

    public static $types = array(
        'pdf' => array('label' => 'PDF', 'icon' => 'file-pdf-o'),
        'img' => array('label' => 'Image', 'icon' => 'file-image-o'),
        'xls' => array('label' => 'Excel', 'icon' => 'file-excel-o'),
        'doc' => array('label' => 'Word', 'icon' => 'file-word-o'),
        'txt' => array('label' => 'Texte', 'icon' => 'file-text-o'),
        'zip' => array('label' => 'Archive', 'icon' => 'file-archive-o'),
        'oth' => array('label' => 'Autre', 'icon' => 'file-o')
    );
    public static $ext_types = array(
        'pdf'  => 'pdf',
        'jpg'  => 'img',
        'jpeg' => 'img',
        'png'  => 'img',
        'gif'  => 'img',
        'doc'  => 'doc',
        'docx' => 'doc',
        'docm' => 'doc',
        'dotx' => 'doc',
        'dotm' => 'doc',
        'xsl'  => 'xls',
        'xslx' => 'xls',
        'csv'  => 'xls',
        'txt'  => 'txt',
        'zip'  => 'zip',
        'rar'  => 'zip',
        'tar'  => 'zip',
        'xar'  => 'zip',
        'bz2'  => 'zip',
        'gz'   => 'zip',
        'ls'   => 'zip',
        'rz'   => 'zip',
        'sz'   => 'zip',
        '7z'   => 'zip',
        's7z'  => 'zip',
        'zz'   => 'zip',
    );

    // Traitement des fichiers: 
    public function uploadFile()
    {
        
        //$this->getFileUrl();die;
        $errors = array();

        $file = $_FILES['file'];
        $newFileName = $this->getData('file_name');
        $tabT = explode(".", $file["name"]);
        
        
        if($newFileName == "")
            $newFileName = $file["name"];
        else
            $newFileName .= ".".$tabT[count($tabT)-1];
        $_FILES['file']['name'] = $newFileName;
        
        
        if(is_file($this->getFilePath().$newFileName)){
                $errors[] = "Le Fichier existe déja";
                $this->dontRemove = true;
        }
        else{
            $ret = dol_add_file_process($this->getFilePath(), 0, 0, 'file');
            if(!$ret)
                    $errors[] = "Fichier non enregistré";
        }

        // Ajouter toute erreur à $errors (texte) 
        // Si pour une raison ou autre la taille du fichier est modifié, faire juste: $this->set('file_size', $new_size)
        // le retour d'un tableau vide indique que tout c'est bien passé

        return $errors;
    }
    
    private function getFilePath(){
        $modulepart2 = $this->getData('files_dir');
        return DOL_DATA_ROOT."/bimpcore/".$modulepart2."/".$this->getId()."/";
    }
    
    private function getId(){
        $tabT = json_decode(GETPOST("associations_params"));
        $id = $tabT[0]->id_object;
        return $id;
    }

    public function getFileUrl()
    {
        $file = $_FILES['file']["name"];
        
        $modulepart2 = $this->getData('files_dir');
        echo DOL_URL_ROOT.'/document.php?modulepart=bimpcore&file='.$modulepart2."/".$this->getId()."/".$file;
    }

    public function removeFile()
    {
        $errors = array();
        
        $file = $_FILES['file']["name"];
        
        
        
        dol_delete_file($this->getFilePath()."/".$file);
        
        return $errors;
    }

    // Overrides : 

    public function create()
    {
        $errors = array();

        if (!isset($_FILES['file']) || empty($_FILES['file'])) {
            $errors[] = 'Aucun fichier transféré';
        } else {
            $errors = $this->validatePost();
        }

        if (!count($errors)) {
            $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
            if ($ext && array_key_exists($ext, self::$ext_types)) {
                $this->set('file_type', self::$ext_types[$ext]);
            } else {
                $this->set('file_type', 'oth');
            }

            $this->set('file_size', $_FILES['file']['size']);

            $errors = $this->uploadFile();
        }

        if (!count($errors)) {
            $errors = parent::create();
        }

        if (count($errors)  && !$this->dontRemove) {
            $this->removeFile();
        }

        return $errors;
    }
}

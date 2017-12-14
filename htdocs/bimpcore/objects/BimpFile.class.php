<?php

class BimpFile extends BimpObject
{

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
        $errors = array();

        $file = $_FILES['file'];
        $newFileName = $this->getData('file_name');

        // Ajouter toute erreur à $errors (texte) 
        // Si pour une raison ou autre la taille du fichier est modifié, faire juste: $this->set('file_size', $new_size)
        // le retour d'un tableau vide indique que tout c'est bien passé

        return $errors;
    }

    public function getFilePath()
    {
        
    }

    public function getFileUrl()
    {
        
    }

    public function removeFile()
    {
        $errors = array();
        
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

        if (count($errors)) {
            $this->removeFile();
        }

        return $errors;
    }
}

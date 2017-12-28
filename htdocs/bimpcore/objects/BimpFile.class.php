<?php

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

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
        $errors = array();

        $file = $_FILES['file'];
        $newFileName = $this->getData('file_name');
        $tabT = explode(".", $file["name"]);

        $file_dir = $this->getFileDir();

        if (!$file_dir) {
            return array('Aucun dossier de destination spécifié');
        }

        if ($newFileName == "")
            $newFileName = $file["name"];
        else
            $newFileName .= "." . $tabT[count($tabT) - 1];

        $_FILES['file']['name'] = $newFileName;


        if (file_exists($file_dir . '/' . $newFileName)) {
            $errors[] = "Le Fichier existe déja";
            $this->dontRemove = true;
        } else {
            $ret = dol_add_file_process($file_dir, 0, 0, 'file');
            if (!$ret) {
                $errors = BimpTools::getDolEventsMsgs();
                if (!count($errors)) {
                    $errors[] = 'Echec de l\'enrgistrement du fichier pour une raison inconnue';
                }
            }
        }

        return $errors;
    }

    public function getFileDir()
    {
        $dir = $this->getData('file_dir');
        if (is_null($dir) || !$dir) {
            return '';
        }
        return DOL_DATA_ROOT . "/bimpcore/" . $dir;
    }

    public function getFilePath()
    {
        $file = $this->getData('file_name');
        $ext = $this->getData('file_ext');
        $dir = $this->getData('file_dir');

        if (is_null($file) || !$file || is_null($dir) || !$dir) {
            return '';
        }

        return DOL_DATA_ROOT . "/bimpcore/" . $dir . "/" . $file . '.' . $ext;
    }

    public function getFileUrl()
    {
        $file = $this->getData('file_name');
        $ext = $this->getData('file_ext');
        $dir = $this->getData('file_dir');

        return DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . $dir . "/" . $file . '.' . $ext;
    }

    public function removeFile()
    {
        $errors = array();
        if (!dol_delete_file($this->getFilePath())) {
            $errors = BimpTools::getDolEventsMsgs();
            if (!count($errors)) {
                $errors[] = 'Echec de la suppression du fichier "' . $this->getFilePath() . '"';
            }
        }
        return $errors;
    }

    public function isDeletable()
    {
        return 1;
    }

    public function isDownloadable()
    {
        return 1;
    }

    public function getDefaultListExtraButtons()
    {
        $buttons = array();
        $url = $this->getFileUrl();
        if ($this->isDownloadable()) {
            if ($url) {
                $buttons[] = array(
                    'label'   => 'Télécharger',
                    'icon'    => 'download',
                    'onclick' => 'window.open(\'' . $url . '\', \'_blank\')'
                );
            }
        }
        switch ($this->getData('file_type')) {
            case 'img':
                if ($url) {
                    $buttons[] = array(
                        'label'   => 'Afficher l\'image',
                        'icon'    => 'eye',
                        'onclick' => 'loadImageModal($(this), \'' . $url . '\', \'' . $this->getData('file_name') . '\')'
                    );
                }
                break;

            case 'pdf':
                if ($url) {
                    $url .= '&attachment=0';
                    $buttons[] = array(
                        'label'   => 'Afficher le document',
                        'icon'    => 'eye',
                        'onclick' => 'window.open(\''.$url.'\', \'_blank\');'
                    );
                }
                break;
        }
            return $buttons;
    }

    // Vérifications:

    public function checkObjectFiles($module, $object_name, $id_object)
    {
        $current_files = array();
        $rows = parent::getList(array(
                    'parent_module'      => $module,
                    'parent_object_name' => $object_name,
                    'id_parent'          => $id_object
                        ), null, null, 'id', 'asc', 'array', array('file_name', 'file_ext'));
        foreach ($rows as $r) {
            $current_files[] = $r['file_name'] . '.' . $r['file_ext'];
        }

        $file_dir = DOL_DATA_ROOT . '/bimpcore/' . $module . '/' . $object_name . '/' . $id_object;
        if (file_exists($file_dir)) {
            $files = scandir($file_dir);
            foreach ($files as $f) {
                if (in_array($f, array('.', '..'))) {
                    continue;
                }

                if (is_dir($file_dir . '/' . $f)) {
                    continue;
                }

                if (!in_array($f, $current_files)) {
                    $this->reset();

                    $path_info = pathinfo($file_dir . '/' . $f);

                    if ($path_info['extension'] && array_key_exists($path_info['extension'], self::$ext_types)) {
                        $file_type = self::$ext_types[$path_info['extension']];
                    } else {
                        $file_type = 'oth';
                    }

                    if (!count($this->validateArray(array(
                                        'parent_module'      => $module,
                                        'parent_object_name' => $object_name,
                                        'id_parent'          => $id_object,
                                        'file_dir'           => $module . '/' . $object_name . '/' . $id_object,
                                        'file_name'          => $path_info['filename'],
                                        'file_ext'           => $path_info['extension'],
                                        'file_size'          => filesize($file_dir . '/' . $f),
                                        'file_type'          => $file_type
                            )))) {
                        parent::create();
                    }
                    $this->reset();
                }
            }
        }
    }

    // Overrides :
    public function getList($filters = array(), $n = null, $p = null, $order_by = 'id', $order_way = 'DESC', $return = 'array', $return_fields = null, $joins = null)
    {
        if (isset($filters['parent_module']) &&
                isset($filters['parent_object_name']) &&
                isset($filters['id_parent'])) {
            $this->checkObjectFiles($filters['parent_module'], $filters['parent_object_name'], $filters['id_parent']);
        }
        return parent::getList($filters, $n, $p, $order_by, $order_way, $return, $return_fields, $joins);
    }

    public function create()
    {
        $errors = array();

        if (!isset($_FILES['file']) || empty($_FILES['file'])) {
            $errors[] = 'Aucun fichier transféré';
        } else {
            $errors = $this->validatePost();
        }

        $file_dir = $this->getData('file_dir');
        if (is_null($file_dir) || !$file_dir) {
            $module = $this->getData('parent_module');
            $object = $this->getData('parent_object_name');
            $id_object = $this->getData('id_parent');

            if (!is_null($module) && $module &&
                    !is_null($object) && $object &&
                    !is_null($id_object) && $id_object) {
                $file_dir = $module . '/' . $object . '/' . $id_object;
                $this->set('file_dir', $file_dir);
            } else {
                $errors[] = 'Aucun dossier de destination indiqué';
            }
        }

        if (!count($errors)) {
            $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
            if ($ext && array_key_exists($ext, self::$ext_types)) {
                $this->set('file_type', self::$ext_types[$ext]);
            } else {
                $this->set('file_type', 'oth');
            }
            $this->set('file_ext', $ext);
            $this->set('file_size', $_FILES['file']['size']);

            $errors = $this->uploadFile();
        }

        if (!count($errors)) {
            $errors = parent::create();
        }

        if (count($errors) && !$this->dontRemove) {
            $this->removeFile();
        }

        return $errors;
    }

    public function update()
    {
        if (is_null($this->id) || !$this->id) {
            return array('ID Absent');
        }

        $current_name = $this->db->getValue($this->getTable(), 'file_name', '`id` = ' . (int) $this->id);
        if (!is_null($current_name)) {
            $new_name = $this->getData('file_name');
            if (!is_null($new_name) && $new_name) {
                if ($new_name !== $current_name) {
                    $dir = $this->getFileDir();
                    $ext = $this->getData('file_ext');
                    if ($error = BimpTools::renameFile($dir, $current_name . '.' . $ext, $new_name . '.' . $ext)) {
                        return array($error);
                    }
                }
            }
        }

        return parent::update();
    }

    public function delete()
    {
        $errors = $this->removeFile();
        if (!count($errors)) {
            $errors = parent::delete();
        }
        return $errors;
    }
}

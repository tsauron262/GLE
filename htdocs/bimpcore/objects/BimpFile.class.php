<?php

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

class BimpFile extends BimpObject
{

    // Chemins des fichier : DOL_DATA_ROOT/nom_module/nom_objet/id_objet/nom_fichier.ext
    private $dontRemove = false;
    public static $visibilities = array(
        1 => 'Membres Bimp et client',
        2 => 'Membres BIMP',
    );
    
    // CAN CONTEXTE CLIENT
    
    public function canClientView(){
        global $userClient;
        if(isset($userClient)){
            return 1;
        }
        return 0;
    }
    
    public function canClientCreate() {
       $instance = $this->getInstance('bimpsupport', 'BS_Ticket', $this->getData('id_parent'));
       if($instance->getData('status') != 999) {
           return 1;
       }
       return 0;
    }
    
    public function canClientEdit() {
        return $this->canClientCreate();
    }
    
    public function currentContext($default_value = null) {
        if(BimpTools::getContext() == 'public') {
            if($default_value == 'default_value')
                return 1;
            return 0;
        }
        if($default_value = 'default_value')
                return 2;
        return 1;
    }
    
    public function getFilterListInterfaceClient() {
        if(BimpTools::getContext() == 'public') {
            return Array(
                Array(
                    'name' => 'visibility',
                    'filter' => 1
                )
            );
        }
    }
    
    // Getters: 

    public function getFilePath()
    {
        $dir = $this->getFileDir();

        if (!$dir) {
            return '';
        }

        $file = (string) $this->getData('file_name');
        $ext = (string) $this->getData('file_ext');

        if (!$file || !$ext) {
            return '';
        }

        return $dir . $file . '.' . $ext;
    }
    
    public function getFileDir()
    {
        $parent = $this->getParentInstance();

        if (!BimpObject::objectLoaded($parent)) {
            return '';
        }

        if (is_a($parent, 'BimpObject')) {
            return $parent->getFilesDir();
        }

        return DOL_DATA_ROOT . '/bimpcore/' . $this->getData('parent_module') . '/' . $this->getData('parent_object_name') . '/' . $this->getData('id_parent') . '/';
    }

    public function getFileUrl()
    {
        $file = (string) $this->getData('file_name');
        $ext = (string) $this->getData('file_ext');

        if (!$file || !$ext) {
            return '';
        }

        $parent = $this->getParentInstance();

        if (!BimpObject::objectLoaded($parent)) {
            return '';
        }

        if (is_a($parent, 'BimpObject')) {
            return $parent->getFileUrl($file . '.' . $ext);
        }

        $file = $this->getData('parent_module') . '/' . $this->getData('parent_object_name') . '/' . $this->getData('id_parent') . '/' . $file . '.' . $ext;

        return DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . urlencode($file);
    }

    public function isDeletable($force_delete = false)
    {
        return (int) (!(int) $this->getData('deleted'));
    }

    public function isDownloadable()
    {
        return 1;
    }

    public function getDefaultListExtraButtons()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            $parent = $this->getParentInstance();

            if (BimpObject::objectLoaded($parent)) {
                if (method_exists($parent, 'getFilesListExtraBtn')) {
                    $buttons = $parent->getFilesListExtraBtn($this);
                }
            }

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
            switch (BimpTools::getFileTypeCode('x.' . $this->getData('file_ext'))) {
                case 'img':
                    if ($url) {
                        $buttons[] = array(
                            'label'   => 'Afficher l\'image',
                            'icon'    => 'far_eye',
                            'onclick' => 'loadImageModal($(this), \'' . $url . '\', \'' . $this->getData('file_name') . '\')'
                        );
                    }
                    break;

//            case 'pdf':
//                if ($url) {
//                    $url .= '&attachment=0';
//                    $buttons[] = array(
//                        'tag'   => 'a',
//                        'class' => 'documentpreview',
//                        'label' => 'Afficher le document',
//                        'icon'  => 'search',
//                        'attrs' => array(
//                            'attr' => array(
//                                'href' => $url,
//                                'mime' => 'application/pdf'
//                            )
//                        ),
//                    );
//                }
//                break;
            }
        }
        return $buttons;
    }

    // Getters - Overrides BimpObject: 

    public function getParentObjectName()
    {
        if (!is_null($this->parent) && is_a($this->parent, 'BimpObject')) {
            return $this->parent->object_name;
        }

        return $this->getData('parent_object_name');
    }

    public function getParentModule()
    {
        if (!is_null($this->parent) && is_a($this->parent, 'BimpObject')) {
            return $this->parent->module;
        }

        return $this->getData('parent_module');
    }

    // Affichages: 
    
    public function displayType($text_only = 0, $icon_only = 0, $no_html = 0)
    {
        $ext = (string) $this->getData('file_ext');

        if ($ext) {
            return BimpTools::displayFileType('x.' . $ext, $text_only, $icon_only, $no_html);
        }

        return '';
    }

    // Traitements: 

    public function uploadFile()
    {
        $errors = array();

        $file_name = $this->getData('file_name');
        $file_ext = $this->getData('file_ext');

        if (!$file_name || !$file_ext) {
            return array('Aucun nom de fichier spécifié');
        }

        $file_dir = $this->getFileDir();

        if (!$file_dir) {
            return array('Aucun dossier de destination spécifié');
        }

        $_FILES['file']['name'] = $file_name . '.' . $file_ext;


        if (file_exists($file_dir . $_FILES['file']['name'])) {
            $errors[] = "Le Fichier existe déja";
            $this->dontRemove = true;
        } else {
            $ret = dol_add_file_process($file_dir, 0, 0, 'file');
            if ($ret <= 0) {
                $errors = BimpTools::getDolEventsMsgs(array('errors', 'warnings'));
                if (!count($errors)) {
                    $errors[] = 'Echec de l\'enregistrement du fichier pour une raison inconnue';
                }
            }
            BimpTools::cleanDolEventsMsgs();
        }


        return $errors;
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

    public function checkObjectFiles($module, $object_name, $id_object)
    {
        if ($this->isLoaded()) {
            return;
        }

        $current_files = array();
        $rows = parent::getList(array(
                    'parent_module'      => $module,
                    'parent_object_name' => $object_name,
                    'id_parent'          => $id_object
                        ), null, null, 'id', 'asc', 'array', array('id', 'file_name', 'file_ext'));
        foreach ($rows as $r) {
            $current_files[(int) $r['id']] = $r['file_name'] . '.' . $r['file_ext'];
        }

        $this->set('parent_module', $module);
        $this->set('parent_object_name', $object_name);
        $this->set('id_parent', $id_object);

        $file_dir = $this->getFileDir();

        $files = array();

        if (file_exists($file_dir)) {
            $files = scandir($file_dir);
            foreach ($files as $f) {
                if (in_array($f, array('.', '..'))) {
                    continue;
                }

                if (is_dir($file_dir . '/' . $f)) {
                    continue;
                }

                if (preg_match('/^(.*)_deleted\..*$/', $f)) {
                    continue;
                }

                if (!in_array($f, $current_files)) {
                    $this->reset();

                    $path_info = pathinfo($file_dir . '/' . $f);

                    if (!count($this->validateArray(array(
                                        'parent_module'      => $module,
                                        'parent_object_name' => $object_name,
                                        'id_parent'          => $id_object,
                                        'file_name'          => $path_info['filename'],
                                        'file_ext'           => $path_info['extension'],
                                        'file_size'          => filesize($file_dir . '/' . $f)
                            )))) {
                        parent::create();
                    }
                }
            }
        }

        foreach ($current_files as $id_file => $file_name) {
            if (!in_array($file_name, $files)) {
                if ($this->fetch((int) $id_file)) {
                    $warnings = array();
                    $this->delete($warnings, true);
                    $this->reset();
                }
            }
        }
    }

    public function moveToObject(BimpObject $object, $keep_copy = false)
    {
        $errors = array();

        if (!$this->isLoaded()) {
            $errors[] = 'ID du fichier absent';
        } elseif (!is_a($object, 'BimpObject')) {
            $errors[] = 'Objet invalide';
        } elseif (!BimpObject::objectLoaded($object)) {
            $errors[] = 'ID ' . $object->getLabel('of_the') . ' absent';
        }

        $src_file = $this->getFilePath();

        if (!file_exists($src_file)) {
            $errors[] = 'Le fichier à copier "' . $this->getData('file_name') . '.' . $this->getData('file_ext') . '" n\'existe pas';
        } else {
            $dst_dir = $object->getFilesDir();
            if (!preg_match('/^.*\/$/', $dst_dir)) {
                $dst_dir .= '/';
            }

            if (!file_exists($dst_dir)) {
                if (dol_mkdir($dst_dir, DOL_DATA_ROOT) <= 0) {
                    $errors[] = 'Echec de la création du dossier de destination';
                }
            }

            $dst_file = $object->getFilesDir() . $this->getData('file_name') . '.' . $this->getData('file_ext');
            if (file_exists($dst_file)) {
                $errors[] = 'Le fichier de destination existe déjà';
            }
        }

        if (!count($errors) && file_exists($dst_dir)) {
            if ($keep_copy) {
//                echo $dst_dir; exit;
//                ini_set('display_errors', 1);
//                error_reporting(E_ALL);
                if (!copy($src_file, $dst_file)) {
                    $errors[] = 'Echec de la copie du fichier';
                } else {
                    $data = array(
                        'parent_module'      => $object->module,
                        'parent_object_name' => $object->object_name,
                        'id_parent'          => (int) $object->id,
                        'file_name'          => $this->getData('file_name'),
                        'file_ext'           => $this->getData('file_ext'),
                        'description'        => $this->getData('description'),
                        'deleted'            => (int) $this->getData('deleted'),
                        'user_delete'        => (int) $this->getData('user_delete'),
                        'date_delete'        => (int) $this->getData('date_delete')
                    );

                    $result = $this->db->insert($this->getTable(), $data, true);

                    if ($result <= 0) {
                        $msg = 'Echec de la création du nouveau fichier';
                        $sqlerror = $this->db->db->lasterror();
                        if ($sqlerror) {
                            $msg .= ' - ' . $sqlerror;
                        }
                        $errors[] = $msg;
                    }
                }
            } else {
                if (!rename($src_file, $dst_file)) {
                    $errors[] = 'Echec du déplacement du fichier';
                } else {
                    $this->set('parent_module', $object->module);
                    $this->set('parent_object_name', $object->object_name);
                    $this->set('id_parent', (int) $object->id);
                    $up_warnings = array();
                    $up_errors = $this->update($up_warnings, true);
                    if (count($up_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour du propriétaire du fichier');
                    }
                }
            }
        }

        return $errors;
    }

    // Overrides: 

    public function getList($filters = array(), $n = null, $p = null, $order_by = 'id', $order_way = 'DESC', $return = 'array', $return_fields = null, $joins = null)
    {
        if (!$this->isLoaded()) {
            if (isset($filters['parent_module']) &&
                    isset($filters['parent_object_name']) &&
                    isset($filters['id_parent'])) {
                $this->checkObjectFiles($filters['parent_module'], $filters['parent_object_name'], $filters['id_parent']);
            }
        }
        return parent::getList($filters, $n, $p, $order_by, $order_way, $return, $return_fields, $joins);
    }

    public function validate()
    {
        $name = $this->getData('file_name');
        $name = BimpTools::cleanStringForUrl($name);
        $this->set('file_name', $name);
        
        return parent::validate();
    }
    
    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = array();

        if (!isset($_FILES['file']) || empty($_FILES['file'])) {
            $errors[] = 'Aucun fichier transféré';
        } else {
            $name = (string) $this->getData('file_name');

            if (!$name) {
                $name = pathinfo($_FILES['file']['name'], PATHINFO_FILENAME);

                if (!$name) {
                    $errors[] = 'Nom du fichier invalide';
                }
                $this->set('file_name', $name);
            }

            $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);

            if (!(string) $ext) {
                $errors[] = 'Extension du fichier absente. Veuillez renommer le fichier à envoyer avec une extension valide';
            }
            if (!count($errors)) {
                $this->set('file_ext', $ext);
                $this->set('file_size', $_FILES['file']['size']);
                $errors = $this->uploadFile();

                if (!count($errors)) {
                    $errors = parent::create($warnings, $force_create);
                }

                if (count($errors) && !$this->dontRemove) {
                    $this->removeFile();
                }
            }
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        if (!$this->isLoaded()) {
            return array('ID Absent');
        }

        $current_name = (string) $this->getInitData('file_name');
        if (!is_null($current_name)) {
            $new_name = (string) $this->getData('file_name');
            if ($new_name) {
                if ($new_name !== $current_name) {
                    $dir = $this->getFileDir();
                    $ext = $this->getData('file_ext');
                    if (file_exists($dir . $current_name . '.' . $ext)) {
                        if ($error = BimpTools::renameFile($dir, $current_name . '.' . $ext, $new_name . '.' . $ext)) {
                            return array($error);
                        }
                    }
                }
            }
        }

        return parent::update($warnings, $force_update);
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        if (!$this->isLoaded()) {
            return array('ID ' . $this->getLabel('of_the') . ' absent');
        }

        if (!$force_delete && !$this->can("delete")) {
            return array('Vous n\'avez pas la permission de supprimer ' . $this->getLabel('this'));
        }

        if ((int) $this->getData('deleted')) {
            return array($this->getLabel('this') . ' a déjà été supprimé');
        }

        $errors = array();

        global $user;

        $this->set('deleted', 1);
        $this->set('user_delete', (int) $user->id);
        $this->set('date_delete', date('Y-m-d H:i:s'));
        $this->set('file_name', $this->getData('file_name') . $this->id . '_deleted');

        $errors = $this->update($warnings, true);

        if (!count($errors)) {
            $dir = $this->getFileDir();
            $file = $this->getData('file_name') . '.' . $this->getData('file_ext');

            if (file_exists($dir . $file)) {
                BimpTools::renameFile($dir, $file, $this->getData('file_name') . $this->id . '_deleted.' . $this->getData('file_ext'));
            }
        }

        return $errors;
    }
}

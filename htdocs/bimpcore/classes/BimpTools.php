<?php

class BimpTools
{

    public static $currencies = array(
        'EUR' => array(
            'label' => 'euro',
            'icon'  => 'euro',
            'html'  => '&euro;'
        ),
        'USD' => array(
            'label' => 'dollar',
            'icon'  => 'dollar',
            'html'  => '&#36;'
        )
    );
    private static $context = "";

    public static function getCommercialArray($socid)
    {
        // Cette fonction n'a rien à faire ici, daplacée dans BimpCache, deplus quelques erreurs dans le code: une même instance est affecté à chaque entrée du tableau.
        // Donc: toutes les entrées du tableau contiendront le dernier user qui aura été fetché. 

        return BimpCache::getSocieteCommerciauxObjectsList($socid);

//        global $db;
//        $conf;
//        require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
//        $bimp = new BimpDb($db);
//        $return = Array();
//        $comm_list = $bimp->getRows('societe_commerciaux', 'fk_soc = ' . $socid);
//        $need_default = true;
//        $instance = new User($db);
//        if (count($comm_list) > 0) {
//            foreach ($comm_list as $comm) {
//                $instance->fetch($comm->fk_user);
//                if ($instance->statut == 1) {
//                    $return[$comm->fk_user] = $instance;
//                    $need_default = false;
//                }
//            }
//        }
//        if ($need_default) {
//            $default_id_commercial = BimpCore::getConf('default_id_commercial');
//            $instance->fetch($default_id_commercial);
//            $return[$default_id_commercial] = $instance;
//        }
//        $instance = null;
//        return $return;
    }

    // Gestion GET / POST

    public static function isSubmit($key)
    {
        $keys = explode('/', $key);

        $array = null;
        foreach ($keys as $current_key) {
            if (is_null($array)) {
                if (isset($_POST[$current_key])) {
                    $array = $_POST[$current_key];
                } elseif (isset($_GET[$current_key])) {
                    $array = $_GET[$current_key];
                } else {
                    return 0;
                }
            } else {
                if (isset($array[$current_key])) {
                    $array = $array[$current_key];
                } else {
                    return 0;
                }
            }
        }
        return 1;
    }

    public static function getValue($key, $default_value = null, $decode = true)
    {
        $keys = explode('/', $key);

        $value = null;
        foreach ($keys as $current_key) {
            if (is_null($value)) {
                if (isset($_POST[$current_key])) {
                    $value = $_POST[$current_key];
                } elseif (isset($_GET[$current_key])) {
                    $value = $_GET[$current_key];
                } else {
                    break;
                }
            } else {
                if (isset($value[$current_key])) {
                    $value = $value[$current_key];
                } else {
                    $value = null;
                    break;
                }
            }
        }

        if (is_null($value)) {
            return $default_value;
        }

        if (is_string($value) && $decode) {
            return stripslashes(urldecode(preg_replace('/((\%5C0+)|(\%00+))/i', '', urlencode($value))));
        }

        return $value;
    }

    public static function getPostFieldValue($field_name, $default_value = null)
    {
        // Chargement d'un formulaire:
        if (BimpTools::isSubmit('param_values/fields/' . $field_name)) {
            return BimpTools::getValue('param_values/fields/' . $field_name);
        }

        // Chargement d'un input: 
        if (BimpTools::isSubmit('fields/' . $field_name)) {
            return BimpTools::getValue('fields/' . $field_name);
        }

        // Action ajax: 
        if (BimpTools::isSubmit('extra_data/' . $field_name)) {
            return BimpTools::getValue('extra_data/' . $field_name);
        }

        // Envoi des données d'un formulaire: 
        if (BimpTools::isSubmit($field_name)) {
            return BimpTools::getValue($field_name);
        }

        // Filtres listes:
        if (BimpTools::isSubmit('param_list_filters')) {
            $filters = json_decode(BimpTools::getValue('param_list_filters'));
            foreach ($filters as $filter) {
                if (isset($filter->name) && $filter->name === $field_name) {
                    return $filter->filter;
                }
            }
        }

        return $default_value;
    }

    // Gestion des objects Dolibarr:

    public static function loadDolClass($module, $file = null, $class = null)
    {
        if (is_null($file)) {
            $file = $module;
        }

        if (is_null($class)) {
            $class = ucfirst($file);
        }

        if (!class_exists($class)) {
            if (file_exists(DOL_DOCUMENT_ROOT . '/' . $module . '/class/' . $file . '.class.php')) {
                require_once DOL_DOCUMENT_ROOT . '/' . $module . '/class/' . $file . '.class.php';
            }
        }
    }

    public static function getDolObjectList($instance, $filters = array())
    {
        //todo
        return array();
    }

    public static function getDolObjectUrl($object, $id_object = null)
    {
        if (is_null($id_object)) {
            if (isset($object->id) && $object->id) {
                $id_object = $object->id;
            }
        }
        $file = strtolower(get_class($object)) . '/card.php';
        $primary = 'id';
        switch (get_class($object)) {
            case 'CommandeFournisseur':
                return DOL_URL_ROOT . '/fourn/commande/card.php?id=' . $id_object;

            case 'Facture':
                return DOL_URL_ROOT . '/compta/facture/card.php?id=' . $id_object;

            case 'Propal':
                return DOL_URL_ROOT . '/comm/propal/card.php?id=' . $id_object;

            case 'Entrepot':
                return DOL_URL_ROOT . '/product/stock/card.php?id=' . $id_object;

            case 'Societe':
                $primary = 'socid';
                break;
        }
        if (file_exists(DOL_DOCUMENT_ROOT . '/' . $file)) {
            return DOL_URL_ROOT . '/' . $file . (!is_null($id_object) && $id_object ? '?' . $primary . '=' . $id_object : '');
        }
        return '';
    }

    public static function getDolObjectListUrl($object)
    {
        $file = strtolower(get_class($object)) . '/card.php';

        switch (get_class($object)) {
            // gérer les exceptions... 
        }

        if (file_exists(DOL_DOCUMENT_ROOT . '/' . $file)) {
            return DOL_URL_ROOT . '/' . $file;
        }
        return '';
    }

    public static function getDolListArray($id_list, $include_empty)
    {
        return BimpCache::getDolListArray($id_list, $include_empty);
    }

    public static function getProductImagesDir($product)
    {
        global $conf;

        if (!is_a($product, 'Product')) {
            return null;
        }

        if (!isset($product->id) || !$product->id) {
            return null;
        }

        $dir = $conf->product->multidir_output[$conf->entity] . '/';

        if (!empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO)) {
            if (DOL_VERSION < '3.8.0') {
                $dir .= get_exdir($product->id, 2) . $product->id . "/photos/";
            } else {
                $dir .= get_exdir($product->id, 2, 0, 0, $product, 'product') . $product->id . "/photos/";
            }
        } else {
            $dir .= get_exdir(0, 0, 0, 0, $product, 'product') . dol_sanitizeFileName($product->ref) . '/';
        }

        return $dir;
    }

    public static function getProductMainImgUrl($product)
    {
        $dir = self::getProductImagesDir($product);

        if (!is_null($dir)) {
            $files = scandir($dir);

            foreach ($files as $f) {
                if (!in_array($f, array('.', '..'))) {
                    return $dir . '/' . $f;
                }
            }
        }

        return '/test2/public/theme/common/nophoto.png';
    }

    public static function getErrorsFromDolObject($object, $errors = null, $langs = null, &$warnings = array())
    {
        if (is_null($langs)) {
            global $langs;
        }

        if (is_null($errors)) {
            $errors = array();
        }

        if (isset($object->error)) {
            if (!is_null($langs)) {
                $errors[] = $langs->trans($object->error);
            } else {
                $errors[] = $object->error;
            }
        }

        if (isset($object->errors) && count($object->errors)) {
            foreach ($object->errors as $e) {
                if (!is_null($langs)) {
                    $errors[] = $langs->trans($e);
                } else {
                    $errors[] = $e;
                }
            }
        }

        $errors = array_merge($errors, self::getDolEventsMsgs(array('errors')));
        $warnings = array_merge($warnings, self::getDolEventsMsgs(array('warnings')));

        return $errors;
    }

    public static function getDateForDolDate($date)
    {
        if (is_null($date) || !$date) {
            return '';
        }
        $DT = new DateTime($date);
        return (int) $DT->format('U');
    }

    public static function getDateFromDolDate($date, $return_format = 'Y-m-d')
    {
        if (is_null($date) || !$date) {
            return '';
        }
        return date($return_format, $date);
    }

    public static function getExtraFieldValues($object_type, $field)
    {
        global $db;
        $bdb = new BimpDb($db);
        $where = '`elementtype` = \'' . $object_type . '\' AND `name` = \'' . $field . '\'';
        $value = $bdb->getValue('extrafields', 'param', $where);
        if (is_null($value)) {
            return array();
        }

        return unserialize($value);
    }

    public static function getDolObjectLinkedObjectsList($dol_object, BimpDb $bdb = null)
    {
        $list = array();

        if (BimpObject::objectLoaded($dol_object)) {
            if (is_null($bdb)) {
                global $db;
                $bdb = new BimpDb($db);
            }

            $where = '(`fk_source` = ' . (int) $dol_object->id . ' AND `sourcetype` = \'' . $dol_object->element . '\')';
            $where .= ' OR (`fk_target` = ' . (int) $dol_object->id . ' AND `targettype` = \'' . $dol_object->element . '\')';
            $rows = $bdb->getRows('element_element', $where, null, 'array');
            if (!is_null($rows) && count($rows)) {
                foreach ($rows as $r) {
                    if ((int) $r['fk_source'] === (int) $dol_object->id &&
                            $r['sourcetype'] === $dol_object->element) {
                        $list[] = array(
                            'id_object' => (int) $r['fk_target'],
                            'type'      => $r['targettype']
                        );
                    } elseif ((int) $r['fk_target'] === (int) $dol_object->id &&
                            $r['targettype'] === $dol_object->element) {
                        $list[] = array(
                            'id_object' => (int) $r['fk_source'],
                            'type'      => $r['sourcetype']
                        );
                    }
                }
            }
        }

        return $list;
    }

    public static function resetDolObjectErrors($object)
    {
        if (is_object($object)) {
            if (isset($object->error)) {
                $object->error = '';
            }
            if (isset($object->errors)) {
                $object->errors = array();
            }
        }
    }

    public static function getObjectFilePath($object, $full_path = false)
    {
        if (is_object($object)) {
            $refl = new ReflectionClass($object);
            $file = $refl->getFileName();

            if (!$full_path) {
                $file = preg_replace('/^.*htdocs\/(.*)$/', '$1', $file);
            }

            unset($refl);
            return $file;
        }

        return '';
    }

    // Gestion générique des objets: 

    public static function getObjectTable(BimpObject $parent, $id_object_field, $object = null)
    {
        if (is_null($parent) || !$parent) {
            return null;
        }

        if (is_null($object) || !$object || !is_object($object)) {
            $object = $parent->config->getObject('fields/' . $id_object_field . '/object', null, true, 'object');
            if (is_null($object) || !is_object($object)) {
                return null;
            }
        }

        if (is_a($object, 'BimpObject')) {
            return $object->getTable();
        }

        if (property_exists($object, 'table_element')) {
            return $object->table_element;
        }

        $instance = $parent->getConf('fields/' . $id_object_field . '/object', null, true, 'any');
        if (is_string($instance)) {
            return $parent->getConf('objects/' . $instance . '/table', null, false);
        }
        return $parent->getConf('fields/' . $id_object_field . '/object/table', null, false);
    }

    public static function getObjectPrimary(BimpObject $parent, $id_object_field, $object = null)
    {
        if (is_null($parent) || !$parent) {
            return null;
        }

        if (is_null($object) || !$object || !is_object($object)) {
            $object = $parent->config->getObject('fields/' . $id_object_field . '/object', null, true, 'object');
            if (is_null($object) || !is_object($object)) {
                return null;
            }
        }

        if (is_a($object, 'BimpObject')) {
            return $object->getPrimary();
        }

        if (property_exists($object, 'id')) {
            return 'rowid';
        }

        $instance = $parent->getConf('fields/' . $id_object_field . '/object', null, true, 'any');
        if (is_string($instance)) {
            return $parent->getConf('objects/' . $instance . '/primary', null, false);
        }
        return $parent->getConf('fields/' . $id_object_field . '/object/primary', null, false);
    }

    public static function changeBimpObjectId($old_id, $new_id, $module, $object_name)
    {
        set_time_limit(120);
        ignore_user_abort(true);

        global $db;
        $bdb = new BimpDb($db);

        $list = BimpCache::getBimpObjectsList();

        $fails = array();

        foreach ($list as $mod => $objects) {
            foreach ($objects as $name) {
                $instance = BimpObject::getInstance($mod, $name);
                if (is_a($instance, 'BimpObject')) {
                    $table = $instance->getTable();

                    if (!$table) {
                        continue;
                    }

                    foreach ($instance->params['objects'] as $obj_conf_name => $obj_params) {
                        if (!$obj_params['relation'] === 'hasOne') {
                            continue;
                        }

                        $obj_module = '';
                        $obj_name = '';

                        if (isset($obj_params['instance']['bimp_object'])) {
                            if (is_string($obj_params['instance']['bimp_object'])) {
                                $obj_name = $obj_params['instance']['bimp_object'];
                                $obj_module = $instance->module;
                            } else {
                                if (isset($obj_params['instance']['bimp_object']['name'])) {
                                    $obj_name = $obj_params['instance']['bimp_object']['name'];
                                    $obj_module = $obj_params['instance']['bimp_object']['module'];
                                    if (!$obj_module) {
                                        $obj_module = $instance->module;
                                    }
                                }
                            }
                        }

                        if (!$obj_name || !$obj_module) {
                            continue;
                        }

                        if ($obj_module === $module && $obj_name === $object_name) {
                            $params = $instance->config->getParams('objects/' . $obj_conf_name . '/instance');

                            if (isset($params['id_object']['field_value'])) {
                                $field = $params['id_object']['field_value'];
                                if ($instance->field_exists($field)) {

                                    if ($instance->isDolObject()) {
                                        if ($instance->isDolExtraField($field)) {
                                            $table .= '_extrafields';
                                        }
                                    }

//                                    echo $instance->object_name . ': ' . $field . '<br/>';
                                    $result = $bdb->update($table, array(
                                        $field => $new_id
                                            ), '`' . $field . '` = ' . (int) $old_id);

                                    if ($result <= 0) {
                                        $fails[] = 'Table: "' . $table . '", Champ: "' . $field . '"';
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!empty($fails)) {
            $subject = 'ERREUR SUITE A CHANGEMENT D\'ID';

            $msg = 'Plateforme: ' . DOL_URL_ROOT . "\n";
            $msg .= 'Object: ' . $object_name . "\n";
            $msg .= 'Ancien ID: ' . $old_id . "\n";
            $msg .= 'Nouvel ID: ' . $new_id . "\n\n";

            $msg .= 'Echec des mises à jour SQL: ' . "\n\n";

            foreach ($fails as $fail) {
                $msg .= ' - ' . $fail . "\n";
            }

            mailSyn2($subject, 'debugerp@bimp.fr', 'BIMP<admin@bimp.fr>', $msg);
            dol_syslog($subject . "\n" . $msg, LOG_ERR);
        }
    }

    public static function changeDolObjectId($old_id, $new_id, $module, $file = '', $class = '')
    {
        set_time_limit(120);
        ignore_user_abort(true);

        if (!(string) $file) {
            $file = $module;
        }

        if (!(string) $class) {
            $class = ucfirst($file);
        }

        global $db;
        $bdb = new BimpDb($db);

        $list = BimpCache::getBimpObjectsList();

        $fails = array();

        foreach ($list as $mod => $objects) {
            foreach ($objects as $name) {
                $instance = BimpObject::getInstance($mod, $name);
                if (is_a($instance, 'BimpObject')) {
                    $table = $instance->getTable();

                    if (!$table) {
                        continue;
                    }

                    foreach ($instance->params['objects'] as $obj_conf_name => $obj_params) {
                        if (!$obj_params['relation'] === 'hasOne') {
                            return;
                        }

                        $obj_module = '';
                        $obj_file = '';
                        $obj_class = '';

                        if (isset($obj_params['instance']['dol_object'])) {
                            if (is_string($obj_params['instance']['dol_object'])) {
                                $obj_module = $obj_file = $obj_params['instance']['dol_object'];
                                $obj_class = ucfirst($obj_file);
                            } else {
                                if (isset($obj_params['instance']['bimp_object']['module'])) {
                                    $obj_module = $obj_params['instance']['bimp_object']['module'];
                                    $obj_file = isset($obj_params['instance']['bimp_object']['file']) ? $obj_params['instance']['bimp_object']['file'] : $module;
                                    $obj_class = isset($obj_params['instance']['bimp_object']['class']) ? $obj_params['instance']['bimp_object']['class'] : ucfirst($file);
                                }
                            }
                        }

                        if (!$obj_module || !$obj_file || !$obj_class) {
                            continue;
                        }

                        if ($obj_class === $class) {
                            $params = $instance->config->getParams('objects/' . $obj_conf_name . '/instance');

                            if (isset($params['id_object']['field_value'])) {
                                $field = $params['id_object']['field_value'];
                                if ($instance->field_exists($field)) {
//                                    echo $instance->object_name . ': ' . $field . '<br/>';
                                    $result = $bdb->update($table, array(
                                        $field => $new_id
                                            ), '`' . $field . '` = ' . (int) $old_id);
                                    if ($result <= 0) {
                                        $fails[] = 'Table: "' . $table . '", Champ: "' . $field . '"';
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!empty($fails)) {
            $subject = 'ERREUR SUITE A CHANGEMENT D\'ID';

            $msg = 'Plateforme: ' . DOL_URL_ROOT . "\n";
            $msg .= 'Object: ' . $class . "\n";
            $msg .= 'Ancien ID: ' . $old_id . "\n";
            $msg .= 'Nouvel ID: ' . $new_id . "\n\n";

            $msg .= 'Echec des mises à jour SQL: ' . "\n\n";

            foreach ($fails as $fail) {
                $msg .= ' - ' . $fail . "\n";
            }

            mailSyn2($subject, 'debugerp@bimp.fr', 'BIMP<admin@bimp.fr>', $msg);

            dol_syslog($subject . "\n" . $msg, LOG_ERR);
        }
    }

    // Gestion fichiers:

    public static function makeDirectories($dir_tree, $root_dir = null)
    {
        if (is_null($root_dir)) {
            $root_dir = DOL_DATA_ROOT . '/bimpdatasync';
        }

        if (!file_exists($root_dir)) {
            if (!mkdir($root_dir, 0777)) {
                return 'Echec de la création du dossier "' . $root_dir . '"';
            }
        }

        foreach ($dir_tree as $dir => $sub_dir_tree) {
            if (!file_exists($root_dir . '/' . $dir)) {
                if (!mkdir($root_dir . '/' . $dir, 0777)) {
                    return 'Echec de la création du dossier "' . $root_dir . '/' . $dir . '"';
                }
            }
            if (!is_null($sub_dir_tree)) {
                if (is_array($sub_dir_tree) && count($sub_dir_tree)) {
                    $result = self::makeDirectories($sub_dir_tree, $root_dir . '/' . $dir);
                    if ($result) {
                        return $result;
                    }
                } elseif (is_string($sub_dir_tree)) {
                    if (!file_exists($root_dir . '/' . $dir . '/' . $sub_dir_tree)) {
                        if (!mkdir($root_dir . '/' . $dir . '/' . $sub_dir_tree, 0777)) {
                            return 'Echec de la création du dossier "' . $root_dir . '/' . $dir . '/' . $sub_dir_tree . '"';
                        }
                    }
                }
            }
        }

        return 0;
    }

    public static function renameFile($dir, $old_name, $new_name)
    {
        if (!preg_match('/.+\/$/', $dir)) {
            $dir .= '/';
        }

        if (!file_exists($dir . $old_name)) {
            return 'Le fichier "' . $old_name . '" n\'existe pas';
        }

        if (file_exists($dir . $new_name)) {
            return 'Le fichier "' . $new_name . '" existe déjà';
        }

        if (!rename($dir . $old_name, $dir . $new_name)) {
            return 'Echec du renommage du fichier';
        }
        if (file_exists($dir . 'thumbs/')) {
            $old_path = pathinfo($old_name, PATHINFO_BASENAME | PATHINFO_EXTENSION);
            $new_path = pathinfo($new_name, PATHINFO_BASENAME | PATHINFO_EXTENSION);
            $dir .= 'thumbs/';
            $suffixes = array('_mini', '_small');
            foreach ($suffixes as $suffix) {
                $old_thumb = $dir . $old_path['basename'] . $suffix . '.' . $old_path['extension'];
                if (file_exists($old_thumb)) {
                    $new_thumb = $dir . $new_path['basename'] . $suffix . '.' . $new_path['extension'];
                    rename($old_thumb, $new_thumb);
                }
            }
        }
        return '';
    }

    public static function getFileIcon($fileName)
    {
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ((string) $ext) {
            if (in_array($ext, array('pdf'))) {
                return 'fas_file-pdf';
            } elseif (in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'tif'))) {
                return 'fas_file-image';
            } elseif (in_array($ext, array('txt'))) {
                return 'fas_file-alt';
            } elseif (in_array($ext, array('php', 'js', 'html', 'css', 'yml', 'tpl', 'scss'))) {
                return 'fas_file-code';
            } elseif (in_array($ext, array('doc', 'docx', 'docm', 'dotx'))) {
                return 'fas_file-word';
            } elseif (in_array($ext, array('csv', 'xls', 'xlsx', 'xlsb', 'xltx', 'xltm', 'xlt', 'xml', 'xlam', 'xla', 'xlw', 'xlr'))) {
                return 'fas_file-excel';
            } elseif (in_array($ext, array('ppt', 'pot', 'pps'))) {
                return 'fas_file-powerpoint';
            } elseif (in_array($ext, array('zip', 'rar', 'tar', 'xar', 'bz2', 'gz', 'ls', 'rz', 'sz', '7z', 's7z', 'zz'))) {
                return 'fas_file-archive';
            }
        }

        return 'fas_file';
    }

    public static function getFileType($fileName)
    {
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ((string) $ext) {
            if (in_array($ext, array('pdf'))) {
                return 'PDF';
            } elseif (in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'tif'))) {
                return 'Image';
            } elseif (in_array($ext, array('txt'))) {
                return 'Texte';
            } elseif (in_array($ext, array('php', 'js', 'html', 'css', 'yml', 'tpl', 'scss'))) {
                return 'Code informatique';
            } elseif (in_array($ext, array('doc', 'docx', 'docm', 'dotx'))) {
                return 'Fichier Word';
            } elseif (in_array($ext, array('csv', 'xls', 'xlsx', 'xlsb', 'xltx', 'xltm', 'xlt', 'xml', 'xlam', 'xla', 'xlw', 'xlr'))) {
                return 'Fichier Excel';
            } elseif (in_array($ext, array('ppt', 'pot', 'pps'))) {
                return 'Fichier powerpoint';
            } elseif (in_array($ext, array('zip', 'rar', 'tar', 'xar', 'bz2', 'gz', 'ls', 'rz', 'sz', '7z', 's7z', 'zz'))) {
                return 'Dossier compressé';
            }
        }

        return 'Divers';
    }

    public static function getFileTypeCode($fileName)
    {
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ((string) $ext) {
            if (in_array($ext, array('pdf'))) {
                return 'pdf';
            } elseif (in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'tif'))) {
                return 'img';
            } elseif (in_array($ext, array('txt'))) {
                return 'txt';
            } elseif (in_array($ext, array('php', 'js', 'html', 'css', 'yml', 'tpl', 'scss'))) {
                return 'code';
            } elseif (in_array($ext, array('doc', 'docx', 'docm', 'dotx'))) {
                return 'word';
            } elseif (in_array($ext, array('csv', 'xls', 'xlsx', 'xlsb', 'xltx', 'xltm', 'xlt', 'xml', 'xlam', 'xla', 'xlw', 'xlr'))) {
                return 'xls';
            } elseif (in_array($ext, array('ppt', 'pot', 'pps'))) {
                return 'ppt';
            } elseif (in_array($ext, array('zip', 'rar', 'tar', 'xar', 'bz2', 'gz', 'ls', 'rz', 'sz', '7z', 's7z', 'zz'))) {
                return 'zip';
            }
        }

        return 'oth';
    }

    public static function displayFileType($fileName, $text_only = 0, $icon_only = 0, $no_html = 0)
    {
        if ($no_html) {
            return self::getFileType($fileName);
        }

        $html = '';

        if (!$text_only) {
            $class = '';
            if (!$icon_only) {
                $class = 'iconLeft';
            }
            $html .= BimpRender::renderIcon(BimpTools::getFileIcon($fileName), $class);
        }

        if (!$icon_only) {
            $html .= self::getfileType($fileName);
        }

        return $html;
    }

    // Gestion Logs: 

    public static function logTechnicalError($object, $method, $msg)
    {
        if (is_object($object)) {
            $object = get_class($object);
        }

        $message = '[ERREUR TECHNIQUE] ' . $object . '::' . $method . '() - ' . $msg;
        dol_syslog($message, LOG_ERR);
    }

    // Gestion SQL:

    public static function getSqlSelect($return_fields = null, $default_alias = 'a')
    {
        $sql = 'SELECT ';

        if (!is_null($return_fields) && is_array($return_fields) && count($return_fields)) {
            $first_loop = true;
            foreach ($return_fields as $field) {
                if (!$first_loop) {
                    $sql .= ', ';
                } else {
                    $first_loop = false;
                }
                if (preg_match('/\./', $field)) {
                    $sql .= $field;
                } elseif (!is_null($default_alias) && $default_alias) {
                    $sql .= $default_alias . '.' . $field;
                }
            }
        } else {
            $sql .= '*';
        }

        return $sql;
    }

    public static function getSqlFrom($table, $joins = null, $default_alias = 'a')
    {
        $sql = '';
        if (!is_null($table) && $table) {
            if (is_null($default_alias)) {
                $default_alias = '';
            }

            if (!$default_alias && (!is_null($joins) && is_array($joins) && count($joins))) {
                $default_alias = 'a';
            }
            $sql = ' FROM ' . MAIN_DB_PREFIX . $table . ($default_alias ? ' ' . $default_alias : '');

            if (!is_null($joins) && is_array($joins) && count($joins)) {
                foreach ($joins as $join) {
                    if (isset($join['table']) && isset($join['alias']) && isset($join['on'])) {
                        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . $join['table'] . ' ' . $join['alias'] . ' ON ' . $join['on'];
                    }
                }
            }
        }
        return $sql;
    }

    public static function getSqlWhere($filters, $default_alias = 'a')
    {
        $sql = '';
        if (!is_null($filters) && is_array($filters) && count($filters)) {
            $sql .= ' WHERE ';
            $first_loop = true;
            foreach ($filters as $field => $filter) {
                if (!$first_loop) {
                    $sql .= ' AND ';
                } else {
                    $first_loop = false;
                }

                $sql .= self::getSqlFilter($field, $filter, $default_alias);
            }
        }
        return $sql;
    }

    public static function getSqlFilter($field, $filter, $default_alias = 'a')
    {
        $sql = '';

        if (is_array($filter) && isset($filter['or'])) {
            $sql .= ' (';
            $fl = true;
            foreach ($filter['or'] as $or_field => $or_filter) {
                if (!$fl) {
                    $sql .= ' OR ';
                } else {
                    $fl = false;
                }
                $sql .= self::getSqlFilter($or_field, $or_filter, $default_alias);
            }
            $sql .= ')';
        } elseif (is_array($filter) && isset($filter['and'])) {
            $sql .= ' (';
            $fl = true;
            foreach ($filter['and'] as $and_filter) {
                if (!$fl) {
                    $sql .= ' AND ';
                } else {
                    $fl = false;
                }
                $sql .= self::getSqlFilter($field, $and_filter, $default_alias);
            }
            $sql .= ')';
        } elseif (is_array($filter) && isset($filter['and_fields'])) {
            $sql .= ' (';
            $fl = true;
            foreach ($filter['and_fields'] as $and_field => $and_filter) {
                if (!$fl) {
                    $sql .= ' AND ';
                } else {
                    $fl = false;
                }
                $sql .= self::getSqlFilter($and_field, $and_filter, $default_alias);
            }
            $sql .= ')';
        } elseif (is_array($filter) && isset($filter['or_field'])) {
            $sql .= ' (';
            $fl = true;
            foreach ($filter['or_field'] as $or_filter) {
                if (!$fl) {
                    $sql .= ' OR ';
                } else {
                    $fl = false;
                }
                $sql .= self::getSqlFilter($field, $or_filter, $default_alias);
            }
            $sql .= ')';
        } else {
            if (preg_match('/\./', $field)) {
                $sql .= $field;
            } elseif (!is_null($default_alias) && $default_alias) {
                $sql .= $default_alias . '.' . $field;
            } else {
                $sql .= '`' . $field . '`';
            }

            if (isset($filter['IN']))
                $filter['in'] = $filter['IN'];

            if (is_array($filter)) {
                if (isset($filter['min']) && isset($filter['max'])) {
                    $sql .= ' BETWEEN ' . (is_string($filter['min']) ? '\'' . $filter['min'] . '\'' : $filter['min']);
                    $sql .= ' AND ' . (is_string($filter['max']) ? '\'' . $filter['max'] . '\'' : $filter['max']);
                } elseif (isset($filter['operator']) && isset($filter['value'])) {
                    $sql .= ' ' . $filter['operator'] . ' ' . (is_string($filter['value']) ? '\'' . $filter['value'] . '\'' : $filter['value']);
                } elseif (isset($filter['part_type']) && isset($filter['part'])) {
                    $filter['part'] = addslashes($filter['part']);
                    $sql .= ' LIKE \'';
                    switch ($filter['part_type']) {
                        case 'beginning':
                            $sql .= $filter['part'] . '%';
                            break;

                        case 'end':
                            $sql .= '%' . $filter['part'];
                            break;

                        default:
                        case 'middle':
                            $sql .= '%' . $filter['part'] . '%';
                            break;
                    }
                    $sql .= '\'';
                } elseif (isset($filter['in'])) {
                    if (is_array($filter['in'])) {
                        $sql .= ' IN (' . implode(',', $filter['in']) . ')';
                    } elseif ($filter['in'] == "") {
                        $sql .= ' = 0 AND 0';
                    } else {
                        $sql .= ' IN (' . $filter['in'] . ')';
                    }
                } elseif (isset($filter['not_in'])) {
                    if (is_array($filter['not_in'])) {
                        $sql .= ' NOT IN (' . implode(',', $filter['not_in']) . ')';
                    } else {
                        $sql .= ' NOT IN (' . $filter['not_in'] . ')';
                    }
                } else {
                    $sql .= ' IN (' . implode(',', $filter) . ')';
                }
            } elseif ($filter === 'IS_NULL') {
                $sql .= ' IS NULL';
            } elseif ($filter === 'IS_NOT_NULL') {
                $sql .= ' IS NOT NULL';
            } else {
                $sql .= ' = ' . (BimpTools::isString($filter) ? '\'' . $filter . '\'' : $filter);
            }
        }
        return $sql;
    }

    public static function getSqlOrderBy($order_by = null, $order_way = 'ASC', $default_alias = 'a', $extra_order_by = null, $extra_order_way = 'ASC')
    {
        $sql = '';
        if (!is_null($order_by) && $order_by) {
            $sql .= ' ORDER BY ';
            if (preg_match('/\./', $order_by)) {
                $sql .= $order_by;
            } elseif (!is_null($default_alias) && $default_alias) {
                $sql .= $default_alias . '.' . $order_by;
            } else {
                $sql .= '`' . $order_by . '`';
            }

            if (is_null($order_way) || !$order_way) {
                $order_way = 'ASC';
            }

            $sql .= ' ' . strtoupper($order_way);

            if (!is_null($extra_order_by) && $extra_order_by) {
                $sql .= ', ';
                if (preg_match('/\./', $extra_order_by)) {
                    $sql .= $extra_order_by;
                } elseif (!is_null($default_alias) && $default_alias) {
                    $sql .= $default_alias . '.' . $extra_order_by;
                } else {
                    $sql .= '`' . $extra_order_by . '`';
                }

                if (is_null($extra_order_way) || !$extra_order_way) {
                    $extra_order_way = 'ASC';
                }

                $sql .= ' ' . strtoupper($extra_order_way);
            }
        }

        return $sql;
    }

    public static function getSqlLimit($n = 0, $p = 1)
    {
        $sql = '';
        if (!is_null($n) && $n > 0) {
            if (is_null($p) || !$p) {
                $p = 1;
            }

            if ($p > 1) {
                $offset = ($n * ($p - 1));
            } else {
                $offset = 0;
            }
            $sql .= ' LIMIT ' . $offset . ', ' . $n;
        }
        return $sql;
    }

    public static function mergeSqlFilter($filters, $field, $new_filter)
    {
        if (isset($filters[$field])) {
            if (isset($filters[$field]['and'])) {
                $filters[$field]['and'][] = $new_filter;
            } else {
                $current_filter = $filters[$field];
                $filters[$field] = array('and' => array());
                $filters[$field]['and'][] = $current_filter;
                $filters[$field]['and'][] = $new_filter;
            }
        } else {
            $filters[$field] = $new_filter;
        }

        return $filters;
    }

    // Gestion de données:

    public static function checkValueByType($type, &$value)
    {
        if (is_null($value)) {
            return true;
        }

        switch ($type) {
            case 'any':
                return true;

            case 'string':
                $value = (string) $value;
                return is_string($value) || is_numeric($value);

            case 'array':
                if (is_string($value)) {
                    $rows = explode(',', $value);
                    $value = array();
                    foreach ($rows as $r) {
                        if (preg_match('/^(.+)=>(.+)$/', $r, $matches)) {
                            $value[$matches[1]] = $matches[2];
                        } else {
                            $value[] = $r;
                        }
                    }
                    return true;
                }
                return is_array($value);

            case 'id':
            case 'id_object':
            case 'int':
                if (is_string($value)) {
                    if ($value === '') {
                        $value = 0;
                    }
                    if (preg_match('/^\-?[0-9]+$/', $value)) {
                        $value = (int) $value;
                    }
                }
                return is_int($value);

            case 'bool':
                if (is_string($value)) {
                    if (in_array(strtolower($value), array('true', 'oui', 'yes', 'vrai', '1'))) {
                        $value = 1;
                    }
                    if (in_array(strtolower($value), array('false', 'non', 'no', 'faux', '0', ''))) {
                        $value = 0;
                    }
                }

                if (is_numeric($value)) {
                    if ((int) $value !== 0) {
                        $value = 1;
                    } else {
                        $value = 0;
                    }

                    $value = (int) $value;
                }
                return is_int($value);

            case 'float':
            case 'money':
            case 'percent':
            case 'qty':
                if (is_string($value)) {
                    if ($value === '') {
                        $value = 0;
                    }
                    $value = str_replace(',', '.', $value);
                    if (preg_match('/^\-?[0-9]+\.?[0-9]*$/', $value)) {
                        $value = (float) $value;
                    }
                }

                if (is_numeric($value)) {
                    $value = (float) $value;
                }

                return is_float($value);

            case 'object':
                return is_object($value);

            case 'date':
                if ($value === '') {
                    $value = null;
                    return true;
                }
                if (preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $value)) {
                    return true;
                }
                return false;

            case 'time':
                if ($value === '') {
                    $value = null;
                    return true;
                }
                if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value)) {
                    return true;
                }
                return false;

            case 'datetime':
                if ($value === '') {
                    $value = null;
                    return true;
                }
                if (preg_match('/^\d{4}\-\d{2}\-\d{2} \d{2}:\d{2}(:\d{2})?$/', $value)) {
                    return true;
                }
                return false;

            case 'json':
                if (is_string($value)) {
                    if ($value) {
                        $value = json_decode($value, true);
                    }

                    if (!$value) {
                        $value = array();
                    }
                }
                return is_array($value);
        }
        return true;
    }

    public static function isNumericType($value)
    {
        return (is_int($value) || is_float($value) || is_bool($value) || (is_string($value) && preg_match('/^[0-9]+[.,]*[0-9]*$/', $value)));
    }

    public static function isString($value)
    {
        if (is_string($value) && !preg_match('/^[0-9]+$/', $value)) {
            return 1;
        }

        return 0;
    }

    public static function getDataTypeLabel($type)
    {
        switch ($type) {
            case 'any':
                return 'indéfini';

            case 'string':
                return 'chaîne de caractères';

            case 'array':
                return 'tableau';

            case 'id':
            case 'id_object':
            case 'int':
                return 'nombre entier';

            case 'bool':
                return 'booléen';

            case 'float':
                return 'nombre décimal';

            case 'money':
                return 'nombre monétaire';

            case 'percent':
                return 'pourcentage';

            case 'qty':
                return 'quantité';

            case 'object':
                return 'objet';

            case 'date':
                return 'date';

            case 'time':
                return 'heure';

            case 'datetime':
                return 'date et heure';

            case 'json':
                return 'json';
        }
    }

    // Gestion des durées:

    public static function getTimeDataFromSeconds($total_seconds)
    {
        $return = array(
            'total_seconds' => $total_seconds,
            'total_minutes' => 0,
            'total_hours'   => 0,
            'total_days'    => 0,
            'seconds'       => 0,
            'minutes'       => 0,
            'hours'         => 0,
            'days'          => 0
        );

        if ($total_seconds >= 60) {
            $return['total_minutes'] = (int) floor($total_seconds / 60);
            $return['secondes'] = (int) ($total_seconds - ($return['total_minutes'] * 60));
        } else {
            $return['secondes'] = $total_seconds;
        }

        if ($return['total_minutes'] >= 60) {
            $return['total_hours'] = (int) floor($return['total_minutes'] / 60);
            $return['minutes'] = (int) ($return['total_minutes'] - ($return['total_hours'] * 60));
        } else {
            $return['minutes'] = $return['total_minutes'];
        }

        if ($return['total_hours'] >= 24) {
            $return['total_days'] = (int) floor($return['total_hours'] / 24);
            $return['hours'] = (int) ($return['total_hours'] - ($return['total_days'] * 24));
        } else {
            $return['hours'] = $return['total_hours'];
        }

        $return['days'] = $return['total_days'];

        return $return;
    }

    public static function displayTimefromSeconds($total_seconds)
    {
        $timer = self::getTimeDataFromSeconds((int) $total_seconds);
        $html = '<span class="timer">';
        if ($timer['days'] > 0) {
            $html .= $timer['days'] . ' j ';
            $html .= $timer['hours'] . ' h ';
            $html .= $timer['minutes'] . ' min ';
        } elseif ($timer['hours'] > 0) {
            $html .= $timer['hours'] . ' h ';
            $html .= $timer['minutes'] . ' min ';
        } elseif ($timer['minutes'] > 0) {
            $html .= $timer['minutes'] . ' min ';
        }
        $html .= $timer['secondes'] . ' sec ';
        $html .= '</span>';
        return $html;
    }

    // Devises / prix: 

    public static function getCurrencyIcon($currency)
    {
        if (array_key_exists(strtoupper($currency), self::$currencies)) {
            return self::$currencies[$currency]['icon'];
        }

        return 'euro';
    }

    public static function getCurrencyHtml($currency)
    {
        if (array_key_exists(strtoupper($currency), self::$currencies)) {
            return self::$currencies[$currency]['html'];
        }

        return '&euro;';
    }

    public static function displayMoneyValue($value, $currency = 'EUR', $with_styles = false)
    {
        if (is_numeric($value)) {
            $value = (float) $value;
        }

        if (!is_float($value)) {
            return $value;
        }

        if ($value > -0.01 && $value < 0.01) {
            $value = 0;
        }

//        $value = round($value, 2);

        $price = price($value, 1, '', 1, -1, -1, $currency);

        $html = '';

        if ($with_styles) {
            $html .= '<span style="';
            if ((float) $value != 0) {
                $html .= 'font-weight: bold;';
            }
            if ((float) $value < 0) {
                $html .= 'color: #A00000;';
            }
            $html .= '">';
        }

        $html .= $price;

        if ($with_styles) {
            $html .= '</span>';
        }
        return $html;
    }

    public static function getTaxes($id_country = 1)
    {
        return BimpCache::getTaxes($id_country);
    }

    public static function getTaxeRateById($id_tax)
    {
        $taxes = BimpCache::getTaxes();
        if (isset($taxes[(int) $id_tax])) {
            return (float) $taxes[(int) $id_tax];
        }
        return 0;
    }

    public static function calculatePriceTaxEx($amout_tax_in, $tax_rate_percent, $precision = 6)
    {
        $rate = 1 + ($tax_rate_percent / 100);
        return (float) round($amout_tax_in / $rate, $precision);
    }

    public static function calculatePriceTaxIn($amout_tax_ex, $tax_rate_percent, $precision = 6)
    {
        $rate = 1 + ($tax_rate_percent / 100);
        return (float) round($amout_tax_ex * $rate, $precision);
    }

    // Traitements sur des strings: 

    public static function ucfirst($str)
    {
        if (preg_match('/^[éèêëàâäïîöôùûüŷÿ].*/', $str)) {
            $first = substr($str, 1, 1);
            $first = preg_replace('/[éèëê]/', 'e', $first);
            $first = preg_replace('/[àâä]/', 'a', $first);
            $first = preg_replace('/[îï]/', 'i', $first);
            $first = preg_replace('/[ôö]/', 'o', $first);
            $first = preg_replace('/[ùûü]/', 'i', $first);
            $first = preg_replace('/[ŷÿ]/', 'y', $first);
            $str = substr_replace($str, $first, 0, 2);
        }


        return ucfirst($str);
    }

    public static function lcfirst($str)
    {
        if (preg_match('/^[éèêëàâäïîöôùûüŷÿ].*/', $str)) {
            $first = substr($str, 1, 1);
            $first = preg_replace('/[éèëê]/', 'e', $first);
            $first = preg_replace('/[àâä]/', 'a', $first);
            $first = preg_replace('/[îï]/', 'i', $first);
            $first = preg_replace('/[ôö]/', 'o', $first);
            $first = preg_replace('/[ùûü]/', 'i', $first);
            $first = preg_replace('/[ŷÿ]/', 'y', $first);
            $str = substr_replace($str, $first, 0, 2);
        }

        return lcfirst($str);
    }

    public static function replaceBr($text, $replacement = "\n")
    {
        return preg_replace("/<[ \/]*br[ \/]*>/", $replacement, $text);
    }

    public static function cleanString($string)
    {
        $string = str_replace("\xc2\xa0", ' ', $string);
        $string = str_replace("  ", " ", $string);

        return $string;
    }

    public static function cleanStringForUrl($text, $separator = '_', $charset = 'utf-8')
    {
        $text = mb_convert_encoding($text, 'HTML-ENTITIES', $charset);

        // On vire les accents
        $text = preg_replace(array('/ß/', '/&(..)lig;/', '/&([aouAOU])uml;/', '/&(.)[^;]*;/'), array('ss', "$1", "$1" . 'e', "$1"), $text);

        // on vire tout ce qui n'est pas alphanumérique
        $text_clear = preg_replace('/[^a-zA-Z0-9_\-\(\)]/', ' ', trim($text)); // ^a-zA-Z0-9_-
        
        // Nettoyage pour un espace maxi entre les mots
        $array = explode(' ', $text_clear);
        $str = '';
        $i = 0;
        foreach ($array as $key => $valeur) {
            if (trim($valeur) != '' && trim($valeur) != $separator && $i > 0) {
                $str .= $separator . $valeur;
            } elseif (trim($valeur) != '' && trim($valeur) != $separator && $i == 0) {
                $str .= $valeur;
            }
            $i++;
        }

        return $str;
    }

    // Traitements sur des array: 

    public static function getMsgFromArray($msgs, $title = '')
    {
        $msg = '';
        if ($title) {
            $msg .= $title . ' : <br/>';
        }

        if (is_array($msgs)) {
//            $msg .= '<div style="padding-left: 15px">';
            $msg .= '<ul>';
//            $fl = true;
            foreach ($msgs as $m) {
//                if (!$fl) {
//                    $msg .= '<br/>';
//                } else {
//                    $fl = false;
//                }
//                $msg .= ' - ' . $m;
                $msg .= '<li>' . $m . '</li>';
            }
            $msg .= '</ul>';
//            $msg .= '</div>';
        } else {
            $msg .= '&nbsp;&nbsp;&nbsp;&nbsp;- ' . $msgs;
        }

        return $msg;
    }

    public static function unsetArrayValue($array, $value)
    {
        if (is_array($array)) {
            foreach ($array as $key => $val) {
                if ($val == $value) {
                    unset($array[$key]);
                }
            }
        }

        return $array;
    }

    // Divers:

    public static function getContext()
    {
        if (self::$context != "")
            return self::$context;

        if (isset($_REQUEST['context'])) {
            self::setContext($_REQUEST['context']);
        }

        if (isset($_SESSION['context'])) {
            return $_SESSION['context'];
        }

        return "";
    }

    public static function displayFloatValue($value, $decimals = 2, $separator = ',', $with_styles = false)
    {
        $html = '';

        if ($with_styles) {
            $html .= '<span style="';
            if ((float) $value != 0) {
                $html .= 'font-weight: bold;';
            }
            if ((float) $value < 0) {
                $html .= 'color: #A00000;';
            }
            $html .= '">';
        }

        $html .= str_replace('.', $separator, '' . (round((float) $value, $decimals)));

        if ($with_styles) {
            $html .= '</span>';
        }
        return $html;
    }

    public static function getAlertColor($class)
    {
        switch ($class) {
            case 'success':
                return '348C41';
            case 'info':
                return '3B6EA0';
            case 'warning':
                return 'E69900';
            case 'danger':
                return 'A00000';
            case 'important':
                return '963E96';
            default:
                return '636363';
        }
    }

    public static function getDolEventsMsgs($types = array('mesgs', 'errors', 'warnings'), $clean = true)
    {
        $return = array();
        foreach ($types as $type) {
            if (isset($_SESSION['dol_events'][$type])) {
                $return = array_merge($_SESSION['dol_events'][$type], $return);
                if ($clean) {
                    unset($_SESSION['dol_events'][$type]);
                }
            }
        }

        return $return;
    }

    public static function cleanDolEventsMsgs($types = array('mesgs', 'errors', 'warnings'))
    {
        foreach ($types as $type) {
            if (isset($_SESSION['dol_events'][$type])) {
                unset($_SESSION['dol_events'][$type]);
            }
        }
    }

    public static function makeUrlFromConfig(BimpConfig $config, $path, $default_module, $default_controller)
    {
        $url = DOL_URL_ROOT . '/';

        $params = $config->get($path, null, true, 'array');

        if (is_null($params)) {
            return '';
        }

        if (isset($params['url'])) {
            $url .= $config->get($path . '/url', '');
        } else {
            $module = $config->get($path . '/module', $default_module);
            $controller = $config->get($path . '/controller', $default_controller);
            if ((string) $module && (string) $controller) {
                $url .= $module . '/index.php?fc=' . $controller;
            }
        }

        if ($url && isset($params['url_params'])) {
            $url_params = $config->getCompiledParams($path . '/url_params');
            foreach ($url_params as $name => $value) {
                if ((string) $name && (string) $value) {
                    if (!preg_match('/\?/', $url)) {
                        $url .= '?';
                    } else {
                        $url .= '&';
                    }
                    $url .= $name . '=' . $value;
                }
            }
        }

        return $url;
    }

    // Gestion des couleurs: 

    public static function changeColorLuminosity($color_code, $percentage_adjuster = 0)
    {
        $percentage_adjuster = round($percentage_adjuster / 100, 2);
        if (is_array($color_code)) {
            $r = $color_code["r"] + (round($color_code["r"]) * $percentage_adjuster);
            $g = $color_code["g"] + (round($color_code["g"]) * $percentage_adjuster);
            $b = $color_code["b"] + (round($color_code["b"]) * $percentage_adjuster);

            return array("r" => round(max(0, min(255, $r))),
                "g" => round(max(0, min(255, $g))),
                "b" => round(max(0, min(255, $b))));
        } else if (preg_match("/#/", $color_code)) {
            $hex = str_replace("#", "", $color_code);
            $r = (strlen($hex) == 3) ? hexdec(substr($hex, 0, 1) . substr($hex, 0, 1)) : hexdec(substr($hex, 0, 2));
            $g = (strlen($hex) == 3) ? hexdec(substr($hex, 1, 1) . substr($hex, 1, 1)) : hexdec(substr($hex, 2, 2));
            $b = (strlen($hex) == 3) ? hexdec(substr($hex, 2, 1) . substr($hex, 2, 1)) : hexdec(substr($hex, 4, 2));
            $r = round($r + ($r * $percentage_adjuster));
            $g = round($g + ($g * $percentage_adjuster));
            $b = round($b + ($b * $percentage_adjuster));

            return "#" . str_pad(dechex(max(0, min(255, $r))), 2, "0", STR_PAD_LEFT)
                    . str_pad(dechex(max(0, min(255, $g))), 2, "0", STR_PAD_LEFT)
                    . str_pad(dechex(max(0, min(255, $b))), 2, "0", STR_PAD_LEFT);
        }
    }

    public static function setColorSL($color_code, $saturation = null, $luminosity = null)
    {
        if (preg_match("/#/", $color_code)) {
            $hex = str_replace("#", "", $color_code);
            $hsl = self::hexToHsl($hex);

            if (!is_null($saturation)) {
                $hsl[1] = $saturation;
            }
            if (!is_null($luminosity)) {
                $hsl[2] = $luminosity;
            }

            return '#' . self::hslToHex($hsl);
        }
        return $color_code;
    }

    public static function hexToHsl($hex)
    {
        $hex = array($hex[0] . $hex[1], $hex[2] . $hex[3], $hex[4] . $hex[5]);
        $rgb = array_map(function($part) {
            return hexdec($part) / 255;
        }, $hex);

        $max = max($rgb);
        $min = min($rgb);

        $l = ($max + $min) / 2;

        if ($max == $min) {
            $h = $s = 0;
        } else {
            $diff = $max - $min;
            $s = $l > 0.5 ? $diff / (2 - $max - $min) : $diff / ($max + $min);

            switch ($max) {
                case $rgb[0]:
                    $h = ($rgb[1] - $rgb[2]) / $diff + ($rgb[1] < $rgb[2] ? 6 : 0);
                    break;
                case $rgb[1]:
                    $h = ($rgb[2] - $rgb[0]) / $diff + 2;
                    break;
                case $rgb[2]:
                    $h = ($rgb[0] - $rgb[1]) / $diff + 4;
                    break;
            }

            $h /= 6;
        }

        return array($h, $s, $l);
    }

    public static function hslToHex($hsl)
    {
        list($h, $s, $l) = $hsl;

        if ($s == 0) {
            $r = $g = $b = 1;
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;

            $r = self::hue2rgb($p, $q, $h + 1 / 3);
            $g = self::hue2rgb($p, $q, $h);
            $b = self::hue2rgb($p, $q, $h - 1 / 3);
        }

        return self::rgb2hex($r) . self::rgb2hex($g) . self::rgb2hex($b);
    }

    public static function hue2rgb($p, $q, $t)
    {
        if ($t < 0)
            $t += 1;
        if ($t > 1)
            $t -= 1;
        if ($t < 1 / 6)
            return $p + ($q - $p) * 6 * $t;
        if ($t < 1 / 2)
            return $q;
        if ($t < 2 / 3)
            return $p + ($q - $p) * (2 / 3 - $t) * 6;

        return $p;
    }

    public static function rgb2hex($rgb)
    {
        return str_pad(dechex($rgb * 255), 2, '0', STR_PAD_LEFT);
    }

    public static function setContext($context)
    {
        self::$context = $context;
        $_SESSION['context'] = $context;
    }
}

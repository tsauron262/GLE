<?php

class BimpTools
{

    public static $currencies = array(
        'EUR' => array(
            'label'   => 'euro',
            'icon'    => 'euro',
            'html'    => '&euro;',
            'no_html' => '€'
        ),
        'USD' => array(
            'label'   => 'dollar',
            'icon'    => 'dollar',
            'html'    => '&#36;',
            'no_html' => '$'
        )
    );
    private static $context = "";

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
            if (is_object($object) && isset($object->id) && $object->id) {
                $id_object = $object->id;
            }
        }

        $class_name = '';
        if (is_object($object)) {
            $class_name = get_class($object);
        } elseif (is_string($object)) {
            $class_name = $object;
        }

        if ($class_name) {
            $file = strtolower($class_name) . '/card.php';
            $primary = 'id';
            switch ($class_name) {
                case 'CommandeFournisseur':
                    return DOL_URL_ROOT . '/fourn/commande/card.php?id=' . $id_object;

                case 'FactureFournisseur':
                    return DOL_URL_ROOT . '/fourn/facture/card.php?facid=' . $id_object;

                case 'Facture':
                    return DOL_URL_ROOT . '/compta/facture/card.php?id=' . $id_object;

                case 'Propal':
                    return DOL_URL_ROOT . '/comm/propal/card.php?id=' . $id_object;

                case 'Entrepot':
                    return DOL_URL_ROOT . '/product/stock/card.php?id=' . $id_object;

                case 'ActionComm':
                    return DOL_URL_ROOT . '/comm/action/card.php?id=' . $id_object;

                case 'UserGroup':
                    return DOL_URL_ROOT . '/user/group/card.php?id=' . $id_object;

                case 'Societe':
                    $primary = 'socid';
                    break;
            }
            if (file_exists(DOL_DOCUMENT_ROOT . '/' . $file)) {
                return DOL_URL_ROOT . '/' . $file . (!is_null($id_object) && $id_object ? '?' . $primary . '=' . $id_object : '');
            }
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

    public static function getErrorsFromDolObject($object, $errors = null, $langs = null, &$warnings = array(), $with_events = false)
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

        if ($with_events) {
            $errors = BimpTools::merge_array($errors, self::getDolEventsMsgs(array('errors'), false));
            $warnings = BimpTools::merge_array($warnings, self::getDolEventsMsgs(array('warnings'), false));
        }

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

    public static function getDolObjectLinkedObjectsList($dol_object, BimpDb $bdb = null, $type_filters = array())
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
                        if (!empty($type_filters) && !in_array($r['targettype'], $type_filters)) {
                            continue;
                        }
                        $list[] = array(
                            'id_object' => (int) $r['fk_target'],
                            'type'      => $r['targettype']
                        );
                    } elseif ((int) $r['fk_target'] === (int) $dol_object->id &&
                            $r['targettype'] === $dol_object->element) {
                        if (!empty($type_filters) && !in_array($r['sourcetype'], $type_filters)) {
                            continue;
                        }
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

    public static function getDolObjectLinkedObjectsListByTypes($dol_object, BimpDb $bdb = null, $type_filters = array())
    {
        $list = self::getDolObjectLinkedObjectsList($dol_object, $bdb, $type_filters);

        $items = array();

        foreach ($list as $item) {
            if (!isset($items[$item['type']])) {
                $items[$item['type']] = array();
            }

            if (!in_array((int) $item['id_object'], $items[$item['type']])) {
                $items[$item['type']][] = (int) $item['id_object'];
            }
        }

        return $items;
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

    public static function getTypeContactCodeById($id_type_contact)
    {
        if ((int) $id_type_contact) {
            return BimpCache::getBdb()->getValue('c_type_contact', 'code', '`rowid` = ' . (int) $id_type_contact);
        }

        return '';
    }

    public static function getDolObjectActionsComm($dol_object, $contact = null, $code_filter = '', $done_filter = '', $label_search = '', $order_by = 'a.datep,a.id', $order_way = 'DESC')
    {
        global $conf;
        $bdb = BimpCache::getBdb();
        $now = date('Y-m-d H:i:s');

        $sql = '';

        $fields = array('id', 'label', 'a.datep as date_start', 'a.datep2 as date_end', 'note', 'percent', 'a.fk_element as id_object', 'a.elementtype as obj_type', 'a.fk_user_author as id_author', 'a.fk_contact as id_contact', 'c.code as ac_code', 'c.libelle as ac_label', 'c.picto ac_picto', 'u.rowid as id_user_action');
        $joins = array(
            array(
                'table' => 'user',
                'alias' => 'u',
                'on'    => 'u.rowid = a.fk_user_action'
            ),
            array(
                'table' => 'c_actioncomm',
                'alias' => 'c',
                'on'    => 'c.id = a.fk_action'
            )
        );
        $filters = array(
            'a.entity' => array(
                'in' => '(' . getEntity('agenda') . ')'
            )
        );

        switch (get_class($dol_object)) {
            case 'Societe':
                $filters['a.fk_soc'] = $dol_object->id;
                break;

            case 'Adherent':
                $filters['a.fk_element'] = $dol_object->id;
                $filters['a.elementtype'] = 'member';
                break;

            case 'CommandeFournisseur':
                $filters['a.fk_element'] = $dol_object->id;
                $filters['a.elementtype'] = 'order_supplier';
                break;

            case 'Product':
                $filters['a.fk_element'] = $dol_object->id;
                $filters['a.elementtype'] = 'product';
                break;

            case 'Project':
                $filters['a.fk_project'] = $dol_object->id;
                break;
        }

        if (BimpObject::objectLoaded($contact)) {
            $filters['a.fk_contact'] = $contact->id;
        }

        if ($code_filter) {
            if (empty($conf->global->AGENDA_USE_EVENT_TYPE)) {
                switch ($code_filter) {
                    case 'AC_NON_AUTO':
                    case 'AC_OTH':
                        $filters['c.type'] = array(
                            'operator' => '!=',
                            'value'    => 'systemauto'
                        );
                        break;

                    case 'AC_ALL_AUTO':
                    case 'AC_OTH_AUTO':
                        $filters['c.type'] = 'systemauto';
                        break;
                }
            } else {
                switch ($code_filter) {
                    case 'AC_NON_AUTO':
                        $filters['c.type'] = array(
                            'operator' => '!=',
                            'value'    => 'systemauto'
                        );
                        break;

                    case 'AC_ALL_AUTO':
                        $filters['c.type'] = 'systemauto';
                        break;

                    default:
                        $filters['c.type'] = $bdb->db->escape($code_filter);
                        break;
                }
            }
        }

        if ($done_filter) {
            switch ($done_filter) {
                case 'done':
                    $filters['done_custom'] = array(
                        'custom' => '(a.percent = 100 OR (a.percent = -1 AND a.datep <= \'' . $now . '\'))'
                    );
                    break;

                case 'todo':
                    $filters['done_custom'] = array(
                        'custom' => '((a.percent >= 0 AND a.percent < 100) OR (a.percent = -1 AND a.datep > \'' . $now . '\'))'
                    );
                    break;
            }
        }

        if ($label_search) {
            $filters['label_custom'] = array(
                'custom' => natural_search('a.label', $label_search)
            );
        }

        $sql = BimpTools::getSqlSelect($fields);
        $sql .= BimpTools::getSqlFrom('actioncomm', $joins);
        $sql .= BimpTools::getSqlWhere($filters);
        $sql .= BimpTools::getSqlOrderBy($order_by, $order_way, 'a');

        $rows = $bdb->executeS($sql, 'array');

        if (!is_array($rows)) {
            $rows = array();
        }

        foreach ($rows as $idx => $r) {
            $state = '';
            if (((float) $r['percent'] >= 0 && (float) $r['percent'] < 100) || ($r['percent'] == -1 && $r['date_start'] > $now)) {
                $state = 'todo';
            }

            $rows[$idx]['type'] = 'action';
            $rows[$idx]['state'] = $state;
        }

        if (!empty($conf->mailing->enabled) && is_object($conf) && isset($contact->email)) {
            $sql = "SELECT m.rowid as id, mc.date_envoi as date_start, mc.date_envoi as date_end, m.titre as note, '100' as percentage,";
            $sql .= " 'AC_EMAILING' as ac_code,";
            $sql .= " m.fk_user_valid as id_user_action"; // User that valid action
            $sql .= " FROM " . MAIN_DB_PREFIX . "mailing as m, " . MAIN_DB_PREFIX . "mailing_cibles as mc";
            $sql .= " WHERE mc.email = '" . $bdb->db->escape($contact->email) . "'"; // Search is done on email.
            $sql .= " AND mc.statut = 1";
            $sql .= " AND mc.fk_mailing=m.rowid";
            $sql .= " ORDER BY mc.date_envoi DESC, m.rowid DESC";

            $rows2 = $bdb->executeS($sql, 'array');

            if (is_array($rows2)) {
                foreach ($rows2 as $idx => $r) {
                    $rows[$idx]['type'] = 'mailing';
                    $rows[$idx]['state'] = 'done';
                }
                $rows = array_merge($rows, $rows2);
            }
        }

        return $rows;
    }

    public static function getInstanceByElementType($element_type, $id_object = 0)
    {
        switch ($element_type) {
            case 'societe':
                return BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $id_object);

            case 'propal':
                return BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', $id_object);

            case 'commande':
            case 'order':
                return BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $id_object);

            case 'facture':
            case 'invoice':
                return BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_object);

            case 'commande_fourn':
            case 'commande_fournisseur':
            case 'order_supplier':
                return BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', $id_object);

            case 'facture_fourn':
            case 'facture_fournisseur':
            case 'invoice_supplier':
                return BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureFourn', $id_object);

            case 'product':
                return BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_object);
        }

        return null;
    }

    public static function getBimpObjectFromDolObject($dol_object)
    {
        if (is_a($dol_object, 'BimpObject')) {
            return $dol_object;
        }

        if (is_object($dol_object)) {
            switch (get_class($dol_object)) {
                case 'Propal':
                    return BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', (int) $dol_object->id);

                case 'Commande':
                    return BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $dol_object->id);

                case 'Facture':
                    return BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $dol_object->id);

                case 'User':
                    return BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $dol_object->id);

                case 'Societe':
                    if ((int) $dol_object->client) {
                        return BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', (int) $dol_object->id);
                    } elseif ((int) $dol_object->fournisseur) {
                        return BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', (int) $dol_object->id);
                    }
                    return BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', (int) $dol_object->id);
            }
        }

        return null;
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

    public static function getNextRef($table, $field, $prefix = '', $numCaractere = null)
    {

        $prefix = str_replace("{AA}", date('y'), $prefix);
        $prefix = str_replace("{MM}", date('m'), $prefix);


        if ($prefix) {
            $where = '`' . $field . '` LIKE \'' . $prefix . '%\'';
        } else {
            $where = '1';
        }


//        $max = BimpCache::getBdb()->getMax($table, $field, $where);
        $max = BimpCache::getBdb()->getMax($table, $field, $where . "  AND LENGTH(" . $field . ") = (SELECT MAX(LENGTH(" . $field . ")) as max FROM `" . MAIN_DB_PREFIX . $table . "`   WHERE " . $where . ")");

        if ((string) $max) {
            if (preg_match('/^' . $prefix . '([0-9]+)$/', $max, $matches)) {
                $num = (int) $matches[1] + 1;
            } else {
                $num = 1;
            }
        } else {
            $num = 1;
        }

        if ($numCaractere > 0) {
            $diff = $numCaractere - strlen($num);
            if ($diff < 0)
                die("impossible trop de caractére BimpTools::GetNextRef");
            else {
                for ($i = 0; $i < $diff; $i++) {
                    $num = "0" . $num;
                }
            }
        }

        return $prefix . $num;
    }

    // Gestion fichiers:

    public static function makeDirectories($dir_tree, $root_dir = null)
    {
        if (is_null($root_dir)) {
            $root_dir = DOL_DATA_ROOT;
        }

        if (!file_exists($root_dir)) {
            if (!mkdir($root_dir, 0777)) {
                return 'Echec de la création du dossier "' . $root_dir . '"';
            }
        }

        if (is_string($dir_tree)) {
            $array = explode('/', $dir_tree);
            $dir_tree = array();

            foreach ($array as $key => $value) {
                if (!(string) $value) {
                    unset($array[$key]);
                }
            }

            while ($dirname = array_pop($array)) {
                $dir_tree = array(
                    $dirname => $dir_tree
                );
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

            if (isset($old_path['basename']) && isset($new_path['basename'])) {
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

    public static function unZip($srcPath, $destPath, &$errors = array())
    {
        if (!file_exists($srcPath)) {
            $errors[] = 'L\'archive "' . $srcPath . '" n\'existe pas';
        }

        if (!is_dir($destPath)) {
            $errors[] = 'Le dossier de destination "' . $destPath . '" n\'existe pas';
        }

        if (!class_exists('ZipArchive')) {
            $errors[] = 'La classe "ZipArchive" n\'existe pas';
        }

        if (!count($errors)) {
            $zip = new ZipArchive();
            $result = $zip->open($srcPath);
            if ($result === true) {
                if ($zip->extractTo($destPath) === true) {
                    $zip->close();
                    return true;
                }

                $errors[] = 'Echec de l\'extraction de l\'archive "' . $srcPath . '"';
                $zip->close();
            } else {
                $errors[] = 'Echec de l\'ouverture de l\'archive "' . $srcPath . '" (Code erreur: ' . $result . ')';
            }
        }

        return false;
    }

    // Gestion Logs: 

    public static function logTechnicalError($object, $method, $msg, $extra_data = array())
    {
        $message = '[ERREUR TECHNIQUE] ' . $msg;

        if (is_object($object)) {
            $extra_data['Classe'] = get_class($object);
        }

        if ($method) {
            $extra_data['Méthode'] = $method;
        }

        BimpCore::addlog($message, Bimp_Log::BIMP_LOG_ERREUR, 'bimpcore', (is_a($object, 'BimpObject') ? $object : null), $extra_data);
    }

    // Gestion SQL:

    public static function getSqlSelect($return_fields = null, $default_alias = 'a')
    {
        $sql = 'SELECT ';

        if (!is_null($return_fields) && !empty($return_fields)) {
            if (is_array($return_fields)) {
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
                    } else {
                        $sql .= $field;
                    }
                }
            } elseif (is_string($return_fields)) {
                $sql .= $return_fields;
            } else {
                $sql .= '*';
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

    public static function getSqlWhere($filters, $default_alias = 'a', $operator = 'WHERE')
    {
        $sql = '';
        if (!is_null($filters) && is_array($filters) && !empty($filters)) {
            $first_loop = true;
            foreach ($filters as $field => $filter) {
                $sql_filter = self::getSqlFilter($field, $filter, $default_alias);

                if ($sql_filter) {
                    if (!$first_loop) {
                        $sql .= ' AND ';
                    } else {
                        if ($operator) {
                            $sql .= ' ' . $operator . ' ';
                        }
                        $first_loop = false;
                    }
                    $sql .= $sql_filter;
                }
            }
        }
        return $sql;
    }

    public static function getSqlCase($filters, $value_true, $value_false, $default_alias = 'a')
    {
        $sql = '';
        if (!is_null($filters) && is_array($filters) && !empty($filters)) {
            $sql .= ' CASE';
            $sql .= BimpTools::getSqlWhere($filters, $default_alias, 'WHEN');
            $sql .= ' THEN ' . $value_true . ' ELSE ' . $value_false;
            $sql .= ' END';
        } else {
            $sql .= $value_true;
        }

        return $sql;
    }

    public static function getSqlFilter($field, $filter, $default_alias = 'a')
    {
        $sql = '';

        if (is_array($filter) && isset($filter['or'])) {
            $fl = true;
            $or_clause = '';
            foreach ($filter['or'] as $or_field => $or_filter) {
                $sql_filter = self::getSqlFilter($or_field, $or_filter, $default_alias);
                if ($sql_filter) {
                    if (!$fl) {
                        $or_clause .= ' OR ';
                    } else {
                        $fl = false;
                    }
                    $or_clause .= $sql_filter;
                }
            }
            if ($or_clause) {
                $sql .= '(' . $or_clause . ')';
            }
        } elseif (is_array($filter) && isset($filter['and'])) {
            $fl = true;
            $and_clause = '';
            foreach ($filter['and'] as $and_filter) {
                $sql_filter = self::getSqlFilter($field, $and_filter, $default_alias);
                if ($sql_filter) {
                    if (!$fl) {
                        $and_clause .= ' AND ';
                    } else {
                        $fl = false;
                    }
                    $and_clause .= $sql_filter;
                }
            }
            if ($and_clause) {
                $sql .= '(' . $and_clause . ')';
            }
        } elseif (is_array($filter) && isset($filter['and_fields'])) {
            $fl = true;
            $and_clause = '';
            foreach ($filter['and_fields'] as $and_field => $and_filter) {
                $sql_filter = self::getSqlFilter($and_field, $and_filter, $default_alias);
                if ($sql_filter) {
                    if (!$fl) {
                        $and_clause .= ' AND ';
                    } else {
                        $fl = false;
                    }
                    $and_clause .= $sql_filter;
                }
            }
            if ($and_clause) {
                $sql .= '(' . $and_clause . ')';
            }
        } elseif (is_array($filter) && isset($filter['or_field'])) {
            $fl = true;
            $or_clause = '';
            foreach ($filter['or_field'] as $or_filter) {
                $sql_filter = self::getSqlFilter($field, $or_filter, $default_alias);
                if ($sql_filter) {
                    if (!$fl) {
                        $or_clause .= ' OR ';
                    } else {
                        $fl = false;
                    }
                    $or_clause .= $sql_filter;
                }
            }
            if ($or_clause) {
                $sql .= '(' . $or_clause . ')';
            }
        } elseif (is_array($filter) && isset($filter['custom'])) {
            $sql .= $filter['custom'];
        } else {
            if (preg_match('/\./', $field)) {
                $sql .= $field;
            } elseif (!is_null($default_alias) && $default_alias) {
                $sql .= $default_alias . '.' . $field;
            } else {
                $sql .= '`' . $field . '`';
            }

            if (isset($filter['IN'])) {
                $filter['in'] = $filter['IN'];
            }

            if (is_array($filter)) {
                if (isset($filter['min']) || isset($filter['max'])) {
                    if (isset($filter['min']) && (string) $filter['min'] !== '' && isset($filter['max']) && (string) $filter['max'] !== '') {
                        if (isset($filter['not'])) {
                            $sql .= ' NOT';
                        }
                        $sql .= ' BETWEEN ' . ((is_string($filter['min']) && $filter['min'] != 'now()') ? '\'' . $filter['min'] . '\'' : $filter['min']);
                        $sql .= ' AND ' . ((is_string($filter['max']) && $filter['max'] != 'now()') ? '\'' . $filter['max'] . '\'' : $filter['max']);
                    } elseif (isset($filter['min']) && (string) $filter['min'] !== '') {
                        $sql .= ' >= ' . (is_string($filter['min']) ? '\'' . $filter['min'] . '\'' : $filter['min']);
                    } elseif (isset($filter['max']) && (string) $filter['max'] !== '') {
                        $sql .= ' <= ' . (is_string($filter['max']) ? '\'' . $filter['max'] . '\'' : $filter['max']);
                    } else {
                        return '';
                    }
                } elseif (isset($filter['operator']) && isset($filter['value'])) {
                    $sql .= ' ' . $filter['operator'] . ' ' . (is_string($filter['value']) ? '\'' . $filter['value'] . '\'' : $filter['value']);
                } elseif (isset($filter['part_type']) && isset($filter['part'])) {
                    $escape_char = '';
                    foreach (array('$', '|', '&', '@') as $char) {
                        if (strpos($filter['part'], $char) === false) {
                            $escape_char = $char;
                            break;
                        }
                    }
                    $filter['part'] = addslashes($filter['part']);
                    if ($escape_char) {
                        $filter['part'] = str_replace('%', $escape_char . '%', $filter['part']);
                        $filter['part'] = str_replace('_', $escape_char . '_', $filter['part']);
                    }
                    if (isset($filter['not']) && (int) $filter['not']) {
                        $sql .= ' NOT';
                    }
                    $sql .= ' LIKE \'';
                    switch ($filter['part_type']) {
                        case 'full':
                            $sql .= $filter['part'];
                            break;

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
                    if ($escape_char) {
                        $sql .= ' ESCAPE \'$\'';
                    }
                } elseif (isset($filter['in'])) {
                    if (is_array($filter['in'])) {
                        $sql .= ' IN ("' . implode('","', $filter['in']) . '")';
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
            } elseif (is_string($filter) && preg_match('/^ *([<>!=]{1,2}) *(.+)$/', $filter, $matches)) {
                $sql .= ' ' . $matches[1] . ' \'' . $matches[2] . '\'';
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
                    if (preg_match('/^\-?[0-9]+([\.,][0-9]+)?$/', $value)) {
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
                if (!is_array($value)) {
                    if (is_string($value)) {
                        $value = json_decode($value, true);
                    } elseif (!empty($value)) {
                        $value = array($value);
                    }
                    if (empty($value)) {
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

    public static function displayValueByType($value, $type)
    {
        switch ($type) {
            case 'bool':
                if ((int) $value) {
                    return 'OUI';
                }
                return 'NON';

            case 'percent':
                return self::displayFloatValue($value, 4) . '%';
            case 'money':
                return self::displayMoneyValue($value);

            case 'date':
                $dt = new DateTime($value);
                return $dt->format('d / m / Y');
            case 'time':
                $dt = new DateTime($value);
                return $dt->format('H:i:s');
            case 'datetime':
                $dt = new DateTime($value);
                return $dt->format('d / m / Y H:i:s');

            case 'id_object':
            case 'id_parent':
            case 'color':
            case 'string':
            case 'text':
            case 'html':
            case 'int':
            case 'float':
            case 'qty':
            default :
                return $value;
        }

        return $value;
    }

    public static function value2String($value, $no_html = false)
    {
        if (is_bool($value)) {
            if ($value) {
                if ($no_html) {
                    return 'OUI';
                } else {
                    return '<span class="success">OUI</span>';
                }
            } else {
                if ($no_html) {
                    return 'NON';
                } else {
                    return '<span class="danger">NON</span>';
                }
            }
        }

        if (is_string($value) && preg_match('/^(\d{4}\-\d{2}\-\d{2}).?(\d{2}:\d{2}:\d{2})?.*$/', $value, $matches)) {
            if (preg_match('/^1970\-01\-01.*$/', $value)) {
                return '';
            }

            $datetime = $matches[1];
            if (isset($matches[2]) && $matches[2] && $matches[2] !== '00:00:00') {
                $datetime .= $matches[2];
            }

            $dt = new DateTime($datetime);
            if (isset($matches[2]) && $matches[2] && $matches[2] !== '00:00:00') {
                return $dt->format('d / m / Y H:i:s');
            } else {
                return $dt->format('d / m / Y');
            }
        }

        return $value;
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

    // Gestion des dates: 

    public function printDate($date, $balise = "span", $class = '', $format = 'd/m/Y H:i:s', $format_mini = 'd / m / Y')
    {
        if (is_string($date) && stripos($date, '-') > 0) {
            $date = new DateTime($date);
        }

        if (is_object($date)) {
            $date = $date->getTimestamp();
        }

        if (is_array($class))
            $class = explode(" ", $class);

        $html = '<' . $balise;
        if ($format != $format_mini)
            $html .= ' title="' . date($format, $date) . '"';
        if ($class != '')
            $html .= ' class="' . $class . '"';
        $html .= '>' . date($format_mini, $date) . '</' . $balise . '>';
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

    public static function getCurrencyNoHtml($currency)
    {
        if (array_key_exists(strtoupper($currency), self::$currencies)) {
            return self::$currencies[$currency]['no_html'];
        }

        return '€';
    }

    public static function getDecimalesNumber($float_number)
    {
        if (is_numeric($float_number)) {
            $value_str = number_format($float_number, 8);

            if ($value_str) {
                if (preg_match('/^(\d+)\.(\d+)0*$/U', $value_str, $matches)) {
                    return strlen($matches[2]);
                }
            }
        }

        return 0;
    }

    public static function displayMoneyValue($value, $currency = 'EUR', $with_styles = false, $truncate = false, $no_html = false, $decimals = 2, $round_points = false, $separator = ',', $spaces = true)
    {
        // $decimals: indiquer 'full' pour afficher toutes les décimales. 

        if (is_numeric($value)) {
            $value = (float) $value;
        }

        if (!is_float($value)) {
            return $value;
        }

        $base_price = $value;
        $code = '';
        $hasMoreDecimals = false;

        // Troncature: 
        if ($truncate) {
            if ($value > 1000000000) {
                $code = 'G';
                $value = $value / 1000000000;
            } elseif ($value > 1000000) {
                $code = 'M';
                $value = $value / 1000000;
            } elseif ($value > 100000) {
                $code = 'K';
                $value = $value / 1000;
            }

            $decimals = 2;
        }

        // Ajustement du nombre de décimales:
        if ($decimals === 'full') {
            $decimals = (int) self::getDecimalesNumber($value);
        }

        if ($value) {
            $min_decimals = 2;
            $min = 0.01;
            for ($i = 2; $i <= 8; $i++) {
                if ($value > -$min && $value < $min) {
                    $min /= 10;
                    $min_decimals++;
                    continue;
                }
                break;
            }

            if ($min_decimals > $decimals) {
                $decimals = $min_decimals;
            }
        }

        if ((int) $decimals < 2) {
            $decimals = 2;
        } elseif ((int) $decimals > 8) {
            $decimals = 8;
        }

        // Arrondi: 
        $value = round($value, (int) $decimals);

        if ($value != round($base_price, 8)) {
            $hasMoreDecimals = true;
        }

        // Espaces entre les milliers: 
        $price = number_format($value, $decimals, $separator, ($spaces ? ' ' : ''));

        $html = '';

        if (!$no_html) {
            // Styles: 
            $html .= '<span';

            if ($with_styles) {
                $html .= ' style="';
                if ((float) $value != 0) {
                    $html .= 'font-weight: bold;';
                }
                if ((float) $value < 0) {
                    $html .= 'color: #A00000;';
                }
                $html .= '"';
            }

            // popover: 
            if ($hasMoreDecimals) {
                $html .= ' class="bs-popover"';
                $html .= BimpRender::renderPopoverData(number_format($base_price, 8, $separator, ($spaces ? ' ' : '')), 'top', 'true');
            }

            $html .= '>';

            $html .= $price;

            if ($hasMoreDecimals && $round_points) {
                $html .= '...';
            }

            if ($code) {
                $html .= ' ' . $code;
            }

            if ($currency) {
                $html .= ' ' . self::getCurrencyHtml($currency);
            }

            $html .= '</span>';
        } else {
            $html .= $price;

            if ($hasMoreDecimals && $round_points) {
                $html .= '...';
            }

            if ($code) {
                $html .= ' ' . $code;
            }

            if ($currency) {
                $html .= ' ' . self::getCurrencyHtml($currency);
            }
            $html = str_replace('&nbsp;', ' ', $html);
        }

        return $html;
    }

    public static function getTaxes($id_country = 1)
    {
        return BimpCache::getTaxes($id_country);
    }

    public static function getDefaultTva($id_country = null)
    {
        return 20;
    }

    public static function getTaxeRateById($id_tax)
    {
        $taxes = BimpCache::getTaxes();
        if (isset($taxes[(int) $id_tax])) {
            return (float) $taxes[(int) $id_tax];
        }
        return 0;
    }

    public static function getTaxeRateByCode($code, $id_country = 1, $return_default = true)
    {
        $taxes = BimpCache::getTaxes($id_country, true, false, 'code');

        if (isset($taxes[$code])) {
            return (float) $taxes[$code];
        }

        if ($return_default) {
            return self::getDefaultTva($id_country);
        }

        return 0;
    }

    public static function getTvaRateFromPrices($price_ht, $price_ttc)
    {
        if (!(float) $price_ht) {
            return 0;
        }

        $tva = $price_ttc - $price_ht;
        $tx = (float) ($tva / $price_ht) * 100;

        $taxes = BimpCache::getTaxes(1);

        // Pour répondre aux problèmes d'arrondis: (on retourne le taux en cours le plus proche)
        foreach ($taxes as $id_taxe => $tva_tx) {
            if ($tx < ($tva_tx + 1) && $tx > ($tva_tx - 1)) {
                return $tva_tx;
            }
        }

        return $tx;
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
        // Commenter car apparemment ça converti les undescore en "n" => pose problème dans le cas des propales révisées. 
//        $text = mb_convert_encoding($text, 'HTML-ENTITIES', $charset);
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

    public static function stringToFloat($str_number)
    {
        $str_number = (string) $str_number;

        // On vire tout espace: 
        $str_number = str_replace(' ', '', $str_number);

        // On remplace la virgule: 
        $str_number = str_replace(',', '.', $str_number);

        // On retourne un float: 
        return (float) $str_number;
    }

    public static function getStringNbLines($string, $maxLineChars)
    {
        $words = explode(' ', $string);

        $n = 1;
        $count = 0;

        foreach ($words as $word) {
            $count += strlen($word);
            if ($count > $maxLineChars) {
                $n++;
                $count = $maxLineChars;
            }
        }

        return $n;
    }

    public static function utf8_encode($value)
    {
        // Encodage récursif si $value = array() 

        if (is_array($value)) {
            foreach ($value as $key => $subValue) {
                $value[$key] = self::utf8_encode($subValue);
            }

            return $value;
        }

        if (is_string($value)) {
            $value = utf8_encode($value);
        }

        return $value;
    }

    public static function addZeros($str, $nbCarac)
    {
        // Ajoute des zéros en début de chaîne de manière à obtenir $nbCarac caractères. 
        $str = '' . $str;
        if (strlen($str) < $nbCarac) {
            $n = ($nbCarac - strlen($str));
            while ($n > 0) {
                $str = '0' . $str;
                $n--;
            }
        }
        return $str;
    }

    public static function cleanEmailsStr($emails_str, $name = '', $allow_multiple = true)
    {
        if ((string) $emails_str) {
            $emails = str_replace(' ', '', $emails_str);
            $emails = str_replace(';', ',', $emails);
            $name = str_replace(',', '', $name);
            $emails_str = '';

            foreach (explode(',', $emails) as $email) {
                if ($name) {
                    $emails_str .= ($emails_str ? ', ' : '') . $name . ' <' . $email . '>';
                } else {
                    $emails_str .= ($emails_str ? ', ' : '') . $email;
                }
            }
        }

        return $emails_str;
    }

    // Traitements sur des array: 

    public static function getMsgFromArray($msgs, $title = '', $no_html = false)
    {
        $msg = '';

        if ($no_html) {
            if ($title) {
                $msg .= htmlentities($title) . ' : ' . "\n";
            }

            if (is_array($msgs)) {
                $fl = true;
                foreach ($msgs as $m) {
                    if (!$fl) {
                        $msg .= "\n";
                    } else {
                        $fl = false;
                    }

                    $msg .= "\t" . '- ' . htmlentities($m);
                }
            } else {
                $msg .= ($title ? "\t" . '- ' : '') . $msgs;
            }
        } else {
            if ($title) {
                $msg .= $title . ' : <br/>';
            }

            if (is_array($msgs)) {
                $msg .= '<ul>';
                foreach ($msgs as $m) {
                    $msg .= '<li>' . $m . '</li>';
                }
                $msg .= '</ul>';
            } else {
                $msg .= ($title ? '&nbsp;&nbsp;&nbsp;&nbsp;- ' : '') . $msgs;
            }
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

    public static function getArrayValueFromPath($array, $path, $default_value = null, &$errors = array(), $required = false, $missing_msg = '', $params = array())
    {
        $params = BimpTools::overrideArray(array(
                    'value2String' => false
                        ), $params);

        $keys = explode('/', $path);

        $current_value = null;

        foreach ($keys as $key) {
            if (is_null($current_value)) {
                if (isset($array[$key])) {
                    $current_value = $array[$key];
                } else {
                    $current_value = $default_value;
                    break;
                }
            } elseif (isset($current_value[$key])) {
                $current_value = $current_value[$key];
            } else {
                $current_value = $default_value;
                break;
            }
        }

        if (!is_null($current_value)) {
            if ($params['value2String']) {
                $current_value = self::value2String($current_value);
            }
        }

        if ($required && empty($current_value)) {
            $errors[] = ($missing_msg ? $missing_msg : 'Valeur absente: "' . $path . '"');
        }

        return $current_value;
    }

    public static function overrideArray($array, $override, $skip_null = false, $recursive = false)
    {
        if (!is_array($array)) {
            $array = array();
        }

        if (is_array($override)) {
            foreach ($override as $key => $value) {
                if ($skip_null && is_null($value)) {
                    continue;
                }

                if ($recursive) {
                    if (is_array($array[$key]) && is_array($value)) {
                        $array[$key] = self::overrideArray($array[$key], $value, $skip_null, $recursive);
                        continue;
                    }
                }
                $array[$key] = $value;
            }
        }

        return $array;
    }

    public static function getMaxArrayDepth($array)
    {
        $max_depth = 1;

        if (is_array($array)) {
            foreach ($array as $value) {
                if (is_array($value)) {
                    $depth = self::getMaxArrayDepth($value) + 1;

                    if ($depth > $max_depth) {
                        $max_depth = $depth;
                    }
                }
            }
        }

        return $max_depth;
    }

    public static function implodeWithQuotes($array, $delimiter = ',')
    {
        $str = '';

        if (is_array($array)) {
            foreach ($array as $value) {
                $str .= ($str ? $delimiter : '') . "'" . $value . "'";
            }
        }

        return $str;
    }

    public static function implodeArrayKeys($array, $delimiter = ',', $with_quotes = false)
    {
        $str = '';

        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $str .= ($str ? $delimiter : '') . ($with_quotes ? "'" : '') . $key . ($with_quotes ? "'" : '');
            }
        }

        return $str;
    }

    public static function merge_array($array1, $array2 = null)
    {
        if (!is_array($array1)) {
            if (!is_null($array1)) {
                BimpCore::addlog('Erreur BimpTools::merge_array() - "$array1" n\'est pas un tableau', Bimp_Log::BIMP_LOG_ERREUR, 'bimpcore', null, array(
                    'Type array1' => gettype($array1)
                ));
            }
            return $array2;
        }
        if (!is_array($array2)) {
            if (!is_null($array2)) {
                BimpCore::addlog('Erreur BimpTools::merge_array() - "$array2" n\'est pas un tableau', Bimp_Log::BIMP_LOG_ERREUR, 'bimpcore', null, array(
                    'Type array2' => gettype($array1)
                ));
            }
            return $array1;
        }

        return array_merge($array1, $array2);
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
                $return = BimpTools::merge_array($_SESSION['dol_events'][$type], $return);
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

    public static function makeTreeFromArray($items, $parent_id, $id_parent_key = 'id_parent', $id_key = 'id', $label_key = 'label')
    {
        $array = array();

        foreach ($items as $idx => $item) {
            if (isset($item[$id_parent_key]) && $item[$id_parent_key] == $parent_id) {
                $data = array(
                    'label' => (isset($item[$label_key]) ? $item[$label_key] : 'Elément ' . $idx)
                );

                if (isset($item[$id_key])) {
                    $children = self::makeTreeFromArray($items, $item[$id_key], $id_parent_key, $id_key, $label_key);
                    if (!empty($children)) {
                        $data['children'] = $children;
                    }
                }

                $array[(isset($item[$id_key]) ? $item[$id_key] : $idx)] = $data;
            }
        }

        return $array;
    }

    public static function getDateLimReglement($date_begin, $id_cond_reglement)
    {
        
    }

    public static function getRemiseExceptLabel($desc)
    {
        global $langs;

        if (preg_match('/\(CREDIT_NOTE\)/', $desc))
            $desc = preg_replace('/\(CREDIT_NOTE\)/', $langs->trans("CreditNote"), $desc);
        if (preg_match('/\(DEPOSIT\)/', $desc))
            $desc = preg_replace('/\(DEPOSIT\)/', $langs->trans("Deposit"), $desc);
        if (preg_match('/\(EXCESS RECEIVED\)/', $desc))
            $desc = preg_replace('/\(EXCESS RECEIVED\)/', $langs->trans("ExcessReceived"), $desc);
        if (preg_match('/\(EXCESS PAID\)/', $desc))
            $desc = preg_replace('/\(EXCESS PAID\)/', $langs->trans("ExcessPaid"), $desc);

        return $desc;
    }

    public static function getCommercialArray($socid)
    {
        // Ce type de fonction ne doit pas être mise dans BimpTools mais dans BimpCache. 
        return BimpCache::getSocieteCommerciauxObjectsList($socid);
    }

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

    // Debug: 

    public static function getBacktraceArray($backtrace)
    {
        // Génère un tableau de la backtrace classée par fichiers
        // Ce tableau peut être fourni à BimpRender::renderBacktrace() pour un rendu HTML

        $files = array();

        $base_dir = DOL_DOCUMENT_ROOT;

        if ($base_dir === '/var/www/html/bimp8/') {
            $base_dir = '/var/GLE/bimp8/htdocs/';
        }

        if (is_array($backtrace) && !empty($backtrace)) {
            unset($backtrace[0]);
            krsort($backtrace);

            $current_file = '';
            $lines = array();

            foreach ($backtrace as $idx => $trace) {
                $file = str_replace($base_dir, '', $trace['file']);
                if (!$current_file) {
                    $current_file = $file;
                } elseif ($file != $current_file) {
                    // Changement de fichier: 
                    $files[] = array(
                        'file'  => $current_file,
                        'lines' => $lines
                    );
                    $current_file = $file;
                    $lines = array();
                }

                $args = '';

                if (isset($trace['args'])) {
                    foreach ($trace['args'] as $arg) {
                        if ($args) {
                            $args .= ', ';
                        }

                        if (is_object($arg)) {
                            $args .= '*' . get_class($arg) . (isset($arg->id) ? ' #' . $arg->id : '');
                        } elseif (is_bool($arg)) {
                            $args .= ((int) $arg ? 'true' : 'false');
                        } else {
                            $args .= (string) $arg;
                        }
                    }
                }

                $line = $trace['line'] . ': ';

                if (isset($trace['class']) && $trace['class']) {
                    $line .= $trace['class'] . BimpTools::getArrayValueFromPath($trace, 'type', '->');
                }

                $line .= $trace['function'] . '(' . $args . ')';
                $lines[] = $line;
            }

            if ($current_file && !empty($lines)) {
                $files[] = array(
                    'file'  => $current_file,
                    'lines' => $lines
                );
            }
        }

        return $files;
    }

    // Autres:

    public static function setContext($context)
    {
        self::$context = $context;
        $_SESSION['context'] = $context;
    }

    public static $nbMax = 10;

    public static function bloqueDebloque($type, $bloque = true, $nb = 1)
    {
        $file = static::getFileBloqued($type);
        if ($bloque) {
            $random = rand(0, 10000000);
            $text = "Yes" . $random;
            if (!file_put_contents($file, $text))
                die('droit sur fichier incorrect : ' . $file);
            sleep(0.400);
            $text2 = file_get_contents($file);
            if ($text == $text2)
                return 1;
            else {//conflit
                mailSyn2("Conflit de ref évité", "dev@bimp.fr", "admin@bimp.fr", "Attention : Un conflit de ref de type " . $type . " a été évité");
                $nb++;
                if ($nb > static::$nbMax)
                    die('On arrete tout erreur 445834834857');
                self::sleppIfBloqued($type, $nb);
                return static::bloqueDebloque($type, $bloque, $nb);
            }
        } elseif (is_file($file))
            return unlink($file);
    }

    public static function getFileBloqued($type)
    {
        $folder = DOL_DATA_ROOT . '/bloqueFile/';
        if (!is_dir($folder))
            mkdir($folder);
        return $folder . $type . ".txt";
    }

    public static function isBloqued($type)
    {
        $file = static::getFileBloqued($type);
        return (file_exists($file));
    }

    public static function sleppIfBloqued($type, $nb = 0)
    {
        $nb++;
        if (static::isBloqued($type)) {
            if ($nb < static::$nbMax) {
                sleep(1);
                return static::sleppIfBloqued($type, $nb);
            } else {
                $text = "sleppIfBloqued() : bloquage de plus de " . static::$nbMax . " secondes";
                static::bloqueDebloque($type, false, $nb);
                BimpCore::addlog($text, Bimp_Log::BIMP_LOG_URGENT, 'bimpcore', null, array(
                    'Type' => $type
                ));
                return 0;
            }
        } else
            return 0;
    }

    public static function getMailOrSuperiorMail($idComm)
    {
        $userT = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $idComm);
        $ok = true;
        if ($userT->getData("statut") < 1)
            $ok = false;
        if ($ok && $userT->getData('email') != '')
            return $userT->getData('email');

        if ($userT->getData('fk_user') > 0)
            return static::getMailOrSuperiorMail($userT->getData('fk_user'));

        return "admin@bimp.fr";
    }

    public static function mailGrouper($to, $from, $msg)
    {
        $dir = DOL_DATA_ROOT . "/bimpcore/mailsGrouper/";
        if (!is_dir($dir))
            mkdir($dir);
        if (!is_dir($dir))
            return false;
        $msg = "<br/><br/>" . dol_print_date(dol_now(), '%d/%m/%Y %H:%M:%S') . " : " . $msg;
        $file = $dir . $to;
        $file .= "$" . $from;
        $file .= "$.txt";
        file_put_contents($file, $msg, FILE_APPEND);
    }

    public static function htmlToText($html)
    {
        $html = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $html);


        return $html;
    }

    public static function envoieMailGrouper()
    {
        $dir = DOL_DATA_ROOT . "/bimpcore/mailsGrouper/";
        if (!is_dir($dir))
            mkdir($dir);
        if (!is_dir($dir))
            return false;
        $files = scandir($dir);
        $i = 0;
        foreach ($files as $file) {
            $tabInfo = explode("$", $file);
            if (isset($tabInfo[2])) {
                $msg = file_get_contents($dir . $file);
                if (mailSyn2("Infos grouper Bimp-ERP", $tabInfo[0], $tabInfo[1], $msg)) {
                    unlink($dir . $file);
                    $i++;
                }
            }
        }
        $this->output = "OK " . $i . ' mails envoyés';
        return 0;
    }
}

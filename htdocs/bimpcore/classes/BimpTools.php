<?php

class BimpTools
{

    public $output = '';
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
    public static $bloquages = array();

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

    public static function isPostFieldSubmit($field_name)
    {
        // Chargement d'un formulaire:
        if (BimpTools::isSubmit('param_values/fields/' . $field_name)) {
            return 1;
        }

        // Chargement d'un input: 
        if (BimpTools::isSubmit('fields/' . $field_name)) {
            return 1;
        }

        // Action ajax: 
        if (BimpTools::isSubmit('extra_data/' . $field_name)) {
            return 1;
        }

        // Envoi des données d'un formulaire: 
        if (BimpTools::isSubmit($field_name)) {
            return 1;
        }

        // Filtres listes:
        if (BimpTools::isSubmit('param_list_filters')) {
            $filters = json_decode(BimpTools::getValue('param_list_filters'));
            foreach ($filters as $filter) {
                if (isset($filter->name) && $filter->name === $field_name) {
                    return 1;
                }
            }
        }

        return 0;
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

    // Gestion des fichiers uploadés: 

    public static function getTmpFilesDir()
    {
        // On utilise un dossier par jour pour permettre de nettoyer les fichiers non déplacés. 
        return 'bimpcore/tmp_files/' . date('Ymd');
    }

    public static function getAjaxFileName($field_name)
    {
        return str_replace("C:fakepath", '', BimpTools::getPostFieldValue($field_name));
    }

    public static function moveAjaxFile(&$errors, $field_name, $dir_dest, $name_dest = null)
    {
        // Pas d'extension elle est géré en auto
        global $user;
        if (!is_dir($dir_dest))
            mkdir($dir_dest);

        $dir = DOL_DATA_ROOT . '/' . BimpTools::getTmpFilesDir();
        $file = $user->id . "_" . BimpTools::getAjaxFileName($field_name);

        if ($name_dest == null) {
            $name_dest = BimpTools::getAjaxFileName($field_name);
        }

        if (pathinfo($name_dest, PATHINFO_EXTENSION) == '') {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $name_dest .= '.' . $extension;
        }

        if (!file_exists($dir . '/' . $file)) {
            $errors[] = 'Le fichier "' . $file . '" n\'existe pas';
        } elseif (!rename($dir . '/' . $file, $dir_dest . '/' . $name_dest)) {
            $errors[] = 'Echec du déplacement du fichier "' . $name_dest . '" dans le dossier de destination';
        }
    }

    public static function moveTmpFiles(&$errors, $files, $dir_dest, $name_dest = null)
    {
        if (!is_dir($dir_dest)) {
            $err = BimpTools::makeDirectories($dir_dest);
            if ($err) {
                $errors[] = $err;
                return false;
            }
        }

        $dir = DOL_DATA_ROOT . '/' . BimpTools::getTmpFilesDir() . '/';

        $i = 0;
        foreach ($files as $file_name) {
            $i++;
            if (!file_exists($dir . $file_name)) {
                $errors[] = 'Le fichier "' . $file_name . '" n\'existe pas dans le dossier de téléchargement temporaire';
            } else {
                $file_dest = $dir_dest . '/';
                if ($name_dest) {
                    if (count($files) > 1) {
                        $file_dest .= pathinfo($name_dest, PATHINFO_FILENAME) . '_' . $i . '.' . pathinfo($name_dest, PATHINFO_EXTENSION);
                    } else {
                        $file_dest .= $name_dest;
                    }
                } else {
                    if (preg_match('/^(.+)_tms[0-9]+(\..+)$/', $file_name, $matches)) {
                        $file_dest .= $matches[1] . $matches[2];
                    } else {
                        $file_dest .= $file_name;
                    }
                }

                $i = 0;
                $file_dest_tmp = $file_dest;
                while (file_exists($file_dest_tmp)) {
                    $i++;
                    $file_dest_tmp = pathinfo($file_dest, PATHINFO_DIRNAME) . '/' . pathinfo($file_dest, PATHINFO_FILENAME) . '_' . $i . '.' . pathinfo($file_dest, PATHINFO_EXTENSION);
                }
                $file_dest = $file_dest_tmp;

                if (!rename($dir . $file_name, $file_dest)) {
                    $errors[] = 'Echec du déplacement du fichier "' . $file_name . '" dans le dossier de destination';
                }
            }
        }

        return (count($errors) ? false : true);
    }

    public static function cleanTempFiles()
    {
        $dir = DOL_DATA_ROOT . '/bimpcore/tmp_files/';

        $dt = new DateTime();
        $dt->sub(new DateInterval('P2D'));
        $dt_str = $dt->format('Ymd');

        $files = scandir($dir);

        foreach ($files as $file) {
            if (in_array($file, array('.', '..'))) {
                continue;
            }

            if (!preg_match('/^\d{8}$/', $file)) {
                continue;
            }

            if (is_dir($dir . $file) && $file <= $dt_str) {
                self::removeAllFilesRecursively($dir . $file . '/', true);
            }
        }
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

        if (is_object($object)) {
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
        }

        return $errors;
    }

    public static function getDateTms($date)
    {
        if (is_null($date) || !$date) {
            return '';
        }

        $DT = new DateTime($date);
        return (int) $DT->format('U');
    }

    public static function getDateFromTimestamp($date_tms, $return_format = 'Y-m-d')
    {
        if (is_null($date_tms) || !$date_tms) {
            return '';
        }

        if (!is_int($date_tms)) {
            if (preg_match('/^[0-9]+$/', $date_tms)) {
                $date_tms = (int) $date_tms;
            } elseif (is_string($date_tms)) {
                $new_date_tms = strtotime($date_tms);

                if (!(int) $new_date_tms) {
                    BimpCore::addlog('BimpTools::getDateFromTimestamp() - tms possiblement invalide', 4, 'bimpcore', null, array(
                        'tms initial'  => (string) '"' . $date_tms . '"',
                        'tms converti' => $new_date_tms
                    ));
                }

                $date_tms = $new_date_tms;
            } else {
                BimpCore::addlog('Erreur BimpTools::getDateFromTimestamp() - tms invalide', 4, 'bimpcore', null, array(
                    'tms' => (string) $date_tms
                ));
                return '';
            }
        }

        return date($return_format, $date_tms);
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
                $bdb = BimpCache::getBdb();
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

            case 'fichinter':
                return BimpCache::getBimpObjectInstance('bimptechnique', 'BT_ficheInter', $id_object);
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

    public static function getNextRef($table, $field, $prefix = '', $numCaractere = null, &$errors = array())
    {

        $prefix = str_replace("{AA}", date('y'), $prefix);
        $prefix = str_replace("{MM}", date('m'), $prefix);

        if ($prefix) {
            $where = '`' . $field . '` LIKE \'' . $prefix . '%\'';
        } else {
            $where = '1';
        }


        $nbCaractTotal = strlen($prefix) + $numCaractere;
//        $max = BimpCache::getBdb()->getMax($table, $field, $where);
        if ($numCaractere > 0)
            $max = BimpCache::getBdb()->getMax($table, $field, $where . "  AND LENGTH(" . $field . ") = " . $nbCaractTotal);
        else
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
            if ($diff < 0) {
                $errors[] = 'Trop de caractéres pour l\'obtention d\'une nouvelle ref';
            } else {
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

        if (is_string($dir_tree)) {
            if (preg_match('/^\/*' . preg_quote($root_dir, '/') . '\/+(.+)$/', $dir_tree, $matches)) {
                $dir_tree = $matches[1];
            }
        }

        if (empty($dir_tree)) {
            return 'Nom du dossier à créer absent';
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

    public static function removeAllFilesRecursively($dir, $remove_dir = false)
    {
        if (is_dir($dir)) {
            if (!preg_match('/^.+\/$/', $dir)) {
                $dir .= '/';
            }
            foreach (scandir($dir) as $f) {
                if (in_array($f, array('.', '..'))) {
                    continue;
                }

                if (is_dir($dir . $f)) {
                    self::removeAllFilesRecursively($dir . $f, true);
                } else {
                    unlink($dir . $f);
                }
            }

            if ($remove_dir) {
                rmdir($dir);
            }
        }
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

    public static function getSqlFullSelectQuery($table, $fields, $filters = array(), $joins = array(), $params = array())
    {
        $params = self::overrideArray(array(
                    'order_by'        => null,
                    'order_way'       => 'ASC',
                    'extra_order_by'  => null,
                    'extra_order_way' => 'ASC',
                    'n'               => 0,
                    'p'               => 1,
                    'default_alias'   => 'a',
                    'where_operator'  => 'WHERE'
                        ), $params);

        $sql = self::getSqlSelect($fields, $params['default_alias']);
        $sql .= self::getSqlFrom($table, $joins, $params['default_alias']);

        if (!empty($filters)) {
            $sql .= self::getSqlWhere($filters, $params['default_alias'], $params['where_operator']);
        }

        $sql .= self::getSqlOrderBy($params['order_by'], $params['order_way'], $params['default_alias'], $params['extra_order_by'], $params['extra_order_way']);
        $sql .= self::getSqlLimit($params['n'], $params['p']);

        return $sql;
    }

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
                foreach ($joins as $key => $join) {
                    $alias = (isset($join['alias']) ? $join['alias'] : $key);
                    if (isset($join['table']) && isset($join['on'])) {
                        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . $join['table'] . ' ' . $alias . ' ON ' . $join['on'];
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

                if ($sql_filter !== '') {
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
            $sql_field = '';
            if (preg_match('/\./', $field)) {
                $sql_field = $field;
            } elseif (!is_null($default_alias) && $default_alias) {
                $sql_field = $default_alias . '.' . $field;
            } else {
                $sql_field = '`' . $field . '`';
            }
            $sql .= $sql_field;

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
                        $sql .= ' ESCAPE \'' . $escape_char . '\'';
                    }
                } elseif (isset($filter['in'])) {
                    if (is_array($filter['in'])) {
                        $has_null = false;
                        foreach ($filter['in'] as $key => $in_value) {
                            if (is_null($in_value)) {
                                $has_null = true;
                                unset($filter['in'][$key]);
                            }
                        }

                        if (count($filter['in'])) {
                            if ($has_null) {
                                $sql = '(' . $sql_field;
                            }
                            $sql .= ' IN ("';
                            $sql .= implode('","', $filter['in']);
                            $sql .= '")';
                            if ($has_null) {
                                $sql .= ' OR ' . $sql_field . ' IS NULL)';
                            }
                        } elseif ($has_null) {
                            $sql .= ' IS NULL';
                        } else {
                            $sql .= ' = 0 AND 0';
                        }
                    } elseif ($filter['in'] == "") {
                        $sql .= ' = 0 AND 0';
                    } else {
                        $sql .= ' IN (' . $filter['in'] . ')';
                    }
                } elseif (isset($filter['not_in'])) {
                    if (is_array($filter['not_in'])) {
                        $has_null = false;
                        foreach ($filter['not_in'] as $key => $not_in_value) {
                            if (is_null($not_in_value)) {
                                $has_null = true;
                                unset($filter['not_in'][$key]);
                            }
                        }

                        if (count($filter['not_in'])) {
                            if ($has_null) {
                                $sql = '(' . $sql_field;
                            }
                            $sql .= ' NOT IN ("';
                            $sql .= implode('","', $filter['not_in']);
                            $sql .= '")';
                            if ($has_null) {
                                $sql .= ' AND ' . $sql_field . ' IS NOT NULL)';
                            }
                        } elseif ($has_null) {
                            $sql .= ' IS NOT NULL';
                        } else {
                            $sql .= ' = 0 AND 0';
                        }
                    } else {
                        $sql .= ' NOT IN (' . $filter['not_in'] . ')';
                    }
                } else {
                    if (is_array($filter) && count($filter) > 0) {
                        $sql .= ' IN ("' . implode('","', $filter) . '")';
                    } elseif ((is_array($filter) && count($filter) == 0) || $filter == '') {
                        $sql .= ' = 0 AND 0';
                    } else {
                        BimpCore::addlog('Erreur filtre SQL invalide', Bimp_Log::BIMP_LOG_ERREUR, 'bimpcore', null, array(
                            'Filtre' => $filter
                        ));
                    }
                }
            } elseif ($filter === 'IS_NULL') {
                $sql .= ' IS NULL';
            } elseif ($filter === 'IS_NOT_NULL') {
                $sql .= ' IS NOT NULL';
            } elseif (is_string($filter) && preg_match('/^ *([<>!=]{1,2}) *(.+)$/', $filter, $matches)) {
                $sql .= ' ' . $matches[1] . ' \'' . $matches[2] . '\'';
            } else {
//                $sql .= ' = ' . (BimpTools::isString($filter) ? '\'' . $filter . '\'' : $filter);
                global $db;
                $sql .= ' = \'' . $db->escape($filter) . '\'';
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

    public static function mergeSqlFilter($filters, $sqlKey, $new_filter, $type = 'and')
    {
        if (isset($filters[$sqlKey])) {
            switch (strtolower($type)) {
                case 'or':
                    if (isset($filters[$sqlKey]['or_field'])) {
                        $filters[$sqlKey]['or_field'][] = $new_filter;
                    } else {
                        $current_filter = $filters[$sqlKey];
                        $filters[$sqlKey] = array('or_field' => array());
                        $filters[$sqlKey]['or_field'][] = $current_filter;
                        $filters[$sqlKey]['or_field'][] = $new_filter;
                    }
                    break;

                case 'and':
                    if (isset($filters[$sqlKey]['and'])) {
                        $filters[$sqlKey]['and'][] = $new_filter;
                    } else {
                        $current_filter = $filters[$sqlKey];
                        $filters[$sqlKey] = array('and' => array());
                        $filters[$sqlKey]['and'][] = $current_filter;
                        $filters[$sqlKey]['and'][] = $new_filter;
                    }
                default:
            }
        } else {
            $filters[$sqlKey] = $new_filter;
        }

        return $filters;
    }

    public static function mergeSqlFilters($filters, $new_filters, $type = 'and')
    {
        foreach ($new_filters as $filter_name => $new_filter) {
            $filters = self::mergeSqlFilter($filters, $filter_name, $new_filter, $type);
        }

        return $filters;
    }

    public static function getSqlSelectFullQuery($table, $fields, $filters = array(), $joins = array(), $params = array())
    {
        $params = self::overrideArray(array(
                    'order_by'        => null,
                    'order_way'       => 'ASC',
                    'extra_order_by'  => null,
                    'extra_order_way' => 'ASC',
                    'n'               => 0,
                    'p'               => 1,
                    'default_alias'   => 'a',
                    'where_operator'  => 'WHERE'
                        ), $params);

        $sql = self::getSqlSelect($fields, $params['default_alias']);
        $sql .= self::getSqlFrom($table, $joins, $params['default_alias']);

        if (!empty($filters)) {
            $sql .= self::getSqlWhere($filters, $params['default_alias'], $params['where_operator']);
        }

        $sql .= self::getSqlOrderBy($params['order_by'], $params['order_way'], $params['default_alias'], $params['extra_order_by'], $params['extra_order_way']);
        $sql .= self::getSqlLimit($params['n'], $params['p']);

        return $sql;
    }

    // Gestion de données:

    public static function checkValueByType($type, &$value, &$errors = array())
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

                if (is_bool($value))
                    $value = ($value ? 1 : 0);

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
                if (preg_match('/^(\d{2}):(\d{2}):?(\d{2})?$/', $value, $matches)) {
                    $value = $matches[1] . ':' . $matches[2] . (isset($matches[3]) ? ':' . $matches[3] : ':00');
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
            case 'object_filters':
                $value = BimpTools::json_decode_array($value, $errors);
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

        if (is_array($value)) {
            return BimpRender::renderRecursiveArrayContent($value);
        }

        return $value;
    }

    public static function displayAddress($address, $zip, $town, $dept, $pays, $icon = false, $single_line = false)
    {
        $html = '';

        if ($address) {
            $html .= $address . ($single_line ? ', ' : '<br/>');
        }

        if ($zip) {
            $html .= $zip;

            if ($town) {
                $html .= ' ' . $town;
            }
            $html .= ($single_line ? '' : '<br/>');
        } elseif ($town) {
            $html .= $town . ($single_line ? '' : '<br/>');
        }

        if (!$single_line && $dept) {
            $html .= $dept;

            if ($pays) {
                $html .= ', ' . $pays;
            }
        } elseif ($pays) {
            if ($single_line) {
                $html .= ', ';
            }
            $html .= $pays;
        }

        if ($html && $icon) {
            $html = BimpRender::renderIcon('fas_map-marker-alt', 'iconLeft') . $html;
        }

        return $html;
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

    public static function displayTimefromSeconds($total_seconds, $withDay = true, $withSecondes = true)
    {
        $html = '<span class="timer">';
        if ($total_seconds < 0) {
            $html .= '-';
            $total_seconds = $total_seconds * -1;
        }
        $timer = self::getTimeDataFromSeconds((int) $total_seconds);
        if (!$withDay) {
            $timer['hours'] += 24 * $timer['days'];
            $timer['days'] = 0;
        }
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
        if ($withSecondes && $timer['secondes'])
            $html .= $timer['secondes'] . ' sec ';
        $html .= '</span>';
        return $html;
    }

    // Gestion des dates: 

    public static function printDate($date, $balise = "span", $class = '', $format = 'd / m / Y H:i:s', $format_mini = 'd / m / Y')
    {
        if ($date == '')
            return '';

        if (is_string($date) && stripos($date, '-') > 0) {
            $date = new DateTime($date);
        }

        if (is_object($date)) {
            $date = $date->getTimestamp();
        }

        if (is_array($class)) {
            $class = explode(" ", $class);
        }

        $html = '<' . $balise;

        if ($format != $format_mini) {
            $html .= ' title="' . date($format, $date) . '"';
        }

        if ($class != '') {
            $html .= ' class="' . $class . '"';
        }
        $html .= '>' . date($format_mini, $date) . '</' . $balise . '>';

        return $html;
    }

    public static function getDayOfWeekLabel($day)
    {
        switch ($day) {
            case 1:
                return 'Lundi';

            case 2:
                return 'Mardi';

            case 3:
                return 'Mercredi';

            case 4:
                return 'Jeudi';

            case 5:
                return 'Vendredi';

            case 6:
                return 'Samedi';

            case 7:
                return 'Dimanche';
        }

        return '';
    }

    public static function getMonthLabel($month)
    {
        // todo

        return $month;
    }

    public static function getDayOfTwoWeeks()
    {
        $day = (int) date('w');
        $week = (int) date('W');

        if (floor($week / 2) * 2 == $week) { // sem. paire
            $day += 7;
        }

        return $day;
    }

    public static function isDateRangeValid($date_start, $date_end, &$errors = array())
    {
        if (!$date_start) {
            $errors[] = 'Date de début absente';
        }

        if (!$date_end) {
            $errors[] = 'Date de fin absente';
        }

        if ($date_end < $date_start) {
            $errors[] = 'Date de fin inférieure à la date de début';
        }

        return (count($errors) ? 0 : 1);
    }

    public static function getNextOpenDay($date)
    {
        require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
        $dt = new DateTime($date);
        $i = 0;
        while ($i < 100) {
            $dt->add(new DateInterval('P1D'));
            $tms = strtotime($dt->format('Y-m-d 00:00:00'));
            if (num_public_holiday($tms, $tms, '', 1) == 0) {
                break;
            }

            $i++;
        }

        return $dt->format('Y-m-d');
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

    public static function getTaxeRateByCode($code, $id_country = 1, $return_default = true)
    {
        $taxes = BimpCache::getTaxes($id_country, true, false, 'code');

        if (isset($taxes[$code])) {
            return (float) $taxes[$code];
        }

        if ($return_default) {
            return BimpCache::cacheServeurFunction('getDefaultTva');
            ;
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
                if (preg_match('/^(.+)\[.+\]$/', $email, $matches)) {
                    $email = $matches[1];
                }
                if ($name) {
                    $emails_str .= ($emails_str ? ', ' : '') . $name . ' <' . $email . '>';
                } else {
                    $emails_str .= ($emails_str ? ', ' : '') . $email;
                }
            }
        }

        return $emails_str;
    }

    public static function displayPhone($phone)
    {
        if (strlen($phone) == 10)
            return implode(' ', str_split($phone, 2));
        return $phone;
    }

    public static function cleanStringMultipleNewLines($string, $html = false)
    {
        $string = str_replace("\n\n", "\n", $string);
        $string = str_replace("\r\r", "\n", $string);
        $string = str_replace(CHR(13) . CHR(13), "\n", $string);
        $string = str_replace(CHR(10) . CHR(10), "\n", $string);
        $string = str_replace(PHP_EOL . PHP_EOL, "\n", $string);

        if ($html) {
            $string = self::replaceBr($string, '<br/>');
            $string = str_replace('<br/><br/>', '<br/>', $string);
        }

        return $string;
    }

    public static function isVowelFirst($string)
    {
        if (preg_match('/^[aàâäeéèêëiîïoôöuùûüyŷÿ](.*)$/', strtolower($string))) {
            return 1;
        }

        return 0;
    }

    public static function getOfTheLabel($label, $is_female = false)
    {
        if (self::isVowelFirst($label)) {
            return 'de l\'' . $label;
        } elseif ($is_female) {
            return 'de la ' . $label;
        }

        return 'du ' . $label;
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

        if (is_string($current_value)) {
            if (stripos($current_value, 'DOL_DATA_ROOT') !== false && defined('DOL_DATA_ROOT'))
                $current_value = str_replace('DOL_DATA_ROOT', DOL_DATA_ROOT, $current_value);
            if (stripos($current_value, 'PATH_TMP') !== false && defined('PATH_TMP'))
                $current_value = str_replace('PATH_TMP', PATH_TMP, $current_value);
        }

        return $current_value;
    }

    public static function setArrayValueFromPath(&$array, $path, $value)
    {
        if (is_null($path) || !$path) {
            return false;
        }

        $path = explode('/', $path);

        foreach ($path as $key) {
            if ($key === '') {
                continue;
            }
            if (isset($array[$key])) {
                $array = &$array[$key];
            } else {
                return false;
            }
        }

        if (isset($array)) {
            $array = $value;
            return true;
        }

        return false;
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
                    if (isset($array[$key]) && is_array($array[$key]) && is_array($value)) {
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

    public static function merge_array($array1, $array2 = null, $keep_keys = false)
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

        if ($keep_keys) {
            foreach ($array2 as $key => $value) {
                $array1[$key] = $value;
            }

            return $array1;
        }

        return array_merge($array1, $array2);
    }

    // Gestion des nombres: 

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

    public static function getAvatarImgSrc($text, $size, $color)
    {
        return 'http://placehold.it/' . $size . '/' . $color . '/fff&amp;text=' . $text;
    }

    public static function getBadge($text, $size = 35, $style = 'info', $popover = '')
    {
        return '<span class="badge badge-pill badge-' . $style . (($popover != '') ? ' bs-popover' : '') . '" ' . (($popover != '') ? BimpRender::renderPopoverData($popover) : '') . ' style="size:' . $size . '">' . $text . '</span>';
    }

    public static function displayMoneyValue($value, $currency = 'EUR', $with_styles = false, $truncate = false, $no_html = false, $decimals = 2, $round_points = false, $separator = ',', $spaces = true)
    {
        // $decimals: indiquer 'full' pour afficher toutes les décimales. 
        global $modeCSV;
        if ($modeCSV)
            return str_replace(".", ",", $value);
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
        if (!(string) $separator) {
            $separator = ',';
        }

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

    public static function displayFloatValue($value, $decimals = 2, $separator = ',', $with_styles = false, $truncate = false, $no_html = false, $round_points = false, $spaces = true)
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
        }

        // Ajustement du nombre de décimales:
        if ($decimals === 'full') {
            $decimals = (int) self::getDecimalesNumber($value);
        }

        // Arrondi: 
        $value = round($value, (int) $decimals);

        if ($value != round($base_price, 8)) {
            $hasMoreDecimals = true;
        }

        // Espaces entre les milliers: 
        if (!(string) $separator) {
            $separator = ',';
        }
        $number = number_format($value, $decimals, $separator, ($spaces ? ' ' : ''));

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

            $html .= $number;

            if ($hasMoreDecimals && $round_points) {
                $html .= '...';
            }

            if ($code) {
                $html .= ' ' . $code;
            }

            $html .= '</span>';
        } else {
            $html .= $number;

            if ($hasMoreDecimals && $round_points) {
                $html .= '...';
            }

            if ($code) {
                $html .= ' ' . $code;
            }

            $html = str_replace('&nbsp;', ' ', $html);
        }

        return $html;
    }

    // Divers:

    public static function getContext()
    {
        return BimpCore::getContext();
    }

    public static function setContext($context)
    {
        BimpCore::setContext($context);
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

    public static function makeUrlParamsFromArray($url_params)
    {
        $str = '';

        if (is_array($url_params)) {
            foreach ($url_params as $key => $value) {
                $str .= ($str ? '&' : '') . $key . '=' . urlencode($value);
            }
        }

        return $str;
    }

    public static function makeUrlFromConfig(BimpConfig $config, $path, $default_module, $default_controller)
    {
        $url = DOL_URL_ROOT . '/';

        $params = $config->get($path, null, false, 'array');

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

            if (is_array($url_params) && !empty($url_params)) {
                $params_str = self::makeUrlParamsFromArray($url_params);

                if ($params_str) {
                    if (!preg_match('/\?/', $url)) {
                        $url .= '?';
                    } else {
                        $url .= '&';
                    }
                    $url .= $params_str;
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

    public static function json_decode_array($json, &$errors = array())
    {
        if (is_null($json) || in_array($json, array('', '[]', '{}'))) {
            return array();
        }

        if (is_array($json)) {
            return $json;
        }
        $json = str_replace('\%', '%', $json);

        $result = json_decode($json, 1);

        if (json_last_error()) {
            $errors[] = 'Erreur décodage JSON: ' . json_last_error_msg();
            return array();
        }

        if ($result == '') {
            return array();
        }

        if (!is_array($result)) {
            $result = array($result);
        }

        return $result;
    }

    public static function randomPassword($length, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789')
    {
        for ($i = 0, $z = strlen($chars) - 1, $s = $chars[rand(0, $z)], $i = 1; $i != $length; $x = rand(0, $z), $s .= $chars[$x], $s = ($s[$i] == $s[$i - 1] ? substr($s, 0, -1) : $s), $i = strlen($s)) {
            
        }
        return $s;
    }

    public static function displayMemory(&$init_mem = null)
    {
        $mem = memory_get_usage();
        $html = 'Memory: ' . BimpTools::displayFloatValue($mem, 0);

        if (!is_null($init_mem)) {
            $diff = ($mem - $init_mem);

            if ((int) $diff) {
                $html .= ' (<span class="' . ($diff > 0 ? 'danger' : 'success') . '">' . ($diff > 0 ? '+' : '-') . ' ' . BimpTools::displayFloatValue(abs($diff), 0) . '</span>)';
            } else {
                $html .= ' (<span class="bold">+0</span>)';
            }
        }

        if (!is_null($init_mem)) {
            $init_mem = $mem;
        }

        return $html;
    }

    public static function createQrCodeImg($data, $dir, $file_name)
    {
        require_once(DOL_DOCUMENT_ROOT . "/synopsisphpqrcode/qrlib.php");

        if (!is_dir($dir)) {
            $err = BimpTools::makeDirectories($dir);

            if ($err) {
                return false;
            }
        }

        if (!preg_match('/^.+\.png$/', $file_name)) {
            $file_name .= '.png';
        }

        if (!preg_match('/^.+\/$/', $dir)) {
            $dir .= '/';
        }

        QRcode::png($data
                , $dir . $file_name
                , "L", 4, 2);
    }

    public static function displayBacktrace($nb_lines = 15)
    {
        return BimpRender::renderBacktrace(BimpTools::getBacktraceArray(debug_backtrace(null, $nb_lines)));
    }

    public static function clearAllPhpErrors()
    {
        $err = error_get_last();
        $n = 0;
        while (!empty($err)) {
            error_clear_last();
            $err = error_get_last();
            $n++;

            if ($n > 10000) {
                break;
            }
        }
    }

    public static function getMyCompanyLogoUrl($file = null)
    {
        if (is_null($file)) {
            global $mysoc;
            $file = $mysoc->logo;
        }

//        return DOL_URL_ROOT . '/viewimage.php?modulepart=mycompany&file=' . $file; => Selon changelog DOL16 (A vérifier) 
        return DOL_URL_ROOT . '/viewimage.php?modulepart=mycompany&file=logos/' . $file;
    }

    public static function verifCond($s)
    {
        // Adapté de dol_eval() : nécessaire pour contourner cetaines restrictions. 

        $errors = array();

        if (preg_match('/[^a-z0-9\s' . preg_quote('^$_+-.*>&|=!?():"\',/@', '/') . ']/i', $s)) {
            $errors[] = 'Caractères interdits';
        }
        if (strpos($s, '`') !== false) {
            $errors[] = '` interdit';
        }
        if (preg_match('/[^0-9]+\.[^0-9]+/', $s)) {
            $errors[] = 'Point interdit';
        }

        $forbiddenphpstrings = array('$$');
        $forbiddenphpstrings = array_merge($forbiddenphpstrings, array('_ENV', '_SESSION', '_COOKIE', '_GET', '_POST', '_REQUEST'));

        $forbiddenphpfunctions = array("exec", "passthru", "shell_exec", "system", "proc_open", "popen", "eval", "dol_eval", "executeCLI", "verifCond", "base64_decode");
        $forbiddenphpfunctions = array_merge($forbiddenphpfunctions, array("fopen", "file_put_contents", "fputs", "fputscsv", "fwrite", "fpassthru", "require", "include", "mkdir", "rmdir", "symlink", "touch", "unlink", "umask"));
        $forbiddenphpfunctions = array_merge($forbiddenphpfunctions, array("function", "call_user_func"));

        $forbiddenphpregex = 'global\s+\$|\b(' . implode('|', $forbiddenphpfunctions) . ')\b';

        do {
            $oldstringtoclean = $s;
            $s = str_ireplace($forbiddenphpstrings, '__forbiddenstring__', $s);
            $s = preg_replace('/' . $forbiddenphpregex . '/i', '__forbiddenstring__', $s);
        } while ($oldstringtoclean != $s);

        if (strpos($s, '__forbiddenstring__') !== false) {
            $errors[] = 'Présence d\'une chaîne interdite';
        }

        if (count($errors)) {
            BimpCore::addlog('Erreur condition', Bimp_Log::BIMP_LOG_URGENT, 'bimpcore', null, array(
                'Chaîne'  => $s,
                'Erreurs' => $errors
            ));
            return 0;
        }

        global $user, $conf;
        return eval('return ' . $s . ';');
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
        $rgb = array_map(function ($part) {
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

//        echo $h . ', ' . $s . ', ' . $l . '<br/>';

        if ($s == 0) {
            $r = $g = $b = 1;
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;

            $r = self::hue2rgb($p, $q, $h + 1 / 3);
            $g = self::hue2rgb($p, $q, $h);
            $b = self::hue2rgb($p, $q, $h - 1 / 3);

//            echo $r . ', ' . $g . ', ' . $b . '<br/>';
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

    public static function hex2rgb($hex)
    {
        $hex = str_replace("#", "", $hex);
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        return array($r, $g, $b);
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
                if (isset($trace['file'])) {
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
                        } elseif (is_array($arg)) {
                            $args .= print_r($arg, 1);
                        } else {
                            $args .= (string) $arg;
                        }
                    }
                }

                if (isset($trace['line']))
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

    public static function printBackTrace($depth)
    {
        $bt = debug_backtrace(null, $depth);
        echo BimpRender::renderBacktrace(self::getBacktraceArray($bt));
    }

    // Autres:

    public static $nbMax = 15 * 4;

    public static function lockNum($type, $nb = 0, $errors = array())
    {
        if (!(int) BimpCore::getConf('use_lock_num', null, 'bimpcommercial')) {
            return 1;
        }

//        return 1; //test 
        global $user, $langs;
        //il faut débuggé, si ca pose probléme c'est grave
//        if (BimpCore::isModeDev()) { // Flo: ça plante sur ma version de dev... 
//            return;
//        }

        if (in_array($type, static::$bloquages))//On a deja un verrous pour cette clef
            return true;


        $nb++;
        self::sleppIfBloqued($type, $nb);
        $file = static::getFileBloqued($type);

        if ($nb > static::$nbMax) {
            $errors[] = 'Dépassement du nombre de tentative lockNum';
            BimpCore::addlog('Probléme lockNum ' . $type, Bimp_Log::BIMP_LOG_URGENT, null, null, array('Errors' => $errors));
            die(print_r($errors, 1));
        }


        if (is_file($file)) {
            $errors[] = 'Fichier existant aprés sleepIfBlocked';
            BimpCore::addlog('Probléme lockNum ' . $type, Bimp_Log::BIMP_LOG_URGENT, null, null, array('Errors' => $errors));
            return static::lockNum($type, $nb, $errors);
        }


        $text = "Yes" . rand(0, 10000000) . $user->getFullName($langs);
        if (!file_put_contents($file, $text))
            die('droit sur fichier incorrect : ' . $file);
        usleep(1000000);
        $text2 = file_get_contents($file);
        if ($text == $text2) {
            if (defined('ID_ERP')) {
                $autreInstanceBloquage = static::isBloqued($type, true);
                if (!$autreInstanceBloquage) {
                    static::$bloquages[] = $type;
                    return 1;
                } else {
                    unlink($file);
                    $errors[] = 'Fichier lock d\'une autre instance : ' . $autreInstanceBloquage;
                    BimpCore::addlog('Probléme lockNum ' . $type, Bimp_Log::BIMP_LOG_URGENT, null, null, array('Errors' => $errors));
                    return static::lockNum($type, $nb, $errors);
                }
            } else {
                static::$bloquages[] = $type;
                return 1;
            }
        } else {
            $errors[] = 'Fichier diférent de celui attendue';
            BimpCore::addlog('Probléme lockNum ' . $type, Bimp_Log::BIMP_LOG_URGENT, null, null, array('Errors' => $errors));
            return static::lockNum($type, $nb, $errors);
        }
    }

//    public static function unlockNum($type){
//        
//    }
//    public static function bloqueDebloque($type, $bloque = true, $nb = 1)
//    {
//        $file = static::getFileBloqued($type);
//        if ($bloque) {
//            $msg = '';
//            if (!is_file($file)) {
//                $random = rand(0, 10000000);
//                $text = "Yes" . $random;
//                if (!file_put_contents($file, $text))
//                    die('droit sur fichier incorrect : ' . $file);
//                sleep(0.400);
//                $text2 = file_get_contents($file);
//                if ($text == $text2){
//                    static::$bloquages[] = $type;
//                    return 1;
//                }
//                else
//                    $msg = 'Fichier diférent de celui attendue';
//            }
//            else
//                $msg = 'Fichier deja existant';
//            //conflit
//            global $user;
//            mailSyn2("Conflit de ref évité", BimpCore::getConf('devs_email'), null, $user->login."  Attention : Un conflit de ref de type " . $type . " a été évité : ".$msg);
//            $nb++;
//            if ($nb > static::$nbMax)
//                die('On arrete tout erreur 445834834857');
//            self::sleppIfBloqued($type, $nb);
//            return static::bloqueDebloque($type, $bloque, $nb);
//        } elseif (is_file($file))//on ne debloque plus ici mais dans debloqueAll
//            return 1;//unlink($file);
//    }

    public static function deloqueAll(&$debloquer = array())
    {
        $i = 0;
        foreach (static::$bloquages as $id => $type) {
            if (!unlink(static::getFileBloqued($type)))
                BimpCore::addlog('Suppression fichier de lock impossible ' . static::getFileBloqued($type), Bimp_Log::BIMP_LOG_URGENT);
            unset(static::$bloquages[$id]);
            $i++;
            $debloquer[] = $type;
        }
        return $i;
    }

    public static function getFileBloqued($type)
    {
        return static::getDirBloqued() . $type . (defined('ID_ERP') ? '_' . ID_ERP : '') . ".txt";
    }

    public static function getDirBloqued()
    {
        $folder = DOL_DATA_ROOT . '/bloqueFile/';
        if (!is_dir($folder))
            mkdir($folder);
        return $folder;
    }

    public static function isBloqued($type, $notThis = false)
    {
        $dir = static::getDirBloqued();
        $files = scandir($dir);
        foreach ($files as $file) {
            if (stripos($file, $type) === 0 && (!$notThis || stripos($file, '_' . ID_ERP) === false))
                return $file;
        }
        return false;
    }

    public static function sleppIfBloqued($type, $nb = 0)
    {
        $nb++;
        $fichierBloquant = static::isBloqued($type);
        if ($fichierBloquant) {
            if ($nb < static::$nbMax) {
                usleep(250000);
                return static::sleppIfBloqued($type, $nb);
            } else {
                $text = "sleppIfBloqued() : bloquage de plus de " . static::$nbMax / 4 . " secondes";
//                static::bloqueDebloque($type, false, $nb);
//                unlink(static::getFileBloqued($type));
                BimpCore::addlog($text, Bimp_Log::BIMP_LOG_URGENT, 'bimpcore', null, array(
                    'Type' => $type,
                    'File' => $fichierBloquant
                ));
                global $db;
                $db::stopAll('sleppIfBloqued');
                return 0;
            }
        } else
            return 0;
    }

    public static function getUserEmailOrSuperiorEmail($id_user, $allow_default = true)
    {
        $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user);
        if (BimpObject::objectLoaded($user)) {
            return $user->getEmailOrSuperiorEmail($allow_default);
        }

        if ($allow_default) {
            return (string) BimpCore::getConf('default_user_email', null);
        }

        return '';
    }

    public static function mailGrouper($to, $from, $msg)
    {
        $dir = PATH_TMP . "/bimpcore/mailsGrouper/";
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

    public static function sendMailGrouper()
    {
        $dir = PATH_TMP . "/bimpcore/mailsGrouper/";
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
        return "OK " . $i . ' mails envoyés';
    }

    public function envoieMailGrouper()
    {
        $this->output = static::sendMailGrouper();
        return 0;
    }

    public static function isModuleDoliActif($module)
    {
        global $conf;
        
        if (stripos($module, 'MAIN_MODULE_') === false)
            $module = 'MAIN_MODULE_' . $module;
        
        if (isset($conf->global->$module) && $conf->global->$module)
            return 1;
        
        return 0;
    }

    public static function sendSmsAdmin($text, $tels = array('0628335081', '0686691814'))
    {
        $errors = array();

        global $conf;

        if (!empty($conf->global->MAIN_DISABLE_ALL_SMS)) {
            $errors[] = 'Envoi des SMS désactivé pour le moment';
        } else {
            require_once(DOL_DOCUMENT_ROOT . "/core/class/CSMSFile.class.php");
            foreach ($tels as $tel) {
                $tel = traiteNumMobile($tel);
                $smsfile = new CSMSFile($tel, 'BIMP ADMIN', $text);
                if (!$smsfile->sendfile()) {
                    $errors[] = 'Echec de l\'envoi du sms';
                }
            }
        }

        return $errors;
    }

    public static function getDataLightWithPopover($data, $lenght = 5)
    {
        global $modeCSV;
        if ($modeCSV) {
            return $data;
        } else {
            $data = preg_replace('`[<br />]*$`', '', $data);
            $data = preg_replace('`[<br>]*$`', '', $data);
            $data = preg_replace('`[<br/>]*$`', '', $data);
            if(strlen($data) > $lenght){
                $return = '<span class=" bs-popover"';
                $return .= BimpRender::renderPopoverData($data, 'top', true);
                $return .= '>';
                $return .= substr(strip_tags($data), 0, $lenght) . '...';
                $return .= '</span>';
                return $return;
            }
            else
                return $data;
        }
    }
}

if (!function_exists('mime_content_type')) {

    function mime_content_type($filename)
    {

        $mime_types = array(
            'txt'  => 'text/plain',
            'htm'  => 'text/html',
            'html' => 'text/html',
            'php'  => 'text/html',
            'css'  => 'text/css',
            'js'   => 'application/javascript',
            'json' => 'application/json',
            'xml'  => 'application/xml',
            'swf'  => 'application/x-shockwave-flash',
            'flv'  => 'video/x-flv',
            // images
            'png'  => 'image/png',
            'jpe'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg'  => 'image/jpeg',
            'gif'  => 'image/gif',
            'bmp'  => 'image/bmp',
            'ico'  => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif'  => 'image/tiff',
            'svg'  => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
            // archives
            'zip'  => 'application/zip',
            'rar'  => 'application/x-rar-compressed',
            'exe'  => 'application/x-msdownload',
            'msi'  => 'application/x-msdownload',
            'cab'  => 'application/vnd.ms-cab-compressed',
            // audio/video
            'mp3'  => 'audio/mpeg',
            'qt'   => 'video/quicktime',
            'mov'  => 'video/quicktime',
            // adobe
            'pdf'  => 'application/pdf',
            'psd'  => 'image/vnd.adobe.photoshop',
            'ai'   => 'application/postscript',
            'eps'  => 'application/postscript',
            'ps'   => 'application/postscript',
            // ms office
            'doc'  => 'application/msword',
            'rtf'  => 'application/rtf',
            'xls'  => 'application/vnd.ms-excel',
            'ppt'  => 'application/vnd.ms-powerpoint',
            // open office
            'odt'  => 'application/vnd.oasis.opendocument.text',
            'ods'  => 'application/vnd.oasis.opendocument.spreadsheet',
        );

        $ext = strtolower(array_pop(explode('.', $filename)));
        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        } elseif (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME);
            $mimetype = finfo_file($finfo, $filename);
            finfo_close($finfo);
            return $mimetype;
        } else {
            return 'application/octet-stream';
        }
    }
}
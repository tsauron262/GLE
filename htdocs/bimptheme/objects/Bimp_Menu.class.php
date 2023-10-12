<?php

class Bimp_Menu extends BimpObject
{

    public static $elements = null;
    public static $userTypes = array(
        0 => 'Interne',
        1 => 'Externe',
        2 => 'Tous'
    );

    // Droits User: 

    public function canCreate()
    {
        return (int) BimpCore::isUserDev();
    }

    public function canEdit()
    {
        return (int) BimpCore::isUserDev();
    }

    public function canView()
    {
        return (int) BimpCore::isUserDev();
    }

    public function canDelete()
    {
        return (int) BimpCore::isUserDev();
    }

    public function canSetAction($action)
    {
        return (int) BimpCore::isUserDev();
    }

    // Getters params: 

    public function getListsHeaderButtons()
    {
        $buttons = array();

        if ($this->canSetAction('addSubItem')) {
            $label = '';

            if ($this->isLoaded()) {
                $label = 'Ajouter une sous-ligne';
            } else {
                $label = 'Ajouter une ligne de menu principale';
            }
            $buttons[] = array(
                'label'   => $label,
                'icon'    => 'fas_plus-circle',
                'onclick' => $this->getJsActionOnclick('addSubItem', array(), array(
                    'form_name' => 'add_sub_item'
                ))
            );
        }

        if ($this->isActionAllowed('displayFullTree') && $this->canSetAction('displayFullTree')) {
            $buttons[] = array(
                'label'   => 'Afficher l\'arborescence complète',
                'icon'    => 'fas_stream',
                'onclick' => $this->getJsActionOnclick('displayFullTree', array(), array())
            );
        }

        if ($this->isActionAllowed('updateFullMenuSql') && $this->canSetAction('updateFullMenuSql')) {
            $buttons[] = array(
                'label'   => 'Re-générer le fichier SQL du menu complet',
                'icon'    => 'fas_redo',
                'onclick' => $this->getJsActionOnclick('updateFullMenuSql', array(), array(
                    'confirm_msg' => 'Veillez confirmer. ATTENTION: vous devez être sûr que qu\\\'il s\\\'agit d\\\'une version complète du menu pour éviter une perte de données'
                ))
            );
        }

        return $buttons;
    }

    public function getListExtraBtn()
    {
        $buttons = array();

        $title = '';

        if ($this->isLoaded()) {
            $title = 'Sous-lignes de la section "' . $this->getData('titre') . '"';
        } else {
            $title = 'Sous-lignes principales';
        }

        $buttons[] = array(
            'label'   => 'Sous-lignes',
            'icon'    => 'fas_bars',
            'onclick' => $this->getJsLoadModalCustomContent('renderItemsList', $title, 'array', 'large')
        );

        return $buttons;
    }

    public function getPositionsFilters()
    {
        return array(
            'fk_menu' => (int) $this->getData('fk_menu')
        );
    }

    // Rendus HTML: 

    public function renderTree()
    {
        $html = '';

        $html .= $this->getData('titre') . '<br/>';

        $children = BimpCache::getBimpObjectObjects('bimptheme', 'Bimp_Menu', array(
                    'fk_menu' => (int) $this->id
        ));

        if (!empty($children)) {
            $html .= '<div style="padding-left: 20px">';
            foreach ($children as $child) {
                $html .= $child->renderTree();
            }
            $html .= '</div>';
        }

        return $html;
    }

    public function renderItemsList()
    {
        $id_parent = ($this->isLoaded() ? $this->id : 0);

        $title = '';
        if ($this->isLoaded()) {
            $title = 'Sous-lignes de la section "' . $this->getData('titre') . '"';
        } else {
            $title = 'Lignes de menu principales';
        }
        $list = new BC_ListTable($this, 'default', 1, null, $title, 'fas_bars');
        $list->addFieldFilterValue('fk_menu', $id_parent);
        return $list->renderHtml();
    }

    // Traitements: 

    public function checkCodePath(&$final_code_path = '', $update = false)
    {
        $errors = array();

        $code_pathes = explode('/', $this->getData('code_path'));
        $code = array_pop($code_pathes);

        if (!$code) {
            $errors = 'Code de la ligne de menu absent';
        }

        $id_parent = (int) $this->getData('fk_menu');
        if ($id_parent) {
            $parent = BimpCache::getBimpObjectInstance('bimptheme', 'Bimp_Menu', $id_parent);
            if (!BimpObject::objectLoaded($parent)) {
                $errors[] = 'La ligne de menu parente #' . $id_parent . ' n\'existe plus';
            } else {
                $parent_code_path = $parent->getData('code_path');

                if (!$parent_code_path) {
                    $errors[] = 'Code de la ligne de menu parente absent';
                } else {
                    $final_code_path = $parent_code_path . '/' . $code;

                    $id = (int) self::getBdb()->getValue('menu', 'rowid', 'code_path = \'' . $final_code_path . '\'');

                    if ($id && (!$this->isLoaded() || $id !== (int) $this->id)) {
                        $errors[] = 'Une ligne de menu existe déjà pour le chemin "' . $final_code_path . '"';
                    } else {
                        if ($update) {
                            $errors = $this->updateField('code_path', $final_code_path);
                            if (!count($errors)) {
                                $this->checkChildrenCodePath();
                            }
                        } else {
                            $this->set('code_path', $final_code_path);
                        }
                    }
                }
            }
        } else {
            $final_code_path = $code;
        }

        return $errors;
    }

    public function checkChildrenCodePath()
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            $children = BimpCache::getBimpObjectObjects('bimptheme', 'Bimp_Menu', array(
                        'fk_menu' => $this->id
            ));

            foreach ($children as $child) {
                $final_code_path = '';
                $child_errors = $child->checkCodePath($final_code_path, true);

                if (count($child_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($child_errors, 'Ligne enfant n°' . $child->getData('position') . ' - ' . $child->getData('titre'));
                }
            }
        }

        return $errors;
    }

    public static function getNextItemPosition($id_parent = 0)
    {
        $where .= 'menu_handler = \'bimptheme\'';
        $where .= ' AND fk_menu = ' . $id_parent;
        if (!$id_parent) {
            $where .= ' OR fk_menu IS NULL OR fk_menu <= 0';
        }
        return ((int) self::getBdb()->getMax('menu', 'position', 'fk_menu') + 1);
    }

    public static function addMenuItem($title, $icon, $code, $url, $parent_path = '', $module = '', $bimp_object = '', $perms = '', $enabled = '')
    {
        $errors = array();

        $id_parent = 0;
        if ($parent_path) {
            $id_parent = (int) self::getBdb()->getValue('menu', 'rowid', 'code_path = \'' . $parent_path . '\'');

            if (!$id_parent) {
                $errors[] = 'Ligne parente non trouvée pour le chemin "' . $parent_path . '"';
            }
        }

        if (!$module) {
            $errors[] = 'Module absent';
        }

        if ($bimp_object) {
            if (strpos($bimp_object, '/') === false) {
                $bimp_object = $module . '/' . $bimp_object;
            }
        }

        if (!count($errors)) {
            $item = BimpObject::createBimpObject('bimptheme', 'Bimp_Menu', array(
                        'titre'       => $title,
                        'code_path'   => $code,
                        'fk_menu'     => $id_parent,
                        'position'    => self::getNextItemPosition($id_parent),
                        'url'         => $url,
                        'perms'       => $perms,
                        'enabled'     => $enabled,
                        'bimp_icon'   => $icon,
                        'bimp_object' => $bimp_object,
                        'active'      => 1
                            ), true, $errors);
        }
    }

    public static function getMenuItems($id_parent = 0, $active_only = true, $enabled = true, $check_perms = true)
    {
        $handler = BimpCore::getConf('menu_handler', null, 'bimptheme');
        $handlers = array($handler);

        if ($handler === 'auguria') {
            $handlers[] = 'All';
        }

        $items = array();
        $instance = BimpObject::getInstance('bimptheme', 'Bimp_Menu');

        $filters = array(
            'menu_handler' => array(
                'in' => $handlers
            ),
            'fk_menu'      => $id_parent
        );

        if ($active_only) {
            $filters['active'] = 1;
        }

        $rows = $instance->getList($filters, null, null, 'position', 'ASC', 'array');

        foreach ($rows as $r) {
            if (!self::checkItem($r, $active_only, $enabled, $check_perms)) {
                continue;
            }

            $items[(int) $r['rowid']] = $r;
            $items[(int) $r['rowid']]['sub_items'] = self::getMenuItems((int) $r['rowid'], $active_only, $enabled, $check_perms);
        }
        return $items;
    }

    public static function checkItem($item, $active_only = true, $enabled = true, $check_perms = true)
    {
        if ($active_only && !(int) $item['active']) {
            return false;
        }

        if ($enabled) {
            if (isset($item['enabled']) && $item['enabled']) {
                if (!self::verifCond($item['enabled'], $item['code_path'])) {
                    return false;
                }
            }
            if (isset($item['bimp_module']) && $item['bimp_module'] && strpos($item['bimp_module'], 'bimp') === 0) {
                if (!BimpCore::isModuleActive($item['bimp_module'])) {
                    return false;
                }
            }
        }
        if ($check_perms && isset($item['perms']) && $item['perms']) {
            if (!self::verifCond($item['perms'], $item['code_path'])) {
                $allowed = BimpTools::getArrayValueFromPath($item, 'allowed_users', '');
                if ($allowed) {
                    global $user;
                    if (strpos($allowed, '[' . $user->id . ']') !== false) {
                        return true;
                    }
                }
                return false;
            }
        }

        return true;
    }

    public static function checkItems($items, $active_only = true, $enabled = true, $check_perms = true)
    {
        $return = array();

        foreach ($items as $id => $item) {
            if (!self::checkItem($item, $active_only, $enabled, $check_perms)) {
                continue;
            }

            if (isset($item['sub_items'])) {
                $item['sub_items'] = self::checkItems($item['sub_items'], $active_only, $enabled, $check_perms);
            }

            if ($active_only || $enabled || $check_perms) {
                if ((!isset($item['url']) || !$item['url']) && (!isset($item['sub_items']) || empty($item['sub_items']))) {
                    continue;
                }
            }
            $return[$id] = $item;
        }

        return $return;
    }

    public static function getFullMenu($handler = null, $active_only = true, $enabled = true, $check_perms = true)
    {
        if (is_null($handler)) {
            $handler = BimpCore::getConf('menu_handler', null, 'bimptheme');
        }

        $handlers = array($handler);
        if ($handler === 'auguria') {
            $handlers[] = 'All';
        }

        $items = array();

        if (BimpCore::getConf('use_cache_server_for_menu', null, 'bimptheme')) {
            $items = BimpCache::getCacheServeur('bimpmenu_' . $handler . '_items');
        }

        if (empty($items)) {
            $items = self::getMenuItems(0, false, false, false);

            if (BimpCore::getConf('use_cache_server_for_menu', null, 'bimptheme')) {
                BimpCache::setCacheServeur('bimpmenu_' . $handler . '_items', $items);
            }
        }

        if (!empty($items)) {
            $items = self::checkItems($items, $active_only, $enabled, $check_perms);
        }

        return $items;
    }

    public function getInsertSql(&$errors = array())
    {
        $sql = '';

        if ($this->isLoaded()) {
            $code_path = '';
            $parent_code_path = '';

            if ((int) $this->getData('fk_menu')) {
                $parent = BimpCache::getBimpObjectInstance('bimptheme', 'Bimp_Menu', (int) $this->getData('fk_menu'));

                if (BimpObject::objectLoaded($parent)) {
                    $parent->checkCodePath($parent_code_path, true);
                } else {
                    $errors[] = 'La ligne de menu parente #' . $this->getData('fk_menu') . ' n\'existe plus';
                }
            }
            $this->checkCodePath($code_path, true);

            if (!count($errors)) {
                $sql = 'INSERT INTO `llx_menu` (`menu_handler`, `fk_menu`, `position`, `url`, `titre`, `perms`, `enabled`, `code_path`, `active`, `bimp_module`, `bimp_icon`, `bimp_object`)';
                $sql .= ' VALUES (';
                $sql .= '\'' . $this->getData('menu_handler') . '\',';

                if ($parent_code_path) {
                    $sql .= '(SELECT m2.rowid FROM llx_menu m2 WHERE m2.code_path = \'' . $parent_code_path . '\' AND m2.menu_handler = \'' . $this->getData('menu_handler') . '\'), ';
                } else {
                    $sql .= '0, ';
                }

                $sql .= (int) $this->getData('position') . ', ';
                $sql .= '\'' . addslashes($this->getData('url')) . '\', ';
                $sql .= '\'' . addslashes($this->getData('titre')) . '\', ';
                $sql .= '\'' . addslashes($this->getData('perms')) . '\', ';
                $sql .= '\'' . addslashes($this->getData('enabled')) . '\', ';
                $sql .= '\'' . addslashes($this->getData('code_path')) . '\', ';
                $sql .= (int) $this->getData('active') . ', ';
                $sql .= '\'' . addslashes($this->getData('bimp_module')) . '\', ';
                $sql .= '\'' . addslashes($this->getData('bimp_icon')) . '\', ';
                $sql .= '\'' . addslashes($this->getData('bimp_object')) . '\'';
                $sql .= ');' . "\n";

                $children = BimpCache::getBimpObjectObjects('bimptheme', 'Bimp_Menu', array(
                            'fk_menu' => (int) $this->id
                ));

                if (!empty($children)) {
                    foreach ($children as $child) {
                        $child_errors = array();
                        $sql .= $child->getInsertSql($child_errors);

                        if (count($child_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($child_errors, 'Ligne enfant n°' . $child->getData('position') . ' - ' . $child->getData('titre'));
                        }
                    }
                }
            }
        }

        return $sql;
    }

    public static function verifCond($s, $code_path)
    {
        return BimpTools::verifCond($s);
    }

    // Actions: 

    public function actionAddSubItem($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Ajout effectué';

        $id_parent = 0;
        $parent_path = '';
        $code = BimpTools::getArrayValueFromPath($data, 'code', '');
        if (!$code) {
            $errors[] = 'Code absent';
        }

        if (!count($errors)) {
            unset($data['code']);
            if ($this->isLoaded()) {
                $id_parent = $this->id;
                $parent_path = $this->getData('code_path');
            }
            $data['menu_handler'] = 'bimptheme';
            $data['fk_menu'] = $id_parent;
            $data['code_path'] = ($parent_path ? $parent_path . '/' : '') . $code;

            BimpObject::createBimpObject('bimptheme', 'Bimp_Menu', $data, true, $errors, $warnings);
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionGenerateSqlFile($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $sql = '';

        $ids = BimpTools::getArrayValueFromPath($data, 'id_objects', array());

        if (empty($ids)) {
            $errors[] = 'Aucune ligne de menu sélectionnée';
        } else {
            foreach ($ids as $id_menu) {
                $menu = BimpCache::getBimpObjectInstance('bimptheme', 'Bimp_Menu', $id_menu);

                if (BimpObject::objectLoaded($menu)) {
                    $line_errors = array();
                    $insert_sql = $menu->getInsertSql($line_errors);
                    if ($insert_sql) {
                        $sql .= $insert_sql;
                    }

                    if (count($line_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n°' . $menu->getData('position') . ' - ' . $menu->getData('titre'));
                    }
                } else {
                    $warnings[] = 'La ligne de menu #' . $id_menu . ' n\'existe pas';
                }
            }
        }

        return array(
            'errors'      => $errors,
            'warnings'    => $warnings,
            'modal_title' => 'SQL INSERT lignes de menu sélectionnées',
            'modal_html'  => '<pre>' . $sql . '</pre>'
        );
    }

    public function actionCheckCodePathes($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Codes chemins vérifiés';

        $ids = BimpTools::getArrayValueFromPath($data, 'id_objects', array());

        if (empty($ids)) {
            $errors[] = 'Aucune ligne de menu sélectionnée';
        } else {
            foreach ($ids as $id_menu) {
                $menu = BimpCache::getBimpObjectInstance('bimptheme', 'Bimp_Menu', $id_menu);

                if (BimpObject::objectLoaded($menu)) {
                    $code_path = $this->getData('code_path');
                    $final_code_path = '';
                    $line_errors = $menu->checkCodePath($final_code_path, true);

                    if (count($line_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n°' . $menu->getData('position') . ' - ' . $menu->getData('titre'));
                    }

                    if ($code_path == $final_code_path) {
                        $children_errors = $menu->checkChildrenCodePath();

                        if (count($children_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($children_errors, 'Ligne n°' . $menu->getData('position') . ' - ' . $menu->getData('titre'));
                        }
                    }
                } else {
                    $warnings[] = 'La ligne de menu #' . $id_menu . ' n\'existe pas';
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionDisplayFullTree($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $html = '';

        $rows = $this->getList(array(
            'menu_handler' => 'bimptheme',
            'active'       => 1,
            'fk_menu'      => 0
                ), null, null, 'position', 'ASC', 'array', array('rowid'));

        if (empty($rows)) {
            $errors[] = 'Aucune ligne de menu trouvée pour bimptheme';
        } else {
            foreach ($rows as $r) {
                $menu = BimpCache::getBimpObjectInstance('bimptheme', 'Bimp_Menu', (int) $r['rowid']);

                if (BimpObject::objectLoaded($menu)) {
                    $html .= $menu->renderTree();
                } else {
                    $warnings[] = 'La ligne de menu #' . $r['rowid'] . ' n\'existe pas';
                }
            }
        }

        return array(
            'errors'      => $errors,
            'warnings'    => $warnings,
            'modal_title' => 'Arborescence menu BimpThème',
            'modal_html'  => $html
        );
    }

    public function actionUpdateFullMenuSql($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Fichier mis à jour avec succès';
        $html = '';

        $sql = '';
        $menu_instance = BimpObject::getInstance('bimptheme', 'Bimp_Menu');

        $rows = $menu_instance->getList(array(
            'menu_handler' => 'bimptheme',
            'fk_menu'      => 0
                ), null, null, 'position', 'ASC', 'array', array('rowid'));

        foreach ($rows as $r) {
            $menu = BimpCache::getBimpObjectInstance('bimptheme', 'Bimp_Menu', (int) $r['rowid']);

            if (BimpObject::objectLoaded($menu)) {
                $line_errors = array();
                $insert_sql = $menu->getInsertSql($line_errors);
                if ($insert_sql) {
                    $sql .= $insert_sql;
                }

                if (count($line_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n°' . $menu->getData('position') . ' - ' . $menu->getData('titre'));
                }
            }
        }

        if (!$sql) {
            $errors[] = 'Aucune ligne à insérer trouvée';
        }

        if (!count($errors)) {
            $dir = DOL_DOCUMENT_ROOT . '/bimptheme/sql';
            if (is_dir($dir)) {
                $version = 0;
                foreach (scandir($dir) as $f) {
                    if (strpos($f, 'fullmenu') !== 0) {
                        continue;
                    }

                    if (preg_match('/^fullmenu_(\d+)\.sql$/', $f, $matches)) {
                        if ((int) $matches[1] > $version) {
                            $version = (int) $matches[1];
                        }
                        if (!unlink($dir . '/' . $f)) {
                            $warnings[] = 'Echec de la suppression du fichier "' . $f . '"';
                        }
                    }
                }

                $version++;
                if (!file_put_contents($dir . '/' . 'fullmenu_' . $version . '.sql', $sql)) {
                    $html .= BimpRender::renderAlerts('Echec de la création du fichier "fullmenu_' . $version . '.sql"');
                    $html .= '<br/><br/>Création manuel du fichier "fullmenu_' . $version . '.sql" : <br/><br/>';
//                        $html .= '<span class="btn btn-default" onclick="selectElementText($(this).parent().find(\'pre.sql_content\'))">';
//                        $html .= 'Tout sélectionner';
//                        $html .= '</span>';
                    $html .= '<pre class="sql_content">';
                    $html .= $sql;
                    $html .= '</pre>';
                } else {
                    BimpCore::setConf('full_menu_version', $version, 'bimptheme');
                    $html .= '<div class = "warning" style = "font-size: 16px; margin: 60px 0; text-align: center">';
                    $html .= BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft');
                    $html .= 'IMPORTANT: ne pas oublier de télécharger le fichier "fullmenu_' . $version . '.sql" du serveur et de supprimer tous les autres fichiers "fullmenu_xxx.sql" des sources';
                    $html .= '</div>';
                }
            } else {
                $errors[] = 'Dossier sql absent du module "bimptheme"';
            }
        }

        return array(
            'errors'     => $errors,
            'warnings'   => $warnings,
            'modal_html' => $html
        );
    }

    // Overrides: 

    public function validate()
    {
        // Check du code path (+ parent): 
        $errors = $this->checkCodePath();

        if (!count($errors)) {
            $errors = parent::validate();
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $init_code_path = $this->getInitData('code_path');

        $errors = parent::update($warnings, $force_update);

        if (!count($errors)) {
            if ($init_code_path !== $this->getData('code_path')) {
                $this->checkChildrenCodePath();
            }
        }

        return $errors;
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $id = $this->id;

        $errors = parent::delete($warnings, $force_delete);

        if (!count($errors)) {
            $menus = BimpCache::getBimpObjectObjects('bimptheme', 'Bimp_Menu', array(
                        'fk_menu' => $id
            ));

            if (!empty($menus)) {
                foreach ($menus as $id_menu => $menu) {
                    $menu_warnings = array();
                    $menu_errors = $menu->delete($menu_warnings, true);

                    if (count($menu_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($menu_errors, 'Echec de la suppression de la sous-ligne #' . $id_menu);
                    }

                    if (count($menu_warnings)) {
                        $warnings[] = BimpTools::getMsgFromArray($menu_warnings, 'Erreurs lors de la suppression de la sous-ligne #' . $id_menu);
                    }
                }
            }
        }

        return $errors;
    }

    // Méthodes statiques: 

    public static function getFullMenuUpdateVersion()
    {
        if ((int) BimpCore::getConf('use_full_menu', null, 'bimptheme')) {
            $cur_version = (int) BimpCore::getConf('full_menu_version', 0, 'bimptheme');

            $dir = DOL_DOCUMENT_ROOT . '/bimptheme/sql';
            if (is_dir($dir)) {
                $version = 0;
                foreach (scandir($dir) as $f) {
                    if (strpos($f, 'fullmenu') !== 0) {
                        continue;
                    }

                    if (preg_match('/^fullmenu_(\d+)\.sql$/', $f, $matches)) {
                        if ((int) $matches[1] > $version) {
                            $version = (int) $matches[1];
                        }
                    }
                }

                if ($version > $cur_version) {
                    return $version;
                }
            }
        }

        return 0;
    }

    public static function updateFullMenu()
    {
        $errors = array();
        $version = self::getFullMenuUpdateVersion();

        if ($version) {
            $file = DOL_DOCUMENT_ROOT . '/bimptheme/sql/fullmenu_' . $version . '.sql';

            if (!file_exists($file)) {
                $errors[] = 'Fichier non trouuvé';
            } else {
                $sql = file_get_contents($file);
                if (!$sql) {
                    $errors[] = 'Fichier vide';
                } else {
                    $bdb = BimpCache::getBdb();
                    $bdb->db->begin();

                    if ($bdb->delete('menu', 'menu_handler = \'bimptheme\'') <= 0) {
                        $errors[] = 'Echec de la suppression des éléments actuels du menu bimptheme';
                    } else {
                        $bdb->executeFile($file, $errors);
                    }
                }

                if (!count($errors)) {
                    $bdb->db->commit();
                } else {
                    $bdb->db->rollback();
                }

                BimpCore::setConf('full_menu_version', $version, 'bimptheme');
            }
        }

        return $errors;
    }
}

<?php

class BDS_Process extends BimpObject
{

    public static $types = array(
        'import' => array('label' => 'Import', 'icon' => 'fas_sign-in-alt'),
        'export' => array('label' => 'Export', 'icon' => 'fas_sign-out-alt'),
        'sync'   => array('label' => 'Synchronisation', 'icon' => 'fas_sync'),
        'ws'     => array('label' => 'Web service', 'icon' => 'fas_globe'),
        'other'  => array('label' => 'Autre', 'icon' => 'fas_cogs'),
    );

    // Droits users:

    public function canCreate()
    {
        global $user;
        return (int) $user->admin;
    }

    public function canEdit()
    {
        return $this->canCreate();
    }

    public function canDelete()
    {
        return $this->canCreate();
    }

    public function canSetAction($action)
    {
        switch ($action) {
            case 'installProcess':
            case 'updateProcess':
                if (BimpCore::isUserDev()) {
                    return 1;
                }
                return 0;
        }
        return parent::canSetAction($action);
    }

    public function isActionAllowed($action, &$errors = [])
    {
        switch ($action) {
            case 'updateProcess':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }

                $process_class = $this->getProcessClassName();

                if (!$process_class) {
                    $errors[] = 'Classe absente';
                    return 0;
                }

                if (!class_exists($process_class)) {
                    $errors[] = 'Classe invalide';
                    return 0;
                }

                if ((int) $this->getData('version') >= (int) $process_class::$current_version) {
                    $errors[] = 'Process à jour';
                    return 0;
                }

                return 1;
        }

        return parent::isActionAllowed($action, $errors);
    }

    // Getters booléens: 

    public function isFieldEditable($field, $force_edit = false)
    {
        switch ($field) {
            case 'name':
                if (!$force_edit && $this->isLoaded()) {
                    return 0;
                }
                return 1;
        }
        return parent::isFieldEditable($field, $force_edit);
    }

    // Getters params: 

    public function getDefaultListHeaderButtons()
    {
        $buttons = array();

        if ($this->canSetAction('installProcess')) {
            $buttons[] = array(
                'classes'     => array('btn', 'btn-default'),
                'label'       => 'Installer un processus',
                'icon_before' => 'fas_folder-plus',
                'attr'        => array(
                    'type'    => 'button',
                    'onclick' => $this->getJsActionOnclick('installProcess', array(), array(
                        'form_name' => 'install'
                    ))
                )
            );
        }

        return $buttons;
    }

    public function getActionsButtons()
    {
        $buttons = array();

        if ($this->isActionAllowed('updateProcess') && $this->canSetAction('updateProcess')) {
            $process = $this->getProcessClassName();
            $buttons[] = array(
                'label'   => 'Mettre à jour à la version ' . $process::$current_version,
                'icon'    => 'fas_cogs',
                'onclick' => $this->getJsActionOnclick('updateProcess', array(), array(
                    'confirm_msg' => 'Veuillez confirmer'
                ))
            );
        }

        return $buttons;
    }

    // Getters données: 

    public function getNameProperties()
    {
        // Nécessaire pour régler le conflit avec le champ "name"
        return array('title');
    }

    public function getDefaultProcessToInstallName()
    {
        $class_name = BimpTools::getPostFieldValue('classname', '');

        if ($class_name && self::loadProcessClass($class_name)) {
            if (preg_match('/^BDS_(.+)Process$/', $class_name, $matches)) {
                $name = $matches[1];

                if ($class_name::$allow_multiple_instances) {
                    $name .= '_' . self::getNextProcessIdx($name);
                }

                return $name;
            }
        }

        return '';
    }

    public function getDefaultProcessToInstallTitle()
    {
        $class_name = BimpTools::getPostFieldValue('classname', '');

        if ($class_name && self::loadProcessClass($class_name)) {
            return $class_name::$default_public_title;
        }

        return '';
    }

    public function getProcessClassName($load_class = true)
    {
        $name = $this->getData('name');

        if ($name) {
            if (preg_match('/^(.+)_[0-9]+$/', $name, $matches)) {
                $name = $matches[1];
            }

            $className = 'BDS_' . $name . 'Process';

            if ($load_class && !class_exists($className)) {
                self::loadProcessClass($className);
            }
            return $className;
        }

        return '';
    }

    public static function getNextProcessIdx($process_name)
    {
        $last_name = self::getBdb()->getMax('bds_process', 'name', 'name LIKE \'' . $process_name . '_%\'');
        if ($last_name && preg_match('/^' . $process_name . '_([0-9]+)$/', $last_name, $matches)) {
            return (int) $matches[1] + 1;
        }

        return 1;
    }

    // Getters Array: 

    public function getInstallableProcessesArray()
    {
        $processes = array();

        $dir = DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/process_overrides';

        if (is_dir($dir)) {
            $currents = array();
            foreach (BimpCache::getBimpObjectObjects('bimpdatasync', 'BDS_Process') as $process) {
                $currents[] = $process->getProcessClassName();
            }

            foreach (scandir($dir) as $f) {
                if (in_array($f, array('.', '..'))) {
                    continue;
                }

                if (preg_match('/^(.+)\.php$/', $f, $matches)) {
                    $className = $matches[1];

                    if (self::loadProcessClass($className)) {
                        if (method_exists($className, 'install')) {
                            if ($className::$allow_multiple_instances || !in_array($className, $currents)) {
                                $processes[$className] = $className;
                            }
                        }
                    }
                }
            }
        }

        return $processes;
    }

    // Rendus HTML: 

    public function renderViewChildrenLists()
    {
        $html = '';

        $html .= '<h3>' . BimpRender::renderIcon('fas_cog', 'iconLeft') . 'Configuration</h3>';
        $tabs = array();

        $tabs[] = array(
            'id'      => 'operations_config',
            'title'   => BimpRender::renderIcon('fas_cogs', 'iconLeft') . 'Operations',
            'content' => $this->renderChildrenList('operations', 'process')
        );

        $tabs[] = array(
            'id'      => 'params_config',
            'title'   => BimpRender::renderIcon('fas_sliders-h', 'iconLeft') . 'Paramètres',
            'content' => $this->renderChildrenList('params', 'process')
        );

        $tabs[] = array(
            'id'      => 'options_config',
            'title'   => BimpRender::renderIcon('far_check-square', 'iconLeft') . 'Options',
            'content' => $this->renderChildrenList('options', 'process')
        );

        $tabs[] = array(
            'id'      => 'triggers_config',
            'title'   => BimpRender::renderIcon('fas_bolt', 'iconLeft') . 'Triggers',
            'content' => $this->renderChildrenList('triggers', 'process')
        );

        $tabs[] = array(
            'id'      => 'matches_config',
            'title'   => BimpRender::renderIcon('fas_arrows-alt-h', 'iconLeft') . 'Correspondances',
            'content' => $this->renderChildrenList('matches', 'process')
        );

        $tabs[] = array(
            'id'      => 'crons_config',
            'title'   => BimpRender::renderIcon('fas_clock', 'iconLeft') . 'Tâches planifiées',
            'content' => $this->renderChildrenList('crons', 'process')
        );

        $html .= BimpRender::renderNavTabs($tabs, 'process_children');
        return $html;
    }

    public function renderOperationsView()
    {
        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('ID du processus absent');
        }

        $html = '';

        $html .= '<div class="container-fluid page_content">';
        $html .= '<div class="page_title">';
        $html .= $this->renderObjectMenu();
        $html .= '<h2>';
        $html .= BimpRender::renderIcon('fas_cogs', 'iconLeft') . ' ' . $this->getData('title') . ' - opérations';
        $html .= '</h2>';
        $html .= '</div>';

        $html .= '<div id="process_' . $this->id . '_operations" class="row">';
        $html .= $this->renderOperationExecutionView();
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function renderOperationExecutionView($panels = true)
    {
        $html = '';

        $errors = array();

        if ($this->isLoaded($errors)) {
            $operations = $this->getChildrenObjects('operations', array(
                'active' => 1
            ));

            if (count($operations)) {
                foreach ($operations as $operation) {
                    $html .= $operation->renderExecutionForm(true, (count($operations) <= 2));
                }
            } else {
                $errors[] = 'Aucune opération enregistrée pour ce processus';
            }
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        }

        return $html;
    }

    public function renderReportsView()
    {
        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('ID du processus absent');
        }

        $html = '';

        $html .= '<div class="container-fluid page_content">';
        $html .= '<div class="page_title">';
        $html .= $this->renderObjectMenu();
        $html .= '<h2>';
        $html .= BimpRender::renderIcon('fas_file-alt', 'iconLeft') . ' ' . $this->getData('title') . ' - rapports d\'opérations';
        $html .= '</h2>';
        $html .= '</div>';

        $operations = $this->getChildrenObjects('operations');

        $report = BimpObject::getInstance('bimpdatasync', 'BDS_Report');

        foreach ($operations as $operation) {
            if ((int) $operation->getData('use_report')) {
                $html .= '<h3>' . BimpRender::renderIcon('fas_cogs', 'iconLeft') . $operation->getData('title') . '</h3>';

                $bc_list = new BC_ListTable($report, 'operation', 1, null, 'Liste des rapports', 'fas_bars');
                $bc_list->addIdentifierSuffix('_op' . $operation->id);
                $bc_list->addFieldFilterValue('id_process', (int) $this->id);
                $bc_list->addFieldFilterValue('id_operation', (int) $operation->id);

                $html .= $bc_list->renderHtml();
            }
        }
        return $html;
    }

    // Traitements: 

    public static function loadProcessClass($class_name = '', $process_name = '', &$errors = array())
    {
        if (!$class_name) {
            if ($process_name) {
                $class_name = 'BDS_' . $process_name . 'Process';
            } else {
                $errors[] = 'Nom de la classe non spécifié';
                return false;
            }
        }

        if (!class_exists($class_name)) {
            $file = DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/process_overrides/' . $class_name . '.php';
            if (file_exists($file)) {
                require_once $file;
            } else {
                $errors[] = 'Le fichier de la classe "' . $class_name . '" n\'existe pas';
            }
        }

        if (!class_exists($class_name)) {
            $errors[] = 'La classe "' . $class_name . '" n\'existe pas';
            return false;
        }

        return true;
    }

    // Actions: 

    public function actionInstallProcess($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Processus installé avec succès';

        $className = BimpTools::getArrayValueFromPath($data, 'classname', '');

        if (!$className) {
            $errors[] = 'Aucun processus sélectionné';
        } elseif (self::loadProcessClass($className, '', $errors)) {
            if (!method_exists($className, 'install')) {
                $errors[] = 'Méthode "install" absente de la classe "' . $className . '"';
            } else {
                $className::install($errors, $warnings, BimpTools::getArrayValueFromPath($data, 'title', ''));
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionUpdateProcess($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Processus mis à jour avec succès';

        $process = $this->getProcessClassName();

        $errors = $process::updateProcess($this->id, (int) $this->getData('version'));

        if (!count($errors)) {
            $this->updateField('version', (int) $process::$current_version);
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    // Overrides: 

    public function create(&$warnings = [], $force_create = false)
    {
        $errors = array();

        $name = $this->getData('name');
        if ($name) {
            $className = 'BDS_' . $name . 'Process';
            if (self::loadProcessClass($className, '', $errors)) {
                if ($className::$allow_multiple_instances) {
                    $name .= '_' . self::getNextProcessIdx($name);
                    $this->set('name', $name);
                }
            }
        }

        if (!count($errors)) {
            $errors = parent::create($warnings, $force_create);
        }

        return $errors;
    }
}

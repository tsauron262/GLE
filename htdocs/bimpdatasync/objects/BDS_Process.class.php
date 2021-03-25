<?php

class BDS_Process extends BimpObject
{

    public static $types = array(
        'import' => array('label' => 'Import', 'icon' => 'fas_sign-in-alt'),
        'export' => array('label' => 'Export', 'icon' => 'fas_sign-out-alt'),
        'sync'   => array('label' => 'Synchronisation', 'icon' => 'fas_sync'),
        'ws'     => array('label' => 'Web service', 'icon' => 'fas_globe')
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
        global $user;

        switch ($action) {
            case 'installProcess':
                if ($user->admin) {
                    return 1;
                }
                return 0;
        }
        return parent::canSetAction($action);
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

    // Getters données: 

    public function getNameProperties()
    {
        // Nécessaire pour régler le conflit avec le champ "name"
        return array('title');
    }

    // Getters Array: 

    public function getInstallableProcessesArray()
    {
        $processes = array();

        $dir = DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/process_overrides';

        if (is_dir($dir)) {
            $files = scandir($dir);

            $currents = array();

            foreach (BimpCache::getBimpObjectObjects('bimpdatasync', 'BDS_Process') as $process) {
                $currents[] = 'BDS_' . $process->getData('name') . 'Process';
            }

            foreach ($files as $f) {
                if (preg_match('/^(.+)\.php$/', $f, $matches)) {
                    $className = $matches[1];

                    if (!in_array($className, $currents)) {
                        require_once $dir . '/' . $f;

                        if (class_exists($className) && method_exists($className, 'install')) {
                            $processes[$className] = $className;
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
                    $html .= $operation->renderExecutionForm(true);
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

    // Actions: 

    public function actionInstallProcess($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Processus installé avec succès';

        $className = BimpTools::getArrayValueFromPath($data, 'classname', '');

        if (!$className) {
            $errors[] = 'Aucun processus sélectionné';
        } else {
            $file = DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/process_overrides/' . $className . '.php';
            if (!file_exists($file)) {
                $errors[] = 'Le fichier "' . $file . '" n\'existe pas';
            } else {
                require_once $file;

                if (!class_exists($className)) {
                    $errors[] = 'La classe "' . $className . '" n\'existe pas';
                } elseif (!method_exists($className, 'install')) {
                    $errors[] = 'Méthode "install" absente de la classe "' . $className . '"';
                } else {
                    $className::install($errors, $warnings);
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }
}

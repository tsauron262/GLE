<?php

class Bimp_Log extends BimpObject
{

    const BIMP_LOG_NOTIF = 1;
    const BIMP_LOG_ALERTE = 2;
    const BIMP_LOG_ERREUR = 3;
    const BIMP_LOG_URGENT = 4;

    public static $types = array(
        'bimpcore'   => 'BimpCore',
        'yml'        => 'Config YML',
        'sql'        => 'Erreurs SQL',
        'logistique' => 'Logistique',
        'stocks'     => 'Stocks',
        'divers'     => 'Divers',
    );
    public static $levels = array(
        self::BIMP_LOG_NOTIF  => array('label' => 'Notification', 'classes' => array('info')),
        self::BIMP_LOG_ALERTE => array('label' => 'Alerte', 'classes' => array('warning')),
        self::BIMP_LOG_ERREUR => array('label' => 'Erreur', 'classes' => array('danger')),
        self::BIMP_LOG_URGENT => array('label' => 'Urgent', 'classes' => array('important'))
    );

    // Droits user: 

    public function canView()
    {
        global $user;
        return (int) $user->admin;
    }

    public function canEdit()
    {
        global $user;
        return (int) $user->admin;
    }

    public function canDelete()
    {
        global $user;
        return (int) $user->admin;
    }

    public function canSetAction($action)
    {
        switch ($action) {
            case 'setProcessed':
                return $this->canEdit();
        }

        return parent::canSetAction($action);
    }

    // Getters booléens: 

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'setProcessed':
                if ($this->isLoaded()) {
                    if ((int) $this->getData('processed')) {
                        $errors[] = 'Log déjà traité';
                        return 0;
                    }
                }

                return 1;
        }

        return (int) parent::isActionAllowed($action, $errors);
    }

    // Getters params: 

    public function getActionsButtons()
    {
        return $this->getListExtraButtons();
    }

    public function getListExtraButtons()
    {
        $buttons = array();

        if ($this->isActionAllowed('setProcessed') && $this->canSetAction('processed')) {
            $buttons[] = array(
                'label'   => 'Traité',
                'icon'    => 'fas_check',
                'onclick' => $this->getJsActionOnclick('setProcessed')
            );
        }

        return $buttons;
    }

    // Getters données: 

    public function getObj()
    {
        $module = $this->getData('obj_module');
        $name = $this->getData('obj_name');
        $id = (int) $this->getData('id_object');

        if ($module && $name) {
            if ($id) {
                return BimpCache::getBimpObjectInstance($module, $name, $id);
            }

            return BimpObject::getInstance($module, $name);
        }

        return null;
    }

    // Affichages: 

    public function displayObj()
    {
        $obj = $this->getObj();

        if (BimpObject::objectLoaded($obj)) {
            return $obj->getLink();
        }

        if (is_a($obj, 'BimpObject')) {
            return BimpTools::ucfirst($obj->getLabel());
        }

        return '';
    }

    public function displayExtraData()
    {
        $data = $this->getData('extra_data');

        if (is_array($data) && !empty($data)) {
            $html = BimpRender::renderRecursiveArrayContent($data, array(
                        'title'    => 'Données supplémentaires',
                        'foldable' => 1,
                        'open'     => 1
            ));

            return BimpRender::renderPanel(BimpRender::renderIcon('fas_bars', 'iconLeft') . 'Données supplémentaires', $html, '', array(
                        'type' => 'secondary'
            ));
        }

        return '';
    }

    public function displayBacktrace()
    {
        $bt = $this->getData('backtrace');

        if (is_array($bt) && !empty($bt)) {
            $html = BimpRender::renderBacktrace($bt);
            return BimpRender::renderPanel(BimpRender::renderIcon('fas_history', 'iconLeft') . 'Back trace', $html, '', array(
                        'type' => 'secondary'
            ));
        }

        return '';
    }

    // Actions

    public function actionSetProcessed($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        if ($this->isLoaded() && !(int) $this->getData('processed')) {
            $success = 'Log marqué traité avec succès';
            $this->set('processed', 1);
            $errors = $this->update($warnings, true);
        } else {
            $nOk = 0;
            $ids = BimpTools::getArrayValueFromPath($data, 'id_objects', array());

            foreach ($ids as $id) {
                $obj = BimpCache::getBimpObjectInstance($this->module, $this->object_name, $id);

                if (BimpObject::objectLoaded($obj)) {
                    if (!(int) $obj->getData('processed')) {
                        $obj->set('processed', 1);
                        $obj_warnings = array();
                        $obj_errors = $obj->update($obj_warnings, true);

                        if (count($obj_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($obj_errors, 'Log #' . $id);
                        } else {
                            $nOk++;
                        }
                    }
                }

                if ($nOk > 1) {
                    $success = $nOk . ' logs marqués traités avec succès';
                } else {
                    $success = $nOk . ' log marqué traité';
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Rendus HTML: 

    public function renderHeaderExtraLeft()
    {
        $html = '';

        $html .= '<div class="object_header_infos">';
        $html .= 'Créé le <strong>' . $this->displayData('date') . '</strong>';
        $html .= ' par ' . $this->displayData('id_user', 'nom_url');
        $html .= '</div>';

        if ((int) $this->getData('processed')) {
            $html .= '<div class="object_header_infos">';
            $html .= 'Traité le <strong>' . $this->displayData('date_processed') . '</strong>';
            $html .= ' par ' . $this->displayData('id_user_processed', 'nom_url');
            $html .= '</div>';
        }

        return $html;
    }

    public function renderBeforeListContent()
    {
        $html = '';

        $sql = 'SELECT a.type,';
        $sql .= ' SUM(' . BimpTools::getSqlCase(array(
                    'a.level' => 1
                        ), 1, 0) . ') as nb_notifs';
        $sql .= ', SUM(' . BimpTools::getSqlCase(array(
                    'a.level' => 2
                        ), 1, 0) . ') as nb_alertes';
        $sql .= ', SUM(' . BimpTools::getSqlCase(array(
                    'a.level' => 3
                        ), 1, 0) . ') as nb_erreurs';
        $sql .= ', SUM(' . BimpTools::getSqlCase(array(
                    'a.level' => 4
                        ), 1, 0) . ') as nb_urgents';

        $sql .= BimpTools::getSqlFrom($this->getTable());
        $sql .= BimpTools::getSqlWhere(array('a.processed' => 0));
        $sql .= ' GROUP BY a.type';

        $rows = $this->db->executeS($sql, 'array');

//        $html .= '<pre>';
//        $html .= print_r($rows, 1);
//        $html .= '</pre>';
//        
        if (!empty($rows)) {
            $html .= '<table class="bimp_list_table">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th></th>';
            $html .= '<th>Notifications</th>';
            $html .= '<th>Alertes</th>';
            $html .= '<th>Erreurs</th>';
            $html .= '<th>Urgences</th>';
            $html .= '</tr>';
            $html .= '</thead>';

            $html .= '<tbody>';

            foreach ($rows as $r) {
                $html .= '<tr>';
                $html .= '<th>' . BimpTools::getArrayValueFromPath(self::$levels, $r['type'], $r['type']) . '</th>';

                $html .= '<td>';
                if ((int) $r['nb_notifs']) {
                    $html .= '<span class="badge badge-info">' . $r['nb_notifs'] . '</span>';
                }
                $html .= '</td>';

                $html .= '<td>';
                if ((int) $r['nb_alertes']) {
                    $html .= '<span class="badge badge-warning">' . $r['nb_alertes'] . '</span>';
                }
                $html .= '</td>';

                $html .= '<td>';
                if ((int) $r['nb_erreurs']) {
                    $html .= '<span class="badge badge-danger">' . $r['nb_erreurs'] . '</span>';
                }
                $html .= '</td>';

                $html .= '<td>';
                if ((int) $r['nb_urgents']) {
                    $html .= '<span class="badge badge-important">' . $r['nb_urgents'] . '</span>';
                }
                $html .= '</td>';

                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';

            $panel = BimpRender::renderPanel('Logs à traiter', $html, '', array(
                        'type' => 'secondary'
            ));

            $html = '<div class="row">';
            $html .= '<div class="col-sm-12 col-md-6">';

            $html .= $panel;

            $html .= '</div>';
            $html .= '</div>';
        }

        return $html;
    }

    // Overrides: 

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            if ((int) $this->getData('level') === self::BIMP_LOG_URGENT) {
                if ((int) BimpCore::getConf('bimpcore_logs_urgents_send_email', 0)) {
                    $message = 'Une nouvelle entrée dans les logs à traiter d\'urgence' . "\n\n";
                    $message .= DOL_URL_ROOT . '/bimpcore/index.php?fc=admin&tab=logs' . "\n\n";
                    $message .= 'Message: ' . $this->getData('msg') . "\n";
                    $message .= 'Type: ' . $this->displayData('type', 'default', false, true) . "\n";

                    $obj = $this->getObj();

                    if (is_object($obj)) {
                        if (is_a($obj, 'BimpObject') && BimpObject::objectLoaded($obj)) {
                            $url = $obj->getUrl();
                            $name = BimpTools::ucfirst($obj->getLabel()) . ' ' . $obj->getRef(true);

                            if ($url) {
                                $message .= 'Objet: <a href="' . $url . '">' . $name . '</a>';
                            } else {
                                $message .= 'Objet: ' . $name;
                            }
                        } else {
                            $message .= 'Objet: ' . get_class($obj);
                        }
                        $message .= "\n";
                    }

                    mailSyn2("LOG URGENT", "dev@bimp.fr", "admin@bimp.fr", $message);
                }
            }
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $init_processed = (int) $this->getInitData('processed');

        if ($init_processed !== (int) $this->getData('processed')) {
            if ((int) $this->getData('processed')) {
                global $user;

                $this->set('id_user_processed', $user->id);
                $this->set('date_processed', date('Y-m-d H:i:s'));
            } else {
                $this->set('id_user_processed', 0);
                $this->set('date_processed', null);
            }
        }

        return parent::update($warnings, $force_update);
    }
}

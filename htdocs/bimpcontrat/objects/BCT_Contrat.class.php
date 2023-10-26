<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';

class BCT_Contrat extends BimpDolObject
{

    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_CLOSED = 2;

    public static $status_list = Array(
        self::STATUS_DRAFT     => Array('label' => 'Brouillon', 'classes' => Array('warning'), 'icon' => 'fas_trash-alt'),
        self::STATUS_VALIDATED => Array('label' => 'Validé', 'classes' => Array('success'), 'icon' => 'fas_check'),
        self::STATUS_CLOSED    => Array('label' => 'Fermé', 'classes' => Array('danger'), 'icon' => 'fas_times')
    );

    // Droits user : 

    public function canClientView()
    {
        global $userClient;

        if (!BimpObject::objectLoaded($userClient)) {
            return 0;
        }

        if ($this->isLoaded()) {
            if ((int) $userClient->getData('id_client') !== (int) $this->getData('fk_soc')) {
                return 0;
            }

            if ($userClient->isAdmin()) {
                return 1;
            }

            if (in_array($this->id, $userClient->getAssociatedContratsList())) {
                return 1;
            }

            return 0;
        }

        return 1;
    }

    public function canClientViewDetail()
    {
        global $userClient;
        if (BimpObject::objectLoaded($userClient) && $userClient->isAdmin()) {
            return 1;
        }
        return 0;
    }

    public function canEditField($field_name)
    {
        return 1;
    }

    public function canSetAction($action)
    {
        global $user;

        if ($user->admin) {
            return 1;
        }

        switch ($action) {
            case 'validate':
                if ($user->rights->bimpcontract->to_validate) {
                    return 1;
                }
                return 0;
        }

        return parent::canSetAction($action);
    }

    // Getters booléens : 

    public function isDeletable($force_delete = false, &$errors = array())
    {
        if ($force_delete) {
            return 1;
        }

        if ((int) $this->getData('statut') != self::STATUS_DRAFT) {
            return 0;
        }

        return parent::isDeletable();
    }

    public function isActionAllowed($action, &$errors = []): int
    {
        $status = (int) $this->getData('statut');

        switch ($action) {
            case 'validate':
                if ($status != self::STATUS_DRAFT) {
                    $errors[] = 'Ce contrat n\'est pas au satut brouillon';
                    return 0;
                }
                return 1;

            case 'createSignature':
                if ((int) $this->getData('id_signature')) {
                    $errors[] = 'Signature déjà créée';
                    return 0;
                }

                if ((int) $this->getData('statut') !== self::STATUS_VALIDATED) {
                    $errors[] = 'Ce contrat n\'est pas au statu "Validé"';
                    return 0;
                }

                return 1;
        }

        return parent::isActionAllowed($action, $errors);
    }

    public function isClientCompany()
    {
        $client = $this->getChildObject('client');

        if (BimpObject::objectLoaded($client)) {
            return $client->isCompany();
        }

        return 0;
    }

    public function isSigned()
    {
        if ((int) $this->getData('id_signature')) {
            $signature = $this->getChildObject('signature');

            if (BimpObject::objectLoaded($signature)) {
                return $signature->isSigned();
            }
        }

        return 0;
    }

    public function areLinesEditable()
    {
        return 1;
    }

    // Getters params: 

    public function getActionsButtons()
    {
        $buttons = Array();

        // Valider : 
        if ($this->isActionAllowed('validate') && $this->canSetAction('validate')) {
            $buttons[] = array(
                'label'   => 'Valider',
                'icon'    => 'fas_check',
                'onclick' => $this->getJsActionOnclick('validate', array(), array(
                    'confirm_msg' => 'Veuillez confirmer la validation du contrat'
                ))
            );
        }

        return $buttons;
    }

    public function getFilesDir()
    {
        global $conf;
        return $conf->contract->dir_output;
    }

    // Getters données : 

    public function getConditionReglementClient()
    {
        if (!$this->isLoaded() || (int) BimpTools::getPostFieldValue('is_clone_form', 0)) {
            $id_soc = (int) BimpTools::getPostFieldValue('fk_soc_facturation', BimpTools::getPostFieldValue('fk_soc', 0));
            if (!$id_soc) {
                if ((int) $this->getData('fk_soc_facturation') > 0) {
                    $id_soc = $this->getData('fk_soc_facturation');
                } elseif ((int) $this->getData('fk_soc')) {
                    $id_soc = $this->getData('fk_soc');
                }
            }

            if ($id_soc) {
                $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $id_soc);
                if (BimpObject::objectLoaded($soc)) {
                    return (int) $soc->getData('cond_reglement');
                }
            }
        }

        if (isset($this->data['condregl']) && (int) $this->data['condregl']) {
            return (int) $this->data['condregl']; // pas getData() sinon boucle infinie (getCondReglementBySociete() étant définie en tant que callback du param default_value pour ce champ). 
        }

        return (int) BimpCore::getConf('societe_id_default_cond_reglement', 0);
    }

    public function getModeReglementClient()
    {
        if (!$this->isLoaded() || (int) BimpTools::getPostFieldValue('is_clone_form', 0)) {
            $id_soc = (int) BimpTools::getPostFieldValue('fk_soc_facturation', BimpTools::getPostFieldValue('fk_soc', 0));
            if (!$id_soc) {
                if ((int) $this->getData('fk_soc_facturation') > 0) {
                    $id_soc = $this->getData('fk_soc_facturation');
                } elseif ((int) $this->getData('fk_soc')) {
                    $id_soc = $this->getData('fk_soc');
                }
            }

            if ($id_soc) {
                $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $id_soc);
                if (BimpObject::objectLoaded($soc)) {
                    return (int) $soc->getData('mode_reglement');
                }
            }
        }

        if (isset($this->data['moderegl']) && (int) $this->data['moderegl']) {
            return (int) $this->data['moderegl']; // pas getData() sinon boucle infinie (getModeReglementBySociete() étant définie en tant que callback du param default_value pour ce champ). 
        }

        return (int) BimpCore::getConf('societe_id_default_mode_reglement', 0);
    }

    // Getters Array: 

    public function getClientRibsArray()
    {
        $id_client = (int) $this->getData('fk_soc_facturation');
        if (!$id_client) {
            $id_client = (int) $this->getData('fk_soc');
        }

        return BimpCache::getSocieteRibsArray($id_client, true);
    }

    // Rendus HTML : 

    public function renderHeaderExtraLeft()
    {
        $html = '';

        if ($this->isLoaded()) {
            $html .= '<div class="object_header_infos">';
            $html .= 'Créée le <strong>' . BimpTools::printDate($this->getData('datec'), 'strong') . '</strong>';

            $user = $this->getChildObject('user_create');
            if (BimpObject::objectLoaded($user)) {
                $html .= ' par ' . $user->getLink();
            }

            $html .= '</div>';

            if ((int) $this->getData('statut') > 0) {
                $html .= '<div class="object_header_infos">';
                $html .= 'Validé le <strong>' . BimpTools::printDate($this->getData('date_validate'), 'strong') . '</strong>';

                $user = $this->getChildObject('user_validate');
                if (BimpObject::objectLoaded($user)) {
                    $html .= ' par ' . $user->getLink();
                }

                $html .= '</div>';
            }
        }

        return $html;
    }

    public function renderLinkedObjectsTable($htmlP = '')
    {
        $this->dol_object->element = 'bimp_contrat';

        return parent::renderLinkedObjectsTable($htmlP);
    }

    public static function renderAbonnementsTabs($params)
    {
        $html = '';

        $params = BimpTools::overrideArray(array(
                    'id_contrat' => 0,
                    'id_client'  => 0,
                    'id_fourn'   => 0,
                    'id_product' => 0
                        ), $params);

        $tabs = array();

        $line_instance = BimpObject::getInstance('bimpcontrat', 'BCT_ContratLine');

        // Overview: 
        $content = '<div class="periodic_operations_overview_content">';
        $content .= $line_instance->renderPeriodicOperationsToProcessOverview($params);
        $content .= '</div>';

        $html .= '<div class="row">';
        $html .= '<div class="col-sm-12 col-md-4">';
        $title = BimpRender::renderIcon('fas_exclamation-circle', 'iconLeft') . 'A traiter aujourd\'hui';

        $footer = '<div style="text-align: right">';
        $onclick = $line_instance->getJsLoadCustomContent('renderPeriodicOperationsToProcessOverview', '$(this).findParentByClass(\'panel\').find(\'.periodic_operations_overview_content\')', array($params));
        $footer .= '<span class="btn btn-default" onclick="' . $onclick . '">';
        $footer .= 'Actualiser' . BimpRender::renderIcon('fas_redo', 'iconRight');
        $footer .= '</span>';
        $footer .= '</div>';

        $html .= BimpRender::renderPanel($title, $content, $footer, array(
                    'type' => 'secondary'
        ));
        $html .= '</div>';
        $html .= '</div>';

        if (!(int) $params['id_fourn']) {
            // Facturations: 
            $tabs[] = array(
                'id'            => 'fac_periods_tab',
                'title'         => BimpRender::renderIcon('fas_file-invoice-dollar', 'iconLeft') . 'Facturations périodiques',
                'ajax'          => 1,
                'ajax_callback' => $line_instance->getJsLoadCustomContent('renderPeriodicOperationsList', '$(\'#fac_periods_tab .nav_tab_ajax_result\')', array('fac', $params['id_client'], $params['id_product']), array('button' => ''))
            );
        }

        // Achats: 
        $tabs[] = array(
            'id'            => 'achat_periods_tab',
            'title'         => BimpRender::renderIcon('fas_cart-arrow-down', 'iconLeft') . 'Achats périodiques',
            'ajax'          => 1,
            'ajax_callback' => $line_instance->getJsLoadCustomContent('renderPeriodsList', '$(\'#achat_periods_tab .nav_tab_ajax_result\')', array('achat', $params['id_client'], $params['id_product'], $params['id_fourn'], $params['id_contrat']), array('button' => ''))
        );

        $html .= BimpRender::renderNavTabs($tabs);

        return $html;
    }

    // Actions : 

    public function actionValidate($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Contrat validé avec succès';

        global $user;

        $this->set('statut', self::STATUS_VALIDATED);
        $this->set('date_validate', date('Y-m-d H:i:s'));
        $this->set('fk_user_validate', $user->id);

        $errors = $this->update($warnings, true);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }
}

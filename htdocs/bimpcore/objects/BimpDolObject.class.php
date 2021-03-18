<?php

if (!defined('BIMP_LIB')) {
    require_once __DIR__ . '/../Bimp_Lib.php';
}

class BimpDolObject extends BimpObject
{

    public static $dol_module = '';
    public static $mail_event_code = '';
    public static $email_type = '';

    // Getters array: 

    public function getEmailModelsArray()
    {
        if (!static::$email_type) {
            return array();
        }

        return self::getEmailTemplatesArray(static::$email_type, true);
    }

    public function getEmailUsersFromArray()
    {
        global $user, $langs, $conf;

        $emails = array();

        // User connecté: 

        if (!empty($user->email)) {
            $emails[$user->email] = $user->getFullName($langs) . ' (' . $user->email . ')';
        }

        if (!$user->admin)
            return $emails;

        if (!empty($user->email_aliases)) {
            foreach (explode(',', $user->email_aliases) as $alias) {
                $alias = trim($alias);
                if ($alias) {
                    $alias = str_replace('/</', '', $alias);
                    $alias = str_replace('/>/', '', $alias);
                    if (!isset($emails[$alias])) {
                        $emails[$alias] = $user->getFullName($langs) . ' (' . $alias . ')';
                    }
                }
            }
        }

        // Société: 

        if (!empty($conf->global->MAIN_INFO_SOCIETE_MAIL)) {
            $emails[$conf->global->MAIN_INFO_SOCIETE_MAIL] = $conf->global->MAIN_INFO_SOCIETE_NOM . ' (' . $conf->global->MAIN_INFO_SOCIETE_MAIL . ')';
        }

        if (!empty($conf->global->MAIN_INFO_SOCIETE_MAIL_ALIASES)) {
            foreach (explode(',', $conf->global->MAIN_INFO_SOCIETE_MAIL_ALIASES) as $alias) {
                $alias = trim($alias);
                if ($alias) {
                    $alias = str_replace('/</', '', $alias);
                    $alias = str_replace('/>/', '', $alias);
                    if (!isset($emails[$alias])) {
                        $emails[$alias] = $conf->global->MAIN_INFO_SOCIETE_NOM . ' (' . $alias . ')';
                    }
                }
            }
        }

        // Contacts pièce: 

        if ($this->isLoaded()) {
            $c_user = new User($this->db->db);
            $contacts = $this->dol_object->liste_contact(-1, 'internal');
            foreach ($contacts as $item) {
                $c_user->fetch($item['id']);
                if (BimpObject::objectLoaded($c_user)) {
                    if (!empty($c_user->email) && !isset($emails[$c_user->email])) {
                        $emails[$c_user->email] = $item['libelle'] . ': ' . $c_user->getFullName($langs) . ' (' . $c_user->email . ')';
                    }

                    if (!empty($c_user->email_aliases)) {
                        foreach (explode(',', $c_user->email_aliases) as $alias) {
                            $alias = trim($alias);
                            if ($alias) {
                                $alias = str_replace('/</', '', $alias);
                                $alias = str_replace('/>/', '', $alias);
                                if (!isset($emails[$alias])) {
                                    $emails[$alias] = $item['libelle'] . ': ' . $c_user->getFullName($langs) . ' (' . $alias . ')';
                                }
                            }
                        }
                    }
                }
            }
        }

        return $emails;
    }

    public function getMailsToArray()
    {
        global $user, $langs;

        $client = $this->getChildObject('client');

        $emails = array(
            ""           => "",
            $user->email => $user->getFullName($langs) . " (" . $user->email . ")"
        );

        if ($this->isLoaded()) {
            $contacts = $this->dol_object->liste_contact(-1, 'external');
            foreach ($contacts as $item) {
                if (!isset($emails[(int) $item['id']])) {
                    $emails[(int) $item['id']] = $item['libelle'] . ': ' . $item['firstname'] . ' ' . $item['lastname'] . ' (' . $item['email'] . ')';
                }
            }
        }

        if (BimpObject::objectLoaded($client)) {
            $client_emails = self::getSocieteEmails($client->dol_object);
            if (is_array($client_emails)) {
                foreach ($client_emails as $value => $label) {
                    if (!isset($emails[$value])) {
                        $emails[$value] = $label;
                    }
                }
            }
        }

        if ($this->isLoaded()) {
            $contacts = $this->dol_object->liste_contact(-1, 'internal');
            foreach ($contacts as $item) {
                if (!isset($emails[$item['email']])) {
                    $emails[$item['email']] = $item['libelle'] . ': ' . $item['firstname'] . ' ' . $item['lastname'] . ' (' . $item['email'] . ')';
                }
            }
        }

        $emails['custom'] = 'Autre';

        return $emails;
    }

    // Getters données: 

    public function getModelPdf()
    {
        if ($this->field_exists('model_pdf')) {
            return $this->getData('model_pdf');
        }

        return '';
    }

    public function getDefaultMailTo()
    {
        return array();
    }

    public function getEmailContentByModel()
    {
        $content = '';
        $id_model = (int) BimpTools::getPostFieldValue('id_model', 0);

        if ($id_model) {
            $template = self::getEmailTemplateData($id_model);

            if (!is_null($template)) {
                if ($this->isLoaded()) {
                    if (!class_exists('FormMail')) {
                        require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
                    }

                    global $langs;

                    $formMail = new FormMail($this->db->db);
                    $formMail->setSubstitFromObject($this->dol_object, $langs);

                    if (isset($template['content_lines']) && $template['content_lines']) {
                        $lines = '';
                        foreach ($formMail->substit_lines as $substit_line) {
                            $lines .= make_substitutions($template['content_lines'], $substit_line) . "\n";
                        }
                        $formMail->substit['__LINES__'] = $lines;
                    } else {
                        $formMail->substit['__LINES__'] = '';
                    }

                    $formMail->substit['__LINK__'] = $this->getNomUrl(1);

                    $content = str_replace('\n', "\n", $template['content']);

                    if (dol_textishtml($content) && !dol_textishtml($formMail->substit['__SIGNATURE__'])) {
                        $formMail->substit['__SIGNATURE__'] = dol_nl2br($formMail->substit['__SIGNATURE__']);
                    } else if (!dol_textishtml($content) && dol_textishtml($this->substit['__SIGNATURE__'])) {
                        $content = dol_nl2br($content);
                    }

                    $content = make_substitutions($content, $formMail->substit);
                    $content = preg_replace("/^(<br>)+/", "", $content);
                    $content = preg_replace("/^\n+/", "", $content);
                }
            }
        }

        return $content;
    }

    public function getEmailTopicByModel()
    {
        $topic = '';
        $id_model = (int) BimpTools::getPostFieldValue('id_model', 0);

        if ($id_model) {
            $template = self::getEmailTemplateData($id_model);

            if (!is_null($template)) {
                if ($this->isLoaded()) {
                    if (!class_exists('FormMail')) {
                        require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
                    }

                    global $langs;

                    $formMail = new FormMail($this->db->db);
                    $formMail->setSubstitFromObject($this->dol_object, $langs);
                    $formMail->substit['__LINES__'] = '';
                    $topic = $template['topic'];
                    $topic = make_substitutions($topic, $formMail->substit);

                    $soc = $this->getChildObject("client");
                    if (isset($soc) && is_object($soc)) {
                        $formMail->setSubstitFromObject($soc->dol_object, $langs);
                        $topic = make_substitutions($topic, $formMail->substit);
                    }
                    $soc = $this->getChildObject("societe");
                    if (isset($soc) && is_object($soc)) {
                        $formMail->setSubstitFromObject($soc->dol_object, $langs);
                        $topic = make_substitutions($topic, $formMail->substit);
                    }
                }
            }
        }

        return $topic;
    }

    public function getBimpObjectsLinked()
    {
        $objects = array();
        if ($this->isLoaded()) {
            if ($this->isDolObject()) {
                foreach (BimpTools::getDolObjectLinkedObjectsList($this->dol_object, $this->db) as $item) {
                    $id = $item['id_object'];
                    $class = "";
                    $label = "";
                    $module = "bimpcommercial";
                    switch ($item['type']) {
                        case 'propal':
                            $class = "Bimp_Propal";
                            break;
                        case 'facture':
                            $class = "Bimp_Facture";
                            break;
                        case 'commande':
                            $class = "Bimp_Commande";
                            break;
                        case 'order_supplier':
                            $class = "Bimp_CommandeFourn";
                            break;
                        case 'invoice_supplier':
                            $class = "Bimp_FactureFourn";
                            break;
                        case 'contrat':
                            $module = 'bimpcontract';
                            $class = 'BContract_contrat';
                            break;
//                        case 'fichinter':
//                            $class = 'BimpFi_fiche';
//                            $module = "bimpfi";
//                            break;
                        case 'synopsisdemandeinterv':
                            $class = "BT_demandeInter";
                            $module = "bimptechnique";
                            break;
                        default:
                            break;
                    }
                    if ($class != "") {
                        $objT = BimpCache::getBimpObjectInstance($module, $class, $id);
//                        if ($objT->isLoaded()) { // Ne jamais faire ça: BimpCache renvoie null si l'objet n'existe pas => erreur fatale. 
                        if (BimpObject::objectLoaded($objT)) {
                            $objects[] = $objT;
                        }
                    }
                }
            }

            $client = $this->getChildObject('client');

            if ($client->isLoaded()) {
                $objects[] = $client;
            }
        }


        return $objects;
    }

    public function getDocumentFileId()
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        $ref = dol_sanitizeFileName($this->getRef());
        $ref = BimpTools::cleanStringForUrl($ref);
        
        $where = '`parent_module` = \'' . $this->module . '\' AND `parent_object_name` = \'' . $this->object_name . '\' AND `id_parent` = ' . (int) $this->id;
        $where .= ' AND `file_name` = \'' . $ref . '\' AND `file_ext` = \'pdf\'';
        
        return (int) $this->db->getValue('bimpcore_file', 'id', $where);
    }

    public function getJoinFilesValues()
    {
        $id_model = (int) BimpTools::getPostFieldValue('id_model', 0);

        if ($id_model) {
            $template = self::getEmailTemplateData($id_model);

            if (!(int) BimpTools::getArrayValueFromPath($template, 'joinfiles', 0)) {
                return array();
            }
        }

        $values = BimpTools::getPostFieldValue('join_files', array());

        $id_main_pdf_file = (int) $this->getDocumentFileId();

        if (!in_array($id_main_pdf_file, $values)) {
            $values[] = $id_main_pdf_file;
        }

        $list = $this->getAllFiles();
        $idSepa = 0;
        $idSepaSigne = 0;
        foreach ($list as $id => $elem)
            if (stripos($elem, "sepa")) {
                $idSepa = $id;
                if (stripos($elem, "signe"))
                    $idSepaSigne = $id;
            }


        if ($idSepa > 0 && $idSepaSigne < 1)
            $values[] = $idSepa;

        return $values;
    }

    public function getAllFiles($withLink = true)
    {
        $objects = $this->getBimpObjectsLinked();
        $list = $this->getFilesArray(0);
        if ($withLink) {
            foreach ($objects as $object) {
                $list = $list + $object->getFilesArray(0);
            }
        }
        return $list;
    }

    public function getDirOutput()
    {
        return '';
    }

    public function getFilesDir()
    {
        if ($this->isLoaded()) {
            $dir_output = $this->getDirOutput();
            if ($dir_output) {
                return $dir_output . '/' . dol_sanitizeFileName($this->getRef()) . '/';
            }
        }

        return parent::getFilesDir();
    }

    public function getFileUrl($file_name, $page = 'document')
    {
        $dir = $this->getFilesDir();
        if ($dir) {
            if (file_exists($dir . $file_name)) {
                if (isset(static::$files_module_part)) {
                    $module_part = static::$files_module_part;
                } else {
                    $module_part = static::$dol_module;
                }
                return DOL_URL_ROOT . '/' . $page . '.php?modulepart=' . $module_part . '&file=' . urlencode($this->getRef()) . '/' . urlencode($file_name);
            }
        }

        return '';
    }

    // Affichages: 

    public function displayPDFButton($display_generate = true, $with_ref = true, $btn_label = '')
    {
        $html = '';
        $ref = dol_sanitizeFileName($this->getRef());

        if ($ref) {
            $file_url = $this->getFileUrl($ref . '.pdf');
            if ($file_url) {
                $onclick = 'window.open(\'' . $file_url . '\');';
                $html .= '<button type="button" class="btn btn-default" onclick="' . $onclick . '">';
                $html .= '<i class="fas fa5-file-pdf ' . (($with_ref || $btn_label) ? 'iconLeft' : '') . '"></i>';
                if ($with_ref) {
                    $html .= $ref . '.pdf';
                } elseif ($btn_label) {
                    $html .= $btn_label;
                }
                $html .= '</button>';

                if ($display_generate) {
                    $onclick = 'toggleElementDisplay($(this).parent().find(\'.' . static::$dol_module . 'PdfGenerateContainer\'), $(this));';
                    $html .= '<span class="btn btn-light-default open-close action-open bs-popover" onclick="' . $onclick . '"';
                    $html .= BimpRender::renderPopoverData('Re-générer le document', 'top', 'false');
                    $html .= '>';
                    $html .= BimpRender::renderIcon('fas_sync');
                    $html .= '</span>';
                }
            }

            if ($display_generate) {
                $models = $this->getModelsPdfArray();
                if (count($models)) {
                    $html .= '<div class="' . static::$dol_module . 'PdfGenerateContainer" style="' . ($file_url ? 'margin-top: 15px; display: none;' : '') . '">';
                    $html .= BimpInput::renderInput('select', static::$dol_module . '_model_pdf', $this->getModelPdf(), array(
                                'options' => $models
                    ));
                    $onclick = 'var model = $(this).parent(\'.' . static::$dol_module . 'PdfGenerateContainer\').find(\'[name=' . static::$dol_module . '_model_pdf]\').val();setObjectAction($(this), ' . $this->getJsObjectData() . ', \'generatePdf\', {model: model}, null, null, null, null);';
                    $html .= '<button type="button" onclick="' . $onclick . '" class="btn btn-default">';
                    $html .= '<i class="fas fa5-sync iconLeft"></i>Générer';
                    $html .= '</button>';
                    $html .= '</div>';
                }
            }
        }

        return $html;
    }

    // Rendus: 

    public function renderExtraFile()
    {
        $html = "";
        $objects = $this->getBimpObjectsLinked();
        foreach ($objects as $obj)
            $html .= $this->renderListFileForObject($obj);
        return $html;
    }

    public function renderLinkedObjectsTable($htmlP = '')
    {
        $html = '';
        if ($this->isLoaded()) {
            $objects = array();

            if ($this->isDolObject()) {
                $propal_instance = null;
                $facture_instance = null;
                $commande_instance = null;
                $commande_fourn_instance = null;
                foreach (BimpTools::getDolObjectLinkedObjectsList($this->dol_object, $this->db) as $item) {
                    switch ($item['type']) {
                        case 'propal':
                            $propal_instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', (int) $item['id_object']);
                            if ($propal_instance->isLoaded()) {
                                $icon = $propal_instance->params['icon'];
                                $objects[] = array(
                                    'type'     => BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($propal_instance->getLabel()),
                                    'ref'      => $propal_instance->getNomUrl(0, true, true, 'full'),
                                    'date'     => $propal_instance->displayData('datep'),
                                    'total_ht' => $propal_instance->displayData('total_ht'),
                                    'status'   => $propal_instance->displayData('fk_statut')
                                );
                            }
                            break;

                        case 'facture':
                            $facture_instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $item['id_object']);
                            if ($facture_instance->isLoaded()) {
                                $icon = $facture_instance->params['icon'];
                                $objects[] = array(
                                    'type'     => BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($facture_instance->getLabel()),
                                    'ref'      => $facture_instance->getNomUrl(0, true, true, 'full'),
                                    'date'     => $facture_instance->displayData('datef'),
                                    'total_ht' => $facture_instance->displayData('total'),
                                    'status'   => $facture_instance->displayData('fk_statut')
                                );
                            }
                            break;

                        case 'commande':
                            $commande_instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $item['id_object']);
                            if ($commande_instance->isLoaded()) {
                                $icon = $commande_instance->params['icon'];
                                $objects[] = array(
                                    'type'     => BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($commande_instance->getLabel()),
                                    'ref'      => $commande_instance->getNomUrl(0, true, true, 'full'),
                                    'date'     => $commande_instance->displayData('date_commande'),
                                    'total_ht' => $commande_instance->displayData('total_ht'),
                                    'status'   => $commande_instance->displayData('fk_statut')
                                );
                            }
                            break;

                        case 'order_supplier':
                            $commande_fourn_instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $item['id_object']);
                            if ($commande_fourn_instance->isLoaded()) {
                                $icon = $commande_fourn_instance->params['icon'];
                                $objects[] = array(
                                    'type'     => BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($commande_fourn_instance->getLabel()),
                                    'ref'      => $commande_fourn_instance->getNomUrl(0, true, true, 'full'),
                                    'date'     => $commande_fourn_instance->displayData('date_commande'),
                                    'total_ht' => $commande_fourn_instance->displayData('total_ht'),
                                    'status'   => $commande_fourn_instance->displayData('fk_statut')
                                );
                            }
                            break;

                        case 'invoice_supplier':
                            $facture_fourn_instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureFourn', (int) $item['id_object']);
                            if (BimpObject::objectLoaded($facture_fourn_instance)) {
                                $icon = $facture_fourn_instance->params['icon'];
                                $objects[] = array(
                                    'type'     => BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($facture_fourn_instance->getLabel()),
                                    'ref'      => $facture_fourn_instance->getNomUrl(0, true, true, 'full'),
                                    'date'     => $facture_fourn_instance->displayData('datef'),
                                    'total_ht' => $facture_fourn_instance->displayData('total_ht'),
                                    'status'   => $facture_fourn_instance->displayData('fk_statut')
                                );
                            }
                            break;
                        case 'contrat':
                            $contrat_instance = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat', (int) $item['id_object']);
                            if (BimpObject::objectLoaded($contrat_instance)) {
                                $icon = $contrat_instance->params['icon'];
                                $objects[] = array(
                                    'type'     => BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($contrat_instance->getLabel()),
                                    'ref'      => $contrat_instance->getNomUrl(0, true, true, 'fiche_contrat'),
                                    'date'     => $contrat_instance->displayData('date_start'),
                                    'total_ht' => $contrat_instance->getTotalContrat() . "€",
                                    'status'   => $contrat_instance->displayData('statut')
                                );
                            }
                            break;
                        case 'bimp_ticket':
                            $ticket_instance = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_Ticket', (int) $item['id_object']);
                            if (BimpObject::objectLoaded($ticket_instance)) {
                                $icon = $ticket_instance->params['icon'];
                                $objects[] = array(
                                    'type'   => BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($ticket_instance->getLabel()),
                                    'ref'    => $ticket_instance->getNomUrl(0, true, true),
                                    'date'   => $ticket_instance->displayData('date_create'),
                                    'status' => $ticket_instance->displayData('status')
                                );
                            }
                            break;
                        case 'fichinter':
                            $fi_instance = BimpCache::getBimpObjectInstance('bimptechnique', 'BT_ficheInter', (int) $item['id_object']);
                            if (BimpObject::objectLoaded($fi_instance)) {
                                $icon = $fi_instance->params['icon'];
                                $objects[] = array(
                                    'type'   => BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($fi_instance->getLabel()),
                                    'ref'    => $fi_instance->getNomUrl(0, true, true, 'infos'),
                                    'date'   => $fi_instance->displayData('datec'),
                                    'status' => $fi_instance->displayData('fk_statut')
                                );
                            }
                            break;
                        case 'synopsisdemandeinterv':
                            $di_instance = BimpCache::getBimpObjectInstance('bimptechnique', 'BT_demandeInter', (int) $item['id_object']);
                            if (BimpObject::objectLoaded($di_instance)) {
                                $icon = $di_instance->params['icon'];
                                $objects[] = array(
                                    'type'     => BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($di_instance->getLabel()),
                                    'ref'      => $di_instance->getNomUrl(0, true, true, 'infos'),
                                    'date'     => $di_instance->displayData('datec'),
                                    'total_ht' => $di_instance->displayData('total_ht') . "€",
                                    'status'   => $di_instance->displayData('fk_statut')
                                );
                            }
                            break;
                    }
                }
            }

            $html .= '<table class="bimp_list_table">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th>Type</th>';
            $html .= '<th>Réf.</th>';
            $html .= '<th>Date</th>';
            $html .= '<th>Montant HT</th>';
            $html .= '<th>Statut</th>';
//            $html .= '<th></th>';
            $html .= '</tr>';
            $html .= '</thead>';

            $html .= '<tbody>';

            if (count($objects)) {
                foreach ($objects as $data) {
                    $htmlP .= '<tr>';
                    $htmlP .= '<td><strong>' . $data['type'] . '</strong></td>';
                    $htmlP .= '<td>' . $data['ref'] . '</td>';
                    $htmlP .= '<td>' . $data['date'] . '</td>';
                    $htmlP .= '<td>' . $data['total_ht'] . '</td>';
                    $htmlP .= '<td>' . $data['status'] . '</td>';
//                    $html .= '<td style="text-align: right">';
//                    
//                    $html .= BimpRender::renderRowButton('Supprimer le lien', 'trash', '');
//
//                    $html .= '</td>';
                    $htmlP .= '</tr>';
                }
            }
            if ($htmlP == '') {
                $htmlP .= '<tr>';
                $htmlP .= '<td colspan="5">' . BimpRender::renderAlerts('Aucun objet lié', 'info') . '</td>';
                $htmlP .= '</tr>';
            }

            $html .= $htmlP;
            $html .= '</tbody>';
            $html .= '</table>';

            $html = BimpRender::renderPanel('Objets liés', $html, '', array(
                        'foldable' => true,
                        'type'     => 'secondary-forced',
                        'icon'     => 'fas_link',
//                        'header_buttons' => array(
//                            array(
//                                'label'       => 'Lier à...',
//                                'icon_before' => 'plus-circle',
//                                'classes'     => array('btn', 'btn-default'),
//                                'attr'        => array(
//                                    'onclick' => ''
//                                )
//                            )
//                        )
            ));
        }

        return $html;
    }

    public function renderMailToInputs($input_name)
    {
        $emails = $this->getMailsToArray();

        $html = '';

        $html .= BimpInput::renderInput('select', $input_name . '_add_value', '', array(
                    'options'     => $emails,
                    'extra_class' => 'emails_select principal'
        ));


        $html .= '<p class="inputHelp selectMailHelp">';
        $html .= 'Sélectionnez une adresse e-mail puis cliquez sur "Ajouter"';
        $html .= '</p>';

        $html .= '<div class="mail_custom_value" style="display: none; margin-top: 10px">';
        $html .= BimpInput::renderInput('text', $input_name . '_add_value_custom', '');
        $html .= '<p class="inputHelp">Entrez une adresse e-mail valide puis cliquez sur "Ajouter"</p>';
        $html .= '</div>';

        return $html;
    }

    // Actions: 

    public function actionGeneratePdf($data, &$success = '', $errors = array(), $warnings = array())
    {
        $success = 'PDF généré avec succès';

        if ($this->isLoaded()) {
            if (!$this->isDolObject() || !method_exists($this->dol_object, 'generateDocument')) {
                $errors[] = 'Cette fonction n\'est pas disponible pour ' . $this->getLabel('the_plur');
            } else {
                if (!isset($data['model']) || !$data['model']) {
                    $data['model'] = $this->getModelPdf();
                }
                global $langs;
                $this->dol_object->error = '';
                $this->dol_object->errors = array();
                if ($this->dol_object->generateDocument($data['model'], $langs) <= 0) {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Des erreurs sont survenues lors de la génération du PDF');
                } else {
                    $fileName = isset($data['file_name']) ? $data['file_name'] : '';

                    if (!$fileName) {
                        if (method_exists($this, 'getPdfModelFileName')) {
                            $fileName = $this->getPdfModelFileName($data['model']);
                        } else {
                            $ref = dol_sanitizeFileName($this->getRef());
                            $fileName = $ref . '/' . $ref . ".pdf";
                        }
                    }

                    if (isset(static::$files_module_part)) {
                        $module_part = static::$files_module_part;
                    } else {
                        $module_part = static::$dol_module;
                    }

                    if (method_exists($this, 'getFileUrl') && $this->getFileUrl($fileName . '.pdf') != '') {
                        $url = $this->getFileUrl($fileName . '.pdf');
                    } else {
                        $url = DOL_URL_ROOT . '/document.php?modulepart=' . $module_part . '&file=' . urlencode($fileName);
                    }

                    $success_callback = 'window.open(\'' . $url . '\');';
                }
            }
        } else {
            $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionSendEMail($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Email envoyé avec succès';

        if (!isset($data['from']) || !(string) $data['from']) {
            $errors[] = 'Emetteur absent';
        } elseif (!BimpValidate::isEmail($data['from'])) {
            $errors[] = 'L\'adresse email de l\'émetteur (' . $data['from'] . ') n\'est pas valide';
        }

        if (!isset($data['mail_to']) || !is_array($data['mail_to']) || !count($data['mail_to'])) {
            $errors[] = 'Liste des destinataires absente';
        }

        if (!isset($data['mail_object']) || !(string) $data['mail_object']) {
            $errors[] = 'Objet de l\'e-mail absent';
        }

        if (!isset($data['msg_html']) || !(string) $data['msg_html']) {
            $errors[] = 'Veuillez saisir un message dans le corps de l\'e-mail';
        }

        if (!count($errors)) {
            $from = $data['from'];
            $to = '';
            $cc = '';

            foreach (array('mail_to', 'copy_to') as $type) {
                if (isset($data[$type]) && is_array($data[$type])) {
                    foreach ($data[$type] as $mail_to) {
                        $name = '';
                        $emails = '';
                        if (preg_match('/^[0-9]+$/', '' . $mail_to)) {
                            $contact = BimpObject::getInstance('bimpcore', 'Bimp_Contact', (int) $mail_to);
                            if ($contact->isLoaded()) {
                                if (!(string) $contact->getData('email')) {
                                    $errors[] = 'Aucune adresse e-mail enregistrée pour le contact "' . $contact->getData('firstname') . ' ' . $contact->getData('lastname') . '"';
                                } else {
                                    $emails = $contact->getData('email');
                                    $name = $contact->getData('firstname') . ' ' . $contact->getData('lastname');
                                }
                            } else {
                                $errors[] = 'Le contact d\'ID ' . $mail_to . ' n\'existe pas';
                            }
                        } elseif ($mail_to === 'thirdparty') {
                            $client = $this->getChildObject('client');
                            if (BimpObject::objectLoaded($client)) {
                                if (!(string) $client->getData('email')) {
                                    $errors[] = 'Aucune adresse e-mail enregistrée pour le client';
                                } else {
                                    $name = $client->getData('nom');
                                    $emails = $client->getData('email');
                                }
                            } else {
                                $errors[] = 'Aucun client enregistré pour ' . $this->getLabel('this');
                            }
                        } elseif (is_string($mail_to)) {
                            if (BimpValidate::isEmail($mail_to)) {
                                $emails = $mail_to;
                            } else {
                                $errors[] = '"' . $mail_to . '" n\'est pas une adresse e-mail valide';
                            }
                        }

                        if ($emails) {
                            switch ($type) {
                                case 'mail_to': $to .= ($to ? ', ' : '') . BimpTools::cleanEmailsStr($emails, $name, true);
                                    break;

                                case 'copy_to': $cc .= ($cc ? ', ' : '') . BimpTools::cleanEmailsStr($emails, $name, true);
                                    break;
                            }
                        }
                    }
                }
            }

            $filename_list = array();
            $mimetype_list = array();
            $mimefilename_list = array();

            if (isset($data['join_files']) && is_array($data['join_files'])) {
                foreach ($data['join_files'] as $id_file) {
                    $file = BimpCache::getBimpObjectInstance('bimpcore', 'BimpFile', (int) $id_file);
                    if ($file->isLoaded()) {
                        $file_path = $file->getFilePath();
                        $file_name = $file->getData('file_name') . '.' . $file->getData('file_ext');
                        if (!file_exists($file_path)) {
                            $errors[] = 'Le fichier "' . $file_name . '" n\'existe pas';
                        } else {
                            $filename_list[] = $file_path;
                            $mimetype_list[] = dol_mimetype($file_name);
                            $mimefilename_list[] = $file_name;
                        }
                    } else {
                        $errors[] = 'Le fichier d\'ID ' . $id_file . ' n\'existe pas';
                    }
                }
            }

            if (!$from) {
                $errors[] = 'Aucun expéditeur valide';
            }

            if (!$to) {
                $errors[] = 'Aucun destinataire valide';
            }

            if (!count($errors)) {
                $mail_object .= $data['mail_object'];

                $deliveryreceipt = (isset($data['confirm_reception']) ? (int) $data['confirm_reception'] : 0);
                if (mailSyn2($mail_object, $to, $from, $data['msg_html'], $filename_list, $mimetype_list, $mimefilename_list, $cc, '', $deliveryreceipt)) {
                    if (static::$mail_event_code) {
                        include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                        global $user, $langs, $conf;
                        $interface = new Interfaces($this->db->db);
                        if ($interface->run_triggers(static::$mail_event_code, $this->dol_object, $user, $langs, $conf) < 0) {
                            $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($interface), 'Echec de l\'enregistrement de l\'envoi du mail dans la liste des événements');
                        }
                    }
                } else {
                    $errors[] = 'Echec de l\'envoi du mail';
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides: 

    public function copyContactsFromOrigin($origin, &$errors = array())
    {
        if ($this->isLoaded() && BimpObject::objectLoaded($origin) && is_a($origin, 'BimpDolObject')) {
            BimpTools::resetDolObjectErrors($this->dol_object);
//            die('oooo');
            if ($this->dol_object->copy_linked_contact($origin->dol_object, 'internal') < 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la copie des contacts internes');
            }
            if ((int) $this->getData('fk_soc') === (int) $origin->getData('fk_soc')) {
                BimpTools::resetDolObjectErrors($this->dol_object);
                if ($this->dol_object->copy_linked_contact($origin->dol_object, 'external') < 0) {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la copie des contacts externes');
                }
            }
        }
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = array();

        if (in_array($this->object_name, array('Bimp_Propal', 'BS_SavPropal', 'Bimp_Commande', 'Bimp_Facture', 'BContract_contrat'))) {
            if ($this->field_exists('fk_soc') && (int) $this->getData('fk_soc')) {
                if ($this->object_name !== 'Bimp_Facture' || ($this->object_name === 'Bimp_Facture' && !$force_create)) {
                    $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', (int) $this->getData('fk_soc'));

                    if (BimpObject::objectLoaded($soc)) {
                        if (!(int) $soc->getData('status')) {
                            $errors[] = 'Ce client est désactivé';
                        } elseif ((int) !$soc->isSolvable($this->object_name, $warnings)) {
                            if (!BimpTools::getPostFieldValue('force_create_by_soc', 0)) {
                                $errors[] = 'Il n\'est pas possible de créer une pièce pour ce client (' . Bimp_Societe::$solvabilites[(int) $soc->getData('solvabilite_status')]['label'] . ')';
                            }
                        }
                    } else {
                        $errors[] = 'Client absent';
                    }
                }
            }
        }

        if (!count($errors)) {
            $errors = parent::create($warnings, $force_create);
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $errors = array();

        if ((int) $this->getData('fk_soc') !== (int) $this->getInitData('fk_soc')) {
            $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', (int) $this->getData('fk_soc'));

            if (!BimpObject::objectLoaded($soc)) {
                $errors[] = 'Le client d\'ID ' . $this->getData('fk_soc') . ' n\'existe pas';
            } elseif (!(int) $soc->getData('status')) {
                $errors[] = 'Ce client est désactivé';
            } elseif ((int) !$soc->isSolvable($this->object_name, $warnings)) {
                $errors[] = 'Il n\'est pas possible de créer une pièce pour ce client (' . Bimp_Societe::$solvabilites[(int) $soc->getData('solvabilite_status')]['label'] . ')';
            }
        }

        if (count($errors)) {
            return $errors;
        }

        return parent::update($warnings, $force_update);
    }
}

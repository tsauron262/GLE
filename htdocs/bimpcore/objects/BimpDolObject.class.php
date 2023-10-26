<?php

if (!defined('BIMP_LIB')) {
    require_once __DIR__ . '/../Bimp_Lib.php';
}

if (BimpCore::isModuleActive('bimpvalidation') && !defined('BV_LIB')) {
    require_once DOL_DOCUMENT_ROOT . '/bimpvalidation/BV_Lib.php';
}

if (BimpCore::isModuleActive('bimptocegid')) {
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/viewEcriture.class.php';
}

class BimpDolObject extends BimpObject
{

    public static $element_name = '';
    public static $dol_module = '';
    public static $mail_event_code = '';
    public static $email_type = '';
    public static $external_contact_type_required = true;
    public static $internal_contact_type_required = true;
    public $output = '';
    public static $expertise = [
        ''  => "",
        10  => "Arts graphiques",
        20  => "Constructions",
        30  => "Education & Administration (CRT)",
        35  => "Administration",
        40  => "Infrastructure",
        50  => "Marketing",
        60  => "Mobilité",
        70  => "Partner",
        80  => "Santé",
        90  => "SAV",
        14  => "Bureautique",
        15  => "Formation",
        16  => "Sécurité",
        100 => "Autre (ne pas utiliser)"
    ];

    // Droits user: 

    public function canEditCommercial()
    {
        if ($this->object_name === 'Bimp_Facture') {
            global $user;

            $secteur = $this->getData('ef_type');

            if ($secteur && in_array($secteur, array('M'))) {
                return 1;
            }

            if ($user->admin || $user->rights->bimpcommercial->edit_commercial) {
                return 1;
            }

            return 0;
        }

        return 1;
    }

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
        $cache_key = $this->module . '_' . $this->object_name . ($this->isLoaded() ? '_' . $this->id : '') . '_email_users_array';

        if (!isset(self::$cache[$cache_key])) {
            global $user, $langs, $conf;

            self::$cache[$cache_key] = array();

            // User connecté: 
            if (!empty($user->email)) {
                self::$cache[$cache_key][$user->email] = $user->getFullName($langs) . ' (' . $user->email . ')';
            }

            // E-mail entrepôt: 
            $id_ent = $this->getData('entrepot');
            if ($id_ent > 0) {
                $ent = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Entrepot', $id_ent);
                if (BimpObject::objectLoaded($ent)) {
                    $mail = $ent->getMail();

                    if ($mail) {
                        self::$cache[$cache_key][$mail] = $mail;
                    }
                }
            }

            if ($user->admin) {
                if (!empty($user->email_aliases)) {
                    foreach (explode(',', $user->email_aliases) as $alias) {
                        $alias = trim($alias);
                        if ($alias) {
                            $alias = str_replace('/</', '', $alias);
                            $alias = str_replace('/>/', '', $alias);
                            if (!isset(self::$cache[$cache_key][$alias])) {
                                self::$cache[$cache_key][$alias] = $user->getFullName($langs) . ' (' . $alias . ')';
                            }
                        }
                    }
                }

                // Société: 

                if (!empty($conf->global->MAIN_INFO_SOCIETE_MAIL)) {
                    self::$cache[$cache_key][$conf->global->MAIN_INFO_SOCIETE_MAIL] = $conf->global->MAIN_INFO_SOCIETE_NOM . ' (' . $conf->global->MAIN_INFO_SOCIETE_MAIL . ')';
                }

                if (!empty($conf->global->MAIN_INFO_SOCIETE_MAIL_ALIASES)) {
                    foreach (explode(',', $conf->global->MAIN_INFO_SOCIETE_MAIL_ALIASES) as $alias) {
                        $alias = trim($alias);
                        if ($alias) {
                            $alias = str_replace('/</', '', $alias);
                            $alias = str_replace('/>/', '', $alias);
                            if (!isset(self::$cache[$cache_key][$alias])) {
                                self::$cache[$cache_key][$alias] = $conf->global->MAIN_INFO_SOCIETE_NOM . ' (' . $alias . ')';
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
                            if (!empty($c_user->email) && !isset(self::$cache[$cache_key][$c_user->email])) {
                                self::$cache[$cache_key][$c_user->email] = $item['libelle'] . ': ' . $c_user->getFullName($langs) . ' (' . $c_user->email . ')';
                            }

                            if (!empty($c_user->email_aliases)) {
                                foreach (explode(',', $c_user->email_aliases) as $alias) {
                                    $alias = trim($alias);
                                    if ($alias) {
                                        $alias = str_replace('/</', '', $alias);
                                        $alias = str_replace('/>/', '', $alias);
                                        if (!isset(self::$cache[$cache_key][$alias])) {
                                            self::$cache[$cache_key][$alias] = $item['libelle'] . ': ' . $c_user->getFullName($langs) . ' (' . $alias . ')';
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $extra_emails = explode(',', BimpCore::getConf('extra_emails_from', null, 'bimpcommercial'));
            if (!empty($extra_emails)) {
                foreach ($extra_emails as $email) {
                    $email = BimpTools::cleanEmailsStr($email);
                    self::$cache[$cache_key][$email] = $email;
                }
            }
        }

        return self::$cache[$cache_key];
    }

    public function extrafieldsIsConfig($name)
    {
        $result = $this->db->getValue('extrafields', 'rowid', "`name` = '" . $name . "' AND `elementtype` = '" . static::$dol_module . "'");
        if ($result > 0)
            return 1;
        return 0;
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
                if ((!isset($emails[(int) $item['id']]) || $item['code'] == 'BILLING2') && $item['statuscontact'] == 1) {
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

                    if (dol_textishtml($content) && !dol_textishtml($formMail->substit['__USER_SIGNATURE__'])) {
                        $formMail->substit['__USER_SIGNATURE__'] = dol_nl2br($formMail->substit['__USER_SIGNATURE__']);
                    } else if (!dol_textishtml($content) && dol_textishtml($formMail->substit['__USER_SIGNATURE__'])) {
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
        $list = $this->getAllFiles();

        $id_main_pdf_file = (int) $this->getDocumentFileId();

        if ($id_main_pdf_file < 1)
            BimpController::addAjaxWarnings('Attention fichier PDF introuvable');

        if (!in_array($id_main_pdf_file, $values)) {
            $values[] = $id_main_pdf_file;
        }

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

        $files = $this->getFilesArray();

        foreach ($files as $id_file => $file_name) {
            if (!in_array($id_file, array($values))) {
                $file = BimpCache::getBimpObjectInstance('bimpcore', 'BimpFile', $id_file);
                if (BimpObject::objectLoaded($file)) {
                    if ((int) $file->getData('in_emails')) {
                        $values[] = (int) $id_file;
                    }
                }
            }
        }

        return $values;
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
                return DOL_URL_ROOT . '/' . $page . '.php?modulepart=' . $module_part . '&entity=' . $this->dol_object->entity . '&file=' . urlencode($this->getRef()) . '/' . urlencode($file_name);
            }
        }

        return '';
    }

    public function getCommercialId($params = array(), &$is_superior = false, &$is_default = false)
    {
        $params = BimpTools::overrideArray(array(
                    'check_active'    => false,
                    'allow_superior'  => false,
                    'allow_default'   => false,
                    'id_default_user' => (int) BimpCore::getConf('default_id_commercial', null)
                        ), $params);

        if ($this->isLoaded()) {
            $users = $this->dol_object->getIdContact('internal', 'SALESREPFOLL');
            if (!empty($users)) {
                $user = null;

                foreach ($users as $id_user) {
                    $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user);

                    if (!BimpObject::objectLoaded($user)) {
                        continue;
                    }

                    if (!(int) $user->getData('statut')) {
                        continue;
                    }
                }

                if (BimpObject::objectLoaded($user)) {
                    if (!$params['check_active'] || ($params['check_active'] && (int) $user->getData('statut'))) {
                        return $user->id;
                    } elseif ($params['check_active'] && !(int) $user->getData('statut')) {
                        if ($params['allow_superior']) {
                            if ((int) $user->getData('fk_user')) {
                                $is_superior = true;
                                return (int) $user->getData('fk_user');
                            }
                        }

                        if ($params['allow_default']) {
                            $is_default = true;
                            return $params['id_default_user'];
                        }
                    }
                } elseif ($params['allow_default']) {
                    $is_default = true;
                    return $params['id_default_user'];
                }
            }
        }

        return 0;
    }

    public function getIdTypeContact($type = 0, $code = '')
    {
        $list = $this->dol_object->liste_type_contact($type, 'position', 0, 0, $code);
        foreach ($list as $id => $inut)
            return $id;
    }

    public function getContactsByCodes($source = 'external')
    {
        $contacts = array();

        if ($this->isLoaded()) {
            $items = $this->dol_object->liste_contact(-1, $source);

            foreach ($items as $item) {
                if (!isset($contacts[$item['code']])) {
                    $contacts[$item['code']] = array();
                }

                $contacts[$item['code']][] = $item['id'];
            }
        }

        return $contacts;
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
                if (is_array($models) && count($models)) {
                    $html .= '<div class="' . static::$dol_module . 'PdfGenerateContainer" style="' . ($file_url ? 'margin-top: 15px; display: none;' : '') . '">';
                    $html .= BimpInput::renderInput('select', static::$dol_module . '_model_pdf', $this->getModelPdf(), array(
                                'options' => $models
                    ));
                    $onclick = 'var model = $(this).parent(\'.' . static::$dol_module . 'PdfGenerateContainer\').find(\'[name=' . static::$dol_module . '_model_pdf]\').val();';
                    $onclick .= 'setObjectAction($(this), ' . $this->getJsObjectData() . ', \'generatePdf\', {model: model});';
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

    public function renderExtraFile($withThisObject = true)
    {
        $html = "";

        if ($withThisObject) {
            $html .= $this->renderListFileForObject($this);
        }

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
            $collection = array();

            if ($this->isDolObject()) {
                $propal_instance = null;
                $facture_instance = null;
                $commande_instance = null;
                $commande_fourn_instance = null;
                foreach (BimpTools::getDolObjectLinkedObjectsList($this->dol_object, $this->db) as $item) {
                    $collection[$item['type']][] = $item['id_object'];
                }
            }

            foreach ($collection as $type => $ids) {
                switch ($type) {
                    case 'fichinter':
                        $bcInter = BimpCollection::getInstance('bimptechnique', 'BT_ficheInter');
                        $bcInter->addFields(array('datec', 'fk_statut', 'datei'));
                        $bcInter->addItems($ids);
                        foreach ($ids as $id) {
                            $fi_instance = $bcInter->getObjectInstance((int) $id);

                            if (BimpObject::objectLoaded($fi_instance)) {
                                $icon = $fi_instance->params['icon'];
                                $objects[] = array(
                                    'type'   => BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($fi_instance->getLabel()),
                                    'ref'    => $fi_instance->getNomUrl(0, true, true, null),
                                    'date'   => $fi_instance->displayData('datei'),
                                    'status' => $fi_instance->displayData('fk_statut')
                                );
                            }
                        }
                        break;
                    case 'facture':
                        $bc = BimpCollection::getInstance('bimpcommercial', 'Bimp_Facture');
                        $bc->addFields(array('datef', 'total_ht', 'fk_statut'));
                        $bc->addItems($ids);
                        foreach ($ids as $id) {
                            $facture_instance = $bc->getObjectInstance((int) $id);
//                            $facture_instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id);
                            if ($facture_instance && $facture_instance->isLoaded()) {
                                $icon = $facture_instance->params['icon'];
                                $objects[] = array(
                                    'type'     => BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($facture_instance->getLabel()),
                                    'ref'      => $facture_instance->getNomUrl(0, true, true, null),
                                    'date'     => $facture_instance->displayData('datef'),
                                    'total_ht' => $facture_instance->displayData('total_ht'),
                                    'status'   => $facture_instance->displayData('fk_statut')
                                );
                            }
                        }
                        break;
                    case 'propal':
                        $bc = BimpCollection::getInstance('bimpcommercial', 'Bimp_Propal');
                        $bc->addFields(array('datep', 'total_ht', 'fk_statut'));
                        $bc->addItems($ids);
                        foreach ($ids as $id) {
                            $propal_instance = $bc->getObjectInstance((int) $id);
                            if ($propal_instance->isLoaded()) {
                                $icon = $propal_instance->params['icon'];
                                $objects[] = array(
                                    'type'     => BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($propal_instance->getLabel()),
                                    'ref'      => $propal_instance->getNomUrl(0, true, true, null),
                                    'date'     => $propal_instance->displayData('datep'),
                                    'total_ht' => $propal_instance->displayData('total_ht'),
                                    'status'   => $propal_instance->displayData('fk_statut')
                                );
                            }
                        }
                        break;

                    case 'bf_demande':
                        $bc = BimpCollection::getInstance('bimpfinancement', 'BF_Demande');
                        $bc->addFields(array('date_create', 'montant_materiels', 'montant_services', 'montant_logiciels', 'status'));
                        $bc->addItems($ids);
                        foreach ($ids as $id) {
                            $demande_instance = $bc->getObjectInstance((int) $id);
                            if ($demande_instance->isLoaded()) {
                                $icon = $demande_instance->params['icon'];
                                $objects[] = array(
                                    'type'     => BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($demande_instance->getLabel()),
                                    'ref'      => $demande_instance->getLink(),
                                    'date'     => $demande_instance->displayData('date_create', 'default', false),
                                    'total_ht' => BimpTools::displayMoneyValue($demande_instance->getTotalDemandeHT(), 'EUR', 0, 0, 0, 2, 1),
                                    'status'   => $demande_instance->displayData('status', 'default', false)
                                );
                            }
                        }
                        break;

                    default:
                        foreach ($ids as $id) {//TODO a traduire au dessus en collection
                            $item['id_object'] = $id;
                            switch ($type) {
                                case 'propal':
                                    $propal_instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', (int) $item['id_object']);
                                    if ($propal_instance->isLoaded()) {
                                        $icon = $propal_instance->params['icon'];
                                        $objects[] = array(
                                            'type'     => BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($propal_instance->getLabel()),
                                            'ref'      => $propal_instance->getNomUrl(0, true, true, null),
                                            'date'     => $propal_instance->displayData('datep'),
                                            'total_ht' => $propal_instance->displayData('total_ht'),
                                            'status'   => $propal_instance->displayData('fk_statut')
                                        );
                                    }
                                    break;

                                case 'commande':
                                    $commande_instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $item['id_object']);
                                    if ($commande_instance->isLoaded()) {
                                        $icon = $commande_instance->params['icon'];
                                        $objects[] = array(
                                            'type'     => BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($commande_instance->getLabel()),
                                            'ref'      => $commande_instance->getNomUrl(0, true, true, null),
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
                                            'ref'      => $commande_fourn_instance->getNomUrl(0, true, true, null),
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
                                            'ref'      => $facture_fourn_instance->getNomUrl(0, true, true, null),
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

                                case 'bimp_contrat':
                                    $contrat_instance = BimpCache::getBimpObjectInstance('bimpcontrat', 'BCT_Contrat', (int) $item['id_object']);
                                    if (BimpObject::objectLoaded($contrat_instance)) {
                                        $icon = $contrat_instance->params['icon'];
                                        $objects[] = array(
                                            'type'     => BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($contrat_instance->getLabel()),
                                            'ref'      => $contrat_instance->getNomUrl(0, true, true, 'full'),
                                            'date'     => $contrat_instance->displayData('datec'),
                                            'total_ht' => (isset($contrat_instance->dol_object->total_ht) ? BimpTools::displayMoneyValue($contrat_instance->dol_object->total_ht) : ''),
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
                                    $collection['fichinter'][] = $item['id_object'];
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
                        break;
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

    public function renderMailExtraFilesInput($input_name)
    {
        $html = '';

        $linked_objects = $this->getFullLinkedObjetsArray(true);

        if (!empty($linked_objects)) {
            $files = array();
            $values = array();

            foreach ($linked_objects as $obj_data => $obj_label) {
                $obj_data = json_decode($obj_data, 1);
                $obj = BimpCache::getBimpObjectInstance($obj_data['module'], $obj_data['object_name'], $obj_data['id_object']);

                if (BimpObject::objectLoaded($obj)) {
                    $obj_files = $obj->getFilesArray();

                    if (!empty($obj_files)) {
                        foreach ($obj_files as $id_file => $file_label) {
                            $files[$id_file] = $obj_label . ' : ' . $file_label;

                            if (!in_array($id_file, $values)) {
                                $file = BimpCache::getBimpObjectInstance('bimpcore', 'BimpFile', $id_file);
                                if (BimpObject::objectLoaded($file)) {
                                    if ($file->getData('in_emails')) {
                                        $values[] = $id_file;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $html .= BimpInput::renderInput('check_list', $input_name, $values, array(
                        'items' => $files
            ));
        } else {
            $html .= BimpRender::renderAlerts('Aucun objet lié trouvé', 'warning');
        }

        return $html;
    }

    public function renderContactsList($type = 0, $code = '', $list_id = '')
    {
        $html = '';

        $list = array();

        if ($this->isLoaded() && method_exists($this->dol_object, 'liste_contact')) {
            $list_int = $list_ext = array();
            if ($type == 0 || $type == 1 || $type == 'internal')
                $list_int = $this->dol_object->liste_contact(-1, 'internal', 0, $code);
            if ($type == 0 || $type == 2 || $type == 'external')
                $list_ext = $this->dol_object->liste_contact(-1, 'external', 0, $code);
            $list = BimpTools::merge_array($list_int, $list_ext);
        }

        if (count($list)) {
            global $conf;
            BimpTools::loadDolClass('societe');
            BimpTools::loadDolClass('contact');

            if ($list_id == '')
                $list_id = $this->object_name . ((int) $this->id ? '_' . $this->id : '') . '_contacts_list_' . $type . '_' . $code;

            foreach ($list as $item) {
                $html .= '<tr>';
                switch ($item['source']) {
                    case 'internal':
                        $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $item['id']);
                        if ($type == 0)
                            $html .= '<td>Utilisateur</td>';
                        $html .= '<td>' . $conf->global->MAIN_INFO_SOCIETE_NOM . '</td>';
                        $html .= '<td>';
                        if (BimpObject::objectLoaded($user)) {
                            $html .= $user->getLink();
                        } else {
                            $html .= '<span class="danger">L\'utilisateur #' . $item['id'] . ' n\'existe plus</span>';
                        }
                        $html .= '</td>';
                        break;

                    case 'external':
                        $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', (int) $item['socid']);
                        $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', (int) $item['id']);

                        if ($type == 0)
                            $html .= '<td>Contact tiers</td>';
                        $html .= '<td>';
                        if (BimpObject::objectLoaded($soc)) {
                            $html .= $soc->getLink();
                        } else {
                            $html .= '<span class="danger">Le tiers #' . $item['socid'] . ' n\'existe plus</span>';
                        }
                        $html .= '</td>';
                        $html .= '<td>';
                        if (BimpObject::objectLoaded($contact)) {
                            $html .= $contact->getLink();
                        } else {
                            $html .= '<span class="danger">Le contact #' . $item['id'] . ' n\'existe plus</span>';
                        }
                        $html .= '</td>';
                        break;
                }
                if ($code == '')
                    $html .= '<td>' . $item['libelle'] . '</td>';
                $html .= '<td style="text-align: right">';
                $html .= BimpRender::renderRowButton('Supprimer le contact', 'trash', $this->getJsActionOnclick('removeContact', array('id_contact' => (int) $item['rowid']), array(
                                    'confirm_msg'      => 'Etes-vous sûr de vouloir supprimer ce contact?',
                                    'success_callback' => 'function(result) {if (result.contact_list_html) {$(\'#' . $list_id . '\').html(result.contact_list_html);}}'
                )));
                $html .= '</td>';
                $html .= '</tr>';
            }
        } else {
            $html .= '<tr>';
            $html .= '<td colspan="5">';
            $html .= BimpRender::renderAlerts('Aucun contact enregistré', 'info');
            $html .= '</td>';
            $html .= '</tr>';
        }

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
                } else {
                    if ($this->field_exists('model_pdf'))
                        $this->updateField('model_pdf', $data['model']);
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

            $files = array();
//            $filename_list = array();
//            $mimetype_list = array();
//            $mimefilename_list = array();
            // Fichiers joints: 
            if (isset($data['join_files']) && is_array($data['join_files'])) {
                foreach ($data['join_files'] as $id_file) {
                    $file = BimpCache::getBimpObjectInstance('bimpcore', 'BimpFile', (int) $id_file);
                    if ($file->isLoaded()) {
                        $file_path = $file->getFilePath();
                        $file_name = $file->getData('file_name') . '.' . $file->getData('file_ext');
                        if (!file_exists($file_path)) {
                            $errors[] = 'Le fichier "' . $file_name . '" n\'existe pas';
                        } else {
                            $files[] = array($file_path, dol_mimetype($file_name), $file_name);
//                            $filename_list[] = $file_path;
//                            $mimetype_list[] = dol_mimetype($file_name);
//                            $mimefilename_list[] = $file_name;
                        }
                    } else {
                        $errors[] = 'Le fichier d\'ID ' . $id_file . ' n\'existe pas';
                    }
                }
            }

            // Fichiers joints des objets liés: 
            if (isset($data['extra_joins_files']) && is_array($data['extra_joins_files'])) {
                foreach ($data['extra_joins_files'] as $id_file) {
                    $file = BimpCache::getBimpObjectInstance('bimpcore', 'BimpFile', (int) $id_file);
                    if ($file->isLoaded()) {
                        $file_path = $file->getFilePath();
                        $file_name = $file->getData('file_name') . '.' . $file->getData('file_ext');
                        if (!file_exists($file_path)) {
                            $errors[] = 'Le fichier "' . $file_name . '" n\'existe pas';
                        } else {
                            $files[] = array($file_path, dol_mimetype($file_name), $file_name);
//                            $filename_list[] = $file_path;
//                            $mimetype_list[] = dol_mimetype($file_name);
//                            $mimefilename_list[] = $file_name;
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
                $mail_object = $data['mail_object'];

                $id_model = (int) BimpTools::getPostFieldValue('id_model', 0);

                if ($id_model) {
                    global $langs;
                    $template = self::getEmailTemplateData($id_model);
                    $langs->tab_translate['InvoiceSentByEMail'] .= ' modéle : ' . $template['label'];
                }

                $deliveryreceipt = (isset($data['confirm_reception']) ? (int) $data['confirm_reception'] : 0);
                $bimpMail = new BimpMail($this, $mail_object, $to, $from, $data['msg_html'], '', $cc, '', $deliveryreceipt);
                $bimpMail->addFiles($files);
//                if (mailSyn2($mail_object, $to, $from, $data['msg_html'], $filename_list, $mimetype_list, $mimefilename_list, $cc, '', $deliveryreceipt)) {
                if ($bimpMail->send($errors)) {
                    if (static::$mail_event_code) {
                        include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                        global $user, $langs, $conf;
                        $interface = new Interfaces($this->db->db);
                        if ($interface->run_triggers(static::$mail_event_code, $this->dol_object, $user, $langs, $conf) < 0) {
                            $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($interface), 'Echec de l\'enregistrement de l\'envoi du mail dans la liste des événements');
                        }
                    }
                } elseif (!count($errors)) {
                    $errors[] = 'Echec envoi de l\'e-mail pour une raison inconnue';
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionAddContact($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Ajout du contact effectué avec succès' . $success;

        if (!$this->isLoaded()) {
            $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
        } else {
            if (!isset($data['type']) || !(int) $data['type']) {
                $errors[] = 'Nature du contact absent';
            } else {
                switch ((int) $data['type']) {
                    case 1:
                        $id_contact = isset($data['id_contact']) ? (int) $data['id_contact'] : 0;
                        $type_contact = isset($data['tiers_type_contact']) ? (int) $data['tiers_type_contact'] : 0;
                        if (!$id_contact) {
                            $errors[] = 'Contact non spécifié';
                        }
                        if (!$type_contact && static::$external_contact_type_required) {
                            $errors[] = 'Type de contact non spécifié';
                        }

                        if (!count($errors)) {
                            if ($this->dol_object->add_contact($id_contact, $type_contact, 'external') <= 0) {
                                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de l\'ajout du contact');
                            }
                        }
                        break;

                    case 2:
                        $id_user = isset($data['id_user']) ? (int) $data['id_user'] : 0;
                        $type_contact = isset($data['user_type_contact']) ? (int) $data['user_type_contact'] : 0;
                        if (!$id_user) {
                            $errors[] = 'Utilisateur non spécifié';
                        }

                        if (!$type_contact && static::$internal_contact_type_required) {
                            $errors[] = 'Type de contact non spécifié';
                        }

                        $id_type_commercial = (int) $this->db->getValue('c_type_contact', 'rowid', 'source = \'internal\' AND element = \'' . $this->dol_object->element . '\' AND code = \'SALESREPFOLL\'');
                        if ($type_contact == $id_type_commercial) {
                            if (!$this->canEditCommercial()) {
                                $errors[] = 'Vous n\'avez pas la permission de changer le commercial ' . $this->getLabel('of_a');
                            } else {
                                $id_cur_commercial = $this->getCommercialId();

                                if ($id_cur_commercial) {
                                    $list = $this->dol_object->liste_type_contact();
                                    $label = (isset($list[$id_type_commercial]) ? $list[$id_type_commercial] : 'Responsable suivi ' . $this->getLabel());
                                    $msg = 'Un "' . $label . '" a déjà été attribué à ' . $this->getLabel('this') . '<br/>';
                                    $msg .= 'Si vous souhaitez modifier celui-ci, veuillez d\'abord le supprimer';
                                    $errors[] = $msg;
                                }
                            }
                        }

                        if (!count($errors)) {
                            if ($this->dol_object->add_contact($id_user, $type_contact, 'internal') <= 0) {
                                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de l\'ajout du contact');
                            }
                        }
                        break;
                }
            }
        }

        return array(
            'errors'            => $errors,
            'warnings'          => $warnings,
            'contact_list_html' => $this->renderContactsList(),
            'succes_callback'   => 'if('
        );
    }

    // Overrides:

    public function copyContactsFromOrigin($origin, &$errors = array())
    {
        if ($this->isLoaded() && BimpObject::objectLoaded($origin) && is_a($origin, 'BimpDolObject')) {
            BimpTools::resetDolObjectErrors($this->dol_object);

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
                            } else {
                                global $user, $langs;
                                $errors = array_merge($errors, $soc->addObjectLog('Création ' . $this->getLabel() . ' forcée par ' . $user->getFullName($langs)));
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

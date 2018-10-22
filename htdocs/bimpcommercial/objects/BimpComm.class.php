<?php

class BimpComm extends BimpObject
{

    public static $comm_type = '';
    public static $email_type = '';
    public $remise_globale_line_rate = null;

    // Getters: 

    public static function getInstanceByType($type, $id_object = null)
    {
        switch ($type) {
            case 'propal':
                return BimpObject::getInstance('bimpcommercial', 'Bimp_Propal', $id_object);

            case 'facture':
                return BimpObject::getInstance('bimpcommercial', 'Bimp_Facture', $id_object);

            case 'commande':
                return BimpObject::getInstance('bimpcommercial', 'Bimp_Commande', $id_object);

            case 'commande_fourn':
                return BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFourn', $id_object);
        }

        return null;
    }

    public function isEditable()
    {
        if ($this->isLoaded()) {
            if ((int) $this->getData('fk_statut') === 0) {
                return 1;
            }

            return 0;
        }

        return 1;
    }

    public function isDeletable()
    {
        return 1;
    }

    public function getModelPdf()
    {
        if ($this->field_exists('model_pdf')) {
            return $this->getData('model_pdf');
        }

        return '';
    }

    public function getModelsPdfArray()
    {
        return array();
    }

    public function getAddContactIdClient()
    {
        $id_client = (int) BimpTools::getPostFieldValue('id_client');
        if (!$id_client) {
            $id_client = (int) $this->getData('fk_soc');
        }

        return $id_client;
    }

    public function getClientContactsArray()
    {

        $id_client = $this->getAddContactIdClient();
        return self::getSocieteContactsArray($id_client, false);
    }

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

        if (empty($user->email)) {
            $langs->load('errors');
            $emails['user'] = $user->getFullName($langs) . ' &lt;' . $langs->trans('ErrorNoMailDefinedForThisUser') . '&gt;';
        } else {
            $emails['user'] = $user->getFullName($langs) . ' &lt;' . $user->email . '&gt;';
        }

        $emails['company'] = $conf->global->MAIN_INFO_SOCIETE_NOM . ' &lt;' . $conf->global->MAIN_INFO_SOCIETE_MAIL . '&gt;';

        $aliases = array('user_aliases' => $user->email_aliases, 'global_aliases' => $conf->global->MAIN_INFO_SOCIETE_MAIL_ALIASES);

        foreach ($aliases as $type => $list) {
            $i = 0;
            foreach (explode(',', $list) as $alias) {
                $i++;
                $alias = trim($alias);
                if ($alias) {
                    $alias = preg_replace('/</', '&lt;', $alias);
                    $alias = preg_replace('/>/', '&gt;', $alias);
                    if (!preg_match('/&lt;/', $alias)) {
                        $alias = '&lt;' . $alias . '&gt;';
                    }
                    $emails[$type . '_' . $i] = $alias;
                }
            }
        }
        return $emails;
    }

    public function getActionsButtons()
    {
        return array();
    }

    public function getDirOutput()
    {
        return '';
    }

    public function getTotalTtc()
    {
        if ($this->isDolObject()) {
            if (property_exists($this->dol_object, 'total_ttc')) {
                return (float) $this->dol_object->total_ttc;
            }
        }

        return 0;
    }

    public function getTotalTtcWithoutRemises()
    {
        $total = 0;

        if ($this->isLoaded()) {
            $lines = $this->getChildrenObjects('lines');
            foreach ($lines as $line) {
                $total += $line->getTotalTtcWithoutRemises();
            }
        }
        return $total;
    }

    public function getTotalHt()
    {
        if ($this->isDolObject()) {
            if (property_exists($this->dol_object, 'total_ht')) {
                return (float) $this->dol_object->total_ht;
            }
        }

        return 0;
    }

    public function getRequestSelectRemisesFilters()
    {
        $filter = '';

        $discounts = (int) BimpTools::getValue('discounts', BimpTools::getValue('param_values/fields/discounts', 1));
        if ($discounts) {
            $filter = "(fk_facture_source IS NULL OR (fk_facture_source IS NOT NULL AND ((description LIKE '(DEPOSIT)%' OR description LIKE 'Acompte%') AND description NOT LIKE '(EXCESS RECEIVED)%')))";
        }
        $creditNotes = (int) BimpTools::getValue('credit_notes', BimpTools::getValue('param_values/fields/credit_notes', 1));
        if ($creditNotes) {
            if ($discounts) {
                $filter .= ' OR ';
            }
            $filter .= "(fk_facture_source IS NOT NULL AND ((description NOT LIKE '(DEPOSIT)%' AND description NOT LIKE 'Acompte%') OR description LIKE '(EXCESS RECEIVED)%'))";
        }

        if ($discounts && $creditNotes) {
            return '(' . $filter . ')';
        }

        return $filter;
    }

    public function getRemiseGlobaleAmount()
    {
        if ($this->isLoaded() && $this->field_exists('remise_globale')) {
            $total_ttc = (float) $this->getTotalTtcWithoutRemises();
            return round($total_ttc * ((float) $this->getData('remise_globale') / 100), 2);
        }

        return 0;
    }

    public function getDocumentFileId()
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        $ref = dol_sanitizeFileName($this->getRef());
        $where = '`parent_module` = \'' . $this->module . '\' AND `parent_object_name` = \'' . $this->object_name . '\' AND `id_parent` = ' . (int) $this->id;
        $where .= ' AND `file_name` = \'' . $ref . '\' AND `file_ext` = \'pdf\'';

        return (int) $this->db->getValue('bimp_file', 'id', $where);
    }

    public function getJoinFilesValues()
    {
        $values = BimpTools::getValue('fields/join_files', array());

        $id_main_pdf_file = (int) $this->getDocumentFileId();

        if (!in_array($id_main_pdf_file, $values)) {
            $values[] = $id_main_pdf_file;
        }

        return $values;
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
                }
            }
        }

        return $topic;
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

    public function getRemisesInfos()
    {
        $infos = array(
            'remises_lines_percent'     => 0,
            'remises_lines_amount_ht'   => 0,
            'remises_lines_amount_ttc'  => 0,
            'remise_globale_percent'    => 0,
            'remise_globale_amount_ht'  => 0,
            'remise_globale_amount_ttc' => 0,
            'remise_total_percent'      => 0,
            'remise_total_amount_ht'    => 0,
            'remise_total_amount_ttc'   => 0
        );

        if ($this->isLoaded()) {
            $total_ttc_without_remises = 0;

            $lines = $this->getChildrenObjects('lines');

            if ($this->field_exists('remise_globale')) {
                $infos['remise_globale_percent'] = (float) $this->getData('remise_globale');
            }

            foreach ($lines as $line) {
                $line_infos = $line->getRemiseTotalInfos();
                $infos['remises_lines_amount_ttc'] += (float) $line_infos['line_amount_ttc'];
                $infos['remises_lines_amount_ht'] += (float) $line_infos['line_amount_ht'];
                $total_ttc_without_remises += $line_infos['total_ttc_without_remises'];

                if ($infos['remise_globale_percent']) {
                    $infos['remise_globale_amount_ttc'] += $line_infos['remise_globale_amount_ttc'];
                    $infos['remise_globale_amount_ht'] += $line_infos['remise_globale_amount_ht'];
                }
            }

            if ($infos['remises_lines_amount_ttc']) {
                $infos['remises_lines_percent'] = ($infos['remises_lines_amount_ttc'] / $total_ttc_without_remises) * 100;
            }

            $infos['remise_total_percent'] = $infos['remises_lines_percent'] + $infos['remise_globale_percent'];
            $infos['remise_total_amount_ttc'] = $infos['remises_lines_amount_ttc'] + $infos['remise_globale_amount_ttc'];
            $infos['remise_total_amount_ht'] = $infos['remises_lines_amount_ht'] + $infos['remise_globale_amount_ht'];
        }

        return $infos;
    }

    public function getRemiseGlobaleLineRate()
    {
        if (is_null($this->remise_globale_line_rate)) {
            $this->remise_globale_line_rate = 0;

            $remise_globale = (float) $this->getData('remise_globale');
            if ($remise_globale) {
                $ttc = $this->getTotalTtcWithoutRemises();
                $remise_amount = $ttc * ($remise_globale / 100);
                $total_lines = 0;

                $lines = $this->getChildrenObjects('lines');
                foreach ($lines as $line) {
                    if ($line->isRemisable()) {
                        $total_lines += (float) $line->getTotalTtcWithoutRemises();
                    }
                }

                if ($total_lines) {
                    $this->remise_globale_line_rate = ($remise_amount / $total_lines) * 100;
                }
            }
        }

        return $this->remise_globale_line_rate;
    }

    // Getters - Overrides BimpObject

    public function getFilesDir()
    {
        if ($this->isLoaded()) {
            $dir_output = $this->getDirOutput();
            if ($dir_output) {
                return $dir_output . '/' . dol_sanitizeFileName($this->getRef()) . '/';
            }
        }

        return '';
    }

    public function getFileUrl($file_name)
    {
        $dir = $this->getFilesDir();
        if ($dir) {
            if (file_exists($dir . $file_name)) {
                return DOL_URL_ROOT . '/document.php?modulepart=' . static::$comm_type . '&file=' . htmlentities(dol_sanitizeFileName($this->getRef()) . '/' . $file_name);
            }
        }

        return '';
    }

    // Setters:

    public function setRef($ref)
    {
        if ($this->field_exists('ref')) {
            $this->set('ref', $ref);
            $dol_prop = $this->getConf('fields/ref/dol_prop', 'ref');
            if (property_exists($this->dol_object, $dol_prop)) {
                $this->dol_object->{$dol_prop} = $ref;
            }
        }
    }

    // Affichages: 

    public function displayRemisesClient()
    {
        $html = '';

        if ($this->isLoaded()) {
            $soc = $this->getChildObject('client');
            if (BimpObject::objectLoaded($soc)) {
                global $langs, $conf;

                $soc = $soc->dol_object;

//                if ($soc->remise_percent) {
//                    $html .= $langs->trans("CompanyHasRelativeDiscount", $soc->remise_percent);
//                } else {
//                    $html .= $langs->trans("CompanyHasNoRelativeDiscount");
//                }

                if (!empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) { // Never use this
                    $filterabsolutediscount = "fk_facture_source IS NULL"; // If we want deposit to be substracted to payments only and not to total of final invoice
                    $filtercreditnote = "fk_facture_source IS NOT NULL"; // If we want deposit to be substracted to payments only and not to total of final invoice
                } else {
                    $filterabsolutediscount = "fk_facture_source IS NULL OR (fk_facture_source IS NOT NULL AND ((description LIKE '(DEPOSIT)%' OR description LIKE 'Acompte%') AND description NOT LIKE '(EXCESS RECEIVED)%'))";
                    $filtercreditnote = "fk_facture_source IS NOT NULL AND ((description NOT LIKE '(DEPOSIT)%' AND description NOT LIKE 'Acompte%') OR description LIKE '(EXCESS RECEIVED)%')";
                }

                $absolute_discount = (float) round(price2num($soc->getAvailableDiscounts('', $filterabsolutediscount), 'MT'), 2);
                $absolute_creditnote = (float) round(price2num($soc->getAvailableDiscounts('', $filtercreditnote), 'MT'), 2);

                $can_use_discount = false;

//                if ($absolute_discount > 0.009) {
//                    $html .= '<br/>';
//                    $html .= $langs->trans("CompanyHasAbsoluteDiscount", price($absolute_discount), $langs->transnoentities("Currency" . $conf->currency));
                // bug dans dolibarr: Commande::insert_discount - désactivé
//                    if ($status === Commande::STATUS_DRAFT) {
//                        $can_use_discount = true;
//                    }
//                }
//                if ($absolute_creditnote > 0.009) {
//                    $html .= '<br/>';
//                    $html .= $langs->trans("CompanyHasCreditNote", price($absolute_creditnote), $langs->transnoentities("Currency" . $conf->currency));
//                }
//
//                if (!$absolute_discount && !$absolute_creditnote) {
//                    $html .= '<br/>';
//                    $html .= $langs->trans("CompanyHasNoAbsoluteDiscount");
//                }

                $html .= '<table class="bimp_list_table">';
                $html .= '<tbody>';
                $html .= '<tr>';
                $html .= '<td style="width: 140px">Remise par défaut: </td>';
                $html .= '<td style="font-weight: bold;">' . BimpTools::displayFloatValue((float) $soc->remise_percent) . '%</td>';
                $html .= '</tr>';

                $html .= '<tr>';
                $html .= '<td style="width: 140px">Acomptes: </td>';
                $html .= '<td style="font-weight: bold;">' . BimpTools::displayMoneyValue((float) $absolute_discount, 'EUR') . '</td>';
                $html .= '</tr>';

                $html .= '<tr>';
                $html .= '<td style="width: 140px">Avoirs: </td>';
                $html .= '<td style="font-weight: bold;">' . BimpTools::displayMoneyValue((float) $absolute_creditnote, 'EUR') . '</td>';
                $html .= '</tr>';
                $html .= '</tbody>';
                $html .= '</table>';

                if ($can_use_discount) {
                    $html .= '<div class="buttonsContainer align-right">';
                    $onclick = $this->getJsActionOnclick('useRemise', array(
                        'discounts'    => ($can_use_discount ? 1 : 0),
                        'credit_notes' => 0
                            ), array(
                        'form_name' => 'use_remise'
                    ));
                    $html .= '<button class="btn btn-default" onclick="' . $onclick . '">';
                    $html .= '<i class="' . BimpRender::renderIconClass('fas_file-import') . ' iconLeft"></i>Appliquer une remise disponible';
                    $html .= '</button>';

                    $html .= '</div>';
                }
            }
        }

        return $html;
    }

    public function displayTotalRemises()
    {
        $html = '';

        if ($this->isLoaded()) {
            $infos = $this->getRemisesInfos();

            if ($infos['remise_total_amount_ttc']) {
                $html .= '<table class="bimp_list_table">';

                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th></th>';
                $html .= '<th>HT</th>';
                $html .= '<th>TTC</th>';
                $html .= '<th>%</th>';
                $html .= '</tr>';
                $html .= '</thead>';

                $html .= '<tbody>';

                if ($infos['remises_lines_amount_ttc']) {
                    $html .= '<tr>';
                    $html .= '<td style="font-weight: bold;width: 160px;">Remises lignes: </td>';
                    $html .= '<td>' . BimpTools::displayMoneyValue($infos['remises_lines_amount_ht'], 'EUR') . '</td>';
                    $html .= '<td>' . BimpTools::displayMoneyValue($infos['remises_lines_amount_ttc'], 'EUR') . '</td>';
                    $html .= '<td>' . BimpTools::displayFloatValue($infos['remises_lines_percent'], 4) . ' %</td>';
                    $html .= '</tr>';
                }

                if ($infos['remise_globale_amount_ttc']) {
                    $html .= '<tr>';
                    $html .= '<td style="font-weight: bold;width: 160px;">Remise globale: </td>';
                    $html .= '<td>' . BimpTools::displayMoneyValue($infos['remise_globale_amount_ht'], 'EUR') . '</td>';
                    $html .= '<td>' . BimpTools::displayMoneyValue($infos['remise_globale_amount_ttc'], 'EUR') . '</td>';
                    $html .= '<td>' . BimpTools::displayFloatValue($infos['remise_globale_percent'], 4) . ' %</td>';
                    $html .= '</tr>';
                }

                $html .= '</tbody>';

                $html .= '<tfoot>';
                $html .= '<td style="font-weight: bold;width: 160px;">Total Remises: </td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($infos['remise_total_amount_ht'], 'EUR') . '</td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($infos['remise_total_amount_ttc'], 'EUR') . '</td>';
                $html .= '<td>' . BimpTools::displayFloatValue($infos['remise_total_percent'], 4) . ' %</td>';
                $html .= '</tfoot>';
                $html .= '</table>';
            } else {
                $html .= '<p>Aucune remise</p>';
            }
        }

        return $html;
    }

    public function displayPDFButton($display_generate = true)
    {
        $ref = dol_sanitizeFileName($this->getRef());

        if ($ref) {
            $file_url = $this->getFileUrl($ref . '.pdf');
            if ($file_url) {
                $onclick = 'window.open(\'' . $file_url . '\');';
                $button = '<button type="button" class="btn btn-default" onclick="' . $onclick . '">';
                $button .= '<i class="fas fa5-file-pdf iconLeft"></i>';
                $button .= $ref . '.pdf</button>';
                $html .= $button;
            }

            if ($display_generate) {
                $models = $this->getModelsPdfArray();
                if (count($models)) {
                    $html .= '<div class="' . static::$comm_type . 'PdfGenerateContainer" style="margin-top: 15px">';
                    $html .= BimpInput::renderInput('select', static::$comm_type . '_model_pdf', $this->getModelPdf(), array(
                                'options' => $models
                    ));
                    $onclick = 'var model = $(this).parent(\'.' . static::$comm_type . 'PdfGenerateContainer\').find(\'[name=' . static::$comm_type . '_model_pdf]\').val();setObjectAction($(this), ' . $this->getJsObjectData() . ', \'generatePdf\', {model: model}, null, null, null, null);';
                    $html .= '<button type="button" onclick="' . $onclick . '" class="btn btn-default">';
                    $html .= '<i class="fas fa5-sync iconLeft"></i>Générer';
                    $html .= '</button>';
                    $html .= '</div>';
                }
            }
        }

        return $html;
    }

    // Rendus HTML: 

    public function renderMarginsTable()
    {
        $html = '';

        if ($this->isLoaded()) {
            if (!class_exists('FormMargin')) {
                require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmargin.class.php';
            }

            $form = new FormMargin($this->db->db);
            $marginInfo = $form->getMarginInfosArray($this->dol_object);

            

            if (!empty($marginInfo)) {
                global $conf;
                $conf_tx_marque = (int) BimpCore::getConf('bimpcomm_tx_marque');

                $html .= '<table class="bimp_list_table">';

                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th>Marges</th>';
                $html .= '<th>Prix de vente</th>';
                $html .= '<th>Prix de revient</th>';
                $html .= '<th>Marge';
                if ($conf_tx_marque) {
                    $html .= ' (Tx marque)';
                } else {
                    $html .= ' (Tx marge)';
                }
                $html .= '</th>';
                $html .= '</tr>';
                $html .= '</thead>';

                $html .= '<tbody>';
                if (!empty($conf->product->enabled)) {
                    $html .= '<tr>';
                    $html .= '<td>Marge / Produits</td>';
                    $html .= '<td>' . price($marginInfo['pv_products']) . '</td>';
                    $html .= '<td>' . price($marginInfo['pa_products']) . '</td>';
                    $html .= '<td>' . price($marginInfo['margin_on_products']) . ' (';
                    if ($conf_tx_marque) {
                        $html .= round($marginInfo['mark_rate_products'], 4);
                    } else {
                        $html .= round($marginInfo['margin_rate_products'], 4);
                    }
                    $html .= ' %)</td>';
                    $html .= '</tr>';
                }

                if (!empty($conf->service->enabled)) {
                    $html .= '<tr>';
                    $html .= '<td>Marge / Services</td>';
                    $html .= '<td>' . price($marginInfo['pv_services']) . '</td>';
                    $html .= '<td>' . price($marginInfo['pa_services']) . '</td>';
                    $html .= '<td>' . price($marginInfo['margin_on_services']) . ' (';
                    if ($conf_tx_marque) {
                        $html .= round($marginInfo['mark_rate_services'], 4);
                    } else {
                        $html .= round($marginInfo['margin_rate_services'], 4);
                    }
                    $html .= ' %)</td>';
                    $html .= '</tr>';
                }
                $html .= '</tbody>';

                $html .= '<tfoot>';
                if (!empty($conf->product->enabled) && !empty($conf->service->enabled)) {
                    $html .= '<tr>';
                    $html .= '<td>Marge totale</td>';
                    $html .= '<td>' . price($marginInfo['pv_total']) . '</td>';
                    $html .= '<td>' . price($marginInfo['pa_total']) . '</td>';
                    $html .= '<td>' . price($marginInfo['total_margin']) . ' (';
                    if ($conf_tx_marque) {
                        $html .= round($marginInfo['total_mark_rate'], 4);
                    } else {
                        $html .= round($marginInfo['total_margin_rate'], 4);
                    }

                    $html .= ' %)</td>';
                    $html .= '</tr>';
                }
                $html .= '</tfoot>';
                $html .= '</table>';
            }
        }

        return $html;
    }

    public function renderFilesTable()
    {
        $html = '';

        if ($this->isLoaded()) {
            global $conf;

            $dir = $this->getDirOutput() . '/' . dol_sanitizeFileName($this->getRef());

            if (!function_exists('dol_dir_list')) {
                require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
            }

            $files_list = dol_dir_list($dir, 'files', 0, '', '(\.meta|_preview.*.*\.png)$', 'date', SORT_DESC);

            $html .= '<table class="bimp_list_table">';

            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th>Fichier</th>';
            $html .= '<th>Taille</th>';
            $html .= '<th>Date</th>';
            $html .= '<th></th>';
            $html .= '</tr>';
            $html .= '</thead>';

            $html .= '<tbody>';


            if (count($files_list)) {
                $url = DOL_URL_ROOT . '/document.php?modulepart=' . static::$comm_type . '&file=' . dol_sanitizeFileName($this->getRef()) . urlencode('/');
                foreach ($files_list as $file) {
                    $html .= '<tr>';

                    $html .= '<td><a class="btn btn-default" href="' . $url . $file['name'] . '" target="_blank">';
                    $html .= '<i class="' . BimpRender::renderIconClass(BimpTools::getFileIcon($file['name'])) . ' iconLeft"></i>';
                    $html .= $file['name'] . '</a></td>';

                    $html .= '<td>';
                    if (isset($file['size']) && $file['size']) {
                        $html .= $file['size'];
                    } else {
                        $html .= 'taille inconnue';
                    }
                    $html .= '</td>';

                    $html .= '<td>';
                    if ((int) $file['date']) {
                        $html .= date('d / m / Y H:i:s', $file['date']);
                    }
                    $html .= '</td>';


                    $html .= '<td class="buttons">';
                    $html .= BimpRender::renderRowButton('Aperçu', 'search', '', 'documentpreview', array(
                                'attr' => array(
                                    'target' => '_blank',
                                    'mime'   => dol_mimetype($file['name'], '', 0),
                                    'href'   => $url . $file['name'] . '&attachment=0'
                                )
                                    ), 'a');

                    $onclick = $this->getJsActionOnclick('deleteFile', array('file' => htmlentities($file['fullname'])), array(
                        'confirm_msg'      => 'Veuillez confirmer la suppression de ce fichier',
                        'success_callback' => 'function() {window.location.reload();}'
                    ));
                    $html .= BimpRender::renderRowButton('Supprimer', 'trash', $onclick);
                    $html .= '</td>';
                    $html .= '</tr>';
                }
            } else {
                $html .= '<tr>';
                $html .= '<td colspan="4">';
                $html .= BimpRender::renderAlerts('Aucun fichier', 'info');
                $html .= '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';

            $html = BimpRender::renderPanel('Documents PDF ' . $this->getLabel('of_the'), $html, '', array(
                        'icon'     => 'fas_file',
                        'type'     => 'secondary',
                        'foldable' => true
            ));
        }

        return $html;
    }

    public function renderEventsTable()
    {
        $html = '';

        if ($this->isLoaded()) {
            if (!class_exists('FormActions')) {
                require_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
            }

            BimpTools::loadDolClass('comm/action', 'actioncomm', 'ActionComm');

            $type_element = static::$comm_type;
            switch ($type_element) {
                case 'facture':
                    $type_element = 'invoice';
                    break;

                case 'commande':
                    $type_element = 'order';
                    break;
            }

            $list = ActionComm::getActions($this->db->db, (int) $this->getData('fk_soc'), $this->id, $type_element);
            if (!is_array($list)) {
                $html .= BimpRender::renderAlerts('Echec de la récupération de la liste des événements');
            } else {
                global $conf;

                $urlBack = DOL_URL_ROOT . '/' . $this->module . '/index.php?fc=' . $this->getController() . '&id=' . $this->id;
                $href = DOL_URL_ROOT . '/comm/action/card.php?action=create&datep=' . dol_print_date(dol_now(), 'dayhourlog');
                $href .= '&origin=' . static::$comm_type . '&originid=' . $this->id . '&socid=' . (int) $this->getData('fk_soc');
                $href .= '&backtopage=' . urlencode($urlBack);

                if (isset($this->dol_object->fk_project) && (int) $this->dol_object->fk_project) {
                    $href .= '&projectid=' . $this->dol_object->fk_project;
                }

                $html .= '<table class="bimp_list_table">';
                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th>Réf.</th>';
                $html .= '<th>Action</th>';
                $html .= '<th>Type</th>';
                $html .= '<th>Date</th>';
                $html .= '<th>Par</th>';
                $html .= '<th></th>';
                $html .= '</tr>';
                $html .= '</thead>';

                $html .= '<tbody>';

                if (count($list)) {
                    $userstatic = new User($this->db->db);

                    foreach ($list as $action) {
                        $html .= '<tr>';
                        $html .= '<td>' . $action->getNomUrl(1, -1) . '</td>';
                        $html .= '<td>' . $action->getNomUrl(0, 38) . '</td>';
                        $html .= '<td>';
                        if (!empty($conf->global->AGENDA_USE_EVENT_TYPE)) {
                            if ($action->type_picto) {
                                $html .= img_picto('', $action->type_picto);
                            } else {
                                switch ($action->type_code) {
                                    case 'AC_RDV':
                                        $html .= img_picto('', 'object_group');
                                        break;
                                    case 'AC_TEL':
                                        $html .= img_picto('', 'object_phoning');
                                        break;
                                    case 'AC_FAX':
                                        $html .= img_picto('', 'object_phoning_fax');
                                        break;
                                    case 'AC_EMAIL':
                                        $html .= img_picto('', 'object_email');
                                        break;
                                }
                                $html .= $action->type;
                            }
                        }
                        $html .= '</td>';
                        $html .= '<td align="center">';
                        $html .= dol_print_date($action->datep, 'dayhour');
                        if ($action->datef) {
                            $tmpa = dol_getdate($action->datep);
                            $tmpb = dol_getdate($action->datef);
                            if ($tmpa['mday'] == $tmpb['mday'] && $tmpa['mon'] == $tmpb['mon'] && $tmpa['year'] == $tmpb['year']) {
                                if ($tmpa['hours'] != $tmpb['hours'] || $tmpa['minutes'] != $tmpb['minutes'] && $tmpa['seconds'] != $tmpb['seconds']) {
                                    $html .= '-' . dol_print_date($action->datef, 'hour');
                                }
                            } else {
                                $html .= '-' . dol_print_date($action->datef, 'dayhour');
                            }
                        }
                        $html .= '</td>';
                        $html .= '<td>';
                        if (!empty($action->author->id)) {
                            $userstatic->id = $action->author->id;
                            $userstatic->firstname = $action->author->firstname;
                            $userstatic->lastname = $action->author->lastname;
                            $html .= $userstatic->getNomUrl(1, '', 0, 0, 16, 0, '', '');
                        }
                        $html .= '</td>';
                        $html .= '<td align="right">';
                        if (!empty($action->author->id)) {
                            $html .= $action->getLibStatut(3);
                        }
                        $html .= '</td>';
                        $html .= '<td></td>';
                        $html .= '</tr>';
                    }
                } else {
                    $html .= '<tr>';
                    $html .= '<td colspan="6">';
                    $html .= BimpRender::renderAlerts('Aucun événement enregistré', 'info');
                    $html .= '</td>';
                    $html .= '</tr>';
                }

                $html .= '</tbody>';
                $html .= '</table>';

                $html = BimpRender::renderPanel('Evénements', $html, '', array(
                            'foldable'       => true,
                            'type'           => 'secondary',
                            'icon'           => 'fas_clock',
                            'header_buttons' => array(
                                array(
                                    'label'       => 'Créer un événement',
                                    'icon_before' => 'plus-circle',
                                    'classes'     => array('btn', 'btn-default'),
                                    'attr'        => array(
                                        'onclick' => 'window.location = \'' . $href . '\''
                                    )
                                )
                            )
                ));
            }
        }

        return $html;
    }

    public function renderLinkedObjectsTable()
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
                            if (is_null($propal_instance)) {
                                $propal_instance = BimpObject::getInstance('bimpcommercial', 'Bimp_Propal');
                            }
                            if ($propal_instance->fetch((int) $item['id_object'])) {
                                $objects[] = array(
                                    'type'     => BimpTools::ucfirst($propal_instance->getLabel()),
                                    'ref'      => BimpObject::getInstanceNomUrlWithIcons($propal_instance),
                                    'date'     => $propal_instance->displayData('datep'),
                                    'total_ht' => $propal_instance->displayData('total_ht'),
                                    'status'   => $propal_instance->displayData('fk_statut')
                                );
                            }
                            break;

                        case 'facture':
                            if (is_null($facture_instance)) {
                                $facture_instance = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');
                            }
                            if ($facture_instance->fetch((int) $item['id_object'])) {
                                $objects[] = array(
                                    'type'     => BimpTools::ucfirst($facture_instance->getLabel()),
                                    'ref'      => BimpObject::getInstanceNomUrlWithIcons($facture_instance),
                                    'date'     => $facture_instance->displayData('datef'),
                                    'total_ht' => $facture_instance->displayData('total'),
                                    'status'   => $facture_instance->displayData('fk_statut')
                                );
                            }
                            break;

                        case 'commande':
                            if (is_null($commande_instance)) {
                                $commande_instance = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande');
                            }
                            if ($commande_instance->fetch((int) $item['id_object'])) {
                                $objects[] = array(
                                    'type'     => BimpTools::ucfirst($commande_instance->getLabel()),
                                    'ref'      => BimpObject::getInstanceNomUrlWithIcons($commande_instance),
                                    'date'     => $commande_instance->displayData('date_commande'),
                                    'total_ht' => $commande_instance->displayData('total_ht'),
                                    'status'   => $commande_instance->displayData('fk_statut')
                                );
                            }
                            break;

                        case 'order_supplier':
                            if (is_null($commande_fourn_instance)) {
                                $commande_fourn_instance = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFourn');
                            }
                            if ($commande_fourn_instance->fetch((int) $item['id_object'])) {
                                $objects[] = array(
                                    'type'     => BimpTools::ucfirst($commande_fourn_instance->getLabel()),
                                    'ref'      => BimpObject::getInstanceNomUrlWithIcons($commande_fourn_instance->getNomUrl),
                                    'date'     => '', //$commande_fourn_instance->displayData('date_commande'),
                                    'total_ht' => '', //$commande_fourn_instance->displayData('total_ht'),
                                    'status'   => $commande_fourn_instance->displayData('fk_statut')
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
                    $html .= '<tr>';
                    $html .= '<td>' . $data['type'] . '</td>';
                    $html .= '<td>' . $data['ref'] . '</td>';
                    $html .= '<td>' . $data['date'] . '</td>';
                    $html .= '<td>' . $data['total_ht'] . '</td>';
                    $html .= '<td>' . $data['status'] . '</td>';
//                    $html .= '<td style="text-align: right">';
//                    
//                    $html .= BimpRender::renderRowButton('Supprimer le lien', 'trash', '');
//
//                    $html .= '</td>';
                    $html .= '</tr>';
                }
            } else {
                $html .= '<tr>';
                $html .= '<td colspan="5">' . BimpRender::renderAlerts('Aucun objet lié', 'info') . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';

            $html = BimpRender::renderPanel('Objets liés', $html, '', array(
                        'foldable' => true,
                        'type'     => 'secondary',
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

    public function renderContacts()
    {
        $html = '';

        $html .= '<table class="bimp_list_table">';

        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Nature</th>';
        $html .= '<th>Tiers</th>';
        $html .= '<th>Utilisateur / Contact</th>';
        $html .= '<th>Type de contact</th>';
        $html .= '<th></th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $list_id = $this->object_name . ((int) $this->id ? '_' . $this->id : '') . '_contacts_list';
        $html .= '<tbody id="' . $list_id . '">';
        $html .= $this->renderContactsList();

        $html .= '</tbody>';

        $html .= '</table>';

        return BimpRender::renderPanel('Liste des contacts', $html, '', array(
                    'type'           => 'secondary',
                    'icon'           => 'user-circle',
                    'header_buttons' => array(
                        array(
                            'label'       => 'Ajouter un contact',
                            'icon_before' => 'plus-circle',
                            'classes'     => array('btn', 'btn-default'),
                            'attr'        => array(
                                'onclick' => $this->getJsActionOnclick('addContact', array('id_client' => (int) $this->getData('fk_soc')), array(
                                    'form_name'        => 'contact',
                                    'success_callback' => 'function(result) {if (result.contact_list_html) {$(\'#' . $list_id . '\').html(result.contact_list_html);}}'
                                ))
                            )
                        )
                    )
        ));
    }

    public function renderContactsList()
    {
        $html = '';

        $list = array();

        if ($this->isLoaded() && method_exists($this->dol_object, 'liste_contact')) {
            $list_int = $this->dol_object->liste_contact(-1, 'internal');
            $list_ext = $this->dol_object->liste_contact(-1, 'external');
            $list = array_merge($list_int, $list_ext);
        }

        if (count($list)) {
            global $conf;
            BimpTools::loadDolClass('societe');
            BimpTools::loadDolClass('contact');

            $soc = new Societe($this->db->db);
            $user = new User($this->db->db);
            $contact = new Contact($this->db->db);

            $list_id = $this->object_name . ((int) $this->id ? '_' . $this->id : '') . '_contacts_list';

            foreach ($list as $item) {
                $html .= '<tr>';
                switch ($item['source']) {
                    case 'internal':
                        $user->id = $item['id'];
                        $user->lastname = $item['lastname'];
                        $user->firstname = $item['firstname'];
                        $user->photo = $item['photo'];
                        $user->login = $item['login'];

                        $html .= '<td>Utilisateur</td>';
                        $html .= '<td>' . $conf->global->MAIN_INFO_SOCIETE_NOM . '</td>';
                        $html .= '<td>' . $user->getNomUrl(-1) . BimpRender::renderObjectIcons($user) . '</td>';
                        break;

                    case 'external':
                        $soc->fetch((int) $item['socid']);
                        $contact->id = $item['id'];
                        $contact->lastname = $item['lastname'];
                        $contact->firstname = $item['firstname'];

                        $html .= '<td>Contact tiers</td>';
                        $html .= '<td>' . $soc->getNomUrl(1) . BimpRender::renderObjectIcons($soc) . '</td>';
                        $html .= '<td>' . $contact->getNomUrl(1) . BimpRender::renderObjectIcons($contact) . '</td>';
                        break;
                }
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

    public function renderMailForm()
    {
        return BimpRender::renderAlerts('L\'envoi par email n\'est pas disponible pour ' . $this->getLabel('the_plur'));
    }

    public function renderContentExtraLeft()
    {
        return '';
    }

    public function renderContentExtraRight()
    {
        return '';
    }

    public function renderMailToInputs($input_name)
    {
        $html = '';

        $client = $this->getChildObject('client');

        $emails = array(
            '' => ''
        );

        if (BimpObject::objectLoaded($client)) {
            $client_emails = self::getSocieteEmails($client->dol_object);
            if (is_array($client_emails)) {
                foreach ($client_emails as $value => $label) {
                    $emails[$value] = $label;
                }
            }
        }

        $emails['custom'] = 'Autre';

        $html .= BimpInput::renderInput('select', $input_name . '_add_value', '', array(
                    'options'     => $emails,
                    'extra_class' => 'emails_select'
        ));

        $html .= '<p class="inputHelp selectMailHelp">';
        $html .= 'Sélectionnez une adresse e-mail puis cliquez sur "Ajouter"';
        $html .= '</p>';

        $html .= '<div class="mail_custom_value" style="display: none; margin-top: 10px">';
        $html .= BimpInput::renderInput('text', $input_name . '_add_value_custom', '');
        $html .= '<p class="inputHelp">Entrez une adresse e-mail valide puis cliquez sur "Ajouter"</p>';
        $html .= '</div>';

//        $html .= '</div>';
//        $html .= BimpInput::renderMultipleValuesList($this, $input_name . '_list', array(), $input_name . '_list_add_value');

        return $html;
    }

    // Traitements:

    public function checkLines()
    {
        $errors = array();

        if (($this->isLoaded())) {
            $dol_lines = array();
            $bimp_lines = array();

            foreach ($this->dol_object->lines as $line) {
                $dol_lines[(int) $line->id] = $line;
            }

            $bimp_line = $this->getChildObject('lines');
            $rows = $this->db->getRows($bimp_line->getTable(), '`id_obj` = ' . (int) $this->id, null, 'array', array('id', 'id_line', 'position', 'remise'));

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    $bimp_lines[(int) $r['id_line']] = array(
                        'id'       => (int) $r['id'],
                        'position' => (int) $r['position'],
                        'remise'   => (float) $r['remise']
                    );
                }
            }

            // Suppression des lignes absentes de l'objet dolibarr:
            foreach ($bimp_lines as $id_dol_line => $data) {
                if (!array_key_exists((int) $id_dol_line, $dol_lines)) {
                    if ($bimp_line->fetch($data['id'])) {
                        $bimp_line->delete();
                        unset($bimp_lines[$id_dol_line]);
                    }
                }
            }

            // Création des lignes absentes de l'objet bimp: 
            $objectLine = $this->getChildObject('lines');
            $bimp_line->reset();

            $i = 0;
            foreach ($dol_lines as $id_dol_line => $dol_line) {
                $i++;
                if (!array_key_exists($id_dol_line, $bimp_lines) && method_exists($objectLine, 'createFromDolLine')) {
                    $line_errors = $objectLine->createFromDolLine((int) $this->id, $dol_line, $warnings);
                    if (count($line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($line_errors, 'Des erreurs sont survenues lors de la récupération des données pour la ligne n° ' . $i);
                    }
                } elseif ((int) $bimp_lines[(int) $id_dol_line]['position'] !== (int) $dol_line->rang) {
                    $bimp_line->updateField('position', (int) $dol_line->rang, $bimp_lines[(int) $id_dol_line]['id']);
                } elseif ((float) $bimp_lines[(int) $id_dol_line]['remise'] !== (float) $dol_line->remise_percent) {
                    if ($bimp_line->fetch((int) $bimp_lines[(int) $id_dol_line]['id'], $this)) {
                        $bimp_line->checkRemises();
                    }
                }
            }
        }

        return $errors;
    }

    public function duplicate($new_data = array(), &$warnings = array(), $force_create = false)
    {
        $errors = array();

        if (!$force_create && !$this->canCreate()) {
            return array('Vous n\'avez pas la permission de créer ' . $this->getLabel('a'));
        }

        if (!$this->isLoaded()) {
            return array('ID ' . $this->getLabel('of_the') . ' absent');
        }

//        if (!$this->fetch($this->id)) {
//            return array(BimpTools::ucfirst($this->getLabel('this')) . ' est invalide. Copie impossible');
//        }

        if (!method_exists($this->dol_object, 'createFromClone')) {
            return array('Cette fonction n\'est pas disponible pour ' . $this->getLabel('the_plur'));
        }

        $validate_errors = $this->validate();
        if (count($validate_errors)) {
            return array(BimpTools::getMsgFromArray($validate_errors), BimpTools::ucfirst($this->getLabel('this')) . ' comporte des erreurs. Copie impossible');
        }

        global $user, $conf, $hookmanager;

//        $new_id = $this->dol_object->createFromClone(isset($new_data['fk_soc']) ? (int) $new_data['fk_soc'] : 0);

        $new_object = clone $this;
        $new_object->id = null;
        $new_object->id = 0;
        $new_object->set('id', 0);
        $new_object->set('ref', '');
        $new_object->set('fk_statut', 0);
        $new_object->set('remise_globale', 0);

        if (isset($new_data['fk_soc']) && ((int) $new_data['fk_soc'] !== (int) $this->getData('fk_soc'))) {
            $new_object->set('ref_client', '');
            $new_object->dol_object->fk_project = '';
            $new_object->dol_object->fk_delivery_address = '';
        } elseif (empty($conf->global->MAIN_KEEP_REF_CUSTOMER_ON_CLONING)) {
            $new_object->set('ref_client', '');
        }

        foreach ($new_data as $field => $value) {
            $new_object->set($field, $value);
        }

        $new_object->dol_object->user_author = $user->id;
        $new_object->dol_object->user_valid = '';

        $copy_errors = $new_object->create($warnings, $force_create);

        if (count($copy_errors)) {
            $errors[] = BimpTools::getMsgFromArray($copy_errors, 'Echec de la copie ' . $this->getLabel('of_the'));
        } else {
            $new_object->dol_object->error = '';
            $new_object->dol_object->errors = array();
            if ($new_object->dol_object->copy_linked_contact($this->dol_object, 'internal') < 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($new_object->dol_object), 'Echec de la copie des contacts internes');
            }
            $new_object->dol_object->error = '';
            $new_object->dol_object->errors = array();
            if ($new_object->dol_object->copy_linked_contact($this->dol_object, 'external') < 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($new_object->dol_object), 'Echec de la copie des contacts externes');
            }
            $lines_errors = $new_object->createLinesFromOrigin($this);
            if (count($lines_errors)) {
                $errors[] = BimpTools::getMsgFromArray($lines_errors, 'Des erreurs sont survenues lors de la copie des lignes ' . $this->getLabel('of_the'));
            } else {
                $remise_globale = (float) $this->getData('remise_globale');
                if ($remise_globale) {
                    $new_object->updateField('remise_globale', $remise_globale);
                    $lines = $new_object->getChildrenObjects('lines');
                    foreach ($lines as $line) {
                        $line->calcRemise();
                    }
                }

                if (is_object($hookmanager)) {
                    $parameters = array('objFrom' => $this->dol_object, 'clonedObj' => $new_object->dol_object);
                    $action = '';
                    $hookmanager->executeHooks('createFrom', $parameters, $new_object->dol_object, $action);
                }
            }

            $this->fetch($new_object->id);
        }

        return $errors;
    }

    public function createLinesFromOrigin($origin)
    {
        $errors = array();

        if (!BimpObject::objectLoaded($origin) || !is_a($origin, 'BimpComm')) {
            return array('Element d\'origine absent ou invalide');
        }

        $line_instance = BimpObject::getInstance($this->module, $this->object_name . 'Line');
        $lines = $origin->getChildrenObjects('lines', array(), 'position', 'asc');

        $warnings = array();
        $i = 0;
        $remise = BimpObject::getInstance('bimpcommercial', 'ObjectLineRemise');

        // Création des lignes: 
        foreach ($lines as $line) {
            $i++;
            $line_instance->reset();
            $line_instance->validateArray(array(
                'id_obj'    => (int) $this->id,
                'type'      => $line->getData('type'),
                'deletable' => $line->getData('deletable'),
                'editable'  => $line->getData('Editable'),
                'remisable' => $line->getData('remisable')
            ));
            $line_instance->desc = $line->desc;
            $line_instance->tva_tx = $line->tva_tx;
            $line_instance->id_product = $line->id_product;
            $line_instance->qty = $line->qty;
            $line_instance->pu_ht = $line->pu_ht;
            $line_instance->pa_ht = $line->pa_ht;
            $line_instance->id_fourn_price = $line->id_fourn_price;
            $line_instance->date_from = $line->date_from;
            $line_instance->date_to = $line->date_to;
            $line_instance->id_remise_except = $line->id_remise_except;

            $line_errors = $line_instance->create($warnings, true);
            if (count($line_errors)) {
                $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de la création de la ligne n°' . $i);
            }

            // Création des remises pour la ligne en cours:
            $remises = $line->getRemises();
            if (!is_null($remises) && count($remises)) {
                $j = 0;
                foreach ($remises as $r) {
                    $j++;
                    $remise->reset();
                    $remise->validateArray(array(
                        'id_object_line' => (int) $line_instance->id,
                        'object_type'    => $line_instance::$parent_comm_type,
                        'label'          => $r->getData('label'),
                        'type'           => (int) $r->getData('type'),
                        'percent'        => (float) $r->getData('percent'),
                        'montant'        => (float) $r->getData('montant'),
                        'per_unit'       => (int) $r->getData('per_unit')
                    ));
                    $remise_errors = $remise->create($warnings, true);
                    if (count($remise_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($remise_errors, 'Echec de la création de la remise n°' . $j . ' pour la ligne n°' . $i);
                    }
                }
            }
        }

        return $errors;
    }

    public function onChildSave($child)
    {
        if ($this->isLoaded()) {
            if (is_a($child, 'objectLine')) {
                // Vérification du changement de taux de remise globale: 
                $current_rate = (float) $this->remise_globale_line_rate;
                $this->remise_globale_line_rate = null;
                $new_rate = (float) $this->getRemiseGlobaleLineRate();

                if ($new_rate !== $current_rate) {
                    foreach ($this->getChildrenObjects('lines') as $line) {
                        $line->calcRemise();
                    }
                }
            }
        }
    }

    // Actions:

    public function actionGeneratePdf($data, &$success)
    {
        $errors = array();
        $warnings = array();
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
                    $ref = dol_sanitizeFileName($this->getRef());
                    $url = DOL_URL_ROOT . '/document.php?modulepart=' . static::$comm_type . '&file=' . $ref . '/' . $ref . '.pdf';
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

    public function actionValidate($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = BimpTools::ucfirst($this->getLabel('')) . ' validé';
        if ($this->isLabelFemale()) {
            $success .= 'e';
        }
        $success .= ' avec succès';
        $success_callback = 'window.location.reload();';

        global $conf, $langs, $user;

        $result = $this->dol_object->valid($user);
        if ($result > 0) {
            if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
                $this->fetch($this->id);
                $this->dol_object->generateDocument($this->getModelPdf(), $langs);
            }
        } else {
            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object, null, null, $warnings), 'Des erreurs sont survenues lors de la validation ' . $this->getLabel('of_the'));
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionModify($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Remise au statut "Brouillon" effectué avec succès';

        if (!$this->isLoaded()) {
            $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
        } elseif (!$this->canEdit()) {
            $errors[] = 'Vous n\'avez pas la permission d\'effectuer cette action';
        } elseif (!method_exists($this->dol_object, 'set_draft')) {
            $errors[] = 'Erreur: cette action n\'est pas possible';
        } else {
            global $user;
            if ($this->dol_object->set_draft($user) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la remise au statut "Brouillon"');
            } else {
                global $conf, $langs;

                if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
                    $this->fetch($this->id);
                    if ($this->dol_object->generateDocument($this->getModelPdf(), $langs) <= 0) {
                        $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Des erreurs sont survenues lors de la génération du document PDF');
                    }
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'window.location.reload();'
        );
    }

    public function actionAddContact($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Ajout du contact effectué avec succès';

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
                        if (!$type_contact) {
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
                        if (!$type_contact) {
                            $errors[] = 'Type de contact non spécifié';
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
            'contact_list_html' => $this->renderContactsList()
        );
    }

    public function actionRemoveContact($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Suppression du contact effectué avec succès';

        if (!$this->isLoaded()) {
            $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
        } else {
            if (!isset($data['id_contact']) || !(int) $data['id_contact']) {
                $errors[] = 'Contact à supprimer non spécifié';
            } else {
                if ($this->dol_object->delete_contact((int) $data['id_contact']) <= 0) {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la suppression du contact');
                }
            }
        }

        return array(
            'errors'            => $errors,
            'warnings'          => $warnings,
            'contact_list_html' => $this->renderContactsList()
        );
    }

    public function actionDuplicate($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Copie effectuée avec succès';

        $errors = $this->duplicate($data, $warnings);

        $url = '';

        if (!count($errors)) {
            $url = $_SERVER['php_self'] . '?fc=' . $this->getController() . '&id=' . $this->id;
        }

        $success_callback = 'window.location = \'' . $url . '\'';

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionUseRemise($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Remise insérée avec succès';

        $error_label = 'Insertion de la remise impossible';

        if (!method_exists($this->dol_object, 'insert_discount')) {
            $errors[] = 'L\'utilisation de remise n\'est pas possible pour ' . $this->getLabel('the_plur');
        } elseif (!isset($data['id_discount']) || !(int) $data['id_discount']) {
            $errors[] = 'Aucune remise sélectionnée';
        } elseif ((int) $this->getData('fk_statut') > 0) {
            $errors[] = $error_label . ' - ' . $this->getData('the') . ' doit avoit le statut "Brouillon';
        } else {
            if (!class_exists('DiscountAbsolute')) {
                require_once DOL_DOCUMENT_ROOT . '/core/class/discount.class.php';
            }
            $discount = new DiscountAbsolute($this->db->db);
            $discount->fetch((int) $data['id_discount']);

            if (!BimpObject::objectLoaded($discount)) {
                $errors[] = 'La remise d\'ID ' . $data['id_discount'] . ' n\'existe pas';
            } else {
//                echo $this->dol_object->insert_discount($discount->id); exit;
                if ($this->dol_object->insert_discount($discount->id) <= 0) {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de l\'insertion de la remise');
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'window.location.reload();'
        );
    }

    public function actionSendEMail($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Email envoyé avec succès';

        global $user, $langs, $conf;
        $langs->load('errors');

        if (!isset($data['from']) || !(string) $data['from']) {
            $errors[] = 'Emetteur absent';
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
            $from = '';
            $to = '';
            $cc = '';

            if ($data['from'] === 'user') {
                if (empty($user->email)) {
                    $errors[] = 'Aucune adresse e-mail enregistrée pour l\'utilisateur "' . $user->getFullName($langs) . '"';
                } else {
                    $from = $user->getFullName($langs) . ' <' . $user->email . '>';
                }
            } elseif ($data['from'] === 'company') {
                $from = $conf->global->MAIN_INFO_SOCIETE_NOM . ' <' . $conf->global->MAIN_INFO_SOCIETE_MAIL . '>';
            } elseif (preg_match('/^user_aliases_(\d+)$/', $data['from'], $matches)) {
                if (isset($user->email_aliases)) {
                    $aliases = explode(', ', (string) $user->email_aliases);
                    if (isset($aliases[((int) $matches[1]) - 1])) {
                        $from = $aliases[((int) $matches[1]) - 1];
                    }
                }
            } elseif (preg_match('/^global_aliases_(\d+)$/', $data['from'], $matches)) {
                if (isset($conf->global->MAIN_INFO_SOCIETE_MAIL_ALIASES)) {
                    $aliases = explode(',', $conf->global->MAIN_INFO_SOCIETE_MAIL_ALIASES);
                    if (isset($aliases[((int) $matches[1] - 1)])) {
                        $from = $aliases[((int) $matches[1]) - 1];
                    }
                }
            }

            $contact = BimpObject::getInstance('bimpcore', 'Bimp_Contact');

            foreach (array('mail_to', 'copy_to') as $type) {
                if (isset($data[$type]) && is_array($data[$type])) {
                    foreach ($data[$type] as $mail_to) {
                        $email = '';
                        if (preg_match('/^[0-9]+$/', '' . $mail_to)) {
                            if ($contact->fetch((int) $mail_to)) {
                                if (!(string) $contact->getData('email')) {
                                    $errors[] = 'Aucune adresse e-mail enregistrée pour le contact "' . $contact->getData('firstname') . ' ' . $contact->getData('lastname') . '"';
                                } else {
                                    $email = $contact->getData('firstname') . ' ' . $contact->getData('lastname') . ' <' . $contact->getData('email') . '>';
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
                                    if ($client->getData('nom')) {
                                        $email = $client->getData('nom') . ' <' . $client->getData('email') . '>';
                                    } else {
                                        $email = $client->getData('email');
                                    }
                                }
                            } else {
                                $errors[] = 'Aucun client enregistré pour ' . $this->getLabel('this');
                            }
                        } elseif (is_string($mail_to)) {
                            if (BimpValidate::isEmail($mail_to)) {
                                $email = $mail_to;
                            } else {
                                $errors[] = '"' . $mail_to . '" n\'est pas une adresse e-mail valide';
                            }
                        }

                        if ($email) {
                            switch ($type) {
                                case 'mail_to': $to .= ($to ? ', ' : '') . $email;
                                    break;

                                case 'copy_to': $cc .= ($cc ? ', ' : '') . $email;
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
                $file = BimpObject::getInstance('bimpcore', 'BimpFile');

                foreach ($data['join_files'] as $id_file) {
                    if ($file->fetch((int) $id_file)) {
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
                // todo: loguer l'envoi du mail
                $deliveryreceipt = (isset($data['confirm_reception']) ? (int) $data['confirm_reception'] : 0);
                mailSyn2($data['mail_object'], $to, $from, $data['msg_html'], $filename_list, $mimetype_list, $mimefilename_list, $cc, '', $deliveryreceipt);
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSetRemiseGlobale($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Remise globale enregistrée avec succès';

        $total_ttc_without_remises = $this->getTotalTtcWithoutRemises();

        if (!count($errors)) {
            if (isset($data['use_amount']) && (int) $data['use_amount']) {
                if (!isset($data['remise_globale_amount'])) {
                    $errors[] = 'Montant de la remise globale absent';
                }

                if ($total_ttc_without_remises > 0) {
                    $data['remise_globale'] = round(((float) $data['remise_globale_amount'] / $total_ttc_without_remises) * 100, 8);
                } else {
                    $data['remise_globale'] = 0;
                }
            }

            if (!count($errors)) {
                if (!isset($data['remise_globale'])) {
                    $errors[] = 'Montant de la remise globale absent';
                } else {
                    $errors = $this->updateField('remise_globale', round((float) $data['remise_globale'], 8));

                    if (!count($errors)) {
                        $lines = $this->getChildrenObjects('lines');
                        foreach ($lines as $line) {
                            $line->calcRemise();
                        }
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides BimpObject:

    public function create(&$warnings = array(), $force_create = false)
    {
        $origin = BimpTools::getValue('origin', '');
        $origin_id = BimpTools::getValue('origin_id', 0);
        $origin_object = null;

        if ($origin && $origin_id) {
            $origin_object = self::getInstanceByType($origin, $origin_id);

            if (!BimpObject::objectLoaded($origin_object)) {
                return array('Elément d\'origine invalide');
            }

            if ($this->isDolObject()) {
                $this->dol_object->origin = $origin;
                $this->dol_object->origin_id = $origin_id;

                $this->dol_object->linked_objects[$this->dol_object->origin] = $origin_id;
            }

            if ($this->field_exists('remise_globale') && $origin_object->field_exists('remise_globale')) {
                $this->set('remise_globale', (float) $origin_object->getData('remise_globale'));
            }
        }

        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            if ($origin && $origin_id) {
                $warnings = array_merge($warnings, $this->createLinesFromOrigin($origin_object));
            }
        }

        return $errors;
    }

    // Gestion des droits: 

    public function canView()
    {
        global $user;

//        echo '<pre>';
//        print_r($user->rights->bimpcommercial->read);
//        exit;
        if (isset($user->rights->bimpcommercial->read) && (int) $user->rights->bimpcommercial->read) {
            return 1;
        }

        return 0;
    }

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'setRemiseGlobale':
                if (!$this->isLoaded()) {
                    $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
                    return 0;
                }
                if (!$this->field_exists('remise_globale')) {
                    $errors[] = 'Les remises globales ne sont pas disponibles pour ' . $this->getLabel('the_plur');
                    return 0;
                }
                if (!$this->isEditable()) {
                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' ne peut plus être éditée';
                    return 0;
                }
                return 1;
        }

        return 1;
    }
}

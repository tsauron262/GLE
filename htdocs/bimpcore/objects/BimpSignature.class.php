<?php

class BimpSignature extends BimpObject
{
    /*
     * *** Mémo ajout signature pour un objet: ***
     * 
     * /!\ ATTENTION: le doc_type doit être unique : voir les doc_types utilisés dans bimpinterfaceclient > docController::display()
     * 
     * - Ajouter les champs "id_signature" et "signature_params" dans l'objet (+ définitions de l'objet BimpSignature) 
     * - Ajouter le tableau static $default_signature_params
     * - Ajouter une procédure pour créer la signature (action/fonction etc.)
     * - Ajouter les méthodes: 
     *      - getSignatureDocFileDir($doc_type): dossier fichier signé ou à signer
     *      - getSignatureDocFileName($doc_type, $signed = false): nom fichier signé ou à signer
     *      - getSignatureDocFileUrl($doc_type, $forced_context = '', $signed = false): URL fichier
     *      - getSignatureDocRef($doc_type): Reférence document
     *      - getSignatureParams($doc_type): Paramètres position signature sur PDF
     *      - onSigned($bimpSignature): Traitement post signature effectuée
     *      - onSignatureCancelled($bimpSignature): Traitement post signature annulée
     *      - isSignatureReopenable($doc_type, &$errors = array()): la signature peut-elle être réouverte (suite annulation) 
     *      - onSignatureReopened($bimpSignature): Traitement post réouverture signature
     * 
     * - Gérer l'enregistrement des paramètres de position de la signature sur le PDF au moment de sa génération (Si besoin) / ou régler par défaut pour les PDF fixes
     * - Intégrer selon le context: marqueur signé (champ booléen ou statut) / indicateur signature dans l'en-tête / etc. 
     * - Gérer Annulation signature si besoin
     * - Gérer Duplication / Révision / Etc. 
     * - Gérer la visualisation du docuement sur l'interface publique (bimpinterfaceclient > docController) 
     * - Gérer le droit canClientView() pour la visualisation du document sur l'espace public. 
     */

    const STATUS_REFUSED = -2;
    const STATUS_CANCELLED = -1;
    const STATUS_NONE = 0;
    const STATUS_SIGNED = 10;

    public static $status_list = array(
        self::STATUS_REFUSED   => array('label' => 'Refusée', 'icon' => 'fas_exclamation-circle', 'classes' => array('important')),
        self::STATUS_CANCELLED => array('label' => 'Annulée', 'icon' => 'fas_times-circle', 'classes' => array('danger')),
        self::STATUS_NONE      => array('label' => 'En attente de signature', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        self::STATUS_SIGNED    => array('label' => 'Signée', 'icon' => 'fas_check', 'classes' => array('success'))
    );

    // Droits users:

    public function canEditField($field_name)
    {
        if (in_array($field_name, array('allow_no_scan'))) {
            global $user;
            return (int) $user->admin;
        }

        return parent::canEditField($field_name);
    }

    public function canClientView()
    {
        global $userClient;

        if (!BimpObject::objectLoaded($userClient)) {
            return 0;
        }

        if ($this->isLoaded()) {
            if ((int) $this->db->getCount('bimpcore_signature_signataire', 'id_signature = ' . $this->id . ' AND id_client = ' . (int) $userClient->getData('id_client')) > 0) {
                return 1;
            }
            return 0;
        }

        return 1;
    }

    public function canDelete()
    {
        global $user;

        if ($user->admin) {
            return 1;
        }

        return 0;
    }

    public function canSetAction($action)
    {
        global $user;

        switch ($action) {
            case 'initDocuSign':
                return (int) $user->admin;

            case 'rebuildSignedDoc':
                if (in_array($user->login, array('admin', 'f.martinez'))) {
                    return 1;
                }
                return 0;
        }
        return parent::canSetAction($action);
    }

    // Getters booléens:

    public function isDeletable($force_delete = false, &$errors = [])
    {
        if (!$force_delete && $this->isSigned()) {
            return 0;
        }

        return 1;
    }

    public function isActionAllowed($action, &$errors = array())
    {
        if (!$this->isLoaded($errors)) {
            return 0;
        }

        switch ($action) {
            case 'signPapier':
            case 'signPapierNoScan':
            case 'initDocuSign':
                if ((int) $this->getData('status') > 0) {
                    $errors[] = 'Signature déjà effectuée ou annulée / refusée';
                    return 0;
                }

                if ((int) $this->getData('status') < 0) {
                    $errors[] = 'Signature annulée ou refusée';
                    return 0;
                }

                switch ($action) {
                    case 'signPapierNoScan':
                        if (!(int) $this->getData('allow_no_scan')) {
                            $errors[] = 'Scan du document signé obligatoire pour cette signature';
                            return 0;
                        }

                    case 'signPapier':
                        if ($this->getData('id_envelope_docu_sign')) {
                            $errors[] = 'Signature via DocuSign en attente';
                            return 0;
                        }
                        break;

                    case 'initDocuSign':
                        $this->getDocuSignApi($errors);
                        if (count($errors)) {
                            return 0;
                        }

                        $where = 'id_signature = ' . $this->id . ' AND status = 0 AND allow_docusign = 1';
                        if (!(int) $this->db->getCount('bimpcore_signature_signataire', $where)) {
                            $errors[] = 'Aucun signataire éligible à la signature via DocuSign';
                            return 0;
                        }
                        break;
                }
                return 1;

            case 'refreshDocuSign':
                return 1;

            case 'downloadDocuSignDocument':
                if (!$this->isSigned()) {
                    $errors[] = 'Signature non effectuée';
                    return 0;
                }

                if (!$this->getData('id_envelope_docu_sign')) {
                    $errors[] = 'Initialisation DocuSign non effectuée (Pas d\'ID DocuSign)';
                    return 0;
                }
                return 1;

            case 'cancel':
                if ((int) $this->getData('status') !== 0) {
                    $errors[] = 'Il n\'est pas possible d\'annuler la signature';
                    return 0;
                }

                $obj = $this->getObj();
                if ($this->isObjectValid($errors, $obj)) {
                    if (method_exists($obj, 'isSignatureCancellable')) {
                        if (!$obj->isSignatureCancellable($this->getData('doc_type'), $errors)) {
                            return 0;
                        }
                    }
                }
                return (count($errors) ? 0 : 1);

            case 'reopen':
                if ((int) $this->getData('status') >= 0) {
                    $errors[] = 'Cette signature n\'a pas besoin d\'être réouverte';
                    return 0;
                }

                $obj = $this->getObj();
                if ($this->isObjectValid($errors, $obj)) {
                    if (method_exists($obj, 'isSignatureReopenable')) {
                        if (!$obj->isSignatureReopenable($this->getData('doc_type'), $errors)) {
                            return 0;
                        }
                    }
                }
                return (count($errors) ? 0 : 1);

            case 'rebuildSignedDoc':
                return 1;
        }

        return parent::isActionAllowed($action, $errors);
    }

    public function isObjectValid(&$errors = array(), $obj = null)
    {
        if (is_null($obj)) {
            $obj = $this->getObj();
        }

        if (!is_a($obj, 'BimpObject')) {
            $errors[] = 'Objet lié invalide';
            return 0;
        }

        if (!BimpObject::objectLoaded($obj)) {
            $errors[] = 'ID ' . $obj->getLabel('of_the') . ' absent';
            return 0;
        }

        return 1;
    }

    public function isSigned()
    {
        return ($this->getData('status') > 0);
    }

    public function isAttenteDocuSign()
    {
        if ($this->isLoaded()) {
            if ((int) $this->db->getCount('bimpcore_signature_signataire', 'id_signature = ' . $this->id . ' AND status = 1')) {
                return 1;
            }
        }

        return 0;
    }

    public function hasMultipleFiles()
    {
        if (!(int) $this->getData('allow_multiple_files')) {
            return 0;
        }

        $files = $this->getUnsignedFilesNames();
        return (count($files) > 1 ? 1 : 0);
    }

    // Getters params: 

    public function getActionsButtons($include_link = false)
    {
        $buttons = array();

        $signataires = $this->getChildrenObjects('signataires');

        foreach ($signataires as $signataire) {
            $buttons = BimpTools::merge_array($buttons, $signataire->getActionsButtons(count($signataires) > 1));
        }

        if ($this->isActionAllowed('initDocuSign') && $this->canSetAction('initDocuSign')) {
            $buttons[] = array(
                'label'   => 'Initialiser DocuSign',
                'icon'    => 'fas_arrow-down',
                'onclick' => $this->getJsActionOnclick('initDocuSign', array(), array(
                    'form_name' => 'init_docu_sign'
                ))
            );
        }

        if ($this->isActionAllowed('refreshDocuSign') && $this->canSetAction('refreshDocuSign')) {
            $buttons[] = array(
                'label'   => 'Actualiser DocuSign',
                'icon'    => 'fas_sync',
                'onclick' => $this->getJsActionOnclick('refreshDocuSign', array(), array(
                    'form_name' => 'refresh_docusign'
                ))
            );
        }

        if ($this->isActionAllowed('signPapier') && $this->canSetAction('signPapier')) {
            $buttons[] = array(
                'label'   => 'Déposer document signé',
                'icon'    => 'fas_file-download',
                'onclick' => $this->getJsLoadModalForm('sign_papier', 'Dépôt document signé')
            );
        }

        if ($this->isActionAllowed('signPapierNoScan') && $this->canSetAction('signPapierNoScan')) {
            $buttons[] = array(
                'label'   => 'Signature papier sans scan',
                'icon'    => 'fas_file-contract',
                'onclick' => $this->getJsActionOnclick('signPapierNoScan', array(), array(
                    'form_name' => 'sign_no_scan'
                ))
            );
        }

        if ($this->isActionAllowed('cancel') && $this->canSetAction('cancel')) {
            $buttons[] = array(
                'label'   => 'Annuler cette signature',
                'icon'    => 'fas_times',
                'onclick' => $this->getJsActionOnclick('cancel', array(), array(
                    'form_name' => 'motif'
                ))
            );
        }

        if ($this->isActionAllowed('reopen') && $this->canSetAction('reopen')) {
            $buttons[] = array(
                'label'   => 'Réouvrir la signature',
                'icon'    => 'fas_undo',
                'onclick' => $this->getJsActionOnclick('reopen', array(), array(
                    'confirm_msg' => 'Veuillez confirmer la réouvertur de la signature'
                ))
            );
        }

        if ($this->isActionAllowed('rebuildSignedDoc') && $this->canSetAction('rebuildSignedDoc')) {
            $buttons[] = array(
                'label'   => 'Recontruire le doc signé',
                'icon'    => 'fas_redo',
                'onclick' => $this->getJsActionOnclick('rebuildSignedDoc', array(), array(
                    'confirm_msg' => 'Confirm'
                ))
            );
        }
        $buttons = BimpTools::merge_array($buttons, $this->getSignedButtons());

        if ($include_link && $this->isLoaded()) {
            $url = $this->getUrl();
            $buttons[] = array(
                'label'   => 'Accéder à la fiche signature',
                'icon'    => 'far_file',
                'onclick' => 'window.open(\'' . $url . '\');'
            );
        }
        return $buttons;
    }

    public function getSignedButtons()
    {
        $buttons = array();

        if ($this->isActionAllowed('downloadDocuSignDocument') && $this->canSetAction('downloadDocuSignDocument')) {
            if (is_file($this->getDocumentFileDir() . $this->getDocumentFileName(true))) {
                $buttons[] = array(
                    'label'   => 'Télécharger à nouveau le document DocuSign',
                    'icon'    => 'fas_file-download',
                    'onclick' => $this->getJsActionOnclick('downloadDocuSignDocument', array(), array('confirm_msg' => "Le fichier existe déjà, télécharger à nouveau et remplacer ?")));
            } else {
                $buttons[] = array(
                    'label'   => 'Télécharger le document DocuSign',
                    'icon'    => 'fas_file-download',
                    'onclick' => $this->getJsActionOnclick('downloadDocuSignDocument'));
            }
        }

        return $buttons;
    }

    public function getListExtraBtn()
    {
        return $this->getActionsButtons();
    }

    // Getters arrays: 

    public function getSignatairesArray()
    {
        $signataires = array();

        if ($this->isLoaded()) {
            foreach ($this->getChildrenObjects('signataires') as $signataire) {
                $signataires[(int) $signataire->id] = $signataire->getName();
            }
        }

        return $signataires;
    }

    public function getUnsignedFilesArray($include_empty = true)
    {
        $files = ($include_empty ? array('' => '') : array());

        $i = 0;
        foreach ($this->getUnsignedFilesNames() as $file_name) {
            $i++;
            $files[$file_name] = 'Document n°' . $i . ' (' . $file_name . ')';
        }

        return $files;
    }

    // Getters defaults: 

    public static function getDefaultSignDistEmailContent($type = 'elec')
    {
        $message = 'Bonjour, <br/><br/>';
        $message .= 'La signature du document "{NOM_DOCUMENT}" pour {NOM_PIECE} {REF_PIECE}  est en attente.<br/><br/>';
//        $message .= '{NOM_PIECE} {REF_PIECE} est en attente de validation.<br/><br/>';

        switch ($type) {
            case 'docusign':
                $message .= 'Merci de bien vouloir le signer électroniquement en suivant les instructions DocuSign.<br/><br/>';
                break;

            case 'elec':
            default:
                $message .= 'Vous pouvez effectuer la signature électronique de ce document directement depuis votre {LIEN_ESPACE_CLIENT} ou nous retourner le document ci-joint signé.<br/><br/>';
                break;

            case 'papier':
                $message .= 'Merci d\'imprimer ce document et de nous le retourner signé.<br/><br/>';
        }

        $message .= 'Vous en remerciant par avance, nous restons à votre disposition pour tout complément d\'information..<br/><br/>';
        $message .= 'Cordialement';

        $signature = BimpCore::getConf('signature_emails_client');
        if ($signature) {
            $message .= ', <br/><br/>' . $signature;
        }

        return $message;
    }

    // Getters données: 

    public function getInputValue($field_name)
    {
        switch ($field_name) {
            case 'email_content':
                $signature_type = BimpTools::getPostFieldValue('signature_type', 'elec');
                $errors = array();
                $obj = $this->getObj();
                if ($this->isObjectValid($errors, $obj)) {
                    if (method_exists($obj, 'getSignatureEmailContent')) {
                        return $obj->getSignatureEmailContent($signature_type);
                    }
                }
                return self::getDefaultSignDistEmailContent($signature_type);
        }

        return null;
    }

    public function getObj()
    {
        $module = $this->getData('obj_module');
        $name = $this->getData('obj_name');
        $id_obj = (int) $this->getData('id_obj');

        if ($module && $name) {
            if ($id_obj) {
                return BimpCache::getBimpObjectInstance($module, $name, $id_obj);
            }

            return BimpObject::getInstance($module, $name);
        }

        return null;
    }

    public function getSignataire($code = 'default')
    {
        if ($this->isLoaded()) {
            return BimpCache::findBimpObjectInstance('bimpcore', 'BimpSignataire', array(
                        'id_signature' => (int) $this->id,
                        'code'         => $code
            ));
        }

        return null;
    }

    public function getCheckMentions($signataire = 'default')
    {
        $obj = $this->getObj();

        $errors = array();

        if ($this->isObjectValid($errors, $obj)) {
            if (method_exists($obj, 'getSignatureCheckMentions')) {
                return $obj->getSignatureCheckMentions($this->getData('doc_type'), $signataire);
            }
        }

        return array();
    }

    public function getDocuSignApi(&$errors = array(), $check_api = true)
    {
        require_once DOL_DOCUMENT_ROOT . '/bimpapi/BimpApi_Lib.php';

        $api = BimpAPI::getApiInstance('docusign');

        if (!is_a($api, 'DocusignAPI')) {
            $errors[] = 'API DocuSign non installée';
            return null;
        }

        if ($check_api) {
            $api->isOk($errors);
        }

        return $api;
    }

    public function getSignatureParams($signataire, $type_params = 'docusign', &$errors = array(), $file_idx = 0)
    {
        $obj = $this->getObj();

        $errors = array();

        if (!BimpObject::objectLoaded($signataire)) {
            $errors[] = 'Signataire invalide';
        } elseif ($this->isObjectValid($errors, $obj)) {
            $field_name = $this->getData('obj_params_field');
            if ($field_name && $obj->field_exists($field_name)) {
                $params = $obj->getData($field_name);
            } elseif (property_exists($obj, $field_name)) {
                $params = $obj::${$field_name};
            } elseif (method_exists($obj, 'getSignatureParams')) {
                $params = $obj->getSignatureParams($this->getData('doc_type'));
            }

            if (empty($params)) {
                $errors[] = 'Aucun paramètre trouvé pour cette signature';
            }

            if ($file_idx > 0) {
                if (!isset($params[$file_idx])) {
                    $errors[] = 'Paramètres absents pour la signature électronique du document sélectionné';
                } else {
                    $params = $params[$file_idx];
                }
            }

            if (!count($errors)) {
                $code_signataire = $signataire->getData('code');

                if (!isset($params[$code_signataire])) {
                    if ($code_signataire === 'default') {
                        if (isset($params[$type_params])) {
                            return $params[$type_params];
                        }
                        if ($type_params === 'elec') {
                            if (isset($params['x_pos'])) {
                                return $params;
                            }
                        }
                    }
                } else {
                    if (!isset($params[$code_signataire][$type_params])) {
                        if ($type_params === 'elec') {
                            if (isset($params[$code_signataire]['x_pos'])) {
                                return $params[$code_signataire];
                            }
                        }
                    }
                }

                if (!isset($params[$code_signataire])) {
                    $errors[] = 'Aucun paramètre trouvé pour le signataire "' . $signataire->getData('label') . '"';
                } elseif (!isset($params[$code_signataire][$type_params])) {
                    $msg = 'Aucun paramètre ';
                    switch ($type_params) {
                        case 'elec':
                            $msg .= 'd\'incrustation de la signature éléctronique';
                            break;

                        case 'docusign':
                            $msg .= 'DocuSign';
                            break;
                    }
                    $msg .= ' trouvé pour le signataire "' . $signataire->getData('label') . '"';
                    $errors[] = $msg;
                } else {
                    return $params[$code_signataire][$type_params];
                }
            }
        }

        return array();
    }

    public function getSignatureParamsFormValues($signataire = null)
    {
        $params = $this->getSignatureParams($signataire);

        foreach ($params as $key => $value) {
            $params[$key] = (int) $value;
        }

        return array(
            'fields' => $params
        );
    }

    public function getDocuSignSignersParams(&$errors = array(), $email_body = '', $api_mode = 'test')
    {
        $obj = $this->getObj();
        if (!$this->isObjectValid($errors, $obj)) {
            return array();
        }

        $signers = array();

        $signataires = $this->getChildrenObjects('signataires', array(
            'status'         => 0,
            'allow_docusign' => 1
                ), 'position', 'asc');

        if (!count($signataires)) {
            $errors[] = 'Aucun signataire éligible à la signature DocuSign trouvé';
        } else {
            $devs_email = BimpCore::getConf('devs_email', 'dev@bimp.fr', 'bimpcore');
            $doc_type = $this->getData('doc_type');
            $doc_title = strip_tags($this->displayDocTitle());
            $nom_piece = $obj->getLabel('the');
            $ref_piece = $obj->getRef();
            $lien_espace_client = '<a href="' . self::getPublicBaseUrl(false, BimpPublicController::getPublicEntityForObjectSecteur($obj)) . '">espace client</a>';

            $i = 1;
            foreach ($signataires as $signataire) {
                if (!$signataire->getData('nom')) {
                    $errors[] = 'Nom absent pour le signataire "' . $signataire->getData('label') . '"';
                    continue;
                }

                $signature_params = $this->getSignatureParams($signataire, 'docusign', $errors);

                if (!empty($signature_params)) {
                    if (!$email_body) {
                        if (method_exists($obj, 'getDocuSignEmailContent')) {
                            $email_body = $obj->getDocuSignEmailContent($doc_type, $signataire);
                        } else {
                            $email_body = self::getDefaultSignDistEmailContent('docusign');
                        }
                    }

                    $email_body = str_replace(array(
                        '{NOM_DOCUMENT}',
                        '{NOM_PIECE}',
                        '{REF_PIECE}',
                        '{LIEN_ESPACE_CLIENT}'
                            ), array(
                        $doc_title,
                        ucfirst($nom_piece),
                        $ref_piece,
                        $lien_espace_client
                            ), $email_body);

                    $font_size = BimpTools::getArrayValueFromPath($signature_params, 'fs', 'Size9');
                    $anchor = BimpTools::getArrayValueFromPath($signature_params, 'anch', 'Signature :');
                    $email = ($api_mode === 'prod' ? $signataire->getData('email') : $devs_email);

                    $params = array(
                        'email'             => $email,
                        'name'              => $signataire->getData('nom'),
                        'signerEmail'       => $email,
                        'recipientId'       => $i,
                        'routingOrder'      => $i,
                        'emailNotification' => array(
                            'emailSubject' => $doc_title,
                            'emailBody'    => $email_body
                        ),
                        'tabs'              => array(
                            'signHereTabs' => array(
                                array(
                                    'name'          => "Signature",
                                    'anchorString'  => $anchor,
                                    'anchorXOffset' => BimpTools::getArrayValueFromPath($signature_params, 'x', 0),
                                    'anchorYOffset' => BimpTools::getArrayValueFromPath($signature_params, 'y', 0),
                                ),
                            ),
                        )
                    );

                    if (isset($signature_params['texts'])) {
                        $params['tabs']['textTabs'] = array();
                        foreach ($signature_params['texts'] as $field_name => $text_params) {
                            $textTab = array(
                                'name'          => BimpTools::getArrayValueFromPath($text_params, 'label', ucfirst($field_name)),
                                'anchorString'  => $anchor,
                                'anchorXOffset' => BimpTools::getArrayValueFromPath($text_params, 'x', 0),
                                'anchorYOffset' => BimpTools::getArrayValueFromPath($text_params, 'y', 0),
                                'fontSize'      => $font_size,
                                'required'      => (bool) BimpTools::getArrayValueFromPath($text_params, 'req', true),
                                'height'        => BimpTools::getArrayValueFromPath($text_params, 'h', 14),
                                'width'         => BimpTools::getArrayValueFromPath($text_params, 'w', 60)
                            );

                            if ($signataire->field_exists($field_name)) {
                                $value = (string) $signataire->getData($field_name);
                                if ($value) {
                                    $textTab['value'] = $value;
                                }
                            }
                            $params['tabs']['textTabs'][] = $textTab;
                        }
                    }

                    if (isset($signature_params['date'])) {
                        $params['tabs']['dateSignedTabs'] = array();
                        $params['tabs']['dateSignedTabs'][] = array(
                            'name'          => "Date signature",
                            'anchorString'  => $anchor,
                            'anchorXOffset' => BimpTools::getArrayValueFromPath($signature_params, 'date/x', 0),
                            'anchorYOffset' => BimpTools::getArrayValueFromPath($signature_params, 'date/y', 0),
                            'fontSize'      => $font_size,
                        );
                    }

                    // Attention le nom sera automatiquement celui indiqué dans $params['name']
                    // Pour demander à saisir un autre nom utiliser plutôt un textTab
                    if (isset($signature_params['nom'])) {
                        $params['tabs']['fullNameTabs'] = array();
                        $params['tabs']['fullNameTabs'][] = array(
                            'name'          => "Nom signataire",
                            'anchorString'  => $anchor,
                            'anchorXOffset' => BimpTools::getArrayValueFromPath($signature_params, 'nom/x', 0),
                            'anchorYOffset' => BimpTools::getArrayValueFromPath($signature_params, 'nom/y', 0),
                            'fontSize'      => $font_size,
                            'required'      => (bool) BimpTools::getArrayValueFromPath($signature_params, 'fonction/req', true),
                            'height'        => BimpTools::getArrayValueFromPath($signature_params, 'nom/h', 14),
                            'width'         => BimpTools::getArrayValueFromPath($signature_params, 'nom/w', 60)
                        );
                    }

                    if (isset($signature_params['fonction'])) {
                        $params['tabs']['titleTabs'] = array();
                        $params['tabs']['titleTabs'][] = array(
                            'name'          => "Qualité signataire",
                            'anchorString'  => $anchor,
                            'anchorXOffset' => BimpTools::getArrayValueFromPath($signature_params, 'fonction/x', 0),
                            'anchorYOffset' => BimpTools::getArrayValueFromPath($signature_params, 'fonction/y', 0),
                            'fontSize'      => $font_size,
                            'required'      => (bool) BimpTools::getArrayValueFromPath($signature_params, 'fonction/req', true),
                            'height'        => BimpTools::getArrayValueFromPath($signature_params, 'fonction/h', 14),
                            'width'         => BimpTools::getArrayValueFromPath($signature_params, 'fonction/w', 60)
                        );
                    }

                    $signers[] = $params;
                    $i++;
                } else {
                    $errors[] = 'Paramètre DocuSign absents pour le signataire "' . $signataire->getName() . '"';
                }
            }
        }

        return $signers;
    }

    public function getOnSignedNotificationEmail(&$use_as_from = false)
    {
        $obj = $this->getObj();

        $errors = array();
        if ($this->isObjectValid($errors, $obj)) {
            if (method_exists($obj, 'getOnSignedNotificationEmail')) {
                return $obj->getOnSignedNotificationEmail($this->getData('doc_type'), $use_as_from);
            }
        }

        return '';
    }

    public function getFileIdx($file_name)
    {
        $file_idx = 0;

        $file_name_base = pathinfo($this->getDocumentFileName(false), PATHINFO_FILENAME);
        if (preg_match('/^' . preg_quote($file_name_base, '/') . '\-(\d+)\.pdf$/', $file_name, $matches)) {
            $file_idx = (int) $matches[1];
        }

        return $file_idx;
    }

    // Getters Doc infos: 

    public function getDocumentFileName($signed = false)
    {
        $doc_type = $this->getData('doc_type');

        if ($doc_type) {
            $obj = $this->getObj();
            $errors = array();

            if ($this->isObjectValid($errors, $obj)) {
                if (method_exists($obj, 'getSignatureDocFileName')) {
                    $file_name = $obj->getSignatureDocFileName($doc_type, $signed);

                    if ($signed) {
                        $file_ext = strtolower($this->getData('signed_doc_ext'));

                        if ($file_ext && $file_ext !== 'pdf') {
                            $file_name = pathinfo($file_name, PATHINFO_FILENAME) . '.' . $file_ext;
                        }
                    }

                    return $file_name;
                }
            }
        }

        return '';
    }

    public function getDocumentUrl($signed = false, $forced_context = '')
    {
        $doc_type = $this->getData('doc_type');

        if ($doc_type) {
            $obj = $this->getObj();
            $errors = array();

            if ($this->isObjectValid($errors, $obj)) {
                if (method_exists($obj, 'getSignatureDocFileUrl')) {
                    $ext = 'pdf';
                    if ($signed && $this->getData('signed_doc_ext')) {
                        $ext = strtolower($this->getData('signed_doc_ext'));
                    }
                    return $obj->getSignatureDocFileUrl($doc_type, $forced_context, $signed);
                }
            }
        }

        return '';
    }

    public function getDocumentFileDir()
    {
        $doc_type = $this->getData('doc_type');

        if ($doc_type) {
            $obj = $this->getObj();
            $errors = array();

            if ($this->isObjectValid($errors, $obj)) {
                $dir = '';
                if (method_exists($obj, 'getSignatureDocFileDir')) {
                    $dir = $obj->getSignatureDocFileDir($doc_type);
                } else {
                    $dir = $obj->getFilesDir();
                }

                return $dir;
            }
        }

        return '';
    }

    public function getDocumentFilePath($signed = false)
    {
        $doc_type = $this->getData('doc_type');

        if ($doc_type) {
            $obj = $this->getObj();
            $errors = array();

            if ($this->isObjectValid($errors, $obj)) {
                $dir = '';
                if (method_exists($obj, 'getSignatureDocFileDir')) {
                    $dir = $obj->getSignatureDocFileDir($doc_type);
                } else {
                    $dir = $obj->getFilesDir();
                }

                if ($dir) {
                    $file = $this->getDocumentFileName($signed);

                    if ($file) {
                        return $dir . $file;
                    }
                }
            }
        }

        return '';
    }

    public function getUnsignedFilesNames()
    {
        if (!$this->isLoaded()) {
            return array();
        }

        $key = 'bimp_signature_' . $this->id . '_unsigned_files';

        if (!isset(self::$cache[$key])) {
            $files = array();

            $dir = $this->getDocumentFileDir();
            $file_name = $this->getDocumentFileName();

            if (file_exists($dir . $file_name)) {
                $files[] = $file_name;
            } elseif ((int) $this->getData('allow_multiple_files')) {
                if (is_dir($dir)) {
                    $pathinfo = pathinfo($file_name);
                    foreach (scandir($dir) as $f) {
                        if (in_array($f, array('.', '..'))) {
                            continue;
                        }

                        if (preg_match('/^' . preg_quote($pathinfo['filename'], '/') . '(\-\d+)?\.' . $pathinfo['extension'] . '$/', $f)) {
                            $files[] = $f;
                        }
                    }
                }
            }

            sort($files, SORT_NATURAL);
            self::$cache[$key] = $files;
        }

        return self::$cache[$key];
    }

    public function getMailFiles()
    {
        $files = array();

        $dir = $this->getDocumentFileDir();
        foreach ($this->getUnsignedFilesNames() as $file_name) {
            $files[] = array($dir . $file_name, 'application/' . pathinfo($file_name, PATHINFO_EXTENSION), $file_name);
        }

        return $files;
    }

    // Affichages: 

    public function displayObj()
    {
        $obj = $this->getObj();

        if (BimpObject::objectLoaded($obj)) {
            return $obj->getLink();
        }

        return '';
    }

    public function displayDocType($of_the = false)
    {
        $type = $this->getData('doc_type');

        if ($type) {
            $obj = $this->getObj();

            if (is_a($obj, 'BimpObject')) {
                $label = $obj->getConf('signatures/' . $type . '/label', $type);

                if ($of_the) {
                    $label = BimpTools::getOfTheLabel($label, (int) $obj->getConf('signatures/' . $type . '/is_label_female', 0));
                }

                return $label;
            }
        }

        return '';
    }

    public function displayDocRef()
    {
        $obj = $this->getObj();

        $errors = array();

        if ($this->isObjectValid($errors, $obj)) {
            if (method_exists($obj, 'getSignatureDocRef')) {
                return $obj->getSignatureDocRef($this->getData('doc_type'));
            } else {
                $ref = $obj->getRef();

                if ($ref) {
                    return $ref;
                }
            }
        }

        if ((int) $this->getData('id_obj')) {
            return '#' . $this->getData('id_obj');
        }

        return '';
    }

    public function displayDocTitle($no_html = false)
    {
        $ref = $this->displayDocRef();
        return $this->displayDocType() . ($ref ? ($no_html ? ' - ' . $ref : ' - <b>' . $ref . '</b>') : '');
    }

    public function displayDocInfos()
    {
        $html = '<h4>' . $this->displayDocTitle() . '</h4>';

        $obj = $this->getObj();

        $errors = array();
        if ($this->isObjectValid($errors, $obj)) {
            if (method_exists($obj, 'displaySignatureDocExtraInfos')) {
                $extra_infos = $obj->displaySignatureDocExtraInfos($this->getData('doc_type'));

                if ($extra_infos) {
                    $html .= '<br/>' . $extra_infos;
                }
            }
        }

        return $html;
    }

    public function displayActionsButtons()
    {
        $html = '';

        if ($this->isLoaded()) {
            $buttons = $this->getActionsButtons();

            if (!empty($buttons)) {
                $html .= '<div>';
                foreach ($buttons as $btn) {
                    $html .= BimpRender::renderRowButton($btn['label'], $btn['icon'], $btn['onclick']);
                }
                $html .= '</div>';
            }
        }

        return $html;
    }

    public function displayPublicDocument($label = 'Document PDF')
    {
        global $userClient;

        if (BimpObject::objectLoaded($userClient)) {
            BimpObject::loadClass('bimpcore', 'BimpSignataire');
            $signataires = $this->getChildrenObjects('signataires', array(
                'type'      => BimpSignataire::TYPE_CLIENT,
                'id_client' => (int) $userClient->getData('id_client'),
            ));

            foreach ($signataires as $signataire) {
                return $signataire->displayPublicDocument($label);
            }
        }

        return '';
    }

    public function displayNoPublicAccessWarnings()
    {
        $html = '';

        BimpObject::loadClass('bimpcore', 'BimpSignataire');
        $signataires = $this->getChildrenObjects('signataires', array(
            'type'       => BimpSignataire::TYPE_CLIENT,
            'status'     => BimpSignataire::STATUS_NONE,
            'allow_dist' => 1
        ));

        $missings = array();
        $has_docusign = false;
        foreach ($signataires as $signataire) {
            $users = $signataire->getData('allowed_users_client');

            if (empty($users)) {
                $missings[] = $signataire;
                if ((int) $signataire->getData('docusign_allowed')) {
                    $has_docusign = true;
                }
            }
        }

        if (count($missings)) {
            $msg .= '<b>Attention : aucune demande de signature à distance effectuée ';

            if (count($missings) > 1) {
                $msg .= 'pour les signataires :';
                foreach ($missings as $missing) {
                    $msg .= '<br/> - ' . $missing->getName();
                }
            }

            $msg .= '</b><br/>Si vous souhaitez envoyer une demande de signature à distance ' . ($has_docusign ? 'via DocuSign' : '') . ' veuillez cliquer sur ';

            if ($has_docusign) {
                $msg .= '"Initialiser DocuSign"';
            } else {
                $msg .= '"Ouvrir accès signature à distance"';
            }

            $html .= BimpRender::renderAlerts($msg, 'warning');
        }

        return $html;
    }

    // Rendus HTML:

    public function renderHeaderExtraLeft()
    {
        $html = '';
        
        if ($this->isLoaded() && !$this->isSigned()) {
            $warning = $this->displayNoPublicAccessWarnings();

            if ($warning) {
                $html .= '<div style="margin-top: 10px">';
                $html .= $warning;
                $html .= '</div>';
            }
        }

        return $html;
    }

    public function renderHeaderExtraRight($no_div = false)
    {
        $html = '';

        $file = $this->getDocumentFilePath();
        $file_signed = $this->getDocumentFilePath(true);
        $file_url = '';
        $file_signed_url = '';

        if ($file && file_exists($file)) {
            $file_url = $this->getDocumentUrl();
        }

        if ($file_signed && file_exists($file_signed)) {
            $file_signed_url = $this->getDocumentUrl(true);
        }

        $ds_certif_name = $this->getData('doc_type') . '_certificat_docusign.pdf';
        if (file_exists($this->getFilesDir() . $ds_certif_name)) {
            $ds_certif_url = $this->getFileUrl($ds_certif_name);
        }

        if ($file_url || $file_signed_url || $ds_certif_url) {
            $html .= '<div class="buttonsContainer align-right">';

            if ($file_url) {
                $html .= '<span class="btn btn-default" onclick="window.open(\'' . $file_url . '\')">';
                $html .= BimpRender::renderIcon('fas_file-pdf', 'iconLeft') . 'Document';
                $html .= '</span>';
            }

            if ($file_signed_url) {
                $html .= '<span class="btn btn-default" onclick="window.open(\'' . $file_signed_url . '\')">';
                $html .= BimpRender::renderIcon('fas_file-pdf', 'iconLeft') . 'Document signé';
                $html .= '</span>';
            }

            if ($ds_certif_url) {
                $html .= '<span class="btn btn-default" onclick="window.open(\'' . $ds_certif_url . '\')">';
                $html .= BimpRender::renderIcon('fas_file-pdf', 'iconLeft') . 'Certificat DocuSign';
                $html .= '</span>';
            }

            $html .= '</div>';
        }
        
        return $html;
    }

    public function renderSignButtonsGroup($label = 'Actions signature')
    {
        $buttons = $this->getActionsButtons();

        if (!empty($buttons)) {
            return BimpRender::renderButtonsGroup($buttons, array(
                        'max'            => 1,
                        'dropdown_icon'  => 'fas_signature',
                        'dropdown_label' => $label
            ));
        }

        return '';
    }

    public function renderRelancesReportsList()
    {
        if ($this->isLoaded()) {
            $list = new BC_ListTable(BimpObject::getInstance('bimpdatasync', 'BDS_ReportLine'), 'linked_object', 1, null, 'Rapports des relances', 'far_file-alt');
            $list->addFieldFilterValue('obj_module', 'bimpcore');
            $list->addFieldFilterValue('obj_name', 'BimpSignature');
            $list->addFieldFilterValue('id_obj', $this->id);

            return $list->renderHtml();
        }

        return '';
    }

    public function renderSignatureAlertes($include_link = true, $include_actions = true, $sign_dist_warning = true)
    {
        $html = '';

        if ($this->isLoaded() && !$this->isSigned()) {
            $att_docusign = $this->isAttenteDocuSign();
            $msg = BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft');

            if ($include_link) {
                $msg .= '<a href="' . $this->getUrl() . '" target="_blank">';
            }

            $msg .= 'Signature ' . $this->displayDocType(true) . ' en attente';
            if ($att_docusign) {
                $msg .= ' (Demande de signature via DocuSign effectuée)';
            }

            if ($include_link) {
                $msg .= BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a>';
            }

            if ($sign_dist_warning) {
                $msg .= $this->displayNoPublicAccessWarnings();
            }

            if ($include_actions) {
                $btn_html = $this->renderSignButtonsGroup();
                if ($btn_html) {
                    $msg .= '<div style="margin-top: 8px; text-align: right">';
                    $msg .= $btn_html;
                    $msg .= '</div>';
                }
            }

            $html .= BimpRender::renderAlerts($msg, 'warning');
        }

        return $html;
    }

    // Traitements:

    public function checkStatus(&$errors = array(), &$warnings = array())
    {
        $cur_status = (int) $this->getData('status');
        if ($cur_status === self::STATUS_CANCELLED) {
            return;
        }

        $signataires = $this->getChildrenObjects('signataires');

        if (count($signataires)) {
            $all_signed = true;
            $has_refused = false;

            foreach ($signataires as $signataire) {
                $status = $signataire->getData('status');
                if ($status <= 0) {
                    $all_signed = false;
                }
                if ($status < 0) {
                    $has_refused = true;
                }
            }

            $new_status = self::STATUS_NONE;
            if ($has_refused) {
                $new_status = self::STATUS_REFUSED;
            } elseif ($all_signed) {
                $new_status = self::STATUS_SIGNED;
            }

            if ($new_status !== $cur_status) {
                $this->set('status', $new_status);
                $errors = $this->update($warnings, true);

                if (!count($errors)) {
                    switch ($new_status) {
                        case self::STATUS_SIGNED:
                            $warnings = BimpTools::merge_array($warnings, $this->onFullySigned());
                            break;

                        case self::STATUS_REFUSED:
                            $warnings = BimpTools::merge_array($warnings, $this->onRefused());
                            break;
                    }
                }
            }
        }
    }

    public function onFullySigned()
    {
        $errors = array();
        $obj = $this->getObj();

        if ($this->isObjectValid($errors, $obj)) {
            if (method_exists($obj, 'onSigned')) {
                $errors = $obj->onSigned($this);
            }
        }

        return $errors;
    }

    public function onRefused()
    {
        $errors = array();
        $obj = $this->getObj();

        if ($this->isObjectValid($errors, $obj)) {
            if (method_exists($obj, 'onSignatureRefused')) {
                $errors = $obj->onSignatureRefused($this);
            }
        }

        return $errors;
    }

    public function onCancelled()
    {
        $errors = array();
        $obj = $this->getObj();

        if ($this->isObjectValid($errors, $obj)) {
            if (method_exists($obj, 'onSignaturecancelled')) {
                $errors = $obj->onSignaturecancelled($this);
            }
        }

        return $errors;
    }

    public function onReopen()
    {
        $errors = array();
        $obj = $this->getObj();

        if ($this->isObjectValid($errors, $obj)) {
            if (method_exists($obj, 'onSignatureReopen')) {
                $errors = $obj->onSignatureReopen($this);
            }
        }

        return $errors;
    }

    public function writeSignatureOnDoc($signataire = null, $params_overrides = array())
    {
        $errors = array();
        $file_idx = 0;

        if ($this->hasMultipleFiles()) {
            $selected_file = $this->getData('selected_file');

            if (!$selected_file) {
                $errors[] = 'Aucun document sélectionné parmi les choix possibles';
            } else {
                $srcFile = $this->getDocumentFileDir() . $selected_file;
                $file_idx = $this->getFileIdx($selected_file);

                if (!$file_idx) {
                    $errors[] = 'Index du fichier sélectionné absent';
                }
            }
        } else {
            $docusign_file = $this->getFilesDir() . 'docusign.pdf';
            if (file_exists($docusign_file)) {
                $srcFile = $docusign_file;
            } else {
                $signed_file = $this->getDocumentFilePath(true);

                if (file_exists($signed_file)) {
                    $srcFile = $signed_file;
                }
            }

            if (!$srcFile) {
                $srcFile = $this->getDocumentFilePath(false);
            }
        }


        $destFile = $this->getDocumentFilePath(true);

        $image = $signataire->getData('base_64_signature');

        if (!$image) {
            $errors[] = 'Image absente';
        }

        if (!BimpObject::objectLoaded($signataire) || !is_a($signataire, 'BimpSignataire')) {
            $errors[] = 'Signataire absent ou invalide';
        }

        if (!count($errors)) {
            $params = $this->getSignatureParams($signataire, 'elec', $errors, $file_idx);

            if (!count($errors)) {
                if (!empty($params_overrides)) {
                    $params = BimpTools::overrideArray($params, $params_overrides, true);
                }

                require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/BimpPDF.php';

                $texts = array();

                if ((int) BimpTools::getArrayValueFromPath($params, 'display_date', 1)) {
                    $texts['date'] = date('d/m/Y', strtotime($signataire->getData('date_signed')));
                }

                $is_company = (int) $signataire->isClientCompany();
                if ((int) BimpTools::getArrayValueFromPath($params, 'display_nom', $is_company)) {
                    $texts['nom'] = utf8_decode($signataire->getData('nom'));
                }

                if ((int) BimpTools::getArrayValueFromPath($params, 'display_fonction', $is_company)) {
                    $texts['fonction'] = utf8_decode($signataire->getData('fonction'));
                }
                
                if ((int) BimpTools::getArrayValueFromPath($params, 'display_ville', 0)) {
                    $texts['ville'] = utf8_decode($signataire->getData('ville'));
                }

                $pdf = new BimpConcatPdf();
                $errors = $pdf->insertSignatureImage($srcFile, $image, $destFile, $params, $texts);
            }
        }

        return $errors;
    }

    public function cancelAllSignatures(&$warnings = array(), $motif = '')
    {
        $errors = array();

        foreach ($this->getChildrenObjects('signataires', array(
            'status' => array(
                'operator' => '<',
                'value'    => 10
            )
        )) as $signataire) {
            $s_err = $signataire->cancelSignature();

            if (count($s_err)) {
                $warnings[] = BimpTools::getMsgFromArray($s_err, 'Echec annulation pour le signataire "' . $signataire->getName() . '"');
            }
        }

        if (!count($errors)) {
            $errors = $this->updateField('status', self::STATUS_CANCELLED);

            if (!count($errors)) {
                $this->addObjectLog('Signature annulée' . ($motif ? '<br/><b>Motif : </b>' . $motif : ''), 'CANCELLED');

                $obj = $this->getObj();

                if ($this->isObjectValid($errors, $obj)) {
                    $msg = 'Signature annulée pour le document "' . $this->displayDocTitle() . '"';
                    if ($motif) {
                        $msg .= '<br/><b>Motif : </b>' . $motif;
                    }
                    $obj->addObjectLog($msg, 'SIGNATURE_' . strtoupper($this->getData('doc_type')) . '_CANCELLED');
                }

                $this->onCancelled();
            }
        }

        return $errors;
    }

    public function reopenAllSignatures(&$warnings = array(), $motif = '')
    {
        $errors = array();

        foreach ($this->getChildrenObjects('signataires', array(
            'status' => array(
                'operator' => '<',
                'value'    => 0
            )
        )) as $signataire) {
            $signataire->set('type_signature', BimpSignataire::TYPE_NONE);
            $signataire->set('status', BimpSignataire::STATUS_NONE);
            $up_errors = $signataire->update($warnings, true);

            if (count($up_errors)) {
                $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec réouverture de la signature pour le signataire "' . $signataire->getName() . '"');
            }
        }

        if (!count($errors)) {
            $this->updateField('status', self::STATUS_NONE);
            $this->checkStatus();
            $warnings = BimpTools::merge_array($warnings, $this->onReopen());

            $this->addObjectLog('Signature réouverte' . ($motif ? '<br/><b>Motif : </b>' . $motif : ''), 'REOPEN');

            $obj = $this->getObj();

            if ($this->isObjectValid($errors, $obj)) {
                $msg = 'Signature réouverte pour le document "' . $this->displayDocTitle() . '"';
                if ($motif) {
                    $msg .= '<br/><b>Motif : </b>' . $motif;
                }

                $obj->addObjectLog($msg, 'SIGNATURE_' . strtoupper($this->getData('doc_type')) . '_REOPEN');
            }
        }

        return $errors;
    }

    public function openAllSignDistAccess($email_content = '', &$warnings = array(), &$success = '')
    {
        $errors = array();

        BimpObject::loadClass('bimpcore', 'BimpSignataire');

        $signataires = $this->getChildrenObjects('signataires', array(
            'type'       => BimpSignataire::TYPE_CLIENT,
            'status'     => 0,
            'allow_dist' => 1
        ));

        if (empty($signataires)) {
            $errors[] = 'Aucun signataire éligible pour l\'ouverture de la signature éléctronique à distance';
        } else {
            $nOK = 0;
            foreach ($signataires as $signataire) {
                $open_errors = $signataire->openSignDistAccess(true, $email_content);
                if (count($open_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($open_errors, 'Echec de l\'ouverture de la signatire à distance pour le signataire "' . $signataire->getData('label') . '" ' . $signataire->getName());
                } else {
                    $nOK++;
                }
            }

            if ($nOK > 0) {
                $success .= ($success ? '<br/>' : '') . 'Ouverture de la signature à distance effectuée pour ' . $nOK . ' signataire' . ($nOK > 1 ? 's' : '');
            }
        }

        return $errors;
    }

    public function initDocuSign($email_content = '', &$warnings = array())
    {
        $errors = array();

        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        if ($this->hasMultipleFiles()) {
            $errors[] = 'Signature via DocuSign non possible car plusieurs documents sont proposés à la signature';
            return $errors;
        }

        $api = $this->getDocuSignApi($errors);

        if (count($errors)) {
            return $errors;
        }

        $signers = $this->getDocuSignSignersParams($errors, $email_content, $api->getOption('mode', 'test'));

        $obj = $this->getObj();
        $this->isObjectValid($errors, $obj);

        $dir = $this->getDocumentFileDir();
        $file_name = $this->getDocumentFileName(false);

        if (!count($errors)) {
            $subject = $this->displayDocTitle(true);
            $result = $api->createEnvelope($dir, $file_name, $subject, $signers, $errors, $warnings);

            if (!count($errors)) {
                $envelopeId = BimpTools::getArrayValueFromPath($result, 'envelopeId', '');

                if ($envelopeId) {
                    $up_errors = $this->updateFields(array(
                        'id_account_docu_sign'  => $api->getParam($api->getOption('mode', 'test') . '_id_compte_api', ''),
                        'id_envelope_docu_sign' => $envelopeId
                    ));
                    if (count($up_errors)) {
                        // Par précaution: 
                        BimpCore::addlog('Echec enregistrement ID enveloppe DocuSign - Correction manuelle nécessaire', Bimp_Log::BIMP_LOG_URGENT, 'signature', $this, array(
                            'ID enveloppe' => $envelopeId,
                            'Erreurs'      => $up_errors
                                ), true);
                    }
                    $signataires = $this->getChildrenObjects('signataires', array(
                        'status'         => 0,
                        'allow_docusign' => 1
                            ), 'position', 'asc');

                    foreach ($signataires as $signataire) {
                        $signataire->updateField('status', BimpSignataire::STATUS_ATT_DOCUSIGN);
                    }

                    $this->addObjectLog('Demande de signature via DocuSign effectuée', 'DOCUSIGN_INIT');
                } else {
                    $errors[] = 'Aucun ID DocuSign reçu';
                }
            }
        }

        return $errors;
    }

    public function refreshDocuSignDocument($send_notification_email = true, &$warnings = array(), &$success = '')
    {
        $errors = array();

        $id_envelope = $this->getData('id_envelope_docu_sign');
        if (!$id_envelope) {
            $errors[] = 'ID de l\'envelope DocuSign absent';
        }

        $obj = $this->getObj();
        $this->isObjectValid($errors, $obj);

        $api = $this->getDocuSignApi($errors);

        if (!count($errors)) {
            $envelope = $api->getEnvelope($id_envelope, $errors, $warnings);

            if (!count($errors)) {
                if (isset($envelope['completedDateTime'])) {
                    $dt_signed = new DateTime($envelope['completedDateTime']);
                    $dt_signed->setTimezone(new DateTimeZone('Europe/Paris'));

                    $success .= "Document signé le " . $dt_signed->format('d / m / Y à H:i') . '<br/>';

                    $date_signed = $dt_signed->format('Y-m-d H:i:s');

                    BimpObject::loadClass('bimpcore', 'BimpSignataire');
                    $signataires = $this->getChildrenObjects('signataires', array(
                        'status' => BimpSignataire::STATUS_ATT_DOCUSIGN
                    ));

                    foreach ($signataires as $signataire) {
                        $signataire->set('status', BimpSignataire::STATUS_SIGNED);
                        $signataire->set('type_signature', BimpSignataire::TYPE_DOCUSIGN);
                        $signataire->set('date_signed', $date_signed);

                        $up_warnings = array();
                        $up_errors = $signataire->update($up_warnings, true);

                        if (count($up_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour du statut du signataire ' . $signataire->getName());
                        }
                    }

                    if (!count($errors)) {
                        $this->checkStatus($warnings);

                        if ($send_notification_email) {
                            $use_as_from = false;
                            $email = $this->getOnSignedNotificationEmail($use_as_from);

                            if ($email) {
                                $doc_title = $this->displayDocTitle();
                                $subject = 'Signature DocuSign effectuée - ' . $doc_title;
                                $msg = 'Bonjour,<br/><br/>';
                                $msg .= 'La (ou les) signature(s) via DocuSign du document "' . $doc_title . '" a été complètée le ' . $dt_signed->format('d / m / Y à H:i') . '.<br/><br/>';
                                if (is_a($obj, 'BimpObject')) {
                                    $msg .= '<b>Objet lié: </b>' . $obj->getLink() . '<br/>';
                                }
                                $msg .= '<b>Fiche signature: </b>' . $this->getLink(array(), 'private') . '<br/><br/>';
                                mailSyn2($subject, $email, '', $msg);
                            }
                        }

                        $doc_warnings = array();
                        $doc_errors = $this->downloadDocuSignDocument($doc_warnings, $success);

                        if (count($doc_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($doc_errors, 'Echec du téléchargement du document DocuSign');
                        }
                        if (count($doc_warnings)) {
                            $warnings[] = BimpTools::getMsgFromArray($doc_warnings, 'Erreurs suite au téléchargement du document DocuSign');
                        }
                    }
                } else {
                    $warnings[] = 'Le document DocuSign n\'a pas encore été complètement signé';
                }
            }
        }

        return $errors;
    }

    public function downloadDocuSignDocument(&$warnings = array(), &$success = '')
    {
        $errors = array();
        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        $id_envelope = $this->getData('id_envelope_docu_sign');
        if (!$id_envelope) {
            $errors[] = 'ID DocuSign absent';
        }

        $api = $this->getDocuSignApi($errors);

        if (!count($errors)) {
            $request_errors = array();
            $result = $api->getEnvelopeFile($id_envelope, 1, $request_errors);

            if (count($request_errors)) {
                $errors[] = BimpTools::getMsgFromArray($request_errors, 'Echec du téléchargement du fichier signé via DocuSign');
            } else {
                $file_content = base64_decode($result);
                $file_name = $this->getDocumentFileName(true);
                $file_dir = $this->getDocumentFileDir();

                if (file_exists($file_dir . $file_name)) {
                    unlink($file_dir . $file_name);
                }

                if (!file_put_contents($file_dir . $file_name, $file_content)) {
                    $errors[] = "Echec de l\'enregistrement du fichier signé via DocuSign";
                } else {
                    $success .= ($success ? '<br/>' : '') . 'Document DocuSign téléchargé avec succès';

                    // Certificat DocuSign
                    $request_errors = array();
                    $result = $api->getEnvelopeFile($id_envelope, 'certificate', $request_errors);

                    if (count($request_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($request_errors, 'Echec du téléchargement du certificat DocuSign');
                    } else {
                        $certificat_content = base64_decode($result);
                        $file_name = $this->getData('doc_type') . '_certificat_docusign.pdf';
                        $file_dir = $this->getFilesDir();

                        // Supression de l'ancien fichier si il existe
                        if (file_exists($file_dir . $file_name)) {
                            if (!unlink($file_dir . $file_name)) {
                                $warnings[] = "Echec de la suppression de l\'ancien certificat";
                            }
                        }

                        if (!is_dir($file_dir)) {
                            $dir_err = BimpTools::makeDirectories($file_dir);

                            if ($dir_err) {
                                $errors[] = 'Impossible d\'enregistrer le certificat - Echec de la création du dossier - ' . $dir_err;
                            }
                        }

                        if (!count($errors)) {
                            if (!file_put_contents($file_dir . $file_name, $certificat_content)) {
                                $warnings[] = "Echec de l\'enregistrement du certificat DocuSign";
                            } else {
                                $success .= '<br/>Certificat DocuSign téléchargé avec succès - ' . $file_dir . $file_name;
                            }
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function replaceEmailContentLabels($email_content)
    {
        $obj_label = '';
        $obj_ref = '';

        $obj = $this->getObj();
        $errors = array();
        if ($this->isObjectValid($errors, $obj)) {
            $obj_label = $obj->getLabel('the');
            $obj_ref = $obj->getRef();
        }

        return str_replace(array(
            '{NOM_DOCUMENT}',
            '{NOM_PIECE}',
            '{REF_PIECE}',
            '{LIEN_ESPACE_CLIENT}'
                ), array(
            $this->displayDocTitle(),
            $obj->getLabel('the'),
            $obj->getRef(),
            '<a href="' . self::getPublicBaseUrl(false, BimpPublicController::getPublicEntityForObjectSecteur($obj)) . '">espace client</a>'
                ), $email_content);
    }

    public function rebuildSignedDoc()
    {
        $errors = array();

        $file = $this->getDocumentFilePath(true);

        if (file_exists($file)) {
            unlink($file);
        }

        $signataires = $this->getChildrenObjects('signataires');

        foreach ($signataires as $signataire) {
            $err = $this->writeSignatureOnDoc($signataire);

            if (count($err)) {
                $errors[] = 'Echec incrustation de la sigature du signataire "' . $signataire->getName() . '"';
            }
        }

        return $errors;
    }

    public function setSelectedFile($selected_file, $signataire = null, $check_signature_params = false)
    {
        $errors = array();

        if (!$selected_file) {
            $errors[] = 'Aucun fichier sélectionné';
        } else {
            $files = $this->getUnsignedFilesArray(false);

            if (!array_key_exists($selected_file, $files)) {
                $errors[] = 'Document sélectionné invalide';
            } else {
                if (BimpObject::objectLoaded($signataire) && $check_signature_params) {
                    $file_idx = $this->getFileIdx($selected_file);
                    if (!$file_idx) {
                        $errors[] = 'Index du fichier sélectionné absent';
                    } else {
                        $this->getSignatureParams($signataire, 'elec', $errors, $file_idx);
                    }
                }

                if (!count($errors)) {
                    $this->updateField('selected_file', $selected_file);
                }
            }
        }

        return $errors;
    }

    // Traitements statiques: 

    public static function checkMultipleFiles($files, $object, $dir, $file_name_base, $params_field_name, &$next_file_idx = 1, $force_new_next_file = false)
    {
        if (!empty($files)) {
            sort($files, SORT_NATURAL);
            $signatures_params = $object->getData($params_field_name);

            if (count($files) > 1) {
                $idx = 1;
                $up_signature_params = false;
                foreach ($files as $f) {
                    if ($f !== $file_name_base . '-' . $idx . '.pdf') {
                        $file_idx = 0;

                        if (preg_match('/^' . preg_quote($file_name_base, '/') . '(\-(\d+))?\.pdf$/', $f, $matches)) {
                            $file_idx = (int) $matches[2];
                        }

                        if ($file_idx) {
                            if (isset($signatures_params[$file_idx])) {
                                $signatures_params[$idx] = $signatures_params[$file_idx];
                                unset($signatures_params[$file_idx]);
                                $up_signature_params = true;
                            }
                        } elseif (!empty($signatures_params)) {
                            $signatures_params[$idx] = $signatures_params;
                            $up_signature_params = true;
                        }

                        BimpTools::renameFile($dir, $f, $file_name_base . '-' . $idx . '.pdf');
                        $bimpfile = BimpCache::findBimpObjectInstance('bimpcore', 'BimpFile', array(
                                    'parent_module'      => $object->module,
                                    'parent_object_name' => $object->object_name,
                                    'id_parent'          => $object->id,
                                    'file_name'          => pathinfo($f, PATHINFO_FILENAME),
                                    'file_ext'           => 'pdf'
                                        ), true);

                        if (BimpObject::objectLoaded($bimpfile)) {
                            $bimpfile->updateField('file_name', $file_name_base . '-' . $idx);
                        }
                    }

                    $idx++;
                }

                if ($up_signature_params) {
                    $object->updateField($params_field_name, $signatures_params);
                }

                $next_file_idx = $idx;
            } else {
                $file_idx = 0;
                foreach ($files as $f) {
                    if (preg_match('/^' . preg_quote($file_name_base, '/') . '(\-(\d+))?\.pdf$/', $f, $matches)) {
                        $file_idx = (int) $matches[2];

                        if ($force_new_next_file) {
                            if ($file_idx !== 1) {
                                if ($file_idx) {
                                    if (isset($signatures_params[$file_idx])) {
                                        $object->updateField($params_field_name, array(1 => $signatures_params[$file_idx]));
                                    }
                                } elseif (isset($signatures_params['default'])) {
                                    $object->updateField($params_field_name, array(1 => $signatures_params));
                                }

                                BimpTools::renameFile($dir, $f, $file_name_base . '-1.pdf');

                                $bimpfile = BimpCache::findBimpObjectInstance('bimpcore', 'BimpFile', array(
                                            'parent_module'      => $object->module,
                                            'parent_object_name' => $object->object_name,
                                            'id_parent'          => $object->id,
                                            'file_name'          => pathinfo($f, PATHINFO_FILENAME),
                                            'file_ext'           => 'pdf'
                                                ), true);

                                if (BimpObject::objectLoaded($bimpfile)) {
                                    $bimpfile->updateField('file_name', $file_name_base . '-1');
                                }
                            }
                        } else {
                            if ($file_idx) {
                                if (isset($signatures_params[$file_idx])) {
                                    $object->updateField($params_field_name, $signatures_params[$file_idx]);
                                }

                                BimpTools::renameFile($dir, $f, $file_name_base . '.pdf');

                                $bimpfile = BimpCache::findBimpObjectInstance('bimpcore', 'BimpFile', array(
                                            'parent_module'      => $object->module,
                                            'parent_object_name' => $object->object_name,
                                            'id_parent'          => $object->id,
                                            'file_name'          => pathinfo($f, PATHINFO_FILENAME),
                                            'file_ext'           => 'pdf'
                                                ), true);

                                if (BimpObject::objectLoaded($bimpfile)) {
                                    $bimpfile->updateField('file_name', $file_name_base);
                                }
                            }
                        }
                    }
                    break;
                }

                $next_file_idx = 2;
            }
        }
    }

    // Actions: 

    public function actionSignPapier($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Document signé enregistré avec succès';

        $signataires = BimpTools::getPostFieldValue('signataires');

        if (empty($signataires)) {
            $errors[] = 'Veuillez sélectionner au moins un signataire';
        } else {
            $obj = $this->getObj();

            if ($this->isObjectValid($errors, $obj)) {
                $date = BimpTools::getPostFieldValue('date_signed', '');

                if (!$date) {
                    $date = date('Y-m-d H:i:s');
                }

                $files = BimpTools::getPostFieldValue('file_signed', array());
                if (!count($files) && (!isset($_FILES['file_signed']) || !isset($_FILES['file_signed']['name']) || empty($_FILES['file_signed']['name']))) {
                    $errors[] = 'Fichier du document signé absent';
                } else {
                    $file_name = $this->getDocumentFileName(true);
                    $file_path = $this->getDocumentFilePath(true/* , 'private' */);
                    $dir = $this->getDocumentFileDir();

                    if (!$dir || !$file_path || !$file_name) {
                        $errors[] = 'Erreurs: chemin du fichier absent';
                    } else {
                        if (file_exists($file_path)) {
                            $errors[] = 'Le document signé existe déjà. Si vous souhaitez le remplacer, veuillez le supprimer manuellement (Nom: ' . $file_name . ')';
                        } else {
                            require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

                            if(!count($files)){
                                $file_ext = pathinfo($_FILES['file_signed']['name'], PATHINFO_EXTENSION);
                                $file_name = pathinfo($file_name, PATHINFO_FILENAME) . '.' . $file_ext;
                                $this->set('signed_doc_ext', strtolower($file_ext));

                                $_FILES['file_signed']['name'] = $file_name;

                                $ret = dol_add_file_process($dir, 0, 0, 'file_signed');
                                if ($ret <= 0) {
                                    $errors = BimpTools::getDolEventsMsgs(array('errors', 'warnings'));
                                    if (!count($errors)) {
                                        $errors[] = 'Echec de l\'enregistrement du fichier pour une raison inconnue';
                                    }
                                } else {
                                    $success .= 'Téléchargement du fichier effectué avec succès';
                                }
                                BimpTools::cleanDolEventsMsgs();
                            }
                            else{
                                $file_ext = pathinfo($files[0], PATHINFO_EXTENSION);
                                $file_name = pathinfo($file_name, PATHINFO_FILENAME) . '.' . $file_ext;
                                $this->set('signed_doc_ext', strtolower($file_ext));
                                BimpTools::moveTmpFiles($errors, $files, $dir,$file_name);
                            }

                            if (!count($errors)) {
                                foreach ($signataires as $id_signataire) {
                                    $signataire = BimpCache::getBimpObjectInstance('bimpcore', 'BimpSignataire', $id_signataire);

                                    if (BimpObject::objectLoaded($signataire)) {
                                        $signataire_warnings = array();
                                        $signataire_errors = $signataire->setSignedPapier($date, $signataire_warnings);
                                        
                                        if (count($signataire_errors)) {
                                            $errors[] = BimpTools::getMsgFromArray($signataire_errors, 'Echec de la mise à jour du signataire "' . $signataire->getName() . '"');
                                        }
                                    } else {
                                        $errors[] = 'Le signataire #' . $id_signataire . ' n\'existe plus';
                                    }
                                }

                                if (!count($errors)) {
                                    $this->checkStatus($errors, $warnings);
                                }
                            }

                            if (count($errors)) {
                                unlink($file_path);
                            }
                        }
                    }
                }
            } else {
                $errors[] = 'Objet lié absent ou invalide';
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    public function actionSignPapierNoScan($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Signature sans scan enregistrée avec succès';

        $obj = $this->getObj();

        if ($this->isObjectValid($errors, $obj)) {

            $date = BimpTools::getArrayValueFromPath($data, 'date_signed', date('Y-m-d H:i:s'));

            if (!count($errors)) {
                foreach ($this->getChildrenObjects('signataires', array(
                    'status' => array(
                        'operator' => '<=',
                        'value'    => 0
                    )
                )) as $signataire) {
                    $signataire_warnings = array();
                    $signataire_errors = $signataire->validateArray(array(
                        'status'         => BimpSignataire::STATUS_SIGNED,
                        'date_signed'    => $date,
                        'type_signature' => BimpSignataire::TYPE_PAPIER_NO_SCAN
                    ));

                    if (!count($signataire_errors)) {
                        $signataire_errors = $signataire->update($signataire_warnings, true);
                    }
                    if (count($signataire_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($signataire_errors, 'Echec de la mise à jour du signataire "' . $signataire->getName() . '"');
                    }
                }

                if (!count($errors)) {
                    $this->checkStatus($errors, $warnings);
                }
            }
        } else {
            $errors[] = 'Objet lié absent ou invalide';
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    public function actionInitDocuSign($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $email_content = BimpTools::getArrayValueFromPath($data, 'email_content', '');
        $errors = $this->initDocuSign($email_content, $warnings);

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    public function actionRefreshDocuSign($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $sc = '';

        $send_notification_email = (int) BimpTools::getArrayValueFromPath($data, 'send_notification_email', 1);
        $errors = $this->refreshDocuSignDocument($send_notification_email, $warnings, $success);

        if (!count($errors)) {
            $file = $this->getDocumentFilePath(true);

            if (file_exists($file)) {
                $url = $this->getDocumentUrl(true);
                if ($url) {
                    $sc = 'window.open(\'' . $url . '\');bimp_reloadPage();';
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $sc
        );
    }

    public function actionDownloadDocuSignDocument($data, &$success)
    {
        $errors = $warnings = array();
        $success = '';
        $callback = '';

        $errors = $this->downloadDocuSignDocument($warnings, $success);

        if (!count($errors)) {
            $url = $this->getDocumentUrl(true);
            if ($url) {
                $callback = 'window.open(\'' . $url . '\');bimp_reloadPage();';
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $callback
        );
    }

    public function actionCancel($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Annulation de la signature effectuée avec succès';

        $motif = BimpTools::getArrayValueFromPath($data, 'motif', '');
        $errors = $this->cancelAllSignatures($warnings, $motif);

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    public function actionReopen($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Réouverture de la signature effectuée avec succès';

        $errors = $this->reopenAllSignatures($warnings, BimpTools::getArrayValueFromPath($data, 'motif', ''));

        if (!count($errors)) {
            $this->addObjectLog('Signature réouverte' . ($motif ? '<b>Motif : </b>' . $motif : ''), 'REOPEN');
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    public function actionRebuildSignedDoc($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'OK';

        $errors = $this->rebuildSignedDoc();

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides : 

    public function update(&$warnings = [], $force_update = false)
    {
        if ((int) $this->getData('status') <= 0 && BimpTools::getPostFieldValue('sign_papier', 0)) {
            // Procédure nécessaire pour téléchargement du fichier: 
            $result = $this->setObjectAction('signPapier');
            $errors = BimpTools::getArrayValueFromPath($result, 'errors', array());
            $warnings = BimpTools::getArrayValueFromPath($result, 'warnings', array());
            return $errors;
        }

        return parent::update($warnings, $force_update);
    }
}

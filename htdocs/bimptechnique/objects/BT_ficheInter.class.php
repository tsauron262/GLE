<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';

global $langs;
$langs->load('bills');
$langs->load('errors');
$cacheInstance = array();

class BT_ficheInter extends BimpDolObject
{

    public $mailSender = 'gle@bimp.fr';
    public $mailGroupFi = 'fi@bimp.fr';
    public static $dol_module = 'fichinter';
    public static $files_module_part = 'ficheinter';
    public static $element_name = 'fichinter';
    public $tmp_facturable = [];
    public static $actioncomm_code = "'AC_INT','RDV_EXT','RDV_INT','ATELIER','LIV','INTER','INTER_SG','FORM_INT','FORM_EXT','FORM_CERTIF','VIS_CTR','TELE','TACHE'";
    public $redirectMode = 4;
    public $no_update_process = false;
    private $tmpClientId = 0;
    # Statuts:

    CONST STATUT_ABORT = -1;
    CONST STATUT_BROUILLON = 0;
    CONST STATUT_VALIDER = 1;
    CONST STATUT_VALIDER_COMMERCIALEMENT = 3;
    CONST STATUT_TERMINER = 2;
    CONST STATUT_ATTENTE_SIGNATURE = 4;
    CONST STATUT_DEMANDE_FACT = 10;
    CONST STATUT_ATTENTE_VALIDATION = 11;

    public static $status_list = [
        self::STATUT_ABORT              => ['label' => "Abandonée", 'icon' => 'fas_times', 'classes' => ['danger']],
        self::STATUT_BROUILLON          => ['label' => "Brouillon", 'icon' => 'fas_file-alt', 'classes' => ['warning']],
        self::STATUT_VALIDER            => ['label' => "Validée", 'icon' => 'fas_check', 'classes' => ['info']],
        self::STATUT_TERMINER           => ['label' => "Terminée", 'icon' => 'fas_check', 'classes' => ['success']],
        self::STATUT_ATTENTE_SIGNATURE  => ['label' => "Attente signature client", 'icon' => 'fas_pen', 'classes' => ['important']],
        self::STATUT_DEMANDE_FACT       => ['label' => "Attente de facturation", 'icon' => 'fas_comment-dollar', 'classes' => ['important']],
        self::STATUT_ATTENTE_VALIDATION => ['label' => "Attente de validation commercial", 'icon' => 'fas_thumbs-up', 'classes' => ['important']],
    ];
    # Type inter: 

    CONST TYPE_NO = 0;
    CONST TYPE_FORFAIT = 1;
    CONST TYPE_GARANTIE = 2;
    CONST TYPE_CONTRAT = 3;
    CONST TYPE_TEMPS = 4;

    public static $type_list = array(
        self::TYPE_NO       => array('label' => 'FI ancienne version', 'icon' => 'refresh', 'classes' => array('info')),
        self::TYPE_FORFAIT  => array('label' => 'Forfait', 'icon' => 'check', 'classes' => array('info')),
        self::TYPE_GARANTIE => array('label' => 'Sous garantie', 'icon' => 'check', 'classes' => array('info')),
        self::TYPE_CONTRAT  => array('label' => 'Contrat', 'icon' => 'check', 'classes' => array('info')),
        self::TYPE_TEMPS    => array('label' => 'Temps pass&eacute;', 'icon' => 'check', 'classes' => array('warning')),
    );
    # Natures: 

    CONST NATURE_NO = 0;
    CONST NATURE_INSTALL = 1;
    CONST NATURE_DEPANNAGE = 2;
    CONST NATURE_TELE = 3;
    CONST NATURE_FORMATION = 4;
    CONST NATURE_AUDIT = 5;
    CONST NATURE_SUIVI = 6;
    CONST NATURE_DELEG = 7;

    public static $nature_list = array(
        self::NATURE_NO        => array('label' => 'FI ancienne version', 'icon' => 'refresh', 'classes' => array('info')),
        self::NATURE_INSTALL   => array('label' => 'Installation', 'icon' => 'download', 'classes' => array('info')),
        self::NATURE_DEPANNAGE => array('label' => 'Dépannage', 'icon' => 'wrench', 'classes' => array('info')),
        self::NATURE_TELE      => array('label' => 'Télémaintenance', 'icon' => 'tv', 'classes' => array('info')),
        self::NATURE_FORMATION => array('label' => 'Formation', 'icon' => 'graduation-cap', 'classes' => array('info')),
        self::NATURE_AUDIT     => array('label' => 'Audit', 'icon' => 'microphone', 'classes' => array('info')),
        self::NATURE_SUIVI     => array('label' => 'Suivi', 'icon' => 'arrow-right', 'classes' => array('info')),
        self::NATURE_DELEG     => array('label' => 'Délégation', 'icon' => 'user', 'classes' => array('info'))
    );
    #Types signatures: 

    const TYPE_SIGN_DIST = 1;
    const TYPE_SIGN_PAPIER = 2;
    const TYPE_SIGN_ELEC = 3;

    public static $types_signature = array(
        0                      => array('label' => ' ', 'icon' => '', 'classes' => array('')),
        self::TYPE_SIGN_DIST   => array('label' => 'Signature à distance', 'icon' => 'fas_file-download'),
        self::TYPE_SIGN_PAPIER => array('label' => 'Signature papier', 'icon' => 'fas_file-signature'),
        self::TYPE_SIGN_ELEC   => array('label' => 'Signature électronique', 'icon' => 'fas_signature')
    );

    // Droits users: 

    public function canCreate()
    {
        global $user;
        return ($user->rights->bimptechnique->plannified);
    }

    public function canEdit()
    {
        return 1;
    }

    public function canDelete()
    {
        global $user;
        if ((($this->getData('fk_user_author') == $user->id || $this->getData('fk_user_tech') == $user->id) && !$this->isOldFi()) ||
                ($user->admin || $user->rights->bimptechnique->delete)) {
            return 1;
        }

        return 0;
    }

    public function canSetAction($action)
    {
        global $user;

        switch ($action) {
            case 'setStatusAdmin':
                return ($user->admin || $user->id == 375);
                break;
            case 'createFacture':
                if ($user->rights->bimptechnique->billing) {
                    return 1;
                }
                return 0;
                break;
            case 'reattach_an_object':
                if ($user->rights->bimptechnique->reattach_an_object)
                    return 1;
                return 0;
                break;
        }

        return parent::canSetAction($action);
    }

    // Getters booléens: 

    public function isEditable($force_edit = false, &$errors = [])
    {
        if (!$force_edit && $this->isLoaded() && $this->isOldFi()) {
            $errors[] = 'Il s\agit d\'une fiche inter créée via l\'ancien module';
            return 0;
        }
        if ($force_edit) {
            return 1;
        }

        if ($this->getData('fk_statut') == self::STATUT_BROUILLON) {
            return 1;
        }

        return 0;
    }

    public function isDeletable($force_delete = false, &$errors = [])
    {
        if (!$force_delete && $this->isOldFi()) {
            $errors[] = 'Ancienne version des FI';
            return 0;
        }
        if ($this->getData('fk_statut') != 0) {
            $errors[] = 'Cette FI n\'est plus en brouillon';
            return 0;
        }

        return 1;
    }

    public function isActionAllowed($action, &$errors = array())
    {
        global $user;
        $status = (int) $this->getData('fk_statut');
        switch ($action) {
            case 'askFacturation':
                if ($status !== self::STATUT_VALIDER) {
                    $errors[] = 'Statut invalide pour cette opération';
                    return 0;
                }
                return 1;

            case 'generatePdf':
                if ($user->admin || $user->rights->bimptechnique->modif_apres_validation)
                    return 1;
                if ($status !== self::STATUT_BROUILLON) {
                    $errors[] = BimpTools::ucfirst($this->getLabel('this') . 'n\'est plus au statut "brouilon"');
                    return 0;
                }
                return 1;

            case 'attenteSign_to_signed':
                if ($status !== self::STATUT_ATTENTE_SIGNATURE) {
                    $errors[] = 'Statut invalide pour cette opération';
                    return 0;
                }
                return 1;

            case 'createFacture':
                if ($status !== self::STATUT_DEMANDE_FACT) {
                    $errors[] = 'Statut invalide pour cette opération';
                    return 0;
                }
                return 1;

            case 'resendFarSignEmail':
                if ($status !== self::STATUT_ATTENTE_SIGNATURE) {
                    $errors[] = 'Statut invalide pour cette opération';
                    return 0;
                }
                return 1;
        }
        return parent::isActionAllowed($action, $errors);
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        if ($force_edit)
            return 1;
        if ($this->isLoaded()) {
            $status = (int) $this->getData('fk_statut');

            if (in_array($field, array('fk_soc', 'fk_contrat', 'commandes', 'tickets', 'urgent', 'fk_user_tech', 'datei', 'time_from', 'time_to', 'description'))) {
                if ($status !== self::STATUT_BROUILLON) {
                    return 0;
                }

                if (in_array($field, array('fk_contrat', 'commandes'))) {
                    if (in_array($this->getData('fk_soc'), explode(',', BimpCore::getConf('id_societe_auto_terminer', '', 'bimptechnique')))) {
                        return 0;
                    }
                }

                return 1;
            }
        }

        return 1;

        return parent::isFieldEditable($field, $force_edit);
    }

    public function iAmAdminRedirect()
    {
        global $user;
        if (in_array($user->id, array(1, 460, 375, 217)))
            return true;
        parent::iAmAdminRedirect();
    }

    public static function isActive()
    {
        global $conf;
        if ($conf->bimptechnique->enabled)
            return 1;
        return 0;
    }

    public function isOldFi()
    {
        return !(int) $this->getData('new_fi');
    }

    public function IsBrouillon()
    {
        if ($this->getData('fk_statut') == self::STATUT_BROUILLON) {
            return 1;
        }
        return 0;
    }

    public function isSign()
    {
        if ($this->getData('signed'))
            return 1;
        return 0;
    }

    public function isNotSign()
    {
        return !$this->isSign();
    }

    public function hasSignaturePapier()
    {
        if ($this->getData('signataire') && $this->isNotSign()) {
            return 1;
        } elseif ($this->getData('signataire') && !$this->getData('base_64_signature') && $this->isSign()) {
            return 1;
        }

        return 0;
    }

    public function hasSignatureElectronique()
    {
        if ($this->isSign() && $this->getData('signataire') && $this->getData('base_64_signature')) {
            return 1;
        }

        return 0;
    }

    public function hasContratLinked()
    {
        if ($this->getData('fk_contrat')) {
            return 1;
        }
        return 0;
    }

    public function userHasRight($right)
    {
        global $user;

        if ($user->rights->bimptechnique->$right) {
            return 1;
        }
        return 0;
    }

    public function isFacturable()
    {

        $children = $this->getChildrenList('inters');

        if (count($children) > 0) {

            foreach ($children as $id_child) {

                $child = $this->getChildObject('inters', $id_child);

                $facturableType = Array(3, 4, 12, 13, 14);

                if (in_array($child->getData('type'), $facturableType))
                    return 1;
            }
        }

        return 0;
    }

    // Getters Params: 

    public function getListExtraBulkActions()
    {
        $actions = array();

        $actions[] = array(
            'label'   => 'Fichiers PDF',
            'icon'    => 'fas_file-pdf',
            'onclick' => $this->getJsBulkActionOnclick('generateBulkPdf', array(), array('single_action' => true))
        );

//        $actions[] = array(
//            'label'       => 'Facture unique',
//            'icon'        => 'fas_file-invoice-dollar',
//            'onclick'     => $this->getJsBulkActionOnclick('generateBulkUf', array(), array('single_action' => true))
//        );

        return $actions;
    }

    public function getListExtraListActions()
    {
        $actions = array();

        $actions[] = array(
            'label'       => 'Fichiers PDF',
            'icon'        => 'fas_file-pdf',
            'action'      => 'generateBulkPdf',
            'confirm_msg' => "Etes-vous sûr d\'avoir sélectionné les bons filtres"
        );
        return $actions;
    }

    public function getActionsButtons()
    {
        global $user;
        $buttons = Array();

        $buttons[] = array(
            'label'   => 'Planning de la FI',
            'icon'    => 'fas_clock',
            'onclick' => $this->getJsLoadModalView("events")
        );

        $buttons[] = array(
            'label'   => 'Dupliquer',
            'icon'    => 'fas_clone',
            'onclick' => $this->getJsActionOnclick('duplicate', array(), array('form_name' => "duplicate"))
        );

        if (!$this->isOldFi()) {
            if ($this->isLoaded()) {
                if ($this->getData('fk_statut') == self::STATUT_TERMINER || $this->getData('fk_statut') == self::STATUT_VALIDER) {

                    if (!$this->getData('fk_facture') && $this->isFacturable()) {
//                        $buttons[] = array(
//                            'label'   => 'Message facturation',
//                            'icon'    => 'fas_paper-plane',
//                            'onclick' => $this->getJsActionOnclick('messageFacturation', array(), array('form_name' => "messageFacturation"))
//                        );
                        $note = BimpObject::getInstance("bimpcore", "BimpNote");
                        $msg = "Bonjour, merci de bien vouloir facturer cette fiche d\'intervention indépendante d\'une commande ou d\'un contrat en cours";
                        $buttons[] = array(
                            'label'   => 'Message facturation',
                            'icon'    => 'far_paper-plane',
                            'onclick' => $note->getJsActionOnclick('repondre', array("obj_type" => "bimp_object", "obj_module" => $this->module, "obj_name" => $this->object_name, "id_obj" => $this->id, "type_dest" => $note::BN_DEST_GROUP, "fk_group_dest" => BimpCore::getUserGroupId('facturation'), "content" => $msg), array('form_name' => 'rep'))
                        );

                        if ($user->rights->bimptechnique->billing && $this->isFacturable()) {
                            $buttons[] = array(
                                'label'   => 'Facturer la FI',
                                'icon'    => 'euro',
                                'onclick' => $this->getJsActionOnclick('billing', array(), array('form_name' => "forBilling"))
                            );
                        }
                    }

                    if ($this->canSetAction("reattach_an_object")) {
                        $buttons[] = array(
                            'label'   => 'Rattacher un objet à la FI',
                            'icon'    => 'link',
                            'onclick' => $this->getJsActionOnclick('reattach_an_object', array(), array('form_name' => "reattach_an_object", 'on_form_submit' => 'on_rattachement_form_submit'))
                        );
                    }
                }
            }

            if ($this->isActionAllowed('askFacturation') && $this->canSetAction('askFacturation')) {
//                $buttons[] = array(
//                    'label'   => 'Demander la facturation',
//                    'icon'    => 'fas_hand-holding-usd',
//                    'onclick' => $this->getJsActionOnclick('askFacturation', array(), array(
//                        'form_name' => 'askFacturation'
//                    ))
//                );
            }

            if (($this->isActionAllowed('generatePdf') && $this->canSetAction('generatePdf')) || $user->admin) {
                $buttons[] = array(
                    'label'   => 'Générer le PDF',
                    'icon'    => 'fas_file-pdf',
                    'onclick' => $this->getJsActionOnclick('generatePdf', array(), array())
                );
            }

            if ($this->canSetAction('setStatusAdmin')) {
                if ((int) $this->getData('fk_statut') === self::STATUT_BROUILLON) {
                    $old_status = (int) $this->getData('old_status');
                    if ($old_status > 0) {
                        $buttons[] = array(
                            'label'   => 'Remettre au satut ' . self::$status_list[$old_status]['label'],
                            'icon'    => 'fas_redo',
                            'onclick' => $this->getJsActionOnclick('setStatusAdmin', array('status' => $old_status), array())
                        );
                    }
                } else {
                    $buttons[] = array(
                        'label'   => 'Remettre en brouillon',
                        'icon'    => 'fas_undo',
                        'onclick' => $this->getJsActionOnclick('setStatusAdmin', array('status' => self::STATUT_BROUILLON), array())
                    );
                }
            }

            if ($this->isActionAllowed('attenteSign_to_signed') && $this->canSetAction('attenteSign_to_signed')) {
                $buttons[] = array(
                    'label'   => 'J\'ai déposé la FI signée',
                    'icon'    => 'fas_upload',
                    'onclick' => $this->getJsActionOnclick('attenteSign_to_signed', array(), array(
                        'confirm_msg' => 'Merci de confirmer que le client à signé cette fiche d\\\'intervention et que vous avez déposé dans l\\\'onglet fichier le PDF signé. Cette action est irréversible.'
                    ))
                );
            }

            if ($this->isActionAllowed('resendFarSignEmail') && $this->canSetAction('resendFarSignEmail')) {
                $buttons[] = array(
                    'label'   => 'Envoyer un nouvel e-mail pour signature à distance',
                    'icon'    => 'fas_envelope',
                    'onclick' => $this->getJsActionOnclick('resendFarSignEmail', array(), array(
                        'confirm_msg' => 'Attention, un nouveau code d\\\'accès pour la signature à distance sera envoyé au client (le code envoyé précédemment ne sera plus valable). Veuillez confirmer.'
                    ))
                );
            }

//            if ($this->isActionAllowed('createFacture') && $this->canSetAction('createFacture')) {
//                $buttons[] = array(
//                    'label'   => 'Facturer',
//                    'icon'    => 'euro',
//                    'onclick' => $this->getJsActionOnclick('createFacture', array(), array())
//                );
//            }
        }

        return $buttons;
    }

    public function getLinkedObjectsActionsButtons()
    {
        $buttons = array();

        if ($this->isLoaded() && (int) $this->getData('fk_statut') === self::STATUT_BROUILLON) {
            $buttons[] = array(
                'label'   => 'Editer les objets liés',
                'icon'    => 'fas_edit',
                'onclick' => $this->getJsLoadModalForm('linked_object')
            );
        }
        return $buttons;
    }

    public function getNextNumRef()
    {
        $client = $this->getChildObject('client');

        if (BimpObject::objectLoaded($client)) {
            return $this->dol_object->getNextNumRef($client->dol_object);
        }

        return '';
    }

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, $main_alias = 'a', &$errors = array(), $excluded = false)
    {
        switch ($field_name) {
            case 'commercialclient':
                $alias = $main_alias . '___sc';
                $joins[$alias] = array(
                    'alias' => $alias,
                    'table' => 'societe_commerciaux',
                    'on'    => $alias . '.fk_soc = ' . $main_alias . '.fk_soc'
                );
                $filters[$alias . '.fk_user'] = array(
                    ($excluded ? 'not_' : '') . 'in' => $values
                );
                break;

            case 'linked':
                $in = [];
                $sql = "SELECT rowid FROM llx_fichinter WHERE ";
                $have_contrat = false;
                $have_commande = false;

                if (count($values) > 0) {
                    if (in_array("0", $values)) { // Contrat
                        $sql .= "fk_contrat > 0 ";
                        $have_contrat = true;
                    }
                    if (in_array("1", $values)) { // Commande
                        $have_commande = true;
                        if ($have_contrat) {
                            $sql .= "AND ";
                        }
                        $sql .= "commandes <> '[]' AND commandes <> '' AND commandes IS NOT NULL";
                    }
                    if (in_array("2", $values)) { // Tickets
                        if ($have_commande || $have_contrat) {
                            $sql .= " AND ";
                        }
                        $sql .= "tickets <> '[]' AND tickets <> '' AND tickets IS NOT NULL";
                    }
                    if (in_array("3", $values)) {
                        if (in_array("2", $values) && !in_array("1", $values) && !in_array("0", $values)) {
                            $sql = "";
                        }
                    }
                }

                if ($sql != "") {
                    $res = $this->db->executeS($sql, 'array');
                    foreach ($res as $nb => $i) {
                        $in[] = $i['rowid'];
                    }
                }

                $filters[$main_alias . '.rowid'] = ['in' => $in];
                break;

            case 'no_linked':
                $in = [];
                $sql = "";
                if (count($values) > 0) {
                    if (in_array("0", $values)) {
                        $sql = "SELECT rowid FROM llx_fichinter WHERE fk_contrat = 0 AND ";
                        $sql .= "(commandes = '[]' OR commandes = '' OR commandes IS NULL) AND ";
                        $sql .= "(tickets = '[]' OR tickets = '' OR tickets IS NULL) AND ";
                        $sql .= "(fk_facture = '[]' OR fk_facture = '' OR fk_facture IS NULL)";
                    }
                }
                if ($sql != "") {
                    $res = $this->db->executeS($sql, 'array');
                    foreach ($res as $nb => $i) {
                        $in[] = $i['rowid'];
                    }
                }
                $filters[$main_alias . '.rowid'] = ['in' => $in];
                break;
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $main_alias, $errors, $excluded);
    }

    public function getDirOutput()
    {
        global $conf;
        return $conf->ficheinter->dir_output;
    }

    public function getFileUrl($file_name, $page = 'document') // A VERIFIER POUR LE PDF
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

    public function getPageTitle()
    {
        return 'FI ' . $this->getRef();
    }

    // Getters données: 

    public function getCommercialClient()
    {
        if ((int) $this->getData('fk_soc')) {
            $client = $this->getChildObject('client');

            if (BimpObject::objectLoaded($client)) {
                return $client->getCommercial();
            }
        }

        return null;
    }

    public function getCommercialclientSearchFilters(&$filters, $value, &$joins = array(), $main_alias = 'a')
    {
        $alias = 'sc';
        $joins[$alias] = array(
            'alias' => $alias,
            'table' => 'societe_commerciaux',
            'on'    => $alias . '.fk_soc = a.fk_soc'
        );
        $filters[$alias . '.fk_user'] = $value;
    }

    public function displayCommercialClient()
    {

        if ($this->isLoaded()) {
            $commercial = $this->getCommercialClient();
            return $commercial->dol_object->getNomUrl();
        }
    }

    public function getDataCommercialClient($field)
    {
        $commercial = $this->getCommercialClient();

        if (BimpObject::objectLoaded($commercial)) {
            return $commercial->getData($field);
        }

        return null;
    }

    public function getDataClient($field)
    {
        $client = $this->getChildObject('client');

        if (BimpObject::objectLoaded($client)) {
            $client->getData($field);
        }

        return null;
    }

    public function getHtLine($type_line, $id_line)
    {
        // Aurait dû s'appeller getTotalHtLine (Essayer d'être précis dans le nom des fonctions, ça facilite la compréhension pour tout le monde). 

        switch ($type_line) {
            case 'contrat':
                // Toujours passer par le cache!! 
//                $obj = $this->getInstance('bimpcontract', 'BContract_contratLine', $id_line);
                $obj = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contratLine', $id_line);
                if (BimpObject::objectLoaded($obj)) {
                    return $obj->getData('total_ht');
                }
                return 0;

            case 'commande':
                $obj = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $id_line);
                if (BimpObject::objectLoaded($obj)) {
                    return $obj->getTotalHt(1);
                }
                return 0;
        }
        return 0;
    }

    public function getProductId($id_line, $type)
    {
        switch ($type) {
            case 'contrat':
                $obj = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contratLine', $id_line);
                if (BimpObject::objectLoaded($obj)) {
                    return $obj->getData('fk_product');
                }
                return 0;

            case 'commande':
                $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $id_line);
                if (BimpObject::objectLoaded($line)) {
                    return (int) $line->id_product;
                }
                return 0;

            default:
                return 0;
        }
    }

    public function getLinesForBilling()
    {
        return $this->tmp_facturable;
    }

    public function getSignatureContactCreateFormValues()
    {
        $client = $this->getChildObject('client');

        if (BimpObject::objectLoaded($client)) {
            $fields = array(
                'fk_soc' => $client->getData('id'),
                'email'  => $client->getData('email'),
            );

            if (!$client->isCompany()) {
                $fields['address'] = $client->getData('address');
                $fields['zip'] = $client->getData('zip');
                $fields['town'] = $client->getData('town');
                $fields['fk_pays'] = $client->getData('fk_pays');
                $fields['fk_departement'] = $client->getData('fk_departement');
            }

            return array(
                'fields' => $fields
            );
        }

        return array();
    }

    public function getEventId()
    {
        $where = 'code <> \'AC_FICHINTER_VALIDATE\' AND fk_element = ' . $this->id . ' AND elementtype = \'fichinter\'';

        return $this->db->getValue('actioncomm', 'id', $where);
    }

    // Getters array: 

    public function getCommandesClientArray($include_empty = true)
    {
        if ((int) $this->getData('fk_soc')) {
            $cache_key = 'fi_commandes_client_' . $this->getData('fk_soc') . '_array';

            if (!isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = array();
                $rows = $this->db->getRows('commande a', 'a.fk_soc = ' . (int) $this->getData('fk_soc'), null, 'array', array(
                    'a.ref',
                    'a.rowid',
                    'ef.libelle',
                    'a.fk_statut'
                        ), 'a.rowid', 'desc', array(
                    'ef' => array(
                        'alias' => 'ef',
                        'table' => 'commande_extrafields',
                        'on'    => 'a.rowid = ef.fk_object'
                    )
                ));

                if (is_array($rows)) {
                    BimpObject::loadClass('bimpcommercial', 'Bimp_Commande');

                    foreach ($rows as $r) {
                        $label = $r['libelle'];
                        $status = (isset(Bimp_Commande::$status_list[(int) $r['fk_statut']]) ? ' (' . Bimp_Commande::$status_list[(int) $r['fk_statut']]['label'] . ')' : '');
                        self::$cache[$cache_key][(int) $r['rowid']] = $r['ref'] . $status . ($label ? ' - ' . $label : '');
                    }
                }
            }
        }
        return self::getCacheArray($cache_key, $include_empty);
    }

    public function getTicketsClientArray($include_empty = true)
    {
        if ((int) $this->getData('fk_soc')) {
            $cache_key = 'fi_tickets_client_' . $this->getData('fk_soc') . '_array';

            if (!isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = array();
                $rows = $this->db->getRows('bs_ticket', 'id_client = ' . (int) $this->getData('fk_soc'), null, 'array', array(
                    'id',
                    'ticket_number',
                    'status',
                        ), 'id', 'desc');

                if (is_array($rows)) {
                    BimpObject::loadClass('bimpsupport', 'BS_Ticket');

                    foreach ($rows as $r) {
                        $status = (isset(BS_Ticket::$status_list[(int) $r['status']]) ? ' (' . BS_Ticket::$status_list[(int) $r['status']]['label'] . ')' : '');
                        self::$cache[$cache_key][(int) $r['id']] = $r['ticket_number'] . $status;
                    }
                }
            }
        }
        return self::getCacheArray($cache_key, $include_empty);
    }

    public function getTypeOfReattachmentObjectArray()
    {
        $reattachment = Array(0 => 'Auncun type d\'objet');
        if (!$this->getData('fk_facture'))
            $reattachment[1] = 'Facture';
        if (!$this->getData('fk_contrat'))
            $reattachment[2] = 'Contrat';
//        if (!count($this->getData('commandes')))
        $reattachment[3] = 'Commande';

        return $reattachment;
    }

    public function getFacturesArray()
    {

        $instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture');

        $filters['fk_soc'] = Array('operator' => '=', 'value' => $this->getData('fk_soc'));

        $factures = $instance->getList($filters);

        foreach ($factures as $object) {
            $return[$object[0]] = ($object['statut'] == 2) ? '<span class=\'danger\'>' . $object['ref'] . '</span>' : $object['ref'];
        }

        return $return;
    }

    public function getContratNNmoins1Array()
    {

        $instance = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat');

        $contrat_n_n_mois_1 = Array();

        $date_butoire_n = new DateTime();
        $date_butoire_n_1 = new DateTime();
        $date_butoire_n_1->sub(new DateInterval("P1Y"));

        $filters['fk_soc'] = Array('operator' => '=', 'value' => $this->getData('fk_soc'));

        $filters['date_start'] = array(
            'operator' => '>=',
            'value'    => $date_butoire_n_1->format('Y-m-d')
        );

        $contrat_n_n_mois_1 = $instance->getList($filters);

        $filters['date_start'] = array(
            'operator' => '>=',
            'value'    => $date_butoire_n->format('Y-m-d')
        );

        $contrat_n_n_mois_1 = BimpTools::merge_array($contrat_n_n_mois_1, $instance->getList($filters));
        //die(print_r($contrat_n_n_mois_1));
        $return = Array();

        $exclude_statut = Array(0, 4, 10);

        foreach ($contrat_n_n_mois_1 as $object) {
            if (!in_array($object['statut'], $exclude_statut)) {
                $return[$object[0]] = ($object['statut'] == 2) ? '<span class=\'danger\'>' . $object['ref'] . '</span>' : $object['ref'];
            }
        }

        return $return;
    }

    public function displayLinesForCommande()
    {

        $html = '';

        $lines = $this->getLinesFacturable();

        $id_commande = BimpTools::getPostFieldValue('id_commande');

        if (count($lines) && $id_commande > 0) {
            foreach ($lines as $id) {
                $child = $this->getChildObject('inters', $id);
                $points = (strlen($child->getData('description')) > 50) ? '...' : '';
                $html .= '- <b class=\'danger bs-popover\' ' . BimpRender::renderPopoverData($child->getData('description'), 'right', true) . ' >' . BT_ficheInter_det::$types[$child->getData('type')]['label'] . ' (' . $child->displayDuree() . 'h)</b> : ';
                $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $id_commande);
                $html .= '<select class=\'extra_select\' name=\'BT_ficheInter_line_for_commande_' . $child->id . '\' child_id=\'' . $child->id . '\'>';
                $html .= '<option value=\'0\'>Ne pas affecter de ligne</option>';
                foreach ($commande->dol_object->lines as $line) {
                    $html .= '<option value=\'' . $line->id . '\'>';
                    $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $line->fk_product);

                    $child_commande = $commande->getChildrenList('lines', Array('id_line' => $line->id))[0];
                    $child_commande_object = $commande->getChildObject('lines', $child_commande);
                    $html .= '<span> ' . $product->getRef() . "\n" . $line->description . '<br />';
                    if ($child_commande_object->getData('force_qty_1'))
                        $html .= '<br /><strong class=\'danger\'>Au forfait</strong>';
                    $html .= '</span>';
                    $html .= '</option>';
                }
                $html .= '</select><br />';
            }
        } else {
            $html .= BimpRender::renderAlerts('Il n\'y à aucune ligne non prévue', 'info', false);
        }

        return $html;
    }

    public function getLinesFacturableContratArray()
    {
        $return = Array();
        if (count($this->getLinesFacturable())) {
            foreach ($this->getLinesFacturable() as $id) {
                $child = $this->getChildObject('inters', $id);
                if ($child->getData('type') == 3 || $child->getData('type') == 4) {
                    $return[$id] = '- <b class=\'danger bs-popover\' ' . BimpRender::renderPopoverData($child->getData('description'), 'right', true) . ' >' . BT_ficheInter_det::$types[$child->getData('type')]['label'] . ' (' . $child->displayDuree() . 'h)</b>';
                }
            }
        }
        return $return;
    }

    public function getLinesFacturable()
    {
        $array = [];
        $children = $this->getChildrenList('inters');
        foreach ($children as $id_child) {
            $child = $this->getChildObject('inters', $id_child);
            if ($child->getData('type') == 3 || $child->getData('type') == 4) {

                $points = (strlen($child->getData('description')) > 50) ? '...' : '';

                $array[$child->id] = $child->id;
            }
        }
        return $array;
    }

    public function getContratsClientArray()
    {
        $contrats = Array(
            0 => 'Aucun contrat'
        );

        $filtres = array(
            'statut' => 11
        );

        if ((int) $this->getData('fk_soc')) {
            $filtres['fk_soc'] = (int) $this->getData('fk_soc');
        }
        foreach (BimpCache::getBimpObjectObjects('bimpcontract', 'BContract_contrat', $filtres, 'rowid', 'desc') as $contrat) {
            $label = $contrat->getData('label');
            $contrats[(int) $contrat->id] = $contrat->getRef() . '&nbsp;&nbsp;' . ($label ? ' - ' . $label : '');
        }

        return $contrats;
    }

    public function getLinkedInput()
    {
        if ((int) $this->getData('fk_soc')) {
            return 'select';
        }
        return null;
    }

    public function getTypeActionCommArray()
    {
        $cache_key = 'bt_ficheinter_types_action_comm_array';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();
            $acceptedCode = ['ATELIER', 'DEP_EXT', 'HOT', 'INTER', 'INTER_SG', 'AC_INT', 'LIV', 'RDV_INT', 'RDV_EXT', 'AC_RDV', 'TELE', 'VIS_CTR'];
            $list = $this->db->getRows('c_actioncomm', 'active = 1 AND code IN (\'' . implode('\',\'', $acceptedCode) . '\')');
            foreach ($list as $nb => $stdClass) {
                self::$cache[$cache_key][$stdClass->id] = $stdClass->libelle;
            }
        }

        return self::$cache[$cache_key];
    }

    public function getTotalFacturableArray()
    {
        $executedArray = $this->getServicesExecutedArray();
        $servicesNonVendu = $this->getServicesByTypeArray(4);
        $deplacementNonVendu = $this->getServicesByTypeArray(3);
        $imponderable = $this->getServicesByTypeArray(1);

        $total_facturable = [];

        foreach ($executedArray as $id_line_commande => $informations) {
            $total_facturable["vendu"] += ($informations['ht_executed'] - $informations['ht_vendu'] - $informations['pourcentage_commerncial']);
        }

        foreach ($servicesNonVendu as $index => $informations) {
            $remise_en_euro = ($informations['remise'] * $informations['tarif']) / 100;
            $total_facturable['inter_non_vendu'] += ($informations['tarif'] * $informations['qty']) - $remise_en_euro;
        }

        foreach ($deplacementNonVendu as $index => $informations) {
            $remise_en_euro = ($informations['remise'] * $informations['tarif']) / 100;
            $total_facturable['dep_non_vendu'] += ($informations['tarif'] * $informations['qty']) - $remise_en_euro;
        }

        foreach ($imponderable as $index => $informations) {
            $remise_en_euro = ($informations['remise'] * $informations['tarif']) / 100;
            $total_facturable['imponderable'] += ($informations['tarif'] * $informations['qty']) - $remise_en_euro;
        }

        return $total_facturable;
    }

    public function getTotalByServicesArray()
    {
        $executedArray = $this->getServicesExecutedArray();
        $servicesNonVendu = $this->getServicesByTypeArray(4);
        $deplacementNonVendu = $this->getServicesByTypeArray(3);
        $imponderable = $this->getServicesByTypeArray(1);

        $finalExecutedServices = 0;
        $finalServicesNonVendu = 0;
        $finalDeplacementNonVendu = 0;
        $finalImponderable = 0;

        array_walk_recursive($servicesNonVendu, function ($item, $key) use (&$finalServicesNonVendu) {
            //if($key == )
            $finalServicesNonVendu++;
        });

        echo $finalServicesNonVendu;
        //print_r($executedArray);
    }

    public function getServicesArray()
    {
        $services = [];
        BimpTools::loadDolClass("commande");
//        $codes = json_decode(BimpCore::getConf("bimptechnique_ref_deplacement", '')); // Non utilisé apparemment (var de conf supprimée de la base)

        $commande = New Commande($this->db->db);
        $product = $this->getInstance('bimpcore', 'Bimp_Product');
        $allCommandes = ($this->getData('commandes')) ? BimpTools::json_decode_array($this->getData('commandes')) : [];
        $array = explode(',', BimpCore::getConf('ref_temps_passe', '', 'bimptechnique'));
        $tp = [];
        foreach ($array as $code) {
            $tp[$code] = "Temps passé de niveau " . substr($code, -1, 1);
        }
        foreach ($allCommandes as $id) {
            $commande->fetch($id);
            foreach ($commande->lines as $line) {
                $product->fetch($line->fk_product);
                if ($product->isLoaded() && !$product->isDep() && ($line->product_type == 1 || $product->getData('fk_product_type'))) {
                    if (array_key_exists($product->getData('ref'), $tp)) {
                        $services['commande_' . $line->id] = $tp[$product->getRef()] . ' (' . price($line->total_ht) . ' € HT) - <b>' . $commande->ref . '</b> <br />' . $line->description;
                    } else {
                        $services['commande_' . $line->id] = $product->getRef() . ' (' . price($line->total_ht) . ' € HT) - <b>' . $commande->ref . '</b> <br />' . $line->description;
                    }
                }
            }
        }

        if ($this->getData('fk_contrat')) {
            $contrat = $this->getInstance('bimpcontract', 'BContract_contrat', $this->getData('fk_contrat'));
            foreach ($contrat->dol_object->lines as $line) {
                $child = $contrat->getChildObject('lines', $line->id);
                if ($child->getData('product_type') == 1 && $child->getData('statut') == 4) {
                    $product->fetch($line->fk_product);
                    $services['contrat_' . $line->id] = 'Intervention sous contrat (' . price($child->getData('total_ht')) . '€) - <strong>' . $contrat->getRef() . '</strong> - ' . $line->description;
                }
            }
        }

        return $services;
    }

    public function getServicesExecutedArray()
    {
        $inters = $this->getChildrenList("inters");
        $services_executed = [];

        foreach ($inters as $id_inter) {
            $inter = $this->getChildObject('inters', $id_inter);

            if (BimpObject::objectLoaded($inter)) {
                if ((int) $inter->getData('id_line_commande')) {
                    $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $inter->getData('id_line_commande'));

                    if (BimpObject::objectLoaded($line)) {
                        $time = $this->timestamp_to_time($inter->getData('duree'));
                        $qty = $this->time_to_qty($time);

                        $services_executed[$line->id]['ht_executed'] += ($line->getTotalHt(1) * $qty);
                        $services_executed[$line->id]['pourcentage_commerncial'] += ($inter->getData('pourcentage_commercial') * $line->total_ht) / 100;

                        if (!array_key_exists("ht_vendu", $services_executed[$line->id]))
                            $services_executed[$line->id]['ht_vendu'] = ($line->getTotalHt(1));

                        $services_executed[$line->id]['qty_executed'] += $qty;

                        if (!array_key_exists("commande", $services_executed[$line->id]))
                            $services_executed[$line->id]['commande'] = $line->getData('id_obj');

                        if (!array_key_exists("date", $services_executed[$line->id]))
                            $services_executed[$line->id]['date'] = $inter->getData('date');

                        $services_executed[$line->id]["lines"][] = $inter->id;
                    }
                }
            }
        }

        return $services_executed;
    }

    public function getServicesByTypeArray($type)
    {
        $children = $this->getChildrenList('inters', ["type" => $type]);

        $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', ($type == 3) ? BimpCore::getConf('id_dep', 0, 'bimptechnique') : BimpCore::getConf('id_serv19', 0, 'bimptechnique'));
        $services = [];
        $index = 1;
        if (count($children)) {
            foreach ($children as $id_child) {
                $child = $this->getChildObject("inters", $id_child);

                if (BimpObject::objectLoaded($child)) {
                    $time = $this->timestamp_to_time($child->getData('duree'));
                    $qty = $this->time_to_qty($time);
                    $services[$index]["tarif"] = $product->getData('price');
                    $services[$index]['qty'] = $qty;
                    $services[$index]['duree'] = $time;
                    $services[$index]['remise'] = $child->getData('pourcentage_commercial');
                    $services[$index]['date'] = $child->getData('date');
                    $services[$index]['line'] = $child->id;
                    $index++;
                }
            }
        }

        return $services;
    }

    public function getCommandesWithDeplacementArray()
    {
        $array = [];
        $commandes = $this->getData('commandes');

        if (is_array($commandes)) {
            foreach ($commandes as $id_commande) {
                $commande = BimpCache::getBimpObjectInstance("bimpcommercial", "Bimp_Commande", $id_commande);
                if (BimpObject::objectLoaded($commande)) {
                    $lines = $commande->getLines('not_text');
                    foreach ($lines as $line) {
                        if (!(int) $line->id_product) {
                            continue;
                        }

                        $product = $line->getProduct();

                        if (BimpObject::objectLoaded($product)) {
                            if ($product->isDep()) {
                                $line_inters = $this->getChildrenList("inters", ['id_line_commande' => (int) $line->id]);
                                if (!count($line_inters)) {
                                    $array[$id_commande] = $commande->getRef() . " - " . $commande->getData('libelle');
                                }
                            }
                        }
                    }
                }
            }
        }
        return $array;
    }

    public function getInputTypesSignatureArray()
    {
        $types = self::$types_signature;

        unset($types[0]);

        global $user;
        $interne = explode(",", BimpCore::getConf('id_societe_auto_terminer', '', 'bimptechnique'));

        if (in_array($this->getData('fk_soc'), $interne) && !$user->admin) {
            unset($types[1]);
            unset($types[2]);
        }


        return $types;
    }

    public function getLinkedTicketsArray()
    {
        $tickets = array();

        $ids = $this->getData('tickets');

        if (!empty($ids)) {
            $rows = $this->db->getRows('bs_ticket', 'id IN (' . implode(',', $ids) . ')', null, 'array', array('id', 'ticket_number'));

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    $tickets[(int) $r['id']] = $r['ticket_number'];
                }
            }
        }

        return $tickets;
    }

    // Affichages: 

    public function displayTech()
    {
        $tech = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $this->getData('fk_user_tech'));
        return $tech->getName();
    }

    public function displayVersion()
    {
        $html = "";

        if ($this->getData('new_fi') == 0) {
            $html .= "<strong>Ancienne version des FI.</strong><br />Pour les informations  réèlles de la FI merci, de cliquer sur le boutton ci-dessous<br />";
            $html .= "<a href='" . DOL_URL_ROOT . "/fichinter/card.php?id=" . $this->id . "' class='btn btn-default' >Ancienne version</a>";
        } else {
            $html .= "<strong class='success'>Nouvelle version</strong>";
        }

        return $html;
    }
    
    public static function dureeToPrice($duree){
        return static::time_to_qty(static::timestamp_to_time($duree)) * BimpCore::getConf('cout_horaire_technicien', null, 'bimptechnique');
    }

    public function displayRatioTotal($display = true, $want = "")
    {
        if ((int) $this->getData('new_fi')) {
            $renta = [];
            $coup_technicien = BimpCore::getConf('cout_horaire_technicien', null, 'bimptechnique');

            $commandes = $this->getData('commandes');
            if (is_array($commandes) && !empty($commandes)) {
                foreach ($commandes as $id_commande) {
                    $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $id_commande);

                    if (BimpObject::objectLoaded($commande)) {
                        $commande_ref = $commande->getRef();
                        foreach ($commande->getLines('not_text') as $line) {
                            if ($line->id_product) {
                                $service = $line->getProduct();

                                if (BimpObject::objectLoaded($service)) {
                                    $qty = 0;

                                    foreach ($this->getChildrenObjects("inters", ['id_line_commande' => $line->id]) as $inter) {
                                        $duration = $inter->getData('duree');
                                        $time = $this->timestamp_to_time($duration);
                                        $qty += $this->time_to_qty($time);
                                    }

                                    $renta[$commande_ref][$service->id] = array(
                                        'service' => $service->getRef(),
                                        'vendu'   => $line->getTotalHT(true),
                                        'cout'    => $qty * $coup_technicien
                                    );
                                }
                            }
                        }
                    }
                }
            }

            $inters = $this->getChildrenObjects('inters');
            foreach ($inters as $inter) {
                if (!(int) $inter->getData('id_line_commande') && !(int) $inter->getData('id_line_contrat')) {
                    if ($inter->getData('type') != 2) { // Exclude ligne libre (Juste ligne de commentaire)
                        $renta['hors_vente'][$inter->getData('type')]['service'] = $inter->displayData('type', 'default', true, true);
                        $renta['hors_vente'][$inter->getData('type')]['vendu'] = 0;
                        $duration = $inter->getData('duree');
                        $time = $this->timestamp_to_time($duration);
                        $qty += $this->time_to_qty($time);
                        $renta['hors_vente'][$inter->getData('type')]['cout'] += $qty * $coup_technicien;
                    }
                }
            }

            $total_vendu_commande = 0;
            $total_coup_commande = 0;

            if (count($renta)) {
                foreach ($renta as $title => $infos) {
                    foreach ($infos as $i) {
                        $total_vendu_commande += $i['vendu'];
                        $total_coup_commande += $i['cout'];
                    }
                }
            }

            $marge = ($total_vendu_commande - $total_coup_commande);

            $class = 'warning';
            $icone = 'arrow-right';

            if ($marge > 0) {
                $class = 'success';
                $icone = 'arrow-up';
            } elseif ($marge < 0) {
                $class = 'danger';
                $icone = 'arrow-down';
            }

            if ($display) {
                if (!empty($commandes)) {
                    $html = "<strong>"
                            . "Commandes: <strong class='$class' >" . BimpRender::renderIcon('fas_' . $icone) . " " . price($marge) . "€</strong><br />"
                            . "</strong>";
                }

                if ($this->getData('fk_contrat')) {
                    $contrat = $this->getInstance('bimpcontract', 'BContract_contrat', $this->getData('fk_contrat'));

                    if (BimpObject::objectLoaded($contrat)) {
                        $html .= $contrat->renderThisStatsFi(true, false);
                    }
                }

                if (empty($commandes) && !(int) $this->getData('fk_contrat') && !empty($inters)) {
                    $duree = 0;
                    foreach ($inters as $inter) {
                        if ($inter->getdata('type') != 2) {
                            $duree += $inter->getData('duree');
                        }
                    }

                    $tms = $this->timestamp_to_time($duree);
                    $qty = $this->time_to_qty($tms);

                    $marge = ($qty * $coup_technicien);
                    $class = 'warning';
                    $icone = 'arrow-right';
                    $signe = "-";
                    if ($marge < 0) {
                        $class = 'success';
                        $icone = 'arrow-up';
                        $signe = "";
                    } elseif ($marge > 0) {
                        $class = 'danger';
                        $icone = 'arrow-down';
                    }

                    $html .= "<strong>"
                            . "FI non liée: <strong class='$class' >" . BimpRender::renderIcon('fas_' . $icone) . " $signe" . price($marge) . "€</strong>"
                            . "</strong>";
                }

                return $html;
            }

            return 0;
        } else {
            return BimpRender::renderAlerts("Calcul de la rentabilité sur les anciennes FI en attente", "danger", false);
        }
    }

    public function displayIfMessageFormFi()
    {
        $msgs = [];

//        $children = $this->getChildrenList("facturation");
//        
//
//        if (count($children) > 0) {
//            foreach ($children as $id_child) {
//                $child = $this->getChildObject('facturation', $id_child);
//            }
//        }
////            $msgs[] = Array(
////                'type' => 'warning',
////                'content' => print_r($children)
////            );


        return $msgs;
    }

    public function displayLinkedContratCard()
    {
        $html = "";

        if ($this->hasContratLinked()) {
            $contrat = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat', $this->getData('fk_contrat'));
            if ($contrat->isLoaded()) {
                $card = new BC_Card($contrat);
                $html .= '<div style="max-width: 650px">';
                $html .= $card->renderHtml();
                $html .= '</div>';
            } else {
                $html .= BimpRender::renderAlerts("Erreur lors du chargement du contrat: #" . $this->getData('fk_contrat'), 'danger', false);
            }
        } else {
            $html .= BimpRender::renderAlerts("Il n'y a pas de contrat lié sur cette fiche d'intervention", "info", false);
        }
        return $html;
    }

    public function displayAllTicketsCards()
    {
        $html = "";

        $tickets = $this->getData('tickets');

        if (!empty($tickets)) {
            $html .= '<table class="bimp_list_table">';
            $html .= '<tbody>';

            foreach ($tickets as $id_ticket) {
                $ticket = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_Ticket', $id_ticket);

                if (BimpObject::objectLoaded($ticket)) {
                    $html .= '<tr>';
                    $html .= '<th style="text-align: left">Ticket #' . $ticket->id . ' - ' . $ticket->getRef() . '</th>';
                    $html .= '</tr>';
                }

                $html .= '<tr>';
                $html .= '<td style="padding: 20px;">';

                if (BimpObject::objectLoaded($ticket)) {
                    $card = new BC_Card($ticket, null, 'with_sujet');
                    $card->setParam('title', '');

                    $html .= '<div style="max-width: 650px">';
                    $html .= $card->renderHtml();
                    $html .= '</div>';
                } else {
                    $html .= BimpRender::renderAlerts('Le ticket support #' . $id_ticket) . ' n\'existe plus';
                }

                $html .= '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';
        } else {
            $html .= BimpRender::renderAlerts("Il n'y a pas de tickets liés sur cette fiche d'intervention", "info", false);
        }

        return $html;
    }

    public function displayAllCommandesCards()
    {
        $html = "";

        $commandes = $this->getData('commandes');

        if (!empty($commandes)) {
            $html .= '<table class="bimp_list_table">';
            $html .= '<tbody>';

            foreach ($commandes as $id_commande) {
                $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $id_commande);

                if (BimpObject::objectLoaded($commande)) {
                    $html .= '<tr>';
                    $html .= '<th style="text-align: left">Commande #' . $commande->id . ' - ' . $commande->getRef() . '</th>';
                    $html .= '</tr>';
                }

                $html .= '<tr>';
                $html .= '<td style="padding: 20px">';

                if (BimpObject::objectLoaded($commande)) {

                    if ($commande->getData('note_public')) {
                        $html .= '<h3>Note publique : </h3>' . $commande->getData('note_public') . '';
                    }
                    if ($commande->getData('note_private')) {
                        $html .= '<h3>Note privée : </h3>' . $commande->getData('note_private') . '';
                    }

                    $card = new BC_Card($commande);
                    $card->setParam('title', '');

                    $html .= '<div style="max-width: 650px">';
                    $html .= $card->renderHtml();
                    $html .= '</div>';

                    $html .= '<u><strong>';
                    $html .= 'Contenu de la commande';
                    $html .= '</strong></u><br />';

                    foreach ($commande->getLines('not_text') as $line) {
                        $service = $line->getProduct();
                        $html .= ' - ';

                        if (BimpObject::objectLoaded($service)) {
                            $html .= $service->getLink();
                        }

                        $html .= "<strong> - (" . price($line->getTotalHT(true)) . "€ HT / " . price($line->getTotalTTC()) . "€ TTC)</strong>";

                        if ($line->getData('force_qty_1') == 1) {
                            $html .= " <strong class='danger'>Au forfait</strong>";
                        }

                        if ($line->desc) {
                            $html .= "<br /><strong style='margin-left:10px'>" . $line->desc . "</strong><br />";
                        } elseif (BimpObject::objectLoaded($service) && $service->getData('description')) {
                            $html .= "<br /><strong style='margin-left:10px'>" . $service->getData('description') . "</strong><br />";
                        } else {
                            $html .= '<br />';
                        }
                    }
                } else {
                    $html .= BimpRender::renderAlerts('La commande #' . $id_commande) . ' n\'existe plus';
                }

                $html .= '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';
        } else {
            $html .= BimpRender::renderAlerts("Il n'y a pas de commandes liées sur cette fiche d'intervention", "info", false);
        }


        return $html;
    }

    public function displayTypeSignature()
    {
        if ($this->getData('fk_statut') == 0) {
            return "<strong class='warning'>" . BimpRender::renderIcon("times") . " Fiche d'intervention pas encore signée</strong>";
        } else {
            if ($this->getData('signed'))
                $icon = "fas_vimeo";
            else
                $icon = "fas_file";

            switch ($this->getData('type_signature')) {
                case 0:
                    $text = "Signature électronique";
                    break;
                case 1:
                    $text = "Signature à distance";
                    break;
                case 2:
                    $text = "Signature papier";
                    break;
                case 3:
                    $text = "Signature électronique";
                    break;
            }
            return "<strong'>" . BimpRender::renderIcon($icon) . " $text</strong>";
        }
    }

    public function displayDataTyped($data, $balise = 'span', $color = "#EF7D00")
    {
        return '<' . $balise . ' style="color:' . $color . '" >' . $data . '</' . $balise . '>';
    }

    public function displayCommercial($with_label = false)
    {
        $html = '';

        if ($with_label) {
            $html .= 'Commercial du client: ';
        }

        $commercial = $this->getCommercialClient();

        if (BimpObject::objectLoaded($commercial)) {
            $html .= $commercial->getLink();
        } elseif ($with_label) {
            $html .= '<span class="danger">aucun</span>';
        }

        return $html;
    }

    public function displayServicesForForm()
    {
        
    }

    public function displayDuree()
    {
        return $this->timestamp_to_time($this->getData('duree'));
    }

    public function displayNombreInters()
    {

        return count($this->getChildrenObjects('inters'));
    }

    // Rendus HTML: 

    public function renderEventsTable()
    {
        $html = '';

        if ($this->isLoaded()) {
            if (!class_exists('FormActions')) {
                require_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
            }

            BimpTools::loadDolClass('comm/action', 'actioncomm', 'ActionComm');

            $fk_soc = (int) $this->getData('fk_soc');

            $ac = new ActionComm($this->db->db);
            $list = $ac->getActions($fk_soc, $this->id, static::$dol_module, '', 'a.id', 'ASC');

            if (!is_array($list)) {
                $html .= BimpRender::renderAlerts('Echec de la récupération de la liste des événements');
            } else {
                global $conf;

                $urlBack = DOL_URL_ROOT . '/' . $this->module . '/index.php?fc=' . $this->getController() . '&id=' . $this->id;
                $href = DOL_URL_ROOT . '/comm/action/card.php?action=create&datep=' . dol_print_date(dol_now(), 'dayhourlog');
                $href .= '&origin=' . $type_element . '&originid=' . $this->id . '&socid=' . (int) $this->getData('fk_soc');
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
                $html .= '</tr>';
                $html .= '</thead>';

                $html .= '<tbody>';

                if (count($list)) {
                    $userstatic = new User($this->db->db);

                    foreach ($list as $action) {
                        $html .= '<tr>';
                        $html .= '<td>' . $action->getNomUrl(1, -1) . '</td>';
                        $html .= '<td>' . $action->getNomUrl(0, 0) . '</td>';
                        $html .= '<td>';
                        if (!empty($conf->global->AGENDA_USE_EVENT_TYPE)) {
                            $html .= $action->type;
                        }
                        $html .= '</td>';
                        $html .= '<td align="center">';
                        $html .= dol_print_date(strtotime($action->datep), 'dayhour');
                        if ($action->datef) {
                            $tmpa = dol_getdate($action->datep);
                            $tmpb = dol_getdate($action->datef);
                            if ($tmpa['mday'] == $tmpb['mday'] && $tmpa['mon'] == $tmpb['mon'] && $tmpa['year'] == $tmpb['year']) {
                                if ($tmpa['hours'] != $tmpb['hours'] || $tmpa['minutes'] != $tmpb['minutes'] && $tmpa['seconds'] != $tmpb['seconds']) {
                                    $html .= '-' . dol_print_date(strtotime($action->datef), 'hour');
                                }
                            } else {
                                $html .= '-' . dol_print_date(strtotime($action->datef), 'dayhour');
                            }
                        }
                        $html .= '</td>';
                        $html .= '<td>';
                        if (!empty($action->author->id)) {
                            $userstatic->id = $action->author->id;
                            $userstatic->firstname = $action->author->firstname;
                            $userstatic->lastname = $action->author->lastname;
                            $html .= $userstatic->getNomUrl();
                        }
                        $html .= '</td>';

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
            }
        }

        return $html;
    }

    public function renderSignaturePad($addClass = '')
    {
        $displayStyle = '';
        $prefix = '';
        if ($addClass == 'expand') {
            $displayStyle = 'display:none';
            $prefix = 'x_';
        }

        $html = '';
        $html .= '<div class="wrapper"> 
                      <canvas id="' . $prefix . 'signature-pad" class="signature-pad ' . $addClass . '" style="border: solid 1px; ' . $displayStyle . '" width=400 height=200></canvas>
                  </div>';

        $html .= '<div class="buttonsContainer align-center">';
        $html .= '<sapn class="clearSignaturePadBtn btn btn-danger btn-large" >' . BimpRender::renderIcon("fas_undo") . ' Refaire la signature</span>';
        $html .= '</div>';

        $html .= '<input type="hidden" name="base_64_signature" value=""/>';

        return $html;
    }

    public function renderHeaderExtraLeft()
    {
        $html = '';

        if ($this->isLoaded()) {
            $html .= '<div class="object_header_infos">';
            $html .= 'Créée le <strong>' . date('d / m / Y', strtotime($this->getData('datec'))) . '</strong>';

            $user_create = $this->getChildObject('userCreate');

            if (BimpObject::objectLoaded($user_create)) {
                $html .= ' par ' . $user_create->getLink();
            }

            $html .= '</div><br/>';

            $tech = $this->getChildObject('user_tech');

            if (BimpObject::objectLoaded($tech)) {
                $html .= '<div class="object_header_infos" style="font-size: 13px">';
                $html .= 'Intervenant: ' . $tech->getLink();
                $html .= '</div>';
            }

            $client = $this->getChildObject('client');

            if (BimpObject::objectLoaded($client)) {
                $html .= '<div class="object_header_infos" style="font-size: 13px">';
                $html .= 'Client: ' . $client->getLink();
                $html .= '</div>';
            }
        }

        return $html;
    }

    public function renderHeaderStatusExtra()
    {
        $html = '';

        if ($this->getData('fk_facture') > 0) {
            $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $this->getData('fk_facture'));
            if ($facture->isLoaded()) {
                $html .= '<div><strong>Facturée avec' . $facture->getNomUrl();

                $button = array(
                    'label'   => 'Dé-linker la facture',
                    'icon'    => 'fas_times',
                    'onclick' => $this->getJsActionOnclick('deleteLinkedFacture', array(), array('confirm_msg' => 'Etes-vous sûr ? cette action est irréverssible'))
                );

                $html .= BimpRender::renderButton($button);

                $html .= '</strong></div>';
            }
        }

        $html .= '<div>Signée :' . $this->displayData('signed', 'default', false) . '</div>';
        $html .= '<div>Intervention urgente :' . $this->displayData('urgent', 'default', false) . '</div>';

        if ((int) $this->getData('signed')) {
            $html .= '<div>';
            $html .= $this->displayData('type_signature', 'default', false);

            if ($this->getData('date_signed')) {
                $html .= ' le ' . date('d / m / Y à H:i', strtotime($this->getData('date_signed')));
            }
            $html .= '</div>';
        }

        return $html;
    }

    public function renderSignatureTab()
    {
        $html = "";
        if (!$this->isOldFi()) {
            if ($this->getData('fk_statut') == self::STATUT_BROUILLON) {
                $form = new BC_Form($this, null, 'signature');
                $html .= $form->renderHtml();
            } else {
                $html .= '<h3>Nom du signataire client: ' . $this->displayDataTyped($this->getData('signataire')) . '</h3>';
                $html .= '<h3>Type de signature: ' . $this->displayDataTyped($this->displayData('type_signature', 'default', false)) . '</h3>';

                $file = $this->getRef() . "/" . $this->getRef() . '_sign_e.pdf';
                if (!is_file(DOL_DATA_ROOT . '/ficheinter/' . $file)) {
                    $file = $this->getRef() . "/" . $this->getRef() . '.pdf';
                }

                $html .= '<embed src="' . DOL_URL_ROOT . "/document.php?modulepart=ficheinter&file=" . $file . '" type="application/pdf"   height="1000px" width="100%">';
            }
        } else {
            $html .= "<center><h3>Cette <span style='color:#EF7D00' >Fiche d'intervention</strong> est une ancienne <strong style='color:#EF7D00' >version</strong></h3></center>";
        }


        return $html;
    }

    // Traitements: 

    public function duplicate($new_data)
    {
        global $user;

        $fieldsNonClone = array('signed', 'type_signature', 'old_status', 'fk_facture', 'no_finish_reason', 'client_want_contact', 'public_signature_date_cloture', 'public_signature_date_delivrance', 'public_signature_url', 'public_signature_code', 'attente_client', 'date_signed', 'signataire', 'base_64_signature', 'fk_user_modif', 'fk_user_valid');

        $new_object = clone $this;
        $new_object->id = null;
        $new_object->id = 0;

        foreach ($new_data as $field => $value) {
            $new_object->set($field, $value);
        }

        $new_object->set('id', 0);
        $new_object->set('ref', '');
        $new_object->set('fk_statut', 0);
        $new_object->set('logs', '');

        foreach ($fieldsNonClone as $fieldNC) {
            $new_object->set($fieldNC, null);
        }

        $new_object->dol_object->user_author = $user->id;
        $new_object->dol_object->user_valid = '';

        $warnings = array();
        $errors = $new_object->create($warnings);

        $lines_errors = $new_object->createLinesFromOrigin($this, $new_object);

        if (count($lines_errors)) {
            $errors[] = BimpTools::getMsgFromArray($lines_errors, 'Des erreurs sont survenues lors de la copie des lignes ' . $this->getLabel('of_the'));
        }

        return $errors;
    }

    public function createFromContrat($contrat, $data)
    {
        global $user;
        $errors = array();

        foreach (BimpTools::getArrayValueFromPath($data, 'techs', array()) as $id_tech) {
            $fi = BimpCache::findBimpObjectInstance($this->module, $this->object_name, array(
                        'fk_user_tech' => $id_tech,
                        'fk_contrat'   => $contrat->id,
                        'fk_statut'    => 0
                            ), true);

            if (!BimpObject::objectLoaded($fi)) {
                $fi = BimpObject::getInstance($this->module, $this->object_name);

                $fi_warnings = array();

                $fi_errors = $fi->validateArray(array(
                    'fk_soc'       => $contrat->getData('fk_soc'),
                    'description'  => BimpTools::getArrayValueFromPath($data, 'description', ''),
                    'fk_contrat'   => (int) $contrat->id,
                    'fk_user_tech' => $id_tech,
                    'commandes'    => BimpTools::getArrayValueFromPath($data, 'linked_commandes', array()),
                    'tickets'      => BimpTools::getArrayValueFromPath($data, 'linked_tickets', array()),
                    'datei'        => BimpTools::getArrayValueFromPath($data, 'le'),
                    'time_from'    => BimpTools::getArrayValueFromPath($data, 'time_from'),
                    'time_to'      => BimpTools::getArrayValueFromPath($data, 'time_to'),
                    'urgent'       => (int) BimpTools::getArrayValueFromPath($data, 'urgent', 0)
                ));

                if (!count($fi_errors)) {
                    $fi_errors = $fi->create($fi_warnings, true);
                }

                if (count($fi_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($fi_errors, 'Echec de la création de la Fiche Inter');
                }
            }

            if (!count($errors) && BimpObject::objectLoaded($fi)) {
                $actioncomm = new ActionComm($this->db->db);

                $actioncomm->label = $fi->getRef();
                $actioncomm->note = $fi->getData('description');
                $actioncomm->punctual = 1;
                $actioncomm->userownerid = (int) BimpCore::getConf('default_id_user_actioncomm', null, 'bimptechnique');
                $actioncomm->elementtype = 'fichinter';
                $actioncomm->type_id = (int) BimpTools::getArrayValueFromPath($data, 'type_planning', 0);
                $actioncomm->datep = $data['le'] . " " . $data['de'];
                $actioncomm->datef = $data['le'] . " " . $data['a'];
                $actioncomm->socid = (int) $contrat->getData('fk_soc');
                $actioncomm->fk_element = $fi->id;

                $sujet = "L\'intervention " . $this->getRef() . " vous à été attribuée";

                $message = "<h3><b>Bimp</b><b style='color:#EF7D00' >Technique</b></h3>";
                $message .= "<p>Fiche Inter: " . $fi->getLink() . "</p>";
                $message .= "<a href='" . DOL_URL_ROOT . "/bimptechnique/?fc=fi&id=" . $fi->id . "&navtab-maintabs=actioncomm' class='btn btn-primary'>Prendre en charge l'intervention</a>";

                $actioncomm->create($user);

                $tech = $fi->getChildObject('user_tech');

                if (BimpObject::objectLoaded($tech)) {
                    mailSyn2($sujet, BimpTools::cleanEmailsStr($tech->getData('email')), $this->mailSender, $message);
                }
            }
        }

        return $errors;
    }

    public function addLog($text)
    {
        $errors = array();

        if ($this->isLoaded($errors) && $this->field_exists('logs')) {
            $logs = (string) $this->getData('logs');
            if ($logs) {
                $logs .= '<br/>';
            }
            global $user, $langs;
            $logs .= ' - <strong> Le ' . date('d / m / Y à H:i') . '</strong> par ' . $user->getFullName($langs) . ': ' . $text;
            $errors = $this->updateField('logs', $logs, null, true);
        }

        return $errors;
    }

    public function url($tab = '')
    {
        $url = DOL_URL_ROOT . '/' . "bimptechnique" . '/index.php?fc=' . "fi" . '&id=' . $this->id;
        if (!empty($tab))
            $url .= "&navtab-maintabs=" . $tab;
        return $url;
    }

    public function setSigned(&$warnings = array())
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            global $user;

            if ($this->dol_object->setValid($user) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la validation de la fiche inter');
            } else {
                $this->hydrateFromDolObject();

                $type_signature = (int) $this->getData('type_signature');

                if ($type_signature === self::TYPE_SIGN_DIST) {
                    $errors = $this->sendClientEmailForFarSign();

                    if (!count($errors)) {
                        $this->set('fk_statut', self::STATUT_ATTENTE_SIGNATURE);
                        $this->set('public_signature_url', md5($this->getData('ref')));
                        $this->set('signed', 0);
                        $this->set('date_signed', null);

                        $this->no_update_process = true;
                        $errors = $this->update($warnings, true);
                        $this->no_update_process = false;
                    }
                } else {
                    $this->set('signed', 1);
                    $this->set('date_signed', date('Y-m-d H:i:s'));

                    $this->no_update_process = true;
                    $errors = $this->update($warnings, true);
                    $this->no_update_process = false;
                }

                //print_r($errors); die('dhudfishfds');
                if (!count($errors)) {
                    // Mise à jour ActionComm
                    $tech = $this->getChildObject('user_tech');

                    if (BimpObject::objectLoaded($tech)) {
                        $sql_where = 'code = "RC_RDV" AND fk_user_action = ' . $tech->id . ' AND datep LIKE "' . date('Y-m-d') . '%"';

                        $id_actionComm = $this->db->getValue('actioncomm', 'id', $sql_where);

                        if ($id_actionComm > 0) {
                            $actionComm = $this->getInstance("bimpcore", "Bimp_ActionComm", $id_actionComm);
                            $actionComm->updateField("percent", 100);
                        }
                    }

                    // Génération PDF: 
                    $result = $this->actionGeneratePdf([]);

                    if (count($result['errors'])) {
                        $warnings[] = BimpTools::getMsgFromArray($result['errors'], 'Echec création du fichier PDF');
                    }

                    // Fermeture auto: 
                    $auto_terminer = in_array((int) $this->getData('fk_soc'), explode(',', BimpCore::getConf('id_societe_auto_terminer', '', 'bimptechnique'))) ? true : false;

                    if ($auto_terminer) {
                        $this->updateField('fk_statut', self::STATUT_TERMINER);
                    } elseif ($type_signature === self::TYPE_SIGN_ELEC) {
                        $this->updateField('fk_statut', self::STATUT_VALIDER);
                    } else {
                        $this->updateField('fk_statut', self::STATUT_ATTENTE_SIGNATURE);
                    }

                    // Changement du titre de tous les events
                    // Création des lignes de facturation: 
                    $services_executed = $this->getServicesExecutedArray();
                    if (count($services_executed)) {
                        foreach ($services_executed as $id_line_commande => $data) {
                            BimpObject::createBimpObject('bimptechnique', 'BT_ficheInter_facturation', array(
                                'fk_fichinter'        => $this->id,
                                'id_commande'         => BimpTools::getArrayValueFromPath($data, 'commande', 0),
                                'id_commande_line'    => $id_line_commande,
                                'fi_lines'            => BimpTools::getArrayValueFromPath($data, 'lines', array()),
                                'is_vendu'            => 1,
                                'total_ht_vendu'      => BimpTools::getArrayValueFromPath($data, 'ht_vendu', 0),
                                'tva_tx'              => 20,
                                'total_ht_depacement' => ((float) BimpTools::getArrayValueFromPath($data, 'ht_executed', 0) - (float) BimpTools::getArrayValueFromPath($data, 'ht_vendu', 0)),
                                'remise'              => 0,
                                    ), true, $warnings, $warnings);
                        }
                    }

                    $inter_non_vendu = $this->getServicesByTypeArray(4);
                    if (count($inter_non_vendu)) {
                        foreach ($inter_non_vendu as $index => $data) {
                            BimpObject::createBimpObject('bimptechnique', 'BT_ficheInter_facturation', array(
                                'fk_fichinter'        => $this->id,
                                'id_commande'         => 0,
                                'id_commande_line'    => 0,
                                'fi_lines'            => (isset($data['line']) ? array((int) $data['line']) : array()),
                                'is_vendu'            => 0,
                                'total_ht_vendu'      => 0,
                                'tva_tx'              => 20,
                                'total_ht_depacement' => (float) BimpTools::getArrayValueFromPath($data, 'tarif', 0),
                                'remise'              => 0,
                                    ), true, $warnings, $warnings);
                        }
                    }

                    $dep_non_vendu = $this->getServicesByTypeArray(3);
                    if (count($dep_non_vendu)) {
                        foreach ($dep_non_vendu as $index => $data) {
                            BimpObject::createBimpObject('bimptechnique', 'BT_ficheInter_facturation', array(
                                'fk_fichinter'        => $this->id,
                                'id_commande'         => 0,
                                'id_commande_line'    => 0,
                                'fi_lines'            => (isset($data['line']) ? array((int) $data['line']) : array()),
                                'is_vendu'            => 0,
                                'total_ht_vendu'      => 0,
                                'tva_tx'              => 20,
                                'total_ht_depacement' => (float) BimpTools::getArrayValueFromPath($data, 'tarif', 0),
                                'remise'              => 0,
                                    ), true, $warnings, $warnings);
                        }
                    }

                    $imponderable = $this->getServicesByTypeArray(1);
                    if (count($imponderable)) {
                        foreach ($imponderable as $index => $data) {
                            BimpObject::createBimpObject('bimptechnique', 'BT_ficheInter_facturation', array(
                                'fk_fichinter'        => $this->id,
                                'id_commande'         => 0,
                                'id_commande_line'    => 0,
                                'fi_lines'            => (isset($data['line']) ? array((int) $data['line']) : array()),
                                'is_vendu'            => 0,
                                'total_ht_vendu'      => 0,
                                'tva_tx'              => 20,
                                'total_ht_depacement' => (float) BimpTools::getArrayValueFromPath($data, 'tarif', 0),
                                'remise'              => 0,
                                    ), true, $warnings, $warnings);
                        }
                    }

                    // Envoi mails: 
                    global $conf;

                    $mail_cli_errors = array();
                    $ref = $this->getRef();
                    $email_tech = '';
                    $email_comm = '';

                    if (BimpObject::objectLoaded($tech)) {
                        $email_tech = BimpTools::cleanEmailsStr($tech->getData('email'));
                    }

                    $commercial = $this->getCommercialClient();
                    //$commercial = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', 460);
                    if (BimpObject::objectLoaded($commercial)) {
                        $email_comm = BimpTools::cleanEmailsStr($commercial->getData('email'));
                    }

                    $pdf_file = $conf->ficheinter->dir_output . '/' . $ref . '/' . $ref . '.pdf';

                    $client = $this->getChildObject('client');
                    $email_cli = BimpTools::cleanEmailsStr($this->getData('email_signature'));
                    if (!$email_cli) {
                        if (BimpObject::objectLoaded($client)) {
                            $email_cli = BimpTools::cleanEmailsStr($client->getData('email'));
                        }
                    }
                    if (!count($this->getData('commandes')) && !$this->getData('fk_contrat')) {
                        if (!in_array($this->getData('fk_soc'), explode(',', BimpCore::getConf('id_societe_auto_terminer', '', 'bimptechnique')))) {
                            $task = BimpCache::getBimpObjectInstance("bimptask", "BIMP_Task");
                            $data = array(
                                "dst"        => "dispatch@bimp.fr",
                                "src"        => "noreply@bimp.fr",
                                "subj"       => "Fiche d’intervention non liée",
                                "prio"       => 20,
                                'test_ferme' => 'fichinter:rowid=' . $this->id . ' && (commandes != "" OR fk_contrat > 0 OR fk_facture > 0)',
                                "txt"        => "Bonjour,Cette fiche d’intervention a été validée, mais n’est liée à aucune commande et à aucun contrat. Merci de faire les vérifications nécessaires et de corriger si cela est une erreur. " . $this->getNomUrl(),
                            );
                            $errors = BimpTools::merge_array($errors, $task->validateArray($data));
                            $errors = BimpTools::merge_array($errors, $task->create());
                            if ($email_comm != '')
                                $data["dst"] .= ',' . $email_comm;
                            if (!count($errors)) {
                                mailSyn2($data['subj'], $data['dst'], null, $data['txt']);
                            }
                        }
                    }

                    // Envoi au client: 
                    if (!$auto_terminer && $type_signature !== self::TYPE_SIGN_DIST/* && !$this->getData('signed') */) {
                        if (!is_file($pdf_file)) {
                            $mail_cli_errors[] = 'Fichier PDF de la Fiche Inter absent';
                            BimpCore::addlog('PDF Fiche Inter absent pour envoi par mail suite à signature', Bimp_Log::BIMP_LOG_ERREUR, 'bimptechnique', $this, array(
                                'Fichier' => $pdf_file
                            ));
                        }

                        if (!$email_cli) {
                            $mail_cli_errors[] = 'Adresse e-mail du client non renseignée';
                        }

                        if (!count($mail_cli_errors)) {
                            $signed = (int) $this->getData('signed');
                            $subject = "Fiche d'intervention " . $ref;

                            $message = "Bonjour,<br/><br/>Veuillez trouver ci-joint votre Fiche d'Intervention<br/><br/>";

                            if ($type_signature == self::TYPE_SIGN_PAPIER && !$signed) {
                                $message .= "Merci de bien vouloir l'envoyer par email à votre interlocuteur commercial, dûment complétée et signée.<br/>";
                            }

                            $message .= "Vous souhaitant bonne réception de ces éléments, nous restons à votre disposition pour tout complément d'information.<br/>";

                            if ($type_signature == self::TYPE_SIGN_PAPIER && !$signed) {
                                $message .= "Dans l'attente de votre retour.<br/>";
                            }

                            $message .= '<br/>Très courtoisement.';
                            $message .= "<br/><br/><b>Le Service Technique</b>";

                            $reply_to = $email_comm ? $email_comm : $email_tech;
                            $cc = $email_comm;

                            if ($email_tech) {
                                $cc = ($cc ? ', ' : '') . $email_tech;
                            }

//                            $cc .= ($cc ? ', ' : '') . 'f.martinez@bimp.fr';

                            $bimpMail = new BimpMail($this, $subject, $email_cli, '', $message, $reply_to, $cc);
                            $bimpMail->addFile(array($pdf_file, 'application/pdf', $ref . '.pdf'));
                            $bimpMail->send($mail_cli_errors);

                            if (!count($mail_cli_errors)) {
                                $this->addLog("FI envoyée au client avec succès");
                            }
                        }

                        if (count($mail_cli_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($mail_cli_errors, 'Echec de l\'envoi de la FI par e-mail au client');
                        }

                        // Envoi au commecial / tech
                        if ($email_comm || $email_tech) {
                            $subject = '[FI] ' . $this->getRef();

                            if (count($mail_cli_errors)) {
                                $subject .= ' [ECHEC ENVOI E-MAIL AU CLIENT]';
                            }

                            if (BimpObject::objectLoaded($client)) {
                                $subject .= ' - Client ' . $client->getRef() . ' ' . $client->getName();
                            }

                            $message = "Bonjour, pour informations : <br/><br/>";
                            $message .= "L'intervention " . $this->getLink() . ' pour le client ' . $client->getLink() . ' ';

                            if ($auto_terminer) {
                                $message .= "en interne a été signée par le technicien. La FI à été marquée comme terminée automatiquement.";
                            } else {
                                if (count($mail_cli_errors)) {
                                    $message .= 'n\'a pas pu être envoyée par e-mail au client ';
                                } else {
                                    $message .= 'a été envoyée par e-mail au client ';
                                }

                                switch ($type_signature) {
                                    case self::TYPE_SIGN_DIST:
                                        $message .= 'pour signature électronique à distance.';
                                        break;

                                    case self::TYPE_SIGN_ELEC:
                                        $message .= ' suite à sa signature électronique.';
                                        break;

                                    case self::TYPE_SIGN_PAPIER:
                                        $message .= ' pour signature papier à renvoyer par e-mail.';
                                        break;
                                }

                                if (count($mail_cli_errors)) {
                                    $message . '<br/><br/>';
                                    $message .= BimpTools::getMsgFromArray($mail_cli_errors, 'Erreurs');
                                }

                                if ($email_cli) {
                                    $message .= '<br/><br/>Adresse e-mail du client: ' . $email_cli;
                                }

                                $message .= '<br/><br/>';
                            }

                            $to = $email_comm ? $email_comm : $email_tech;
                            $cc = ($email_comm ? $email_tech : '');

//                            $cc .= ($cc ? ', ' : '') . 'f.martinez@bimp.fr';

                            if (!mailSyn2($subject, $to, '', $message, array($pdf_file), array('application/pdf'), array($ref . '.pdf'), $cc)) {
                                $warnings[] = 'Echec de l\'envoi de l\'e-mail de notification au commercial du client';
                            }
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function sendClientEmailForFarSign()
    {
        $errors = array();

        if ($this->getData('email_signature')) {
            $new_password = '';

            for ($i = 0; $i < 100; $i++) {
                $new_password = $this->generateAleatoirePassword(5);
                if (!(int) $this->db->getCount('fichinter', 'public_signature_code = "' . $new_password . '"', 'rowid')) {
                    break;
                }
            }

            $this->set('public_signature_code', $new_password);
            $today = new DateTime();
            $this->set('public_signature_date_delivrance', $today->format('Y-m-d H:i:s'));
            $today->add(new DateInterval("P4D"));
            $this->set('public_signature_date_cloture', $today->format('Y-m-d H:i:s'));

            $this->no_update_process = true;
            $up_warnings = array();
            $up_errors = $this->update($up_warnings, true);
            $this->no_update_process = false;

            if (count($up_errors)) {
                $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de l\'enregistrement du mot de passe');
            } else {
                $subject = 'Fiche d\'intervention - ' . $this->getRef();

                $msg = 'Bonjour,<br/><br/>';
                $msg .= 'Merci de signer votre rapport d\'intervention à l\'adresse suivante: ';
                $msg .= '<a href="' . DOL_URL_ROOT . '/bimptechnique/public">' . DOL_URL_ROOT . '/bimptechnique/public</a>';
                $msg .= ' en entrant votre nom ainsi que le mot de passe suivant: <b>' . $new_password . '</b><br/><br/>';
                $msg .= 'Cet accès n\'est valable que 4 Jours calandaires.<br/><br/>';
                $msg .= 'Cordialement';

                $to = BimpTools::cleanEmailsStr($this->getData('email_signature'));
                $commercial = $this->getCommercialClient();
                $tech = $this->getChildObject('user_tech');

                $email_tech = '';
                $email_comm = '';

                if (BimpObject::objectLoaded($tech)) {
                    $email_tech = $tech->getData('email');
                }

                if (BimpObject::objectLoaded($commercial)) {
                    $email_comm = $commercial->getData('email');
                }

                $reply_to = ($email_comm ? $email_comm : $email_tech);
                $cc = ''; //($email_comm ? $email_tech . ', ' : '') . 't.sauron@bimp.fr, f.martinez@bimp.fr';

                $bimpMail = new BimpMail($this, $subject, $to, '', $msg, $reply_to, $cc);

                global $conf;

                $file = $conf->ficheinter->dir_output . '/' . $this->dol_object->ref . '/' . $this->dol_object->ref . '.pdf';
                if (file_exists($file)) {
                    $bimpMail->addFile(array($file, 'application/pdf', $this->dol_object->ref . '.pdf'));
                }

                $mail_errors = array();
                $bimpMail->send($mail_errors);

                sleep(3);

                if (count($mail_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($mail_errors, 'Echec de l\'envoi de l\'e-mail au client pour la signature à distance');
                }
            }
        } else {
            $errors[] = 'E-mail client absent';
        }

        return $errors;
    }

    public function generateAleatoirePassword($nombre_char)
    {
        $password = "";
        for ($i = 0; $i < $nombre_char; $i++) {
            $selecteur_type = rand(0, 1000);

            if ($selecteur_type % 2 == 0) {
                // C'est un char
                $char = chr(rand(65, 90));
                $selecteur_maj = rand(0, 1000);
                if ($selecteur_maj % 2 == 0) {
                    // C'est une majuscule
                    $password .= strtoupper($char);
                } else {
                    // C'est une minuscule
                    $password .= strtolower($char);
                }
            } else {
                $password .= rand(0, 9);
            }
        }
        return $password;
    }

    // Actions:

    public function actionSetStatusAdmin($data, &$success = '')
    {
        global $user;
        $errors = $warnings = array();
        $success = 'Mise à jour du statut effectuée avec succès';

        if ((int) $data['status'] == self::STATUT_BROUILLON) {
            $this->updateField('old_status', $this->getData('fk_statut'));
        }
        $this->addObjectLog('Remise au statut brouillon');

        $this->updateField('fk_statut', (int) $data['status']);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionAttenteSign_to_signed($data, &$success = '')
    {
        $errors = [];
        $warnings = [];

        $success = 'Mise à jour effectuée';

        $this->addLog("Le client à signé la FI et le fichier est déposé");
        $this->set('type_signature', self::TYPE_SIGN_PAPIER);
        $this->setSigned();
        $this->updateField('fk_statut', 1);

        return [
            'errors'   => $errors,
            'warnings' => $warnings
        ];
    }

    public function actionDuplicate($data, &$success = '')
    {
        $errors = [];
        $warnings = [];

        $success = 'Dupliquée';
        if (is_array($data['dateDuplicate'])) {
            foreach ($data['dateDuplicate'] as $datei) {
                $data['datei'] = $datei;
                $errors = BimpTools::merge_array($errors, $this->duplicate($data));
            }
        } else
            $errors[] = 'Pas de date séléctionnée';
        return [
            'errors'   => $errors,
            'warnings' => $warnings
        ];
    }

    public function createLinesFromOrigin($origin, $newParent)
    {
        $errors = array();

        $params = BimpTools::overrideArray(array(
                    'is_clone' => false,
                        ), $params);

        if (!BimpObject::objectLoaded($origin) || !is_a($origin, 'BT_ficheInter')) {
            return array('Element d\'origine absent ou invalide');
        }

        $lines = $origin->getChildrenObjects('inters', array(), 'position', 'asc');

        $warnings = array();
        $i = 0;

        // Création des lignes:

        foreach ($lines as $line) {
            $i++;

            // Lignes à ne pas copier en cas de clonage: 

            $new_line = clone($line);

            $new_line->set('fk_fichinter', $newParent->id);
            $new_line->set('date', $newParent->getData('datei'));

            $arrived = explode(' ', $new_line->getData('arrived'));
            if (isset($arrived[1]))
                $new_line->set('arrived', $this->getData('datei') . ' ' . $arrived[1]);
            $departure = explode(' ', $new_line->getData('departure'));
            if (isset($departure[1]))
                $new_line->set('departure', $this->getData('datei') . ' ' . $departure[1]);



            $line_errors = $new_line->create($warnings, true);
            if (count($line_errors)) {
                $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de la création de la ligne n°' . $i);

                continue;
            }
        }
        return $errors;
    }

    public function actionSendfacturation($data, &$success = '')
    {
        $errors = [];
        $warnings = [];

        $success = "Service facturation prévenu";

        $client = $this->getInstance('bimpcore', 'Bimp_Societe', $this->getData('fk_soc'));

        $subject = '[FI] ' . $this->getRef();

        if (BimpObject::objectLoaded($client)) {
            $subject .= ' - Client: ' . $client->getRef() . ' ' . $client->getName();
        }

        $msg = 'Bonjour,<br/><br/>';
        $msg .= 'Pour information, la FI ' . $this->getLink();

        if (BimpObject::objectLoaded($client)) {
            $msg .= ' pour le client ' . $client->getRef() . ' ' . $client->getName();
        }

        $msg .= ' a été signée par le client.<br/><br/>';

        mailSyn2($subject, 'facturationclients@bimp.fr', '', $msg);

        $this->addLog("Facturation client prévenue");
        $this->updateField('fk_statut', 2);

        return [
            'errors'   => $errors,
            'warnings' => $warnings
        ];
    }

    public function actionGeneratePdf($data, &$success = '', $errors = Array(), $warnings = Array())
    {
        return parent::actionGeneratePdf(['model' => 'fi'], $success);
    }

    public function actionResendFarSignEmail($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'E-mail envoyé avec succès à ' . $this->getData('email_signature');

        $errors = $this->sendClientEmailForFarSign();

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionGenerateBulkUf($data, &$success)
    {

        $errors = Array();
        $warnings = Array();

        $id_objs = BimpTools::getArrayValueFromPath($data, 'id_objects', array());

        if (count($id_objs) > 0) {

            if (!count($errors)) {
                $factureLabel = '';
                $facture = BimpCache::getBimPObjectInstance('bimpcommercial', 'Bimp_Facture');
                foreach ($id_objs as $id) {

                    $instance = BimpCache::getBimpObjectInstance('bimptechnique', 'BT_ficheInter', $id);

                    if (!BimpObject::objectLoaded($instance)) {
                        $warnings[] = ucfirst($this->getLabel('the')) . ' d\'ID ' . $id_obj . ' n\'existe pas';
                        continue;
                    }


                    $client = BimPCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $instance->getData('fk_soc'));

                    if ((!$instance->getData('signed')) && $instance->getData('fk_statut') != self::STATUT_TERMINER || $instance->getData('fk_statut') != self::STATUT_VALIDER) {
                        $errors[] = 'Vous ne pouvez pas facturer des FI non signées';
                        break;
                    }


                    if (!count($errors)) {
                        if ($this->tmpClientId == 0)
                            $this->tmpClientId = $client->id;

                        if ($client->id == $this->tmpClientId) {
                            $tech = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $instance->getData('fk_user_tech'));
                            $factureLabel .= ($factureLabel == '') ? 'Facturation ' . $instance->getRef() : ', ' . $instance->getRef();
                            $entrepotForFacturation = $tech->getData('defaultentrepot');
                            $ef_type = $instance->getData('ef_type');
                        } else {
                            $errors[] = 'Il n\'est pas possible de facturer plusieurs FI avec des clients différents';
                            break;
                        }
                    }
                }
                $facture->set('libelle', $factureLabel);
                $facture->set('fk_soc', $client->id);
                $facture->set('type', 0);
                $facture->set('entrepot', $entrepotForFacturation);
                $facture->set('datef', date('Y-m-d H:i:s'));
                $facture->set('ef_type', $ef_type);
                $facture->set('model_pdf', 'bimpfact');
                $facture->set('ref_client', $factureLabel);
                $errors[] = $facture->printData();
            }
        }

        return Array('success' => $success, 'errors' => $errors, 'warnings' => $warnings);
    }

    public function actionDeleteLinkedFacture($data, &$success)
    {

        global $user;

        $errors = $warnings = Array();

        if ($user->rights->bimptechnique->billing) {

            $instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $this->getData('fk_facture'));

            if (delElementElement('fichinter', 'facture', $this->id, $this->getData('fk_facture'))) {
                $success = 'Facture dé-liée avec succès';
                $this->addLog('DELINK FACTURE: ' . $instance->getRef());
                $this->updatefield('fk_facture', 0);
            } else {
                $errors[] = 'Une erreur est survenue lors de l\'opération';
            }
        } else {
            $errors[] = 'Vous n\'avez aps les droits pour dé-lier une facture d\'une fiche d\'intervention';
        }

        return ['errors' => $errors, 'warnings' => $warnings, 'success' => $success];
    }

    public function actionReattach_an_object($data, &$success)
    {
        global $user;
        $warnings = [];
        $errors = [];
        $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $this->getData('fk_soc'));

        switch ($data['type_of_object']) {
            case 0;
                $errors[] = "Vous ne pouvez pas rattacher aucun objet";
                break;
            case 1:
                if ($user->rights->bimptechnique->billing) {
                    $instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $data['fk_facture']);
                    if ($this->getData('fk_soc') == $instance->getData('fk_soc')) {
                        $this->set('fk_facture', $data['fk_facture']);
                        $this->addLog('LINK FACTURE: ' . $instance->getRef());
                        addElementElement('fichinter', 'facture', $this->id, $data['fk_facture']);
                    } else {
                        $errors[] = "La facture sélectionnée n'est pas à ce client";
                    }
                    $errors = BimpTools::merge_array($errors, $this->update($warnings, true));
                } else {
                    $errors[] = 'Vous n\'avez pas les droits pour rattacher une facture à une fiche d\'intervention';
                }
                break;
            case 2:
                $instance = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat', $data['id_contrat']);
                if ($data['lines_for_contrat'] == 0) {
                    $errors[] = 'Vous devez rattacher le contrat à au moin une intervention';
                }
                if (!count($errors)) {

                    BimpTools::merge_array($errors, $this->updateField('fk_contrat', $data['id_contrat']));

                    if (!count($errors)) {
                        addElementElement('fichinter', 'contrat', $this->id, $data['id_contrat']);
                        foreach ($data['lines_for_contrat'] as $id_line_fiche) {
                            $child = $this->getChildObject('inters', $id_line_fiche);
                            if ($child->getData('type') == 3)
                                $errors = BimpTools::merge_array($errors, $child->set('type', 5));
                            else
                                $errors = BimpTools::merge_array($errors, $child->set('type', 0));
                            $children_contrat = $instance->getChildrenList('lines');
                            $errors = BimpTools::merge_array($errors, $child->set('id_line_contrat', $children_contrat[0]));
                            $errors = BimpTools::merge_array($errors, $child->update($warnings, true));
                        }
                    }
                }
                break;
            case 3:

                if (!$data['id_commande']) {
                    $errors[] = "Merci de renseigner une commande client";
                }
                if (in_array($data['id_commande'], $this->getData('commandes')))
                    $errors[] = "La commande est déjà liée à cette FI";


                if (!count($errors)) {
                    $instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $data['id_commande']);
                    if ($instance->getData('fk_soc') == $this->getData('fk_soc')) {
                        $errors = $this->updateField('commandes', BimpTools::merge_array($this->getData('commandes'), [$instance->id]));
                        if (!count($errors)) {
                            addElementElement('fichinter', 'commande', $this->id, $instance->id);
                            if (is_array($data['idLineFI_idLineCommande']) && count($data['idLineFI_idLineCommande'])) {
                                foreach ($data['idLineFI_idLineCommande'] as $id_line_fiche => $id_commande_line) {
                                    $child = $this->getChildObject('inters', $id_line_fiche);
                                    if ($child->getData('type') == 3)
                                        $errors = BimpTools::merge_array($errors, $child->updateField('type', 6));
                                    else
                                        $errors = BimpTools::merge_array($errors, $child->updateField('type', 0));

                                    $child->set('id_dol_line_commande', $id_commande_line);

                                    $errors = BimpTools::merge_array($errors, $child->update($warnings, true));
                                }
                            }
                        }
                    } else {
                        $errors[] = 'Cette commande n\'appartient pas à ' . $client->getName();
                    }
                }
                break;
        }



        return [
            'errors'   => $errors,
            'warnings' => $warnings,
            'success'  => $success
        ];
    }

    public function actionGenerateBulkPdf($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Fichier généré avec succès';
        $success_callback = '';

        $id_objs = BimpTools::getArrayValueFromPath($data, 'id_objects', array());

        if (count($id_objs) > 160)
            $errors[] = 'Trop de PDF action impossible';

        if (!count($errors)) {
            if (!is_array($id_objs) || empty($id_objs)) {
                $errors[] = 'Aucune ' . $this->getLabel() . ' sélectionnée';
            } else {
                $files = array();

                foreach ($id_objs as $id_obj) {
                    $obj = BimpCache::getBimpObjectInstance($this->module, $this->object_name, (int) $id_obj);

                    if (!BimpObject::objectLoaded($obj)) {
                        $warnings[] = ucfirst($this->getLabel('the')) . ' d\'ID ' . $id_obj . ' n\'existe pas';
                        continue;
                    }

                    $dir = $obj->getFilesDir();
                    $filename = $obj->getRef() . '.pdf';

                    if (!file_exists($dir . $filename)) {
                        $obj->actionGeneratePdf(array());
                    }

                    if (!file_exists($dir . $filename)) {
                        $warnings[] = ucfirst($this->getLabel()) . ' ' . $obj->getLink() . ': fichier PDF absent (' . $dir . $filename . ')';
                        continue;
                    }

                    $files[] = $dir . $filename;
                }

                if (!empty($files)) {
                    global $user;
                    require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/BimpPDF.php';
                    $fileName = 'bulk_' . $this->dol_object->element . '_' . $user->id . '.pdf';
                    $dir = PATH_TMP . '/bimpcore/';

                    $pdf = new BimpConcatPdf();
                    $pdf->concatFiles($dir . $fileName, $files, 'F');

                    $url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . urlencode($fileName);
                    $success_callback = 'window.open(\'' . $url . '\');';
                } else {
                    $errors[] = 'Aucun PDF trouvé';
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

//    public function actionMessageFacturation($data, &$success)
//    {
//        global $user;
//        $warnings = [];
//        $errors = [];
//        $data = (object) $data;
//
//        if (!$data->message)
//            $errors[] = "Vous ne pouvez pas envoyer un message vide";
//
//        if (!count($errors)) {
//            $cc = ($data->copy) ? $user->email : '';
//            $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $this->getData('fk_soc'));
//            $message = $data->message . "<br />";
//            $message .= "Fiche d'intervention: " . $this->getNomUrl();
//            $message .= "<br /> Client: " . $client->getNomUrl() . ' ' . $client->getName();
//
//            $bimpMail = new BimpMail($this, "Demande de facturation FI - [" . $this->getRef() . "] - " . $client->getRef() . " " . $client->getName(), "facturationclients@bimp.fr", null, $message, null, $cc);
//            $bimpMail->send($errors);
//
//            if (!count($errors)) {
//                $log = "<br /><i><u>Message</u><br />" . $data->message . "<br />";
//                $log .= "<u>Liste de difusion:</u><br >facturationclients@bimp.fr";
//                $log .= (!empty($cc)) ? "<br />" . $cc : '';
//                $log .= "</i>";
//                $this->addLog($log);
//            }
//        }
//
//        return [
//            'success'  => $success,
//            'errors'   => $errors,
//            'warnings' => $warnings
//        ];
//    }

    public function actionBilling($data, &$success)
    {
        $errors = [];
        $warnings = [];
        if (isset($data['ef_type']))
            $this->updateField('ef_type', $data['ef_type']);
        $data = (object) $data;
        $intervenant = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $this->getData('fk_user_tech'));
        $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $this->getData('fk_soc'));

        $new_facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture');
        $new_facture->set('fk_soc', $this->getData('fk_soc'));
        $new_facture->set('libelle', 'Facturation intervention N°' . $this->getRef());
        $new_facture->set('type', 0);
        $new_facture->set('entrepot', $intervenant->getData('defaultentrepot'));
        $new_facture->set('fk_cond_reglement', ($client->getData('cond_reglement')) ? $client->getData('cond_reglement') : 2);
        $new_facture->set('fk_mode_reglement', ($client->getData('mode_reglement')) ? $client->getData('mode_reglement') : 2);
        $new_facture->set('datef', date('Y-m-d H:i:s'));
        $new_facture->set('ef_type', 'FI');
        $new_facture->set('model_pdf', 'bimpfact');
        $new_facture->set('ref_client', $this->getRef());

        if ($this->getData('ef_type')) {
            $new_facture->set('ef_type', $this->getData('ef_type'));
            $errors = $new_facture->create($warnings, true);
        } else {
            $errors[] = "Impossible de créer une facture sans canal de vente. Merci";
        }

        if (!count($errors)) {
            $this->updateField('fk_facture', $new_facture->id);
            addElementElement("fichinter", "facture", $this->id, $new_facture->id);

            $children = $this->getChildrenObjects('inters');
            if (count($children) > 0) {
                $haveDep = false;
                $haveSurSite = false;
                foreach ($children as $id_child => $child) {
                    $arrayCode = $child->getArrayServiceForBilling();
                    $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product');
                    if ($product->find(Array('ref' => $arrayCode[$child->getData('type')]))) {
                        if ($product->getRef() == 'SERV19-FPR-1')
                            $haveSurSite = true;
                        if ($product->getRef() == 'SERV19-FD01')
                            $haveDep = true;
                        $new_factureLine = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');
                        $errors = BimpTools::merge_array($errors, $new_factureLine->validateArray(
                                                array(
                                                    'type'   => ObjectLine::LINE_FREE,
                                                    'id_obj' => (int) $new_facture->id)
                                        )
                        );
                        $new_factureLine->pu_ht = $product->getData('price');
                        if ($child->getData('forfait') == BT_ficheInter_det::MODE_FACT_FORFAIT)
                            $new_factureLine->qty = 1;
                        else
                            $new_factureLine->qty = $this->time_to_qty($this->timestamp_to_time($child->getData('duree')));
                        $new_factureLine->id_product = $product->id;
                        $new_factureLine->tva_tx = 20;
                        $paBase = /* BimpCore::getConf('cout_horaire_technicien', */$product->getData('cur_pa_ht')/* , 'bimptechnique') */;
                        $new_factureLine->pa_ht = ($this->time_to_qty($this->timestamp_to_time($child->getData('duree')))) * (float) $paBase / $new_factureLine->qty;

                        $line_errors = $new_factureLine->create($warnings, true);
                        if (count($line_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec création de la ligne de facture pour "' . $child->displayData('type', 'default', false) . '"');
                        }
                    }
                }
            }

            if (!$haveDep && $haveSurSite) {
                $new_factureLine = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');
                $errors = BimpTools::merge_array($errors, $new_factureLine->validateArray(
                                        array(
                                            'type'   => ObjectLine::LINE_FREE,
                                            'id_obj' => (int) $new_facture->id)
                                )
                );
                $dep_de_reference = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) BimpCore::getConf('id_dep', 0, 'bimptechnique'));
                if ($dep_de_reference->isLoaded()) {
                    $new_factureLine->pu_ht = $dep_de_reference->getData('price');

                    $qty = 1;

                    $children = $this->getChildrenList('inters');
                    if (count($children) > 0) {
                        foreach ($children as $id_child) {
                            $child = $this->getChildObject('inters', $id_child);
                            if ($child->getData('type') == 3)
                                $qty += $this->time_to_qty($this->timestamp_to_time($child->getData('duree')));
                        }
                    }

                    $new_factureLine->qty = $qty;
                    $new_factureLine->id_product = $dep_de_reference->id;
                    $new_factureLine->tva_tx = 20;
                    $new_factureLine->pa_ht = $dep_de_reference->getCurrentPaHt();
                    $errors = BimpTools::merge_array($errors, $new_factureLine->create($warnings, true));
                }
            }

            if (!count($errors)) {
                $callback = "window.open('" . DOL_URL_ROOT . "/bimpcommercial?fc=facture&id=" . $new_facture->id . "')";
                $success = "La facture numéro " . $new_facture->getNomUrl() . " a bien été créée";
            }
        }

        return [
            'success_callback' => $callback,
            'success'          => $success,
            'errors'           => $errors,
            'warnings'         => $warnings
        ];
    }

    // Overrides: 

    public function reset()
    {
        parent::reset();

        $this->tmp_facturable = array();
    }

    public function validatePost()
    {
        $id_contact = (int) BimpTools::getPostFieldValue('id_contact_signature');

        if ($id_contact) {
            $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact);

            if (BimpObject::objectLoaded($contact)) {
                if (array_key_exists('signataire', $_POST) and BimpTools::getPostFieldValue('signataire') == '')
                    $_POST['signataire'] = $contact->getName();

//                if(array_key_exists('email_signature', $_POST) and BimpTools::getPostFieldValue('email_signature') == '')
                // (Certain techs mettent leur propre adresse e-mail, on force l'adresse l'adresse e-mail du contact signataire) 
                $_POST['email_signature'] = $contact->getData('email');
            }
        }

        $errors = parent::validatePost();

        if (!count($errors)) {
            if ((int) BimpTools::getPostFieldValue('signature_set', 0)) {
                if (!count($this->getChildrenList("inters"))) {
                    $errors[] = "Vous ne pouvez pas faire signer une fiche d'intervention sans intervention enregistrée";
//                    $errors[] = print_r($_POST, 1);
                }
            }
        }

        return $errors;
    }

    public function validate()
    {
        $errors = parent::validate();

        if (!count($errors)) {
            if ($this->getData('time_from') && $this->getData('time_to') &&
                    $this->getData('time_to') < $this->getData('time_from')) {
                $errors[] = 'L\'heure de fin de l\'intervention ne peut pas être inférieure à l\'heure de début';
            }

            // Vérif données selon type de signature: 
            $type_sign = (int) $this->getData('type_signature');
            if ($type_sign === self::TYPE_SIGN_ELEC) {
                if (!$this->getData('base_64_signature')) {
                    $errors[] = 'Signature absente';
                }
            }
            if (in_array($type_sign, array(self::TYPE_SIGN_PAPIER, self::TYPE_SIGN_ELEC))) {
                if (!$this->getData('signataire')) {
                    $errors[] = 'Nom du signataire absent';
                }
            }
            if ($type_sign) {
                if (!$this->getData('email_signature')) {
                    $errors[] = 'Adresse e-mail du client absente';
                }

                switch ($type_sign) {
                    case self::TYPE_SIGN_DIST:
                        $this->set('signataire', '');
                    case self::TYPE_SIGN_PAPIER:
                        $this->set('base_64_signature', '');
                        break;
                }
            }

            if ($this->getInitData('fk_contrat') != BimpTools::getPostFieldValue('fk_contrat')) {
                
            }
        }

        return $errors;
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        global $user;

        $errors = Array();

        if ((int) $this->getData('fk_contrat')) {
            $verifContrat = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat', (int) $this->getData('fk_contrat'));
            $heuresRestantes = $verifContrat->getHeuresRestantesDelegation();
            if ($verifContrat->isContratDelegation()) {
                $totalHeuresVendu = 0;

                foreach ($verifContrat->getTotalHeureDelegation(true) as $heure) {
                    $totalHeuresVendu += $heure;
                }

                $from = new DateTime($this->getData('datei') . " " . $this->getData('time_from'));
                $to = new DateTime($this->getData('datei') . " " . $this->getData('time_to'));
                $diff = $from->diff($to);

                $heuresDemander = $diff->h + ($diff->i / 60);
                if ($heuresDemander > $totalHeuresVendu) {
                    $errors[] = 'Vous ne pouvez pas programmer une intervention avec un nombre d\'heures suppérieur à ce qu\'il a été vendue (VENDU:' . $totalHeuresVendu . ' heures, PROGRAMMER:' . $heuresDemander . ' heures)';
                }

                if ($heuresRestantes <= 0) {
                    $errors[] = 'Vous ne pouvez pas programmer une intervention sur ce contrat. Cause: Nombre d\'heures vendues dépassée';
                }
            }
        }

        //$errors[] = 'secu';
        if (!count($errors)) {

            $this->set('new_fi', 1);
            $this->set('fk_user_author', (int) $user->id);

            if (!(int) $this->getData('fk_user_tech')) {
                $this->set('fk_user_tech', $user->id);
            }

            $client = $this->getChildObject('client');

            if (!BimpObject::objectLoaded($client)) {
                return array('Client invalide');
            }

            $this->set('ref', "(PROV$this->id)");

            $errors = parent::create($warnings, $force_create);
            $this->set('ref', "(PROV$this->id)");

            if (!count($errors)) {
                $client->setActivity('Création ' . $this->getLabel('of_the') . ' {{Fiche inter:' . $this->id . '}}');

                $commandes = $this->getData('commandes');
                if (is_array($commandes)) {
                    foreach ($commandes as $id_commande) {
                        addElementElement("commande", "fichinter", $id_commande, $this->id);
                    }
                }

                $tickets = $this->getData('tickets');
                if (is_array($tickets)) {
                    foreach ($tickets as $id_ticket) {
                        addElementElement('bimp_ticket', 'fichinter', $id_ticket, $this->id);
                    }
                }

                if ((int) $this->getData('fk_contrat')) {
                    addElementElement('contrat', 'fichinter', $this->getData('fk_contrat'), $this->id);
                }

                // Création actionComm: 
                BimpTools::loadDolClass('comm/action/', 'actioncomm', 'ActionComm');

                $actioncomm = new ActionComm($this->db->db);

                //$actioncomm->userassigned = Array($data->techs);
                $actioncomm->label = "(PROV$this->id)";
                $actioncomm->note = '';
                $actioncomm->punctual = 1;
                $actioncomm->userownerid = (int) $this->getData('fk_user_tech');
                $actioncomm->elementtype = 'fichinter';
                $actioncomm->type_id = BimpTools::getPostFieldValue('type_planning', 0);
                $actioncomm->datep = strtotime($this->getData('datei') . " " . $this->getData('time_from'));
                $actioncomm->datef = strtotime($this->getData('datei') . " " . $this->getData('time_to'));
                $actioncomm->socid = $this->getData('fk_soc');
                $actioncomm->fk_element = $this->id;
                $actioncomm->create($user);
                $errors = BimpTools::merge_array($errors, BimpTools::getErrorsFromDolObject($actioncomm));
                // Envoi mail au tech: 

                $tech = $this->getChildObject('user_tech');
                if (!count($errors) && BimpObject::objectLoaded($tech)) {
                    $de = new DateTime($this->getData('datei') . " " . $this->getData('time_from'));
                    $a = new DateTime($this->getData('datei') . " " . $this->getData('time_to'));

                    $client = $this->getChildObject('client');

                    $sujet = "[FI] " . $this->getRef() . (BimpObject::objectLoaded($client) ? ' - Client: ' . $client->getData('code_client') . ' - ' . $client->getName() : '');

                    $message = "<h4><strong style='color:#EF7D00'>Bimp</strong><strong style='color:black' >Technique</strong> - <strong style='color:grey' >Fiches d'interventions</strong></h4>";
                    $message .= "Bonjour,<br />Une fiche d'intervention vous a été attribuée";
                    $message .= "<br /><br />";
                    $message .= 'Fiche d\'intervention: ' . $this->getLink() . '<br />';
                    $message .= 'Date prévue de l\'intervention: <strong>Le ' . $de->format('d/m/Y H:i') . ' au ' . $a->format('d/m/Y H:i') . '</strong>';
                    $message .= '<br/><br/>';

                    $this->addLog("Fiche d'intervention créée");
                    mailSyn2($sujet, BimpTools::cleanEmailsStr($tech->getData('email')), "gle@bimp.fr", $message);
                } else {
                    $warnings[] = 'L\'e-mail n\'a pas pu être envoyé au technicien (Technicien sélectionné invalide)';
                }
            }
        }



        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        global $user;

        $errors = array();

        $init_fk_contrat = (int) $this->getInitData('fk_contrat');
        $init_commandes = $this->getInitData('commandes');
        $init_tickets = $this->getInitData('tickets');
        $init_id_tech = (int) $this->getInitData('fk_user_tech');
        $init_date = $this->getInitData('datei');
        $init_time_from = $this->getInitData('time_from');
        $init_time_to = $this->getInitData('time_to');

        $errors = parent::update($warnings, $force_update);

        if (!$this->no_update_process && !count($errors)) {
            if ((int) BimpTools::getPostFieldValue('signature_set', 0)) {
                $this->setSigned($warnings);
            }

            // Màj commandes liées: 
            $commandes = $this->getData('commandes');
            foreach ($init_commandes as $id_commande) {
                if (!in_array((int) $id_commande, $commandes)) {
                    delElementElement("commande", "fichinter", $id_commande, (int) $this->id);
                }
            }
            foreach ($commandes as $id_commande) {
                if (!in_array((int) $id_commande, $init_commandes)) {
                    addElementElement("commande", "fichinter", $id_commande, (int) $this->id);
                }
            }

            // Màj tickets liés: 
            $tickets = $this->getData('tickets');
            foreach ($init_tickets as $id_ticket) {
                if (!in_array((int) $id_ticket, $tickets)) {
                    delElementElement('bimp_ticket', 'fichinter', $id_ticket, $this->id);
                }
            }

            foreach ($tickets as $id_ticket) {
                if (!in_array((int) $id_ticket, $init_tickets)) {
                    addElementElement('bimp_ticket', 'fichinter', $id_ticket, $this->id);
                }
            }

            // Màj contrat lié: 
            if ($init_fk_contrat !== (int) $this->getData('fk_contrat')) {
                // La méthode update() de ficheinter ne met pas à jour fk_contrat. 

                if ($this->isFieldEditable('fk_contrat')) {
                    $err = $this->updateField('fk_contrat', $this->getData('fk_contrat'));
                    if (count($err)) {
                        $warnings[] = BimpTools::getMsgFromArray($err, 'Echec de la mise à jour du contrat lié');
                    } else {
                        $this->db->update('element_element', array(
                            'fk_source' => (int) $this->getData('fk_contrat')
                                ), 'sourcetype = \'contrat\' AND targettype = \'fichinter\' AND fk_source = ' . $init_fk_contrat . ' AND fk_target = ' . $this->id);
                    }
                } else {
                    $this->set('fk_contrat', $init_fk_contrat);
                }
            }

            //die($init_date);
            $changement_de_tech = false;
            BimpTools::loadDolClass('comm/action/', 'actioncomm', 'ActionComm');
            $actionComm = new ActionComm($this->db->db);
            // Changement de tech: 
            if ($init_id_tech !== (int) $this->getData('fk_user_tech')) {
                $changement_de_tech = true;
                $table = 'actioncomm';
                $where = 'code <> \'AC_FICHINTER_VALIDATE\' AND fk_element = ' . $this->id . ' AND fk_soc = ' . $this->getData('fk_soc') . ' AND elementtype = \'fichinter\'';

                $allEvents = $this->db->getRows($table, $where);

                if (count($allEvents) > 0) {
                    foreach ($allEvents as $event) {
                        $actionComm->fetch($event->id);
                        $actionComm->userownerid = (int) $this->getData('fk_user_tech');
                        $actionComm->userassigned = Array();
                        $actionComm->otherassigned = Array();
                        if ($actionComm->update($user) <= 0) {
                            $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($ac), 'Echec du changement d\'utilisateur dans l\'événement agenda');
                        }
                    }
                }

                $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $this->getData('fk_soc'));
                $ancienTech = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $init_id_tech);
                $currentTech = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $this->getData('fk_user_tech'));
                //Envois des mails
                $sujet = 'FI ' . $this->getRef() . ' - Changement de technicien';
                $message = 'Bonjour,<br />' . 'La fiche d\'intervention N°' . $this->getRef() . ' vous a été attribuée<br /></br ><b><u>Détails</u></b><br />';
                $message .= 'Référence: ' . $this->getNomUrl() . ' <br />' . 'Client: ' . $client->getNomUrl() . ' ' . $client->getName() . '<br />Ancien technicien: ' . $ancienTech->getName();
                $message .= '<br />Changement par: ' . $user->getNomUrl();
                $message .= '<br />Pour plus de détails rendez-vous sur la fiche d\'intervention';

                $this->addLog('Changement de technicien: ' . $ancienTech->getName() . ' => ' . $currentTech->getName());

                mailSyn2($sujet, $currentTech->getData('email'), null, $message);
            }

            // Changement de date et d'horaire
            $dateTime_debut = new DateTime($this->getData('datei') . ' ' . $this->getData('time_from'));
            $dateTime_fin = new DateTime($this->getData('datei') . ' ' . $this->getData('time_to'));
            $changement_horaire = false;
            if (($init_date != $this->getData('datei')) || ($init_time_from != $this->getData('time_from') || $init_time_to != $this->getData('time_to'))) {
                $changement_horaire = true;

                $id_event = $this->getEventId();
                if ($id_event > 0) {
                    $actionComm->fetch($id_event);
                    $actionComm->datep = $dateTime_debut->getTimestamp();
                    $actionComm->datef = $dateTime_fin->getTimestamp();
                    if ($actionComm->update($user) <= 0) {
                        $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($ac), 'Echec du changement d\'utilisateur dans l\'événement agenda');
                    }
                }
            }

            if (!$changement_de_tech && $changement_horaire) {
                $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $this->getData('fk_soc'));
                $sujet = 'FI ' . $this->getRef() . ' - Modification horaire';
                $message = 'Bonjour,<br />La fiche d\'intervention N°' . $this->getNomUrl() . ' à été modifiée au niveau des horaires.<br />';
                $message .= 'Nouveaux horaires: ' . '<strong class=\'danger\'>Du ' . $dateTime_debut->format('d/m/Y H:i') . ' au ' . $dateTime_fin->format('d/m/Y H:i') . '</strong>';
                $message .= 'Client: ' . $client->getNomUrl() . ' ' . $client->getName();
                $tech = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $this->getData('fk_user_tech'));

                mailSyn2($sujet, $tech->getData('email'), null, $message);
            }
        }

        return $errors;
    }

    public function delete(&$warnings = [], $force_delete = false)
    {
        $id_fi = (int) $this->id;
        $id_soc = (int) $this->getData('fk_soc');
        $id_tech = (int) $this->getData('fk_user_tech');

        $ids_commande = ($this->getData('commandes')) ? $this->getData('commandes') : [];

        $id_contrat = $this->getData('fk_contrat');

        $refLink = $this->getLink();
        $ref = $this->getRef();
        $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $this->getData('fk_soc'));

        $errors = parent::delete($warnings, $force_delete);

        if (!count($errors)) {
            global $user;

            $actionCommList = $this->db->getRows("actioncomm", 'fk_soc = ' . $id_soc . " AND elementtype = 'fichinter' AND fk_element = " . $id_fi);
            $emailControl = "v.gilbert@bimp.fr";
            $tech = BimpCache::getBimpObjectInstance("bimpcore", "Bimp_User", $id_tech);
            BimpTools::loadDolClass("comm/action", "actioncomm");

            $actionCommClass = new ActionComm($this->db->db);

            foreach ($actionCommList as $index => $object) {
                $actionCommClass->fetch($object->id);
                $actionCommClass->delete();
            }

            global $rgpd_processing;

            if (!$rgpd_processing) {
                $message = "Bonjour,<br />La fiche d'intervention " . $refLink . " à été supprimé par " . $user->getNomUrl() . " ainsi que le ou les évènements dans l'agenda.<br/><br/>Infos:<br />"
                        . "Client: " . $client->getName() . '<br /><br />'
                        . "Commandes: ";

                if (count($ids_commande) > 0) {
                    foreach ($ids_commande as $id_commande) {
                        $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $id_commande);
                        $message .= $commande->getLink() . ',';
                    }
                } else {
                    $message .= 'Pas de commandes';
                }

                $message .= '<br /><br />';

                if ($id_contrat > 0) {
                    $message .= 'Contrat<br />';
                    $contrat = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat', $id_contrat);
                    $message .= $contrat->getLink();
                } else {
                    $message .= 'pas de contrat';
                }

                $to = ($tech->getData('email') != $emailControl) ? $tech->getData('email') . ',' . $emailControl : $tech->getData('email');

                mailSyn2("[FI] " . $ref . " supprimée - " . $client->getName(), $to, null, $message);
            }
        }

        return $errors;
    }

    // Outils : 

    public static function time_to_qty($time)
    {
        $timeArr = explode(':', $time);
        if (count($timeArr) == 3) {
            $decTime = ($timeArr[0] * 60) + ($timeArr[1]) + ($timeArr[2] / 60);
        } else if (count($timeArr) == 2) {
            $decTime = ($timeArr[0]) + ($timeArr[1] / 60);
        } else if (count($timeArr) == 2) {
            $decTime = $time;
        }
        return $decTime;
    }

    public function time_to_decimal($time)
    {
        $timeArr = explode(':', $time);
        $decTime = ($timeArr[0] * 60) + ($timeArr[1]) + ($timeArr[2] / 60);

        return $decTime;
    }

    public static function timestamp_to_time($timestamp)
    {
        $neg = ($timestamp < 0);
        $timestamp = abs($timestamp/60);
        $heures = floor($timestamp / 60);
        $minutes = floor($timestamp % 60);
        return ($neg ? '-' : '').str_pad($heures, 2, "0", STR_PAD_LEFT) . ":" . str_pad($minutes, 2, "0", STR_PAD_LEFT);
    }

    // Méthodes statiques: 

    public static function convertAllNewFi()
    {
        echo 'Script désactivé';
        return;
        $bdb = self::getBdb();

        // Conversion Tickets / Commandes: 
        $where = 'new_fi = 1';
        $where .= ' AND ((commandes IS NOT NULL AND commandes != \'\')';
        $where .= ' OR (tickets IS NOT NULL AND tickets != \'\'))';
        $rows = $bdb->getRows('fichinter', $where, null, 'array', array(
            'rowid', 'tickets', 'commandes'
        ));

        if (is_array($rows)) {
            foreach ($rows as $r) {
                $commandes = '';
                $tickets = '';
                if ($r['commandes']) {
                    $ids = json_decode($r['commandes']);

                    if (is_array($ids) && !empty($ids)) {
                        foreach ($ids as $id) {
                            $commandes .= '[' . $id . ']';
                        }
                    }
                }

                if ($r['tickets']) {
                    $ids = json_decode($r['tickets']);

                    if (is_array($ids) && !empty($ids)) {
                        foreach ($ids as $id) {
                            $tickets .= '[' . $id . ']';
                        }
                    }
                }

                $data = array();

                if ($commandes) {
                    $data['commandes'] = $commandes;
                }

                if ($tickets) {
                    $data['tickets'] = $tickets;
                }

                if (!empty($data)) {
                    $bdb->update('fichinter', $data, 'rowid = ' . $r['rowid']);
                }
            }
        }

        // Conversion lignes FI dans Lignes facturation: 
        $sql = 'SELECT a.id, a.fi_lines FROM llx_fichinter_facturation a';
        $sql .= ' LEFT JOIN llx_fichinter f ON a.fk_fichinter = f.rowid';
        $sql .= ' WHERE f.new_fi = 1';
        $sql .= ' AND a.fi_lines IS NOT NULL AND a.fi_lines != \'\'';

        $rows = $bdb->executeS($sql, 'array');

        if (is_array($rows)) {
            foreach ($rows as $r) {
                if ($r['fi_lines']) {
                    $ids = json_decode($r['fi_lines']);

                    if (is_array($ids) && !empty($ids)) {
                        $lines = '';

                        foreach ($ids as $id) {
                            $lines .= '[' . $id . ']';
                        }

                        $bdb->update('fichinter_facturation', array(
                            'fi_lines' => $lines
                                ), 'id = ' . $r['id']);
                    }
                }
            }
        }


        // Conversion ID lignes de commandes dans Inters:     
        $sql = 'SELECT a.rowid, a.id_line_commande as id_dol_line, cl.id as id_bimp_line FROM llx_fichinterdet a';
        $sql .= ' LEFT JOIN llx_fichinter f ON a.fk_fichinter = f.rowid';
        $sql .= ' LEFT JOIN llx_bimp_commande_line cl ON a.id_line_commande = cl.id_line';
        $sql .= ' WHERE f.new_fi = 1';
        $sql .= ' AND a.id_line_commande > 0';
        $sql .= ' AND (a.id_dol_line_commande = 0 OR a.id_dol_line_commande = a.id_line_commande)';

        $rows = $bdb->executeS($sql, 'array');

        if (is_array($rows)) {
            foreach ($rows as $r) {
                $bdb->update('fichinterdet', array(
                    'id_line_commande'     => ($r['id_bimp_line'] ? (int) $r['id_bimp_line'] : 0),
                    'id_dol_line_commande' => (int) $r['id_dol_line']
                        ), 'rowid = ' . $r['rowid']);
            }
        }

        // Conversion ID lignes de commandes dans facturation:    
        $sql = 'SELECT a.id, a.id_commande_line as id_dol_line, cl.id as id_bimp_line FROM llx_fichinter_facturation a';
        $sql .= ' LEFT JOIN llx_fichinter f ON a.fk_fichinter = f.rowid';
        $sql .= ' LEFT JOIN llx_bimp_commande_line cl ON a.id_commande_line = cl.id_line';
        $sql .= ' WHERE f.new_fi = 1';
        $sql .= ' AND a.id_commande_line > 0';
        $sql .= ' AND (a.id_dol_line_commande = 0 OR a.id_dol_line_commande = a.id_commande_line)';

        $rows = $bdb->executeS($sql, 'array');

        if (is_array($rows)) {
            foreach ($rows as $r) {
                $bdb->update('fichinter_facturation', array(
                    'id_commande_line'     => ($r['id_bimp_line'] ? (int) $r['id_bimp_line'] : 0),
                    'id_dol_line_commande' => (int) $r['id_dol_line']
                        ), 'id = ' . $r['id']);
            }
        }
    }
}

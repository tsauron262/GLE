<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/fichinter/class/fichinter.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';

class BT_ficheInter extends BimpDolObject
{

    public $mailSender = 'gle@bimp.fr';
    public $mailGroupFi = 'fi@bimp.fr';
    public static $dol_module = 'fichinter';
    public static $files_module_part = 'ficheinter';
    public static $element_name = 'fichinter';
    public $tmp_facturable = [];

    CONST STATUT_ABORT = -1;
    CONST STATUT_BROUILLON = 0;
    CONST STATUT_VALIDER = 1;
    CONST STATUT_VALIDER_COMMERCIALEMENT = 3;
    CONST STATUT_TERMINER = 2;
    CONST STATUT_SIGANTURE_PAPIER = 4;
    CONST STATUT_DEMANDE_FACT = 10;
    CONST STATUT_ATTENTE_VALIDATION = 11;
    CONST URGENT_NON = 0;
    CONST URGENT_OUI = 1;
    CONST TYPE_NO = 0;
    CONST TYPE_FORFAIT = 1;
    CONST TYPE_GARANTIE = 2;
    CONST TYPE_CONTRAT = 3;
    CONST TYPE_TEMPS = 4;
    CONST NATURE_NO = 0;
    CONST NATURE_INSTALL = 1;
    CONST NATURE_DEPANNAGE = 2;
    CONST NATURE_TELE = 3;
    CONST NATURE_FORMATION = 4;
    CONST NATURE_AUDIT = 5;
    CONST NATURE_SUIVI = 6;
    CONST NATURE_DELEG = 7;

    public static $status_list = [
        self::STATUT_ABORT              => ['label' => "Abandonée", 'icon' => 'times', 'classes' => ['danger']],
        self::STATUT_BROUILLON          => ['label' => "En cours de renseignement", 'icon' => 'retweet', 'classes' => ['warning']],
        self::STATUT_VALIDER            => ['label' => "Validée", 'icon' => 'check', 'classes' => ['success']],
        self::STATUT_TERMINER           => ['label' => "Terminée", 'icon' => 'thumbs-up', 'classes' => ['important']],
        self::STATUT_SIGANTURE_PAPIER   => ['label' => "Attente signature client", 'icon' => 'warning', 'classes' => ['important']],
        self::STATUT_DEMANDE_FACT       => ['label' => "Attente de facturation", 'icon' => 'euro', 'classes' => ['important']],
        self::STATUT_ATTENTE_VALIDATION => ['label' => "Attente de validation commercial", 'icon' => 'thumbs-up', 'classes' => ['important']],
    ];
    public static $urgent = [
        self::URGENT_NON => ['label' => "NON", 'icon' => 'times', 'classes' => ['success']],
        self::URGENT_OUI => ['label' => "OUI", 'icon' => 'check', 'classes' => ['danger']]
    ];
    public static $type_list = array(
        self::TYPE_NO       => array('label' => 'FI ancienne version', 'icon' => 'refresh', 'classes' => array('info')),
        self::TYPE_FORFAIT  => array('label' => 'Forfait', 'icon' => 'check', 'classes' => array('info')),
        self::TYPE_GARANTIE => array('label' => 'Sous garantie', 'icon' => 'check', 'classes' => array('info')),
        self::TYPE_CONTRAT  => array('label' => 'Contrat', 'icon' => 'check', 'classes' => array('info')),
        self::TYPE_TEMPS    => array('label' => 'Temps pass&eacute;', 'icon' => 'check', 'classes' => array('warning')),
    );
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
    public static $actioncomm_code = "'AC_INT','RDV_EXT','RDV_INT','ATELIER','LIV','INTER','INTER_SG','FORM_INT','FORM_EXT','FORM_CERTIF','VIS_CTR','TELE','TACHE'";
    private $global_user;
    private $global_langs;
    public $redirectMode = 4;

    public function __construct($module, $object_name)
    {
        global $user, $langs;
        $this->global_user = $user;
        $this->global_langs = $langs;
        return parent::__construct($module, $object_name);
    }

    // Droits users: 

    public function canCreate()
    {
        return 1;
    }

    public function canEdit()
    {
        if (!$this->isOldFi()) {
            return 1;
        }
        return 0;
    }

    public function canDelete()
    {
        global $user;
        if ((($this->getData('fk_statut') == 0) && $this->getData('fk_user_author') == $this->global_user->id && !$this->isOldFi()) ||
                ($user->admin || $user->rights->bimptechnique->delete)) {
            return 1;
        }

        return 0;
    }

    public function canSetActionAdmin()
    {
        return $this->canDelete();
    }

    public function canSetAction($action)
    {
        switch ($action) {
            
        }

        return parent::canSetAction($action);
    }

    // Getters booléens: 

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            
        }
        return parent::isActionAllowed($action, $errors);
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
        return !$this->getData('new_fi');
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

    public function haveSignaturePapier()
    {
        if ($this->getData('signataire') && $this->isNotSign()) {
            return 1;
        } elseif ($this->getData('signataire') && !$this->getData('base_64_signature') && $this->isSign()) {
            return 1;
        }
        return 0;
    }

    public function haveSignatureElectronique()
    {
        if ($this->isSign() && $this->getData('signataire') && $this->getData('base_64_signature'))
            return 1;
        return 0;
    }

    public function haveContratLinked()
    {
        if ($this->getData('fk_contrat'))
            return 1;
        return 0;
    }

    public function userHaveRight($right)
    {
        if ($this->global_user->rights->bimptechnique->$right)
            return 1;
        return 0;
    }

    // Gettters params: 

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, $main_alias = 'a', &$errors = array(), $excluded = false)
    {
        switch ($field_name) {
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
                            $sql .= "AND ";
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

                $filters['a.rowid'] = ['in' => $in];
                break;
            case 'no_linked':
                $in = [];
                $sql = "";
                if (count($values) > 0) {
                    if (in_array("0", $values)) {
                        $sql = "SELECT rowid FROM llx_fichinter WHERE fk_contrat = 0 AND ";
                        $sql .= "(commandes = '[]' OR commandes = '' OR commandes IS NULL) AND ";
                        $sql .= "(tickets = '[]' OR tickets = '' OR tickets IS NULL)";
                    }
                }
                if ($sql != "") {
                    $res = $this->db->executeS($sql, 'array');
                    foreach ($res as $nb => $i) {
                        $in[] = $i['rowid'];
                    }
                }
                $filters['a.rowid'] = ['in' => $in];
                break;
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $main_alias, $errors, $excluded);
    }

    public function getDirOutput()
    {
        global $conf;
        return $conf->ficheinter->dir_output;
    }

    public function getActionsButtons()
    {
        global $conf, $langs, $user;
        $buttons = Array();
        $statut = $this->getData('fk_statut');

        if (!$this->isOldFi()) {
            $interne_soc = explode(',', BimpCore::getConf('bimptechnique_id_societe_auto_terminer'));

            $children = $this->getChildrenList("inters");

            if ($statut == self::STATUT_VALIDER) {
                $buttons[] = array(
                    'label'   => 'Demander la facturation',
                    'icon'    => 'fas_hand-holding-usd',
                    'onclick' => $this->getJsActionOnclick('askFacturation', array(), array(
                        'form_name' => 'askFacturation'
                    ))
                );
            }

            if ($statut == self::STATUT_BROUILLON) {
                $buttons[] = array(
                    'label'   => 'Générer le PDF',
                    'icon'    => 'file-pdf',
                    'onclick' => $this->getJsActionOnclick('generatePdf', array(), array())
                );
            }
            if ($statut == self::STATUT_BROUILLON && $this->canSetActionAdmin() && $this->getData('old_status') > 0) {
                $buttons[] = array(
                    'label'   => 'Remettre au satut ' . self::$status_list[$this->getData('old_status')]['label'],
                    'icon'    => 'file-pdf',
                    'onclick' => $this->getJsActionOnclick('setStatusAdmin', array('status' => $this->getData('old_status')), array())
                );
            }
            if ($statut != self::STATUT_BROUILLON && $this->canSetActionAdmin()) {
                $buttons[] = array(
                    'label'   => 'Remmetre en brouillon',
                    'icon'    => 'file-pdf',
                    'onclick' => $this->getJsActionOnclick('setStatusAdmin', array('status' => self::STATUT_BROUILLON), array())
                );
            }

            if ($statut != self::STATUT_VALIDER) {
                if ($statut == self::STATUT_BROUILLON) {
                    if (!in_array($this->getData('fk_soc'), $interne_soc)) {
                        $buttons[] = array(
                            'label'   => 'Lier une ou plusieurs commandes client',
                            'icon'    => 'link',
                            'onclick' => $this->getJsActionOnclick('linked_commande_client', array(), array(
                                'form_name' => 'linked_commande_client'
                            ))
                        );
                        if (!$this->getData('fk_contrat')) {
                            $buttons[] = array(
                                'label'   => 'Lier un contrat client',
                                'icon'    => 'link',
                                'onclick' => $this->getJsActionOnclick('linked_contrat_client', array(), array(
                                    'form_name' => 'linked_contrat_client'
                                ))
                            );
                        }
                    }
                    $buttons[] = array(
                        'label'   => 'Lier un ou plusieurs tickets support',
                        'icon'    => 'link',
                        'onclick' => $this->getJsActionOnclick('linked_ticket_client', array(), array(
                            'form_name' => 'linked_ticket_client'
                        ))
                    );
                }

//            if(!count($children) && $this->userHaveRight("plannified")) {
//                $buttons[] = array(
//                    'label' => 'Changer de technicien',
//                    'icon' => 'retweet',
//                    'onclick' => $this->getJsActionOnclick('changeTech', array(), array(
//                        'form_name' => 'changeTech'
//                    ))
//                );
//            }


                if ($statut == self::STATUT_BROUILLON) {
                    $buttons[] = array(
                        'label'   => 'Ajouter une ligne',
                        'icon'    => 'fas_plus',
                        'onclick' => $this->getJsActionOnclick('addInter', array(), array(
                            'form_name' => 'addInter'
                        ))
                    );
//                $buttons[] = array(
//                    'label' => 'Signature à distance',
//                    'icon' => 'fas_sign',
//                    'onclick' => $this->getJsActionOnclick('farSign', array(), array(
//                        'form_name' => 'farSign'
//                    ))
//                );
//                
                }

                if ($statut == self::STATUT_SIGANTURE_PAPIER) {
                    $buttons[] = array(
                        'label'   => 'J\'ai déposé la FI signée',
                        'icon'    => 'fas_upload',
                        'onclick' => $this->getJsActionOnclick('attenteSign_to_signed', array(), array(
                            "form_name" => "attenteSign_to_sign"
                        ))
                    );
                }
            }

//            if($this->haveSurplusFacturation() && $statut == self::STATUT_VALIDER) {
//                $buttons[] = array(
//                    'label' => 'Demander la facturation de cette fiche',
//                    'icon' => 'euro',
//                    'onclick' => $this->getJsActionOnclick('askFacturation', array(), array())
//                );
//            }

            if ($statut == self::STATUT_DEMANDE_FACT && $user->rights->bimptechnique->billing) {
                $buttons[] = array(
                    'label'   => 'Facturer',
                    'icon'    => 'euro',
                    'onclick' => $this->getJsActionOnclick('createFacture', array(), array())
                );
            }


//            if($statut == self::STATUT_VALIDER) {
//                $buttons[] = array(
//                    'label' => "Prévenir la facturation",
//                    'icon' => 'euro',
//                    'onclick' => $this->getJsActionOnclick('sendFacturation', array(), array(
//                    ))
//                );
//            }
        }


        return $buttons;
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

    public function url($tab = '')
    {
        $url = DOL_URL_ROOT . '/' . "bimptechnique" . '/index.php?fc=' . "fi" . '&id=' . $this->id;
        if (!empty($tab))
            $url .= "&navtab-maintabs=" . $tab;
        return $url;
    }

    // Getters filtres listes: 

    public function getListFilterDefault()
    {
        return Array(
            Array(
                'name'   => 'fk_contrat',
                'filter' => $_REQUEST['id']
            )
        );
    }

    public function getListFilterAll()
    {
        // Utiliser un callback que si du traitement PHP est nécessaire: sinon mettre en dur dans le yml de la liste. 
        return Array(
            Array(
                'name'   => 'new_fi',
                'filter' => 1
            )
        );
    }

    public function getListFilterHistorique()
    {
        return Array(
            Array(
                'name'   => 'new_fi',
                'filter' => 0
            )
        );
    }

    public function getListFilterHistoriqueUser()
    {
        global $user;
        if (isset($_REQUEST['specialTech']) && $_REQUEST['specialTech'] > 0)
            $userId = $_REQUEST['specialTech'];
        else
            $userId = $user->id;
        return Array(
            Array(
                'name'   => 'new_fi',
                'filter' => 0
            ),
            Array(
                'name'   => 'fk_user_author',
                'filter' => $userId
            ),
        );
    }

    public function getListFilterTech()
    {
        global $user;
        if (isset($_REQUEST['specialTech']) && $_REQUEST['specialTech'] > 0)
            $userId = $_REQUEST['specialTech'];
        else
            $userId = $user->id;
        return Array(
            Array(
                'name'   => 'fk_user_author',
                'filter' => $userId
            ),
            Array(
                'name'   => 'new_fi',
                'filter' => 1
            )
        );
    }

    // Getters Données: 

    public function getNextNumRef($soc)
    {
        return $this->dol_object->getNextNumRef($soc);
    }

    public function getContratClient()
    {
        if (!(int) $this->getData('fk_soc')) {
            return array();
        }

        $contrats = [];

        foreach (BimpCache::getBimpObjectObjects('bimpcontract', 'BContract_contrat', array(
            'statut' => 11,
            'fk_soc' => (int) $this->getData('fk_soc')
        )) as $contrat) {
            $add_label = "";
            if ($contrat->getData('label')) {
                $add_label = " - " . $contrat->getData('label');
            }
            $contrats[$contrat->id] = $contrat->getRef() . " (" . $contrat->displayData('statut', 'default', false) . ")" . $add_label;
        }

        return $contrats;
    }

    public function getCommandeClient()
    {

        if (!(int) $this->getData('fk_soc')) {
            return array();
        }

        $commandes = [];
        $my_commandes = ($this->getData('commandes')) ? BimpTools::json_decode_array($this->getData('commandes')) : [];

        $excludeStatut = 3;
        if ((int) BimpTools::getPostFieldValue('afficher_clos', 0) == 1) {
            $excludeStatut = null;
        }

        $filters = array(
            'fk_soc' => (int) $this->getData('fk_soc'),
            'rowid'  => array(
                'not_in' => $my_commandes
            )
        );

        if (!is_null($excludeStatut)) {
            $filters['fk_statut'] = array(
                'operator' => '!=',
                'value'    => $excludeStatut
            );
        }

        foreach (BimpCache::getBimpObjectObjects('bimpcommercial', 'Bimp_Commande', $filters) as $commande) {
            if (!in_array((int) $commande->id)) {
                $add_libelle = "";
                if ($commande->getdata('libelle')) {
                    $add_libelle = " - " . $commande->getData('libelle');
                }
                $commandes[$commande->id] = $commande->getRef() . " (" . $commande->displayData('fk_statut', 'default', false) . ")" . $add_libelle;
            }
        }

        return $commandes;
    }

    public function getTicketClient()
    {
        $tickets = [];
        $my_tickets = ($this->getData('tickets')) ? BimpTools::json_decode_array($this->getData('tickets')) : [];
        $excludeStatut = 999;
        $ticket = $this->getInstance('bimpsupport', 'BS_Ticket');
        $search_tickets = $ticket->getList(['id_client' => $this->getData('fk_soc')]);

        foreach ($search_tickets as $index => $infos) {
            if (!in_array($infos['id'], $my_tickets)) {
                $ticket->fetch($infos['id']);
                $statut = $ticket->getData('status');

                if (BimpTools::getPostFieldValue('afficher_clos') && BimpTools::getPostFieldValue('afficher_clos') == 1) {
                    $excludeStatut = null;
                }
                if ($statut !== $excludeStatut || is_null($excludeStatut)) {
                    $display_statut = " <strong class='" . BS_Ticket::$status_list[$statut]['classes'][0] . "' >";
                    $display_statut .= BimpRender::renderIcon(BS_Ticket::$status_list[$statut]['icon']);
                    $display_statut .= " " . BS_Ticket::$status_list[$statut]['label'] . "</strong>";
                    $tickets[$ticket->id] = $ticket->getRef() . " (" . $display_statut . ") <br /><small style='margin-left:10px'>" . $ticket->getData('sujet') . '</small>';
                }
            }
        }

        return $tickets;
    }

    public function getLinesForInter()
    {
        // Fonction utilisée nulle part mais herureusement car totalement non fonctionnelle. 
        $return = [];

        $parent = $this->getParentInstance(); // ?? Cet objet n'a pas de parent... 

        if (!BimpObject::objectLoaded($parent)) {
            return $return;
        }

        $list = $parent->getChildrenList("inters");

        $product = $this->getInstance('bimpcore', 'Bimp_Product');
        $obj = $this->getInstance('bimpcontract', 'BContract_contratLine');

        foreach ($list as $id) {
            $id_product = 0;

            $det = $parent->getChildObject("lines", $id);
            if (in_array($this->getData('fk_user_author'), BimpTools::json_decode_array($det->getData('techs')))) {
                if ($det->getData('fk_contratdet')) {
                    $obj->fetch($det->getData('fk_contratdet'));
                    $id_product = $obj->getData('fk_product');
                } else {
                    $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $det->getData('fk_commandedet'));
                    if (BimpObject::objectLoaded($line)) {
                        $id_product = $line->id_product;
                    }
                }
                $product = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $id_product);
                $product->fetch($id_product);
                $return[$det->id] = $det->getData('date') . ' - ' . $product->getData('ref');
            }
        }
        return $return;
    }

    public function getCommercialClient()
    {
        if ((int) $this->getData('fk_soc')) {
            $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', (int) $this->getData());

            if (!BimpObject::objectLoaded($client)) {
                return $client->getCommercial();
            }
        }

        return null;
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
        // Aurait dû s'appeller getTotalHtLine (Essayer d'être précis dans le nom des fonctions, ça facilite la compréhension pour tout le monde. 

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

            default:
                return 0;
        }
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

    // Getters Array: 

    public function getTypeActionCommArray()
    {
        $actionComm = [];
        $acceptedCode = ['ATELIER', 'DEP_EXT', 'HOT', 'INTER', 'INTER_SG', 'AC_INT', 'LIV', 'RDV_INT', 'RDV_EXT', 'AC_RDV', 'TELE', 'VIS_CTR'];
        $list = $this->db->getRows('c_actioncomm', 'active = 1');
        foreach ($list as $nb => $stdClass) {
            if (in_array($stdClass->code, $acceptedCode)) {
                $actionComm[$stdClass->id] = $stdClass->libelle;
            }
        }
        return $actionComm;
    }

    public function getCommandesClientArray($posted = true)
    {
        $commandes = [];
        $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande');
        if (($posted))
            $list = $commande->getList(['fk_soc' => BimpTools::getPostFieldValue('client')]);
        else
            $list = [];
        foreach ($list as $nb => $infos) {
            $commande->fetch($infos['rowid']);
            $statut = $commande->getData('fk_statut');

            $display_statut = "<strong class='" . Bimp_Commande::$status_list[$statut]['classes'][0] . "' >";
            $display_statut .= BimpRender::renderIcon(Bimp_Commande::$status_list[$statut]['icon']);
            $display_statut .= " " . Bimp_Commande::$status_list[$statut]['label'] . "</strong>";

            $add_libelle = "";
            if ($commande->getdata('libelle')) {
                $add_libelle = " - " . $commande->getData('libelle');
            }
            $commandes[$commande->id] = $commande->getRef() . " (" . $display_statut . ")" . $add_libelle;
        }
        return $commandes;
    }

    public function getContratsClientArray($posted = true, $choose = true)
    {

        $contrats = Array();

        if ($choose) {
            $contrats[0] = "Aucun contrat";
        }

        $contrat = $this->getInstance('bimpcontract', 'BContract_contrat');

        $id_client = ($posted) ? BimpTools::getPostFieldValue("client") : $this->getData('fk_soc');

        $list = $contrat->getList(["fk_soc" => $id_client, "statut" => 11]);

        foreach ($list as $nb => $i) {
            $contrat->fetch($i['rowid']);
            $statut = $contrat->getData('statut');
            $display_statut = "<strong>";
            $display_statut .= BContract_contrat::$status_list[$statut]['label'] . "</strong>";

            $add_label = "";
            if ($contrat->getData('label')) {
                $add_label = " - " . $contrat->getData('label');
            }
            $contrats[$contrat->id] = $contrat->getRef() . " (" . $display_statut . ")" . $add_label;
        }

        return $contrats;
    }

    public function getTicketsSupportClientArray($posted = true)
    {
        $tickets = [];

        $ticket = $this->getInstance('bimpsupport', 'BS_Ticket');
        if ($posted && BimpTools::getPostFieldValue("client"))
            $list = $ticket->getList(['id_client' => BimpTools::getPostFieldValue("client")]);
        else
            $list = [];
        foreach ($list as $nb => $infos) {
            $ticket->fetch($infos['id']);
            $statut = $ticket->getData('status');

            $display_statut = "<strong class='" . BS_Ticket::$status_list[$statut]['classes'][0] . "' >";
            $display_statut .= BimpRender::renderIcon(BS_Ticket::$status_list[$statut]['icon']);
            $display_statut .= " " . BS_Ticket::$status_list[$statut]['label'] . "</strong>";

            $tickets[$ticket->id] = $ticket->getRef() . " (" . $display_statut . ") <br /><small style='margin-left:10px'>" . $ticket->getData('sujet') . '</small>';
        }

        return $tickets;
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
//        $codes = json_decode(BimpCore::getConf("bimptechnique_ref_deplacement"));
        $commande = New Commande($this->db->db);
        $product = $this->getInstance('bimpcore', 'Bimp_Product');
        $allCommandes = ($this->getData('commandes')) ? BimpTools::json_decode_array($this->getData('commandes')) : [];
        $array = explode(',', BimpCore::getConf('bimptechnique_ref_temps_passe'));
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
                    } elseif ($product->getData('price') != 0) {
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

        $children = $this->getChildrenList("inters");
        $services_executed = [];
        foreach ($children as $id_child) {
            $child = $this->getChildObject('inters', $id_child);
            if ($child->getData('id_line_commande')) {
                $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $child->getData('id_line_commande'));
//                $line = new OrderLine($this->db->db);
//                $line->fetch($child->getData('id_line_commande'));
                $time = $this->timestamp_to_time($child->getData('duree'));
                $qty = $this->time_to_qty($time);
                $services_executed[$child->getData('id_line_commande')]['ht_executed'] += ($line->getTotalHt(1) * $qty);
                $services_executed[$child->getData('id_line_commande')]['pourcentage_commerncial'] += ($child->getData('pourcentage_commercial') * $line->total_ht) / 100;
                if (!array_key_exists("ht_vendu", $services_executed[$child->getData('id_line_commande')]))
                    $services_executed[$child->getData('id_line_commande')]['ht_vendu'] = ($line->getTotalHt(1));
                $services_executed[$child->getData('id_line_commande')]['qty_executed'] += $qty;
                if (!array_key_exists("commande", $services_executed[$child->getData('id_line_commande')]))
                    $services_executed[$child->getData('id_line_commande')]['commande'] = $line->getData('id_obj');
                if (!array_key_exists("date", $services_executed[$child->getData('id_line_commande')]))
                    $services_executed[$child->getData('id_line_commande')]['date'] = $child->getData('date');
                $services_executed[$child->getData('id_line_commande')]["lines"][] = $child->id;
            }
        }
        return $services_executed;
    }

    public function getServicesByTypeArray($type)
    {
        $children = $this->getChildrenList('inters', ["type" => $type]);

        $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', ($type == 3) ? BimpCore::getConf('bimptechnique_id_dep') : BimpCore::getConf('bimptechnique_id_serv19'));
        $services = [];
        $index = 1;
        if (count($children)) {
            foreach ($children as $id_child) {
                $child = $this->getChildObject("inters", $id_child);
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
    
    // Affichages: 

    public function displayVersion()
    {
        $html = "";

        if ($this->getData('new_fi') == 0) {
            $html .= "<strong>Ancienne version des FI.</strong><br />Pour les informations  réèlles de la FI merci, de cliquer sur le boutton ci-dessous<br />";
            $html .= "<a href='" . DOL_URL_ROOT . "/fichinter/card.php?id=" . $this->id . "' class='btn btn-default' >Ancienne version</a>";
        } else {
            $html .= "<strong class='success'>Nouvelle verion</strong>";
        }

        return $html;
    }

    public function displayRatioTotal($display = true, $want = "")
    {
        if ($this->getData('new_fi')) {
            global $db;
            BimpTools::loadDolClass('commande');
            $commande = new Commande($db);
            $commandes = BimpTools::json_decode_array($this->getData('commandes'));
            $service = $this->getInstance('bimpcore', 'Bimp_Product');
            $renta = [];

            $coup_technicien = BimpCore::getConf("bimptechnique_coup_horaire_technicien");

            if (is_array($commandes) && count($commandes) > 0) {
                foreach ($commandes as $id_commande) {
                    $commande->fetch($id_commande);
                    $first_loop = true;
                    foreach ($commande->lines as $line) {
                        $service->fetch($line->fk_product);

                        $children = $this->getChildrenList("inters", ['id_line_commande' => $line->id]);
                        $qty = 0;
                        foreach ($children as $id_child) {
                            $child = $this->getChildObject("inters", $id_child);
                            $duration = $child->getData('duree');
                            $time = $this->timestamp_to_time($duration);
                            $qty += $this->time_to_qty($time);
                        }

                        $renta[$commande->ref][$line->fk_product]['service'] = $service->getRef();
                        $renta[$commande->ref][$line->fk_product]['vendu'] += $line->total_ht;
                        $renta[$commande->ref][$line->fk_product]['cout'] += $qty * $coup_technicien;
                    }
                }
            }


            $children = $this->getChildrenList("inters");
            if (count($children) > 0) {
                foreach ($children as $id_child) {
                    $child = $this->getChildObject('inters', $id_child);
                    if (!$child->getData('id_line_commande') && !$child->getData('id_line_contrat')) {
                        if ($child->getData('type') != 2) { // Exclude ligne libre (Juste ligne de commentaire)
                            $renta['hors_vente'][$child->getData('type')]['service'] = $child->displayData('type', 'default', true, true);
                            $renta['hors_vente'][$child->getData('type')]['vendu'] = 0;
                            $duration = $child->getData('duree');
                            $time = $this->timestamp_to_time($duration);
                            $qty += $this->time_to_qty($time);
                            $renta['hors_vente'][$child->getData('type')]['coup'] += $qty * $coup_technicien;
                        }
                    }
                }
            }
            $total_vendu_commande = 0;
            $total_coup_commande = 0;
            if (count($renta) > 0) {
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
                if (is_array($commandes) && count($commandes) > 0) {
                    $html = "<strong>"
                            . "Commande: <strong class='$class' >" . BimpRender::renderIcon($icone) . " " . price($marge) . "€</strong><br />"
                            . "</strong>";
                }
                //$html .= '<pre>' . print_r($renta, 1) . '</pre>';
                if ($this->getData('fk_contrat')) {
                    $contrat = $this->getInstance('bimpcontract', 'BContract_contrat', $this->getData('fk_contrat'));
                    $html .= $contrat->renderThisStatsFi(true, false);
                }

                if (!(is_array($commandes) && count($commandes) > 0) && !$this->getData('fk_contrat')) {
                    if (count($children) > 0) {
                        $duree = 0;
                        foreach ($children as $id_child) {
                            $child = $this->getChildObject('inters', $id_child);
                            if ($child->getdata('type') != 2) {
                                $duree += $child->getData('duree');
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
                                . "FI non liée: <strong class='$class' >" . BimpRender::renderIcon($icone) . " $signe" . price($marge) . "€</strong>"
                                . "</strong>";
                    }
                }

                return $html;
            } else {
                
            }

            return 0;
        } else {
            return BimpRender::renderAlerts("Calcule de la rentabilitée sur les anciennes FI en attente", "danger", false);
        }
    }

    public function displayIfMessageFormFi()
    {
        $children = $this->getChildrenList("facturation");
        $msgs = [];

        if (count($children) > 0) {
            foreach ($children as $id_child) {
                $child = $this->getChildObject('facturation', $id_child);
            }
        }
//            $msgs[] = Array(
//                'type' => 'warning',
//                'content' => print_r($children)
//            );


        return $msgs;
    }

    public function displayLinkedContratCard()
    {
        $html = "";

        if ($this->haveContratLinked()) {
            $contrat = $this->getInstance('bimpcontract', 'BContract_contrat', $this->getData('fk_contrat'));
            $card = new BC_Card($contrat);
            $html .= $card->renderHtml();
            if ($this->IsBrouillon()) {
                $html .= '<button class="btn btn-default" onclick="' . $this->getJsActionOnclick("unlinked_contrat_client", ['id_contrat' => $contrat->id]) . '" >' . BimpRender::renderIcon('unlink') . ' Dé-lier le contrat ' . $contrat->getData('ref') . '</button>';
            }
        } else {
            $html .= BimpRender::renderAlerts("Il n'y a pas de contrat lié sur cette fiche d'intervention", "info", false);
        }

        return $html;
    }

    public function displayAllTicketsCards()
    {
        $html = "";

        $allTickets = (BimpTools::json_decode_array($this->getData('tickets'))) ? BimpTools::json_decode_array($this->getData('tickets')) : [];
        $ticket = $this->getInstance('bimpsupport', 'BS_Ticket');
        if (count($allTickets) > 0) {
            foreach ($allTickets as $id) {
                $ticket->fetch($id);
                $card = new BC_Card($ticket);
                $html .= $card->renderHtml();

                $html .= '<hr>';

                if ($ticket->getData('sujet')) {
                    $html .= '<u><strong>';
                    $html .= 'Contenu du ticket';
                    $html .= '</strong></u><br />';
                    $html .= "<strong style='margin-left:10px'>" . $ticket->getData('sujet') . "</strong><br />";
                }

                if ($this->IsBrouillon()) {
                    $html .= '<button class="btn btn-default" onclick="' . $this->getJsActionOnclick("unlinked_ticket_client", ['id_ticket' => $id]) . '" >' . BimpRender::renderIcon('unlink') . ' Dé-lier le ticket ' . $ticket->getRef() . ' </button>';
                }
            }
        } else {
            $html .= BimpRender::renderAlerts("Il n'y a pas de tickets liés sur cette fiche d'intervention", "info", false);
        }

        return $html;
    }

    public function displayAllCommandesCards()
    {
        $html = "";

        $allCommandes = BimpTools::json_decode_array($this->getData('commandes'));
        $commande = $this->getInstance('bimpcommercial', 'Bimp_Commande');
        if (is_array($allCommandes) && count($allCommandes) > 0) {
            foreach ($allCommandes as $id) {
                $commande->fetch($id);
                $card = new BC_Card($commande);
                $html .= $card->renderHtml();

                $html .= '<hr>';
                $html .= '<u><strong>';
                $html .= 'Contenu de la commande';
                $html .= '</strong></u><br />';

                $commandeAchanger = new Commande($this->db->db);
                $commandeAchanger->fetch($id);
                foreach ($commandeAchanger->lines as $line) {
                    $service = $this->getInstance('bimpcore', 'Bimp_Product', $line->fk_product);
                    $html .= "- <strong style='color:#EF7D00;'>" . $service->getRef() . "</strong><stronng> - (" . price($line->total_ht) . "€ HT / " . price($line->total_ttc) . "€ TTC)</strong>";
                    if ($line->description) {
                        $html .= "<br /><strong style='margin-left:10px'>" . $line->description . "</strong><br />";
                    } elseif ($service->getData('description')) {
                        $html .= "<br /><strong style='margin-left:10px'>" . $service->getData('description') . "</strong><br />";
                    } else {
                        $html .= '<br />';
                    }
                }

                if ($this->IsBrouillon() && !$this->isOldFi()) {
                    $html .= '<button class="btn btn-default" onclick="' . $this->getJsActionOnclick("unlinked_commande_client", ['id_commande' => $id]) . '" >' . BimpRender::renderIcon('unlink') . ' Dé-lier la commande ' . $commande->getData('ref') . ' </button>';
                }
                $html .= '<hr>';
            }
        } else {
            $html .= BimpRender::renderAlerts("Il n'y à pas de commandes liées sur cette fiche d'intervention", "info", false);
        }


        return $html;
    }

    public function displayTypeSignature()
    {
        if ($this->getData('fk_statut') == 0) {
            return "<strong class='warning'>" . BimpRender::renderIcon("times") . " Fiche d'intervention pas encore signée</strong>";
        } else {
            if ($this->getData('signed'))
                $icon = "vimeo";
            else
                $icon = "file";

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

    public function displayDataTyped($data, $balise = 'i', $color = "#EF7D00")
    {
        return '<' . $balise . ' style="color:' . $color . '" >' . $data . '</' . $balise . '>';
    }

    public function displayCommercial()
    {
        $commercial = $this->getCommercialClient();
        return $commercial->dol_object->getNomUrl(1, 1, 1);
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
                  </div>
                  <div>
                      <button id="save" class="btn btn-success btn-large">' . BimpRender::renderIcon("thumbs-up") . ' Signer la fiche d\'intervention</button>
                      <button id="clear" class="btn btn-danger btn-large" >' . BimpRender::renderIcon("retweet") . ' Refaire la signature</button>
                  </div>';

        return $html;
    }

    public function renderHeaderExtraLeft()
    {

        $html = '';

        if ($this->isLoaded()) {
            $tech = $this->getInstance('bimpcore', 'Bimp_User', (int) $this->getData('fk_user_author'));
            $client = $this->getinstance('bimpcore', 'Bimp_Societe', $this->getData('fk_soc'));
            $html .= '<div class="object_header_infos">';
            $html .= '<h4>Intervenant: ' . $tech->dol_object->getNomUrl(1, 1, 1) . ' </h4>';
            $html .= '<h4>Client: ' . $client->dol_object->getNomUrl(1) . ' </h4>';

            $html .= '</div>';
        }

        return $html;
    }

    public function renderHeaderStatusExtra()
    {

        $extra = '<br />';
        $extra .= 'Signée :' . $this->displayData('signed') . '<br/>';
        $extra .= "<span>Interventions urgentes :" . $this->displayData('urgent') . "</span>";
        $extra .= "<br /><span>" . $this->displayTypeSignature() . "</span>";
        //$extra .= "<br /><a href='".$this->url('facturation')."'>TAB Facturation</a>"; // TEMPORAIRE

        return $extra;
    }

    public function renderSignatureTab()
    {
        $html = "";
        global $user;
        if (!$this->isOldFi()) {
            if ($this->isNotSign()) {
                if ($this->getData('fk_statut') == SELF::STATUT_SIGANTURE_PAPIER) {
                    $html .= $this->displayData('fk_statut');
                } else {
                    $tickets = (BimpTools::json_decode_array($this->getData('tickets'))) ? BimpTools::json_decode_array($this->getData('tickets')) : [];

                    $info = "<b>" . BimpRender::renderIcon('warning') . "</b> Si vous avez des tickets support et que vous ne les voyez pas dans le formulaire, rechargez la page en cliquant sur le bouton suivant: <a href='" . DOL_URL_ROOT . "/bimptechnique/?fc=fi&id=" . $this->id . "&navtab-maintabs=signature'><button class='btn btn-default'>Rafraîchire la page</button></a>";
                    $html .= "<h4>$info</h4>";

                    $interne = explode(",", BimpCore::getConf("bimptechnique_id_societe_auto_terminer"));

                    $html .= "<h3><u>Type de signature</u></h3>";
                    $html .= '<h3><div class="check_list_item" id="checkList" >'
                            . '<input checked="true" type="checkbox" id="BimpTechniqueSign" class="check_list_item_input">'
                            . '<label for="BimpTechniqueSign">'
                            . BimpRender::renderIcon('fas_signature') . ' Signature électronique'
                            . '</label></div></h3>';
                    if (!in_array($this->getData('fk_soc'), $interne) || $user->admin) {
                        $html .= '<h3><div class="check_list_item" id="checkListFar" >'
                                . '<input type="checkbox" id="BimpTechniqueSignFar" class="check_list_item_input">'
                                . '<label for="BimpTechniqueSignFar">'
                                . BimpRender::renderIcon('fas_sign') . ' Signature à distance'
                                . '</label></div></h3>';
                        $html .= '<h3><div class="check_list_item" id="checkListPaper" >'
                                . '<input type="checkbox" id="BimpTechniqueSignChoise" class="check_list_item_input">'
                                . '<label for="BimpTechniqueSignChoise">'
                                . BimpRender::renderIcon('paper-plane') . ' Signature papier'
                                . '</label></div></h3>';
                    }

                    $html .= "<br /><h3><u>Formulaire de signature</u></h3>";
                    $html .= '<div class="row formRow" id="nomSignataireTitle">'
                            . '<div class="inputLabel col-xs-2 col-sm-2 col-md-1"  required>Nom du signataire</div>'
                            . '<div class="formRowInput field col-xs-12 col-sm-6 col-md-9">'
                            . '<div class="inputContainer label_inputContainer " data-field_name="label" data-initial_value="" data-multiple="0" data-field_prefix="" data-required="0" data-data_type="string">'
                            . '<input style="font-size: 20px" type="text" id="BimpTechniqueFormName" name="label" value="" data-data_type="string" data-size="128" data-forbidden_chars="" data-regexp="" data-invalid_msg="" data-uppercase="0" data-lowercase="0">'
                            . '</div></div></div>';
                    $html .= '<div class="row formRow">'
                            . '<div class="inputLabel col-xs-2 col-sm-2 col-md-1" required>Email client</div>'
                            . '<div class="formRowInput field col-xs-12 col-sm-6 col-md-9">'
                            . '<div class="inputContainer label_inputContainer " data-field_name="label" data-initial_value="" data-multiple="0" data-field_prefix="" data-required="0" data-data_type="string">'
                            . '<input style="font-size: 20px" type="text" id="email_client" name="label" data-data_type="string" data-size="128" data-forbidden_chars="" data-regexp="" data-invalid_msg="" data-uppercase="0" data-lowercase="0" value="' . $this->getDataClient('email') . '">'
                            . '</div></div></div>';
                    $html .= '<div class="row formRow">'
                            . '<div class="inputLabel col-xs-2 col-sm-2 col-md-1" required>Préconisation technicien</div>'
                            . '<div class="formRowInput field col-xs-12 col-sm-6 col-md-9">'
                            . '<div class="inputContainer label_inputContainer " data-field_name="label" data-initial_value="" data-multiple="0" data-field_prefix="" data-required="0" data-data_type="string">'
                            . '<textarea id="note_public" name="note_public" rows="4" style="margin-top: 5px; width: 90%;" class="flat"></textarea>'
                            . '</div></div></div>';
                    $html .= '<div class="row formRow">'
                            . '<div class="inputLabel col-xs-2 col-sm-2 col-md-1" required>Attente client</div>'
                            . '<div class="formRowInput field col-xs-12 col-sm-6 col-md-9">'
                            . '&Agrave; remplir obligatoirement si l\'intervention n\'a pas été terminée suite à un évènement dû au client. (Visible sur la FI)'
                            . '<div class="inputContainer label_inputContainer " data-field_name="label" data-initial_value="" data-multiple="0" data-field_prefix="" data-required="0" data-data_type="string">'
                            . '<textarea id="attente_client" name="attente_client" rows="4" style="margin-top: 5px; width: 90%;" class="flat"></textarea>'
                            . '</div></div></div>';
                    $html .= '<div class="row formRow">'
                            . '<div class="inputLabel col-xs-2 col-sm-2 col-md-1" required>Intervention non terminée</div>'
                            . '<div class="formRowInput field col-xs-12 col-sm-6 col-md-9">'
                            . '&Agrave; remplir obligatoirement si l\'intervention n\'est pas terminée (Autre que attente client) (Visible sur la FI).'
                            . '<div class="inputContainer label_inputContainer " data-field_name="label" data-initial_value="" data-multiple="0" data-field_prefix="" data-required="0" data-data_type="string">'
                            . '<textarea id="inter_no_finish" name="inter_no_finish" rows="4" style="margin-top: 5px; width: 90%;" class="flat"></textarea>'
                            . '</div></div></div>';

                    if (count($tickets) > 0) {

                        $html .= '<div class="row formRow" >';
                        $html .= '<div class="inputLabel col-xs-2 col-sm-2 col-md-1" required>Fermeture des tickets support</div>';
                        $html .= '<div class="formRowInput field col-xs-12 col-sm-6 col-md-9">';

                        foreach ($tickets as $id_ticket) {
                            $ticket = $this->getInstance('bimpsupport', 'BS_Ticket', $id_ticket);
                            $html .= '<h3><div class="check_list_item" id="checkList" >'
                                    . '<input type="checkbox" id="BimpTechniqueAttachedTicket_' . $id_ticket . '" class="check_list_item_input">'
                                    . '<label for="BimpTechniqueAttachedTicket_' . $id_ticket . '">'
                                    . $ticket->getRef()
                                    . '</label>'
                                    . '</div></h3>';
                        }


                        $html .= '</div>';
                        $html .= '</div>';
                    }
                    $commercial = $this->getCommercialClient();
                    $html .= '<div class="row formRow" >';
                    $html .= '<div class="inputLabel col-xs-2 col-sm-2 col-md-1" required>Le client souhaite être contacté par son commercial</div>';
                    $html .= '<div class="formRowInput field col-xs-12 col-sm-6 col-md-9">Commercial client: ' . $commercial->getName();
                    $html .= '<h3><div class="check_list_item" id="checkList" >'
                            . '<input type="checkbox" id="BimpTechniqueContactCommercial" class="check_list_item_input">'
                            . '<label for="BimpTechniqueContactCommercial">'
                            . "OUI"
                            . '</label>'
                            . '</div></h3>';
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '<div class="row formRow" >'
                            . '<div class="inputLabel col-xs-2 col-sm-2 col-md-1" required>Signature de la fiche</div>'
                            . '<div  class="formRowInput field col-xs-12 col-sm-6 col-md-9">'
                            . '<div  class="inputContainer label_inputContainer " data-field_name="label" data-initial_value="" data-multiple="0" data-field_prefix="" data-required="0" data-data_type="string">'
                            . $this->renderSignaturePad()
                            . '</div></div></div>';
                    $html .= '<br />';
                }
            } elseif ($this->isSign()) {
                $html .= '<h3>Nom du signataire client: ' . $this->displayDataTyped($this->getData('signataire')) . '</h3>';
                $html .= '<h3>Type de signature: ' . $this->displayDataTyped($this->displayTypeSignature()) . '</h3>';

                global $conf;
                $file = $this->getRef() . "/" . $this->getRef() . '_sign_e.pdf';
                if (!is_file(DOL_DATA_ROOT . '/ficheinter/' . $file))
                    $file = $this->getRef() . "/" . $this->getRef() . '.pdf';
                $html .= '<embed src="' . DOL_URL_ROOT . "/document.php?modulepart=ficheinter&file=" . $file . '" type="application/pdf"   height="1000px" width="100%">';
            }
        } else {
            $html .= "<center><h3>Cette <strong style='color:#EF7D00' >Fiche d'intervention</strong> est une ancienne <strong style='color:#EF7D00' >version</strong></h3></center>";
        }


        return $html;
    }

    public function renderFacturationTab()
    {

        global $user;

        $haveCommande = true;

        $html = "";
        $children = $this->getChildrenList("inters");

        if (!$user->admin && $user->id != 375)
            return BimpRender::renderAlerts("Onglet en cours de développement, il y a donc un accès restreint. Merci de votre compréhension.", 'alert', false);
        if ($haveCommande) {
            if (!count($children)) {
                $msg = BimpRender::renderIcon("warning") . " Il n'y a aucune lignes dans le rapport d'intervention";
                $html .= BimpRender::renderAlerts($msg, "warning", false);
            } else {
                $buttons = [];
                $total_by_service = [];
                $services_executed = $this->getServicesExecutedArray();
                $inter_non_vendu = $this->getServicesByTypeArray(4);
                $dep_non_vendu = $this->getServicesByTypeArray(3);
                $imponderable = $this->getServicesByTypeArray(1);

                $product = BimpCache::getBimpObjectInstance('bimpcore', "Bimp_Product");
                $html .= '<div class="before_list_content" data-refresh="1">';
                $current = 1;
                $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande');

                $html .= "<h3><b><u>Détail de la facturation</u></b></h3>";
                foreach ($services_executed as $id_line_commande => $informations) {
                    $commande->fetch($informations['commande']);
                    $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', $id_line_commande);
//                    $line = new OrderLine($this->db->db);
//                    $line->fetch($id_line_commande);
                    $product->fetch($line->id_product);
                    $date = new DateTime($informations['date']);
                    $total_ht_facturable = (price($informations['ht_executed']) - price($informations['pourcentage_commerncial']) - price($informations['ht_vendu']));
                    $html .= '<div class="bimp_info_card" style="border-color: #348C41">'
                            . '<div class="bimp_info_card_icon" style="color: #348C41">'
                            . $current
                            . '</div>'
                            . '<div class="bimp_info_card_content">'
                            . '<div class="bimp_info_card_title" style="color: #EF7D00"><h4>' . $product->getRef() . ' <a href="' . $product->getUrl() . '" target="_blank"><i class="fas fa5-external-link-alt"></i></a> </h4></div>'
                            . '<div class="bimp_info_card_value">Date: ' . $date->format('d M Y') . '</div>'
                            . '<div class="bimp_info_card_value">Commande: ' . $commande->getNomUrl(true, false, true) . '</div>'
                            . '<div class="bimp_info_card_value">Total Vendu: ' . price($informations['ht_vendu']) . '€ HT</div>'
                            . '<div class="bimp_info_card_value">Total Réalisé: ' . price($informations['ht_executed']) . '€ HT (qté: ' . $informations['qty_executed'] . ')</div>'
                            . '<div class="bimp_info_card_value">Remise commercial sur le reste: ' . price($informations['pourcentage_commerncial']) . '€ HT</div>'
                            . '<div class="bimp_info_card_value" style="color: #EF7D00">Total à facturer: ' . $total_ht_facturable . '€ HT</div>'
                            . '</div>'
                            . '</div>'
                    ;
                    if ($total_ht_facturable != 0) {
                        $total_by_service[$product->getRef() . "@@" . $commande->getRef()] += $total_ht_facturable;
                    }
                    $current++;
                }

                if (count($inter_non_vendu)) {
                    foreach ($inter_non_vendu as $index => $informations) {
                        $date = new DateTime($informations['date']);
                        $remise_euro = ($informations['remise'] * $informations['tarif']) / 100;
                        $total_ht_facturable = ($informations['tarif'] * $informations['qty']) - $remise_euro;
                        $html .= '<div class="bimp_info_card" style="border-color: #3B6EA0">'
                                . '<div class="bimp_info_card_icon" style="color: #3B6EA0">'
                                . $current
                                . '</div>'
                                . '<div class="bimp_info_card_content">'
                                . '<div class="bimp_info_card_title" style="color: #EF7D00"><h4>Intervention non prévue #' . $index . '</h4></div>'
                                . '<div class="bimp_info_card_value">Date: ' . $date->format('d M Y') . '</div>'
                                . '<div class="bimp_info_card_value"><br /></div>'
                                . '<div class="bimp_info_card_value">Tarif horaire: ' . price($informations['tarif']) . '€ HT</div>'
                                . '<div class="bimp_info_card_value">Durée: ' . $informations['duree'] . ' (H:m) Qté: ' . $informations['qty'] . '</div>'
                                . '<div class="bimp_info_card_value">Remise commercial sur l\'intervention: ' . $remise_euro . '€ HT (' . $informations['remise'] . '%)</div>'
                                . '<div class="bimp_info_card_value" style="color: #EF7D00">Total à facturer: ' . price($total_ht_facturable) . '€ HT</div>'
                                . '</div>'
                                . '</div>'
                        ;
                        if ($total_ht_facturable != 0) {
                            $total_by_service[$index . "@@" . "NV"] += $total_ht_facturable;
                        }
                        $current++;
                    }
                }



                if (count($dep_non_vendu)) {
                    foreach ($dep_non_vendu as $index => $informations) {
                        $date = new DateTime($informations['date']);
                        $remise_euro = ($informations['remise'] * $informations['tarif']) / 100;
                        $html .= '<div class="bimp_info_card" style="border-color: #963E96">'
                                . '<div class="bimp_info_card_icon" style="color: #963E96">'
                                . $current
                                . '</div>'
                                . '<div class="bimp_info_card_content">'
                                . '<div class="bimp_info_card_title" style="color: #EF7D00"><h4>Déplacement non prévu #' . $index . '</h4></div>'
                                . '<div class="bimp_info_card_value">Date: ' . $date->format('d M Y') . '</div>'
                                . '<div class="bimp_info_card_value"><br /></div>'
                                . '<div class="bimp_info_card_value">Tarif horaire: ' . price($informations['tarif']) . '€ HT</div>'
                                . '<div class="bimp_info_card_value">Durée: ' . $informations['duree'] . ' (H:m) Qté: ' . $informations['qty'] . '</div>'
                                . '<div class="bimp_info_card_value">Remise commercial sur l\'intervention: ' . $remise_euro . '€ HT (' . $informations['remise'] . '%)</div>'
                                . '<div class="bimp_info_card_value" style="color: #EF7D00">Total à facturer: ' . price(($informations['tarif'] * $informations['qty']) - $remise_euro) . '€ HT</div>'
                                . '</div>'
                                . '</div>'
                        ;
                        $current++;
                    }
                }

                if (count($imponderable)) {
                    foreach ($imponderable as $index => $informations) {
                        $date = new DateTime($informations['date']);
                        $remise_euro = ($informations['remise'] * $informations['tarif']) / 100;
                        $html .= '<div class="bimp_info_card" style="border-color: #A00000">'
                                . '<div class="bimp_info_card_icon" style="color: #A00000">'
                                . $current
                                . '</div>'
                                . '<div class="bimp_info_card_content">'
                                . '<div class="bimp_info_card_title" style="color: #EF7D00"><h4>Impondérable #' . $index . '</h4></div>'
                                . '<div class="bimp_info_card_value">Date: ' . $date->format('d M Y') . '</div>'
                                . '<div class="bimp_info_card_value"><br /></div>'
                                . '<div class="bimp_info_card_value">Tarif horaire: ' . price($informations['tarif']) . '€ HT</div>'
                                . '<div class="bimp_info_card_value">Durée: ' . $informations['duree'] . ' (H:m) Qté: ' . $informations['qty'] . '</div>'
                                . '<div class="bimp_info_card_value">Remise commercial sur l\'intervention: ' . $remise_euro . '€ HT (' . $informations['remise'] . '%)</div>'
                                . '<div class="bimp_info_card_value" style="color: #EF7D00">Total à facturer: ' . price(($informations['tarif'] * $informations['qty']) - $remise_euro) . '€ HT</div>'
                                . '</div>'
                                . '</div>'
                        ;
                        $current++;
                    }
                }

                $html .= '</div>'
                        . '<br />';
                $buttons[] = array(
                    'label'   => 'Facturer cette fiche d\'intervention',
                    'icon'    => 'euro',
                    'onclick' => $this->getJsActionOnclick('factured', array(), array(
                        'form_name' => 'factured'
                    ))
                );
                $html .= "<h3><b><u>Facturation</u></b></h3>";
                $html .= '<table class="noborder objectlistTable" style="border: none; min-width: 480px">';
                $html .= '<thead>';
                $html .= '<tr class="headerRow">';
                $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">#</th>';
                $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Service</th>';
                $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Quantitée</th>';
                $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Prix vendu HT</th>';
                $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Total HT facturable</th>';
                $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Remise</th>';
                $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">Facture</th>';
                $html .= '</tr>';
                $html .= '</thead>';

                $html .= "<tbody class='listRows' >";
                $html .= '<tr><td colspan="16" style="text-align: center" class="fullrow"><p class="alert alert-warning">Il n\' y à pas de lignes à facturer</p></td></tr>';
                $html .= "</tbody>";

                $html .= "</table>";
                $html .= BimpRender::renderButtonsGroup($buttons, []) . "<br />";
                $this->tmp_facturable = $total_by_service;
                $this->getTotalByServicesArray();
                $html .= '<pre>' . print_r($this->tmp_facturable, 1) . '</pre>';
            }
        } else {
            $html = BimpRender::renderAlerts("Cette fiche d'intevention ne concerne pas de commande donc pas de facturation.", 'warning', false);
        }
        return $html;
    }

    // Traitements: 

    public function deleteDolObject(&$errors)
    {
        global $user;
        $actionCommList = $this->db->getRows("actioncomm", 'fk_soc = ' . $this->getData('fk_soc') . " AND elementtype = 'fichinter' AND fk_element = " . $this->id);
        $emailControl = "v.gilbert@bimp.fr";
        $tech = BimpCache::getBimpObjectInstance("bimpcore", "Bimp_User", $this->getData('fk_user_author'));
        BimpTools::loadDolClass("comm/action", "actioncomm");

        $actionCommClass = new ActionComm($this->db->db);

        foreach ($actionCommList as $index => $object) {
            $actionCommClass->fetch($object->id);
            $actionCommClass->delete();
        }

        $message = "Bonjour,<br />La fiche d'intervention " . $this->getRef() . " à été supprimé par " . $user->getNomUrl() . " ainsi que le ou les évènements dans l'agenda.";
        $to = ($tech->getData('email') != $emailControl) ? $tech->getData('email') . ',' . $emailControl : $tech->getData('email');

        mailSyn2("FI " . $this->getRef() . " supprimée", $to, null, $message);

        return parent::deleteDolObject($errors);
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

    public function actionComm(string $action, dateTime $dt_start, dateTime $dt_stop, int $socid, int $type, Bimp_User $tech, BT_ficheInter $instance, Array $params = Array())
    {
        global $user;

        $actionComm = New ActionComm($this->db->db);

        switch ($action) {
            case 'create':
                $actionCommAction = "create($user)";
                $actionComm->label = "FI: " . $instance->getRef();
                $actionComm->note = $instance->getData('description');
                $actionComm->ponctual = 1;
                $actionComm->userownerid = $tech->id;
                $actionComm->elementtype = 'fichinter';
                $actionComm->type_id = $type;
                $actionComm->datep = strtotime($dt_start->format('Y-m-d H:i:s'));
                $actionComm->datef = strtotime($dt_stop->format('Y-m-d H:i:s'));
                $actionComm->socid = $socid;
                break;
            default:
                return 0;
                break;
        }
    }

    public function createFromContrat($contrat, $data)
    {

        $commandes = [];
        $tickets = [];
        if (is_array($data['linked_tickets'])) {
            foreach ($data['linked_tickets'] as $id) {
                array_push($tickets, $id);
            }
        }
        if (is_array($data['linked_commandes'])) {
            foreach ($data['linked_commandes'] as $id) {
                array_push($commandes, $id);
            }
        }

        $new_ref = $this->getNextNumRef($contrat->getData('fk_soc'));
        $new_socid = $contrat->getData('fk_soc');
        $new_desc = $data['description'];
        $new_fk_contrat = $contrat->id;
        $new_statut = self::STATUT_BROUILLON;
        $tech = new User($this->db->db);
        $first_loop = true;
        $emailRecipe = "";
        foreach ($data['techs'] as $id) {
            $new = new Fichinter($this->db->db);
            $new->ref = $new_ref;
            $new->socid = $new_socid;
            $new->description = $new_desc;
            $new->fk_contrat = $new_fk_contrat;
            $new->statut = $new_statut;
            $new->fk_user_author = $id;

            $tech->fetch($id);
            $created = false;
            $instance = $this->getInstance('bimptechnique', 'BT_ficheInter');

            $list_fi_tech_close = $instance->getList(["fk_user_author" => $id, 'fk_contrat' => $contrat->id, 'fk_statut' => 0]);
            $create = count($list_fi_tech_close) > 0 ? false : true;
            if ($create) {
                if ($tech->id > 0) {
                    $id_fi = $new->create($tech);
                    if ($id_fi > 0) {
                        $created = true;
                        $instance->fetch($id_fi);
                        $instance->updateField("commandes", json_encode($commandes));
                        $instance->updateField("tickets", json_encode($tickets));
                        $instance->updateField('new_fi', 1);
                        $instance->updateField("urgent", $data['urgent']);
                        $message = "<h3><b>Bimp</b><b style='color:#EF7D00' >Technique</b></h3>";
                        $message .= "<p>Référence: " . $instance->getNomUrl() . "</p>";

                        if (count($commandes) > 0) {
                            foreach ($commandes as $id_commande) {
                                setElementElement("commande", "fichinter", $id_commande, $id_fi);
                            }
                        }

                        setElementElement('contrat', "fichinter", $contrat->id, $id_fi);

                        if ($first_loop) {
                            $first_loop = false;
                            $emailRecipe .= $tech->email;
                        } else {
                            $emailRecipe .= ',' . $tech->email;
                        }
                    }
                }
            }

            $canPlanning = true;
            if ($create) {
                if (!$created) {
                    $canPlanning = false;
                }
            }

            if (count($list_fi_tech_close) > 0) {
                $instance->find(['fk_contrat' => $contrat->id, 'fk_user_author' => $id]);
            }

            if ($canPlanning) {
                $actioncomm = new ActionComm($this->db->db);
                //$actioncomm->userassigned = Array($id);
                $actioncomm->label = $instance->getRef();
                $actioncomm->note = $data['description'];
                $actioncomm->punctual = 1;
                $actioncomm->userownerid = BimpCore::getConf('bimptechnique_default_user_actionComm');
                $actioncomm->elementtype = 'fichinter';
                $actioncomm->type_id = $data['type_planning'];
                $actioncomm->datep = $data['le'] . " " . $data['de'];
                $actioncomm->datef = $data['le'] . " " . $data['a'];
                $actioncomm->socid = $contrat->getData('fk_soc');
                $actioncomm->fk_element = $instance->id;

                $sujet = "Une intervention vous à été attribuée";
                $message = "<h3><b>Bimp</b><b style='color:#EF7D00' >Technique</b></h3>";
                $message .= "<p>Référence de la FI: " . $instance->getRef() . "</p>";
                $message .= "<a href='" . DOL_URL_ROOT . "/bimptechnique/?fc=fi&id=" . $instance->id . "&navtab-maintabs=actioncomm' class='btn btn-primary'>Prendre en charge l'intervention</a>";

                $actioncomm->create($this->global_user);

                mailSyn2($sujet, $tech->email, $this->mailSender, $message);
            }
        }

        return $id_fi;
    }

    public function farSign($mail_signataire)
    {
        global $user;
        $public_url = md5($this->getData('ref'));

        $continue = true;
        $loop = 1;
        $haveFind = true;
        while ($continue) {
            $new_password = $this->generateAleatoirePassword(5);
            $compare = $this->db->getCount('fichinter', 'public_signature_code = "' . $new_password . '"', 'rowid');

            if ($compare == 0) {
                $continue = false;
            }

            if ($loop == 20 && $continue) {
                $continue = false;
                $haveFind = false;
            }
            $loop++;
        }

        if ($haveFind) {
            $this->dol_object->setValid($user);
            $today = new DateTime();
            $this->set('email_signature', $mail_signataire);
            $this->set('public_signature_url', $public_url);
            $this->set('type_signature', 1);
            $this->set('public_signature_code', $new_password);
            $this->set('public_signature_date_delivrance', $today->format('Y-m-d H:i:s'));
            $this->set('fk_statut', self::STATUT_SIGANTURE_PAPIER);
            $today->add(new DateInterval("P4D"));
            $this->set('public_signature_date_cloture', $today->format('Y-m-d H:i:s'));
            $this->update();

            $commercial = $this->getCommercialClient();
            $bimpMail = new BimpMail($this, "Rapport d'intervention - " . $this->getRef(),
                                     $mail_signataire, '', "Bonjour, merci de signer votre rapport d'intervention à l'adresse suivante: "
                    . "<a href='" . DOL_URL_ROOT . "/bimptechnique/public'>" . DOL_URL_ROOT . "/bimptechnique/public</a> en entrant votre nom ainsi que le mot de passe suivant: <b>$new_password</b><br />Cet accès n'est valable que 4 Jours calandaire."
                    . "<br /><br />Cordialement", $commercial->getData('email'));
            global $conf;
            $files = array();
            $files[] = array($conf->ficheinter->dir_output . '/' . $this->dol_object->ref . '/' . $this->dol_object->ref . '.pdf', 'application/pdf', $this->dol_object->ref . '.pdf');
            $bimpMail->addFiles($files);
            $mail_errors = array();
            $bimpMail->send($mail_errors);

            mailSyn2("Signature à distance - FI", $commercial->getData('email'), null, "Pour information, la FI " . $this->getNomUrl() . " à été envoyée au client pour une signature à distance");
        } else {
            $errors[] = "Merci de rééssayer pour trouver un mot de passe unique.";
        }

        return Array(
            'errors'   => $errors,
            'warnings' => $warnings,
            'success'  => $success
        );
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

    public function chooseModeFacturation()
    {
        
    }

    public function switch_mode_facturation_deponds_type_and_service()
    {
        if (BimpTools::getPostFieldValue('inter_0_type')) {
            $type = BimpTools::getPostFieldValue('inter_0_type');
            switch ($type) {
                case 1:
                    return 2;
                    break;
                case 2:
                    return 0;
                    break;
                case 3:
                    return 1;
            }
        }
        if (BimpTools::getPostFieldValue('inter_0_service')) {
            $service = BimpTools::getPostFieldValue('inter_0_service');
            $explode = explode('_', $service);
            switch ($explode[0]) {
                case 'contrat':
                    return 1;
                    break;
                case 'commande':
                    return 2;
                    break;
            }
        }

        return 2;
    }

    // Actions: 

    public function actionCreateFromRien($data, &$success = '')
    {
        $errors = Array();
        $warnings = Array();

        $data = (object) $data;

        $new_ref = $this->getNextNumRef($data->client);
        $linked_commandes = "";
        $linked_tickets = "";

        if ($data->linked_commandes != 0) {
            $linked_commandes = json_encode($data->linked_commandes);
        }
        if ($data->linked_tickets != 0) {
            $linked_tickets = json_encode($data->linked_tickets);
        }

        $new = new Fichinter($this->db->db);
        $new->ref = $new_ref;
        $new->socid = $data->client;
        if ($data->linked_contrat != 0) {
            $new->fk_contrat = $data->linked_contrat;
        }
        $new->statut = self::STATUT_BROUILLON;
        $new->fk_user_author = $data->techs;

        $technicien = $this->getInstance('bimpcore', "Bimp_User", $data->techs);

        $id_fi = $new->create($technicien->dol_object);

        //echo '<pre>' . $id_fi;
        if ($id_fi > 0) {
            $instance = $this->getInstance('bimptechnique', 'BT_ficheInter', $id_fi);
            $instance->updateField("commandes", $linked_commandes);
            $instance->updateField('new_fi', 1);
            $instance->updateField('datei', $data->le);

            if ($linked_commandes != "") {
                foreach (BimpTools::json_decode_array($linked_commandes) as $current_commande_id) {
                    setElementElement("commande", "fichinter", $current_commande_id, $instance->id);
                }
            }

            if ($linked_tickets != "") {
                foreach (BimpTools::json_decode_array($linked_tickets) as $current_ticket_id) {
                    setElementElement('bimp_ticket', 'fichinter', $current_ticket_id, $instance->id);
                }
            }

            if ($instance->getData('fk_contrat')) {
                setElementElement('contrat', 'fichinter', $instance->getData('fk_contrat'), $instance->id);
            }

            $instance->updateField("tickets", $linked_tickets);
            $instance->updateField("urgent", $data->urgent);
            $instance->updateField('description', $data->description);

            $actioncomm = new ActionComm($this->db->db);
            //$actioncomm->userassigned = Array($data->techs);
            $actioncomm->label = $instance->getRef();
            $actioncomm->note = '';
            $actioncomm->punctual = 1;
            $actioncomm->userownerid = $data->techs;
            $actioncomm->elementtype = 'fichinter';
            $actioncomm->type_id = $data->type_planning;
            $actioncomm->datep = strtotime($data->le . " " . $data->de);
            $actioncomm->datef = strtotime($data->le . " " . $data->a);
            $actioncomm->socid = $data->client;
            $actioncomm->fk_element = $instance->id;
            $actioncomm->create($this->global_user);

            $techForMail = $this->getInstance('bimpcore', 'Bimp_User', $data->techs);
            $client = $this->getInstance("bimpcore", "Bimp_Societe", $instance->getData('fk_soc'));
            $sujet = "[FI] " . $client->getData('code_client') . ' - ' . $client->getName();
            $message = "<h4><strong style='color:#EF7D00'>Bimp</strong><strong style='color:black' >Technique</strong> - <strong style='color:grey' >Fiches d'interventions</strong></h4>";
            $message .= "Bonjour,<br />Une fiche d'intervention vous a été attribuée";
            $message .= "<br /><br />";
            $message .= 'Numéro de la Fiche d\'intervention: ' . $instance->getNomUrl() . '<br />';
            $de = new DateTime($data->le . " " . $data->de);
            $a = new DateTime($data->le . ' ' . $data->a);
            $message .= 'Date prévue de l\'intervention: <strong>Le ' . $de->format('d/m/Y H:i') . ' au ' . $a->format('d/m/Y H:i') . '</strong>';

            //$errors[] = $sujet . "<br />" . $message;
            $instance->addLog("Fiche d'intervention créée");
            mailSyn2($sujet, $techForMail->getData('email') . "", "gle@bimp.fr", $message);
        }

        //echo '<pre>' . print_r($new, 1);        

        return Array(
            'errors'   => $errors,
            'warnings' => $warnings,
            'success'  => $success
        );
    }

    public function actionUnLinked_ticket_client($data, &$success = '')
    {

        $errors = [];
        $warnings = [];
        $new_tickets = [];
        $my_tickets = BimpTools::json_decode_array($this->getData('tickets'));

        if (!in_array($my_tickets, $data['id_ticket'])) {
            foreach ($my_tickets as $id_current_ticket) {
                if ($id_current_ticket != $data['id_ticket']) {
                    $new_tickets[] = $id_current_ticket;
                }
            }
            $errors = $this->updateField('tickets', json_encode($new_tickets));
        } else {
            $errors[] = "Vous ne pouvez pas dé-lier un ticket qui n'apparait pas sur cette fiche d'intervention";
        }

        if (!count($errors)) {
            delElementElement("bimp_ticket", "fichinter", $data['id_ticket'], $this->id);
            $success = "Ticket support dé-lié avec succès";
        }

        return [
            'success'  => $success,
            'errors'   => $errors,
            'warnings' => $warnings
        ];
    }

    public function actionUnLinked_contrat_client($data, &$success = '')
    {
        $errors = [];
        $warnings = [];
        $new_commandes = [];

        $inter_on_the_contrat = false;

        if ($data['id_contrat'] == $this->getData('fk_contrat')) {
            $contrat = $this->getInstance('bimpcontract', 'BContract_contrat', $this->getData('fk_contrat'));
            $children = $this->getChildrenList('inters');
            $children_contrat = $contrat->getChildrenList('lines');

            if (count($children) > 0) {
                foreach ($children as $is_child) {
                    $child = $this->getChildObject('inters', $id_child);
                    foreach ($children_contrat as $id_child_contrat) {
                        $child_contrat = $contrat->getChildObject('inters', $id_child_contrat);
                        if ($child->getData('id_line_contrat') == $child_contrat->id || $child->getData('type') == 5) {
                            $inter_on_the_contrat = true;
                        }
                    }
                }
            }
            if (!$inter_on_the_contrat) {
                $errors = $this->updateField('fk_contrat', null);
            } else {
                $errors[] = "Vous ne pouvez aps dé-lier ce contrat car une intervention est faite avec un code service de contrat sur cette fiche d'intervention";
            }

            if (!count($errors)) {
                delElementElement("contrat", "fichinter", $data['id_contrat'], $this->id);
                $success = "Contrat dé-lié avec succès";
            }
        } else {
            $errors[] = "Vous ne pouvez pas dé-lié un contrat qui n'est pas lié à cette fiche";
        }

        return [
            'success'  => $success,
            'errors'   => $errors,
            'warnings' => $warnings
        ];
    }

    public function actionUnlinked_commande_client($data, &$success = '')
    {
        $errors = [];
        $warnings = [];
        $new_commandes = [];
        $my_commandes = BimpTools::json_decode_array($this->getData('commandes'));

        $inter_on_the_commande = false;

        if (in_array($data['id_commande'], $my_commandes)) {


            // Vérification si pas d'inter avec cette commande
            $commande = new Commande($this->db->db);
            $commande->fetch($data['id_commande']);
            $lines = $this->getChildrenList("inters");
            foreach ($commande->lines as $line) {
                if (count($lines) > 0) {
                    foreach ($lines as $id_line_fi) {
                        $child = $this->getChildObject('inters', $id_line_fi);
                        if ($line->id == $child->getData('id_line_commande')) {
                            $inter_on_the_commande = true;
                        }
                    }
                }
            }

            if (count($my_commandes) > 0 && !count($errors)) {
                foreach ($my_commandes as $id) {
                    if ($id != $data['id_commande']) {
                        $new_commandes[] = $id;
                    }
                }
            }

            if ($inter_on_the_commande) {
                $errors[] = "Cette commande ne peut être dé-liée car il existe une intervention de cette fiche sur cette commande";
            }

            if (!count($errors)) {
                $errors = $this->updateField('commandes', json_encode($new_commandes));
            }

            if (!count($errors)) {
                delElementElement("commande", "fichinter", $data['id_commande'], $this->id);
                $success = "Commande dé-liée avec succès";
            }
        } else {
            $errors[] = "Vous ne pouvez pas dé-lier une commande qui ne figure pas sur la FI";
        }

        return [
            'success'  => $success,
            'errors'   => $errors,
            'warnings' => $warnings
        ];
    }

    public function actionLinked_contrat_client($data, &$success = '')
    {
        $errors = [];
        $warnings = [];

        if ($data['linked']) {
            if ($this->getData('fk_contrat')) {
                $errors[] = "Il y à déjà un contrat lié a cette fiche";
            } else {
                $this->updateField('fk_contrat', $data['linked']);
            }
            if (!count($errors)) {
                setElementElement("contrat", "fichinter", $data['linked'], $this->id);
                $success = "Contrat lié avec succès";
            }
        } else {
            $warnings[] = "Il n'y à pas de contrat à lié";
        }

        return [
            'success'  => $success,
            'errors'   => $errors,
            'warnings' => $warnings
        ];
    }

    public function actionLinked_commande_client($data, &$success = '')
    {

        $errors = [];
        $warnings = [];

        if ($data['linked']) {
            $my_commandes = BimpTools::json_decode_array($this->getData('commandes'));

            foreach ($data['linked'] as $id) {
                $my_commandes[] = $id;
            }

            $errors = $this->updateField('commandes', json_encode($my_commandes));

            if (!count($errors)) {
                setElementElement("commande", "fichinter", $id, $this->id);
                $success = 'Commande liée avec succès';
            }
        } else {
            $warnings[] = "Il n'y à pas de commande à liée";
        }

        return [
            'success'  => $success,
            'errors'   => $errors,
            'warnings' => $warnings
        ];
    }

    public function actionLinked_ticket_client($data, &$success = '')
    {
        $errors = [];
        $warnings = [];

        if ($data['linked']) {
            $my_tickets = BimpTools::json_decode_array($this->getData('tickets'));
            foreach ($data['linked'] as $id) {
                $my_tickets[] = $id;
            }

            $errors = $this->updateField('tickets', json_encode($my_tickets));
            if (!count($errors)) {
                setElementElement("bimp_ticket", "fichinter", $id, $this->id);
                $success = "Ticket lié avec succès";
            }
        } else {
            $warnings[] = "Il n'y à pas de tickets support lié";
        }

        return [
            'success'  => $success,
            'errors'   => $errors,
            'warnings' => $warnings
        ];
    }

    public function actionAttenteSign_to_signed($data, &$success = '')
    {
        $errors = [];
        $warnings = [];

        $this->addLog("Le client à signé la FI et le fichier est déposé");
        $this->updateField('fk_statut', 1);

        return [
            'success'  => $success,
            'errors'   => $errors,
            'warnings' => $warnings
        ];
    }

    public function actionChangeTech($data, &$success = '')
    {
        $errors = Array();
        $warnings = Array();

        $data = (object) $data;

        if ($data->new_tech == $this->getData('fk_user_author')) {
            $errors[] = "Vous ne pouvez pas changer la technicien par lui même ;)";
        }

        if (!count($errors)) {
            $success = "";
            $actionComm = new ActionComm($this->db->db);
            $id_actionComm = $this->db->getValue("actioncomm", "id", "code <> 'AC_FICHINTER_VALIDATE' AND fk_element = " . $this->id . " AND fk_soc = " . $this->getData('fk_soc') . " AND elementtype = 'fichinter'");
            if (!$id_actionComm) {
                $errors[] = "L'évènement du calendrier du technicien initial de la Fiche d'intervention est introuvable";
            }

            if (!count($errors)) {
                global $user;
                $actionComm->fetch($id_actionComm);
                $actionComm->userownerid = $data->new_tech;
                $actionComm->otherassigned = Array();
                if ($actionComm->update($user) > 0) {
                    $success = "Technicien et planning changés avec succès";
                    $this->updateField('fk_user_author', $data->new_tech);
                }
            }
        }

        return Array(
            'errors'   => $errors,
            'warnings' => $warnings,
            'success'  => $success
        );
    }

    public function actionAskFacturation($data, &$success = '')
    {
        $errors = $warnings = array();
        return array('errors' => $errors, 'warnings' => $warnings);
    }

    public function actionAddInter($data, &$success = '')
    {
        global $user, $db;
        $errors = [];
        $warnings = [];
        $description = "";
        $data = (object) $data;
        $objects = array();
        $new = $this->dol_object;
        $notField = array('inters_sub_object_idx_type', 'inters_sub_object_idx_date', 'inters_sub_object_idx_duree', 'inters_sub_object_idx_description');
        //return '<pre>' . print_r($data);
        $allCommandesLinked = getElementElement('commande', "fichinter", null, $this->id);

        if (!count($errors)) {
            foreach ($data as $field => $val) {
                if (!in_array($field, $notField)) {
                    $numInter = explode('_', $field);
                    $objects[$numInter[1]][$field] = $val;
                }
            }

            $startStopUnique = [];
            $startStopX4 = [];

            foreach ($objects as $numeroInter => $value) {
                //die($value['inter_' . $numeroInter . '_service'] . "hdiuodis");
                if (!$this->getData('fk_contrat') && !$this->getData('commandes') && !$this->getData('tickets') && $value['inter_' . $numeroInter . '_type'] == 0) {
                    $errors[] = "Vous ne pouvez pas faire une intervention vendu alors qu'il n'y à rien de lié à votre FI";
                }
                if ($value['inter_' . $numeroInter . '_type'] == 0 && !$value['inter_' . $numeroInter . '_service']) {
                    $errors[] = "Vous ne pouvez pas faire une intervention vendu sans code service, si ceci est une erreur merci d'envoyer un email à: support-fi@bimp.fr";
                }
                if ($value['inter_' . $numeroInter . '_type'] == 5 && (!$this->getData('fk_contrat') || $this->getData('fk_contrat') == 0)) {
                    $errors[] = "Vous ne pouvez pas utiliser un déplacement sous contrat sans contrat lié. Merci";
                }
                $commandes = BimpTools::json_decode_array($this->getData('commandes'));
                if (!is_array($commandes))
                    $commandes = array();
                if ($value['inter_' . $numeroInter . '_type'] == 6 && !count($commandes)) {
                    $errors[] = "Ce type de service est réservé aux commandes";
                }

                if (!count($errors)) {
                    $date = new DateTime($value['inter_' . $numeroInter . '_date']);
                    if ($value['inter_' . $numeroInter . '_temps_dep'] > 0) {
                        $duration = $value['inter_' . $numeroInter . '_temps_dep'];
                    } elseif ($value['inter_' . $numeroInter . '_am_pm'] == 0) {
                        // Arrivée/Départ unique
                        $arrived = strtotime($value['inter_' . $numeroInter . '_global_arrived']);
                        $departure = strtotime($value['inter_' . $numeroInter . '_global_quit']);
                        $duration = $departure - $arrived;
                        $startStopUnique['arrived'] = $value['inter_' . $numeroInter . '_date'] . " " . ($value['inter_' . $numeroInter . '_global_arrived']);
                        $startStopUnique['departure'] = $value['inter_' . $numeroInter . '_date'] . " " . ($value['inter_' . $numeroInter . '_global_quit']);
                    } else {
                        // Arrivée/ Départ x4
                        $arrived_am = strtotime($value['inter_' . $numeroInter . '_am_arrived']);
                        $departure_am = strtotime($value['inter_' . $numeroInter . '_am_quit']);
                        $duration_am = $departure_am - $arrived_am;
                        $arrived_pm = strtotime($value['inter_' . $numeroInter . '_pm_arrived']);
                        $departure_pm = strtotime($value['inter_' . $numeroInter . '_pm_quit']);
                        $duration_pm = $departure_pm - $arrived_pm;
                        $duration = $duration_am + $duration_pm;
                        $startStopX4['arriverd_am'] = $value['inter_' . $numeroInter . '_date'] . " " . $value['inter_' . $numeroInter . '_am_arrived'];
                        $startStopX4['departure_am'] = $value['inter_' . $numeroInter . '_date'] . " " . $value['inter_' . $numeroInter . '_am_quit'];
                        $startStopX4['arriverd_pm'] = $value['inter_' . $numeroInter . '_date'] . " " . $value['inter_' . $numeroInter . '_pm_arrived'];
                        $startStopX4['departure_pm'] = $value['inter_' . $numeroInter . '_date'] . " " . $value['inter_' . $numeroInter . '_pm_quit'];
                    }

                    $timeArray = [];
                    if (count($startStopUnique) > 0) {
                        $timeArray = $startStopUnique;
                    } elseif (count($startStopX4) > 0) {
                        $timeArray = $startStopX4;
                    }
                    if ($duration >= 60 || ($value['inter_' . $numeroInter . '_type'] == 2)) {
                        $desc = $value['inter_' . $numeroInter . '_description'];

                        $new->addline($user, $this->id, $desc, $date->getTimestamp(), $duration);
                        $lastIdLine = $this->db->getMax('fichinterdet', 'rowid', 'fk_fichinter = ' . $this->id);
                        $line = $this->getInstance('bimptechnique', 'BT_ficheInter_det', $lastIdLine);

                        $exploded_service = explode("_", $value['inter_' . $numeroInter . '_service']);
                        $field = 'id_line_' . $exploded_service[0];

                        $line->updateField('type', $value['inter_' . $numeroInter . '_type']);

                        if ($value['inter_' . $numeroInter . '_type'] == 0) {
                            $line->updateField($field, $exploded_service[1]);
                        }

                        if ($value['inter_' . $numeroInter . '_type'] == 6) {
                            if (!$value['inter_' . $numeroInter . '_dep_on_commande']) {
                                $line->updateField('type', 3);
                            } else {
//                                $ids_product = [];
//                                $allCodesDeplacements = json_decode(BimpCore::getConf("bimptechnique_ref_deplacement"));
//                                foreach($allCodesDeplacements as $code) {
//                                    $ids_product[] = $this->db->getValue('product', 'rowid', 'ref = "'.$code.'"');
//                                }
                                $commande = new Commande($this->db->db);
                                $commande->fetch($value['inter_' . $numeroInter . '_dep_on_commande']);
                                $find = false;
                                foreach ($commande->lines as $lineC) {
                                    $produit = BimpCache::getBimpObjectInstance("bimpcore", "Bimp_Product", $lineC->fk_product);
                                    if (!$find && $produit->isDep()) {
                                        $find = true;
                                        $line->updateField('id_line_commande', $lineC->id);
                                    }
                                }
                            }
                        }

                        if (count($timeArray)) {
                            foreach ($timeArray as $field => $time) {
                                $line->updateField($field, $time);
                            }
                        }

                        $mode = 0;
                        $facture = 0;

                        switch ($value['inter_' . $numeroInter . '_type']) {
                            case 2:
                                $mode = 0;
                                $facture = 0;
                                break;
                            case 0:
                            case 1:
                            case 6:
                            case 5:
                                $facture = 1;
                                if ($exploded_service[0] == "contrat" || $value['inter_' . $numeroInter . '_type'] == 5) {
                                    $mode = 1;
                                } else {
                                    $mode = 2;
                                }
                                break;
                            case 3:
                            case 4:
                                $mode = 2;
                                break;
                        }
                        $callback = "window.location.href = '" . DOL_URL_ROOT . "/bimptechnique/?fc=fi&id=" . $this->id . "'";
                        $line->updateField('forfait', $mode);
                        $line->updateField('facturable', $facture);
                    } else {
                        $errors[] = "Le temps renseigné ne semble pas correcte";
                    }
                }
            }
        }

        return [
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $callback
        ];
    }

    public function actionSetStatusAdmin($data, &$success = '')
    {
        $errors = $warnings = array();

        $status = $data['status'];
        if ($status == self::STATUT_BROUILLON)
            $this->updateField('old_status', $this->getData('fk_statut'));
        if (!$this->canSetActionAdmin())
            $errors[] = 'Vous n\'avez pas la permission';
        if (!count($errors))
            $errors = BimpTools::merge_array($errors, $this->setNewStatus($status));

        return array('errors' => $errors, 'warnings' => $warnings);
    }

    public function actionCreateFacture($data, &$success = '')
    {
        $errors = [];
        $warnings = [];

        $facture = BimpCache::getBimpObjectInstance("bimpcommercial", "Bimp_Facture");

        $errors = [];

        return Array(
            "errors"   => $errors,
            "warnings" => $warnings,
            "success"  => $success
        );
    }

    public function actionSendfacturation($data, &$success = '')
    {
        $errors = [];
        $warnings = [];
        $client = $this->getInstance('bimpcore', 'Bimp_Societe', $this->getData('fk_soc'));
        $success = "Service facturation prévenu";
        mailSyn2("[" . $this->getref() . "]", 'facturationclients@bimp.fr', null, "Bonjour, Pour information la FI N°" . $this->getRef() . ' pour le client ' . $client->getdata('code_client') . ' - ' . $client->getName() . ' à été signée par le client');
        $this->addLog("Facturation client prévenue");
        $this->updateField('fk_statut', 2);

        return [
            'errors'   => $errors,
            'warnings' => $warnings,
            'success'  => $success
        ];
    }

    public function actionGeneratePdf($data, &$success = '', $errors = Array(), $warnings = Array())
    {

        //echo '<pre>' . print_r($this->module, 1);
        //$this->dol_object->generateDocument('fi', $this->global_langs);
        //global $conf;
        //echo '<pre>' . print_r($conf->ficheinter);
        return parent::actionGeneratePdf(['model' => 'fi']);
    }

    public function actionRe_open($data, &$success = '')
    {
        $errors = Array();
        $warnings = Array();

        $success = "FI ré-ouverte avec succès";

        return Array(
            'errors'   => $errors,
            'warnings' => $warnings,
            'success'  => $success
        );
    }

    // Outils : 

    public function time_to_qty($time)
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

    public function timestamp_to_time($timestamp)
    {
        $heures = floor($timestamp / 3600);
        if (($timestamp % 3600) >= 60) {
            $minutes = floor(($timestamp % 3600) / 60);
        }
        return str_pad($heures, 2, "0", STR_PAD_LEFT) . ":" . str_pad($minutes, 2, "0", STR_PAD_LEFT);
    }
}

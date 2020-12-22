<?php
require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

class BimpFi_fiche extends BimpDolObject {
    
    CONST STATUT_ABORT = -1;
    CONST STATUT_BROUILLON = 0;
    CONST STATUT_VALIDER = 1;
    CONST STATUT_TERMINER = 2;
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
    
    public static $statut_list = [
        self::STATUT_ABORT => ['label' => "Abandonée", 'icon' => 'times', 'classes' => ['danger']],
        self::STATUT_BROUILLON => ['label' => "Brouillon", 'icon' => 'trash', 'classes' => ['warning']],
        self::STATUT_VALIDER => ['label' => "Validée", 'icon' => 'check', 'classes' => ['success']],
        self::STATUT_TERMINER => ['label' => "Terminée", 'icon' => 'thumbs-up', 'classes' => ['important']]
    ];
    
    public static $urgent = [
        self::URGENT_NON => ['label' => "NON", 'icon' => 'times', 'classes' => ['success']],
        self::URGENT_OUI => ['label' => "OUI", 'icon' => 'check', 'classes' => ['danger']]
    ];
    
    public static $type_list = array(
        self::TYPE_NO => array('label' => 'FI ancienne version', 'icon' => 'refresh', 'classes' => array('info')),
        self::TYPE_FORFAIT => array('label' => 'Forfait', 'icon' => 'check', 'classes' => array('info')),
        self::TYPE_GARANTIE => array('label' => 'Sous garantie', 'icon' => 'check', 'classes' => array('info')),
        self::TYPE_CONTRAT => array('label' => 'Contrat', 'icon' => 'check', 'classes' => array('info')),
        self::TYPE_TEMPS => array('label' => 'Temps pass&eacute;', 'icon' => 'check', 'classes' => array('warning')),
    );
    
    public static $nature_list = array(
        self::NATURE_NO => array('label' => 'FI ancienne version', 'icon' => 'refresh', 'classes' => array('info')),
        self::NATURE_INSTALL => array('label' => 'Installation', 'icon' => 'download', 'classes' => array('info')),
        self::NATURE_DEPANNAGE => array('label' => 'Dépannage', 'icon' => 'wrench', 'classes' => array('info')),
        self::NATURE_TELE => array('label' => 'Télémaintenance', 'icon' => 'tv', 'classes' => array('info')),
        self::NATURE_FORMATION => array('label' => 'Formation', 'icon' => 'graduation-cap', 'classes' => array('info')),
        self::NATURE_AUDIT => array('label' => 'Audit', 'icon' => 'microphone', 'classes' => array('info')),
        self::NATURE_SUIVI => array('label' => 'Suivi', 'icon' => 'arrow-right', 'classes' => array('info')),
        self::NATURE_DELEG => array('label' => 'Délégation', 'icon' => 'user', 'classes' => array('info'))
    );
    
    public static $actioncomm_code = "'AC_INT','RDV_EXT','RDV_INT','ATELIER','LIV','INTER','INTER_SG','FORM_INT','FORM_EXT','FORM_CERTIF','VIS_CTR','TELE','TACHE'";
    
    private $global_user;
    private $global_langs;
    public static $dol_module = 'fichinter';
    public static $element_name = 'fichinter';
    public static $files_module_part = 'fichinter';
    //public $redirectMode = 1;
    
    public function __construct($module, $object_name) {
        global $user, $langs;
        $this->global_user = $user;
        $this->global_langs = $langs;
        return parent::__construct($module, $object_name);
    }
    
    public function canDelete() {
        if($this->getData('statut') == self::STATUT_BROUILLON && $this->global_user->rights->bimpfi->fi_delete)
            return 1;
        return 0;
    }
    
    public function canCreate() {
        if($this->global_user->rights->bimpfi->fi_create)
            return 1;
        return 0;
    }
    
    public function canEdit() {
        return $this->canDelete();
    }
    
    public function getActionsButton() {
        $buttons = [];
        if($this->getData('fk_statut') == self::STATUT_VALIDER) {
            $buttons[] = array(
            'label' => 'Plannifier une intervention',
            'icon' => 'fas_calendar',
            'onclick' => $this->getJsActionOnclick('plannified', array(), array(
                'form_name' => 'plannified'
            ))
            );
        }
        if($this->getData('fk_statut') == self::STATUT_BROUILLON) {
            $buttons[] = array(
            'label' => 'Valider',
            'icon' => 'fas_check',
            'onclick' => $this->getJsActionOnclick('validate', array(), array(
                'confirm_msg' => "Voulez vous valider la FI ?",
            ))
            );
        }
        
        return $buttons;
    }
    
    public function displayTechsArray($nom_url = true) {
        $html = "";
        $tech = $this->getInstance('bimpcore', 'Bimp_User');
        if(($this->getData('techs') || $this->getData('fk_user_author')) && $this->isLoaded()) {
            if($this->getData('fk_user_author') > 0) {
                 $tech->fetch($this->getData('fk_user_author'));
                 if($nom_url)
                    $html .= $tech->dol_object->getNomUrl() . '<br />';
                 else
                     $html .= $tech->dol_object->getFullName($this->global_langs);
            }
            foreach(json_decode($this->getData('techs')) as $id) {
                if($id > 0) {
                    $tech->fetch($id);
                    if($nom_url)
                        $html .= $tech->dol_object->getNomUrl() . "<br />";
                    else
                        $html .= $tech->dol_object->getFullName($this->global_langs) . '<br />';
                }
            }
        } else {
            $html .= BimpRender::renderAlerts("Il n'y à pas d'intervenant sur cette FI", 'danger', false);
        }
        return $html;
    }
    
    public function displayCommandesArray($nom_url = true) {
        $html = "";
        $commande = $this->getInstance('bimpcommercial', 'Bimp_Commande');
        if(($this->getData('commandes')) && $this->isLoaded()) {
            foreach(json_decode($this->getData('commandes')) as $id) {
                if($id > 0) {
                    $commande->fetch($id);
                    if($nom_url)
                        $html .= $commande->dol_object->getNomUrl() . "<br />";
                    else
                        $html .= $commande->dol_object->getFullName($this->global_langs) . '<br />';
                }
            }
        }
        return $html;
    }
    
    public function element_element_commandes() {
        $commandes = json_decode($this->getData('commandes'));
        $all_linked = getElementElement('commande', 'fichinter', null, $this->id);
        foreach($commandes as $id) {
            if(!count(getElementElement('commande', 'fichinter', $id, $this->id))) {
                addElementElement('commande', 'fichinter', $id, $this->id);
            }
        }
        foreach($all_linked as $nb => $infos) {
            if(!in_array($infos['s'], $commandes)) {
                delElementElement('commande', 'fichinter', $infos['s'], $this->id);
            }
        }
    }
    
    public function getTechsArrayForPlanning() {
        $array = $this->getTechsArray();
        $tech = $this->getInstance('bimpcore', 'Bimp_User', $this->getData('fk_user_author'));
        $array[$tech->id] = $tech->dol_object->getFullName($this->global_langs);
        return $array;
    }
    
    public function checkedTechPlanning() {
        $list = $this->getTechsArrayForPlanning();
        foreach($list as $id => $element) {
            $values[] = $id;
        }
        return $values;
    }
    
    public function getTechsArray() {
        if($this->getData('techs') && $this->isLoaded()) {
            $tech = $this->getInstance('bimpcore', 'Bimp_User');
            $arrayTechs = [];
            foreach(json_decode($this->getData('techs')) as $id) {
                if($id > 0) {
                    $tech->fetch((int)$id);
                    $arrayTechs[$tech->id] = $tech->dol_object->getFullName($this->global_langs);
                }
            }
            return $arrayTechs;
        }
        return [];
    }
    
    public function getCommandesArray() {
        if($this->getData('commandes') && $this->isLoaded()) {
            $commande = $this->getInstance('bimpcommercial', 'Bimp_Commande');
            $arrayCommandes = [];
            foreach(json_decode($this->getData('commandes')) as $id) {
                if($id > 0) {
                    $commande->fetch((int)$id);
                    $arrayCommandes[$commande->id] = $commande->getRef();
                }
            }
            return $arrayCommandes;
        }
        return [];
    }
    
    public function renderCommandesInput() {
        $html = '';
        $values = $this->getCommandesArray();
        $input = BimpInput::renderInput('search_commande_client', 'commandes_add_value', '', ['id_client' => $this->getData('fk_soc')]);
        $content = BimpInput::renderMultipleValuesInput($this, 'commandes', $input, $values);
        $html .= BimpInput::renderInputContainer('commandes', '', $content, '', 0, 1, '', array('values_field' => 'commandes'));
        return $html;
    }
    
    public function renderTechsInput() {
        $html = '';
        $values = $this->getTechsArray();
        if(!count($values)) {
            $values = [(int) $this->global_user->id => $this->global_user->getFullName($this->global_langs)];
        }
        $input = BimpInput::renderInput('search_user', 'techs_add_value');
        $content = BimpInput::renderMultipleValuesInput($this, 'techs', $input, $values);
        $html .= BimpInput::renderInputContainer('techs', '', $content, '', 0, 1, '', array('values_field' => 'techs'));
        return $html;
    }

    public function createFromContrat($contrat, $data) {
        $data = (object) $data;
        $this->set('fk_soc', $contrat->getData('fk_soc'));
        $this->set('fk_statut', self::STATUT_BROUILLON);
        $this->set('fk_contrat', $contrat->id);
        $this->set('fk_user_author', $data->fk_user_author);
        $insertTechs = [];
        foreach($data->techs as $id) {
            if(!empty($id) || $id != "")
                $insertTechs[] = $id;
        }
        $this->set('datei', $data->date);
        $this->set('techs', json_encode($insertTechs));
        $this->set('ref', BimpTools::getNextRef('fichinter', 'ref', 'FI{AA}{MM}-'));
        $errors = $this->create();
        if(!count($errors)) {
            $this->addLog("FI Créée depuis le contrat " . $contrat->dol_object->getNomUrl());
            addElementElement('contrat', 'fichinter', $contrat->id, $this->id);
            return $this->id;
        }  
        return 0;
    }
    
    public function createFromCommande($commande, $data) {
        
    }
    
    public function displayTime() {
        $time = $this->getData('duree');
        if($time < 3600) {
            $heures = 0;
            if($time < 60)
                $minutes = 0;
            else
                $minutes = round($time / 60);
        } else {
            $heures = round($time/ 3600);
            $secondes = round($time % 3600);
            $minutes = floor($secondes / 60);
        }
        $lesSecondes = round($secondes % 60);
        $time = "$heures h $minutes min $lesSecondes s";
        return $time;
    }
    
    public function has($field) {
        if($this->getData($field))
            return 1;
        return 0;
    }
    
    public function addLog($text) {
        $errors = array();
        if ($this->isLoaded($errors) && $this->field_exists('logs')) {
            $logs = (string) $this->getData('logs');
            if ($logs) {
                $logs .= '<br/>';
            }
            $logs .= ' - <strong> Le ' . date('d / m / Y à H:i') . '</strong> par ' . $this->global_user->getFullName($this->global_langs) . ': ' . $text;
            $errors = $this->updateField('logs', $logs, null, true);
        }
        return $errors;
    }
    
    public function displayLinesContrats() {
        $html = "";
        if($this->getData('fk_contrat')) {
            $contratLines = $this->getInstance('bimpcontract', 'BContract_contratLine');
            $html .= $contratLines->renderList('fi', 1, "Lignes du contrat lié", 'list', ['fk_contrat' => $this->getData('fk_contrat')]);
        }
        return $html;
    }
    
    public function renderFilesTable() {
        $html = '';
        if ($this->isLoaded()) {
            global $conf;
            $ref = $this->getRef();
            $dir = $this->getFilesDir();
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
                $url = DOL_URL_ROOT . '/document.php?modulepart=fichinter&file=' . $ref . urlencode('/');
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
                                    'mime' => dol_mimetype($file['name'], '', 0),
                                    'href' => $url . $file['name'] . '&attachment=0'
                                )
                                    ), 'a');

                    $onclick = $this->getJsActionOnclick('deleteFile', array('file' => htmlentities($file['fullname'])), array(
                        'confirm_msg' => 'Veuillez confirmer la suppression de ce fichier',
                        'success_callback' => 'function() {bimp_reloadPage();}'
                    ));
                    $html .= BimpRender::renderRowButton('Supprimer', 'trash', $onclick);
                    $html .= '</td>';
                    $html .= '</tr>';
                }
            } else {
                $html .= '<tr>';
                $html .= '<td colspan="4">';
                $html .= BimpRender::renderAlerts('Aucun fichier', 'info', false);
                $html .= '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody>';
            $html .= '</table>';
            $html = BimpRender::renderPanel('Documents PDF ' . $this->getLabel('of_the'), $html, '', array(
                        'icon' => 'fas_file',
                        'type' => 'secondary',
                        'foldable' => true
            ));
        }
        return $html;
    }
    
    public function getActionsButtonsInfos() {
        $buttons = array();
        if($this->isLoaded()) {
            $buttons[] = array(
                'label' => 'Lier une commande',
                'icon' => 'fas_cubes',
                'onclick' => $this->getJsActionOnclick('linkedCommande', array(), array(
                    'form_name' => 'linkedCommande'
                ))
            );
            
            if($this->getData('fk_statut') == self::STATUT_BROUILLON) {
                $buttons[] = [
                    'label' => 'Ajouter / Supprimer des techniciens',
                    'icon' => 'fas_user',
                    'onclick' => $this->getJsActionOnclick('addTech', array(), array('form_name' => 'addTech')),
                ];
            }
            if(!$this->getData('fk_contrat')) {
                $buttons[] = array(
                'label' => 'Lier un contrat',
                'icon' => 'fas_cubes',
                'onclick' => $this->getJsActionOnclick('linkedContrat', array(), array(
                    'form_name' => 'linkedContrat'
                ))
                ); 
            }
        }
        return $buttons;
    }
    
    public function actionAddTech($data, &$success) {
        $data = (object) $data;
        $have_changement = false;
        $success = '<u>Succès:</u> <br />';
        $errors = [];
        $warnings = [];
        if($data->fk_user_author != $this->getInitData('fk_user_author')) {
            $errors = $this->updateField('fk_user_author', $data->fk_user_author);
            if(!count($errors)) {
                $have_changement = true;
                $success .= "Changement du techniciens principal<br />";
            }
        }
        if(json_encode($data->techs) != $this->getInitData('techs')) { 
            $errors = $this->updateField('techs', json_encode($data->techs));
            if(!count($errors)) {
                $have_changement = true;
                $success .= "Changement de la liste des techhniciens supplémentaires";
            }
        }
        if(!$have_changement)
            $success = "Il n'y à eu aucun changement d'affectation de techniciens";
        return [
            'success' => $success,
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
    
    public function actionValidate($data, &$success) {
        $success = "";
        $errors = [];
        $warnings = [];
        if($this->dol_object->setValid($this->global_user) <= 0) {
            $errors[] = $this->dol_object->errors;
        } else {
            $success = "La FI à été validée avec succès";
        }
        return [
            'success' => $success,
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
    
    public function actionLinkedCommande($data, &$success) {
        $data = (object) $data;
        $errors = [];
        $warnings = [];
        $success = "";
        if(!$data->commandes) {
            $errors = $this->updateField('commandes', NULL);
            $success = "Il n'y à plus de commandes liées à cette FI";
        } elseif(json_encode($data->commandes) != $this->getInitData('commandes') && count($data->commandes)) {
            $errors = $this->updateField('commandes', json_encode($data->commandes));
            if(!count($errors))
                $success = "Changement des commandes avec succès";
        }
        $this->element_element_commandes();
        return [
            'success' => $success,
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
    
    public function actionPlannified($data, &$success) {
        $success = "";
        $errors = [];
        $warnings = [];
        $data = (object) $data;
        
            if(!$data->duree || $data->duree == 0)
                $errors[] = "Il doit y avoir une durée pour valider la FI";
            if($data->planning_to == 0)
                $errors[] = "Il doit avoir au moin un techniciens pour plannifier cette FI";
            if(!count($errors)) {
                $contrat = $this->getInstance('bimpcontract', 'BContract_contrat', $this->getData('fk_contrat'));
                BimpTools::loadDolClass('comm/action', 'actioncomm');
                $tms_start = strtotime($data->debut_date . ' ' . $data->debut_heure);
                $tms_end = ($tms_start + $data->duree);
                $actioncomm = new ActionComm($this->db->db);
                $actioncomm->userassigned = $data->planning_to;
                $actioncomm->label = $this->getRef();
                $actioncomm->datep = $tms_start;
                $actioncomm->datef = $tms_end;
                $actioncomm->durationp = $data->duree;
                $actioncomm->userownerid = $this->getData('fk_user_author');
                $actioncomm->authorid = $this->global_user->id;
                $actioncomm->type_id = $data->type;
                $actioncomm->socid = $contrat->getData('fk_soc');
                if($actioncomm->create($this->global_user) <= 0) {
                    return $actioncomm->errors;
                } else {
                    $new_inter = $this->getChildObject('lines')->dol_object;
                    $new_inter->fk_fichinter = $this->id;
                    $count = $this->db->getCount('fichinterdet', 'fk_fichinter = ' . $this->id . " AND ISNULL(fk_parent_line) = 1", 'rowid');
                    $new_inter->desc = $this->getRef() . '-' . ($count+1);
                    $new_inter->datei = $tms_start;
                    $new_inter->duration = $data->duree;
                    $new_inter->insert($this->global_user);
                    $instance = $this->getChildObject('lines', $new_inter->id);
                    $instance->updateField('techs', json_encode($data->planning_to));
                }
            }
        return [
          'success' => $success,
          'errors' => $errors,
          'warnings' => $warnings
        ];
    }
    
    public function displayActioncommArray() {
        $actioncomm_array = [];
        $list = $this->db->getRows('c_actioncomm', 'code IN ('.self::$actioncomm_code.')');
        foreach($list as $nb => $infos) {
            $actioncomm_array[$infos->id] = $infos->libelle;
        }
        return $actioncomm_array;
    }
    
    public function getCodeProduitArray() {
        $codesArray = [];
        $commande = $this->getInstance('bimpcommercial', 'Bimp_Commande');
        $contrat = $this->getInstance('bimpcontract', 'BContract_contrat', $this->getData('fk_contrat'));
        foreach(json_decode($this->getData('commandes')) as $nb => $id) {
            if($id > 0) {
                $list = $this->db->getRows('commandedet', 'fk_commande = ' . $id);
                $commande->fetch($id);
                foreach($list as $num => $line) {
                    if($line->fk_product > 0 && $line->product_type != 0){
                        $codesArray["commande_".$line->rowid] = ['label' => $commande->getRef() . " - " . $this->getProductInfos($line->fk_product, true, true), 'icon' => 'fas_dolly'];
                    }
                }
            }
        }
        $list = $this->db->getRows('contratdet', 'fk_contrat = ' . $this->getData('fk_contrat'));
        foreach($list as $num => $line) {
            if($line->fk_product > 0 && $line->product_type != 0){
                $codesArray["contrat_".$line->rowid] = ['label' => $contrat->getRef() . " - " . $this->getProductInfos($line->fk_product, true, true), 'icon' => 'fas_file-signature'];
            }
        }
        return $codesArray;
    }
    
    public function getProductInfos($id_product, $ref = true, $description = false) {
        $html = "";
        if($id_product > 0) {
            $product = $this->getInstance('bimpcore', 'Bimp_Product', $id_product);
            if(!$ref && !$description){
                $html .= $product->id;
            } else {
                if($ref)
                    $html .= $product->getData('ref');
                if($description){
                    if($ref)
                        $html .= ' - ';
                    $html .= $product->getData('description');
                }
            }
        }
        return $html;
    }
    
}
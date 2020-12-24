<?php
require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/fichinter/class/fichinter.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';

class BT_ficheInter extends BimpDolObject {
    
    public $mailSender = 'admin@bimp.fr';
    public $mailGroupFi = 'fi@bimp.fr';
    public static $dol_module = 'fichinter';
    public static $files_module_part = 'ficheinter';
    public static $element_name = 'fichinter';

    CONST STATUT_ABORT = -1;
    CONST STATUT_BROUILLON = 0;
    CONST STATUT_VALIDER = 1;
    CONST STATUT_VALIDER_COMMERCIALEMENT = 2;
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
        self::STATUT_BROUILLON => ['label' => "En cours de renseignement", 'icon' => 'retweet', 'classes' => ['warning']],
        self::STATUT_VALIDER => ['label' => "Signée par le client", 'icon' => 'check', 'classes' => ['success']],
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
    
    public $redirectMode = 5;
    
    public function __construct($module, $object_name) {
        global $user, $langs;
        $this->global_user = $user;
        $this->global_langs = $langs;
        
        return parent::__construct($module, $object_name);
        
    }
    
    public function iAmAdminRedirect() {
        global $user;
        if(in_array($user->id, array(1, 460, 375, 217)))
            return true;
        parent::iAmAdminRedirect();
    }  
    
    public static function isActive() {
        global $conf;
        if($conf->bimptechnique->enabled)
            return 1;
        return 0;
    }
    
    
    
    public function displayVersion() {
        $html = "";
        
        if($this->getData('new_fi') == 0) {
            $html .= "<strong>Ancienne version des FI.</strong><br />Pour les informations  réèlles de la FI merci, de cliquer sur le boutton ci-dessous<br />";
            $html .= "<a href='".DOL_URL_ROOT."/fichinter/card.php?id=".$this->id."' class='btn btn-default' >Ancienne version</a>";
        } else {
            $html .= "<strong class='success'>Nouvelle verion</strong>";
        }
        
        return $html;
    }
    
    public function displayRatioTotal() {
        $children = $this->getChildrenList('inters');
        $ratio = 0;
        $ratio_contrat = 0;
        foreach($children as $id) {
            $child = $this->getChildObject("inters", $id);
            if($child->getTypeOfThisLine() == 'contrat') {
                $contrat = $this->getInstance('bimpcontract', 'BContract_contrat', $this->getData('fk_contrat'));
                $ratio_contrat = $contrat->renderThisStatsFi(false);
            } else {
                $ratio += $child->getRatio(false);
            }
        }
        
        if($ratio == 0) {
            $class = 'warning';
            $icon = 'arrow-right';
        } elseif($ratio > 0) {
            $class = 'success';
            $icon = 'arrow-up';
        } elseif($ratio < 0) {
            $class = 'danger';
            $icon = 'arrow-down';
        }
        
        if($ratio_contrat == 0) {
            $class_contrat = 'warning';
            $icon_contrat = 'arrow-right';
        } elseif($ratio_contrat > 0) {
            $class_contrat = 'success';
            $icon_contrat = 'arrow-up';
        } elseif($ratio_contrat < 0) {
            $class_contrat = 'danger';
            $icon_contrat = 'arrow-down';
        }
        
        $return = "<strong>Commande: </strong><strong class='".$class."' >".price($ratio)."€ ".BimpRender::renderIcon($icon)."</strong><br />";
        $return.= "<strong>Contrat: </strong><strong class='".$class_contrat."' >".$ratio_contrat."€ ".BimpRender::renderIcon($icon_contrat)."</strong>";
        
        return $return;
        
    }
    
    public function getTypeActionCommArray() {
        
        $actionComm = [];
        $acceptedCode = ['ATELIER', 'DEP_EXT', 'HOT', 'INTER', 'INTER_SG','AC_INT','LIV', 'RDV_INT', 'RDV_EXT', 'AC_RDV', 'TELE', 'VIS_CTR'];
        $list = $this->db->getRows('c_actioncomm', 'active = 1');
        foreach($list as $nb => $stdClass) {
            if(in_array($stdClass->code, $acceptedCode)) {
                $actionComm[$stdClass->id] = $stdClass->libelle;
            }
        }
        return $actionComm;
        
    }
    
    public function getCommandesClientArray($posted = true) {
        $commandes = [];
        $commande = $this->getInstance('bimpcommercial', 'Bimp_Commande');
        if(($posted))
            $list = $commande->getList(['fk_soc' => BimpTools::getPostFieldValue('client')]);
        else 
            $list = [];
        foreach($list as $nb => $infos) {
            $commande->fetch($infos['rowid']);
            $statut = $commande->getData('fk_statut');
            
            $display_statut = "<strong class='".Bimp_Commande::$status_list[$statut]['classes'][0]."' >";
            $display_statut.= BimpRender::renderIcon(Bimp_Commande::$status_list[$statut]['icon']);
            $display_statut.= " " . Bimp_Commande::$status_list[$statut]['label'] . "</strong>";
            
            $add_libelle = "";
            if($commande->getdata('libelle')) {
                $add_libelle = " - " . $commande->getData('libelle');
            }
            $commandes[$commande->id] = $commande->getRef() . " (".$display_statut.")" . $add_libelle;
        } 
        return $commandes;
    }
    
    public function getContratsClientArray($posted = true, $choose = true) {
        
        $contrats = Array();
        
        if($choose) {
            $contrats[0] = "Aucun contrat";
        }
        
        $contrat = $this->getInstance('bimpcontract', 'BContract_contrat');
        
        $id_client = ($posted) ? BimpTools::getPostFieldValue("client") : $this->getData('fk_soc');
        
        $list = $contrat->getList(["fk_soc" => $id_client, "statut" => 11]);
        
        foreach($list as $nb => $i) {
            $contrat->fetch($i['rowid']);
            $statut = $contrat->getData('statut');
            $display_statut = "<strong>";
            $display_statut.= BContract_contrat::$status_list[$statut]['label'] . "</strong>";
            
            $add_label = "";
            if($contrat->getData('label')) {
                $add_label = " - " . $contrat->getData('label');
            }
            $contrats[$contrat->id] = $contrat->getRef() . " (".$display_statut.")" . $add_label;
        }
        
        return $contrats;
        
    }
    
    public function getTicketsSupportClientArray($posted = true) {
        $tickets = [];

        $ticket = $this->getInstance('bimpsupport', 'BS_Ticket');
        if($posted && BimpTools::getPostFieldValue("client"))
            $list = $ticket->getList(['id_client' => BimpTools::getPostFieldValue("client")]);
        else
            $list = [];
        foreach($list as $nb => $infos) {
            $ticket->fetch($infos['id']);
            $statut = $ticket->getData('status');
            
            $display_statut = "<strong class='". BS_Ticket::$status_list[$statut]['classes'][0]."' >";
            $display_statut.= BimpRender::renderIcon(BS_Ticket::$status_list[$statut]['icon']);
            $display_statut.= " " . BS_Ticket::$status_list[$statut]['label'] . "</strong>";
            
            $tickets[$ticket->id] = $ticket->getRef() . " (".$display_statut.") <br /><small style='margin-left:10px'>" . $ticket->getData('sujet') . '</small>' ;
        } 
        
        return $tickets;
    }
    
    public function actionCreateFromRien($data, &$success) {
        
        $errors = Array();
        $warnings = Array();
        $data = (object) $data;
        
        $new_ref = $this->getNextNumRef($data->client);
        $linked_commandes = "";
        $linked_tickets = "";
        
        if($data->linked_commandes != 0) {
            $linked_commandes = json_encode($data->linked_commandes);
        }
        if($data->linked_tickets != 0) {
            $linked_tickets = json_encode($data->linked_tickets);
        }
        
        $new = new Fichinter($this->db->db);
        $new->ref = $new_ref;
        $new->socid = $data->client;
        if($data->linked_contrat != 0) {
            $new->fk_contrat = $data->linked_contrat;
        }
        $new->statut = self::STATUT_BROUILLON;
        $new->fk_user_author = $data->techs;
        
        $id_fi = $new->create($this->global_user);
        //echo '<pre>' . $id_fi;
        if($id_fi > 0) { 
            $instance = $this->getInstance('bimptechnique', 'BT_ficheInter', $id_fi);
            $instance->updateField("commandes", $linked_commandes);
            $instance->updateField('new_fi', 1);
            if($linked_commandes != "") {
                foreach(json_decode($linked_commandes) as $current_commande_id) {
                    setElementElement("commande", "fichinter", $current_commande_id, $instance->id);
                }
            }
            
            if($linked_tickets != "") {
                foreach(json_decode($linked_tickets) as $current_ticket_id) {
                    setElementElement('bimp_ticket', 'fichinter', $current_ticket_id, $instance->id);
                }
            }
            
            if($instance->getData('fk_contrat')) {
                setElementElement('contrat', 'fichinter', $instance->getData('fk_contrat'), $instance->id);
            }
            
            $instance->updateField("tickets", $linked_tickets);
            $instance->updateField("urgent", $data->urgent);
            
            $actioncomm = new ActionComm($this->db->db);
            //$actioncomm->userassigned = Array($data->techs);
            $actioncomm->label = $instance->getRef();
            $actioncomm->note = '';
            $actioncomm->punctual = 1;
            $actioncomm->userownerid = $data->techs;
            $actioncomm->elementtype = 'fichinter';
            $actioncomm->type_id = $data->type_planning;
            $actioncomm->datep = $data->le . " " . $data->de;
            $actioncomm->datef = $data->le . " " . $data->a;
            $actioncomm->socid = $data->client;
            $actioncomm->fk_element = $instance->id;
            $actioncomm->create($this->global_user);
        }
        
        //echo '<pre>' . print_r($new, 1);        
        
        return Array(
            'errors' => $errors,
            'warnings' => $warnings,
            'success' => $success
        );
        
    }

    public function createFromContrat($contrat, $data) {

        $commandes = [];
        $tickets = [];
        if(is_array($data['linked_tickets'])) {
            foreach($data['linked_tickets'] as $id) {
                array_push($tickets, $id);
            }
        }
        if(is_array($data['linked_commandes'])) {
            foreach($data['linked_commandes'] as $id) {
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
        foreach($data['techs'] as $id) {
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
            if($create) {
                if($tech->id > 0) {
                    $id_fi = $new->create($tech);
                    if($id_fi > 0) {
                        $created = true;
                        $instance->fetch($id_fi);
                        $instance->updateField("commandes", json_encode($commandes));
                        $instance->updateField("tickets", json_encode($tickets));
                        $instance->updateField('new_fi', 1);
                        $instance->updateField("urgent", $data['urgent']);
                        $message = "<h3><b>Bimp</b><b style='color:#EF7D00' >Technique</b></h3>";
                        $message.= "<p>Référence: ".$instance->getNomUrl()."</p>";
                        
                        if(count($commandes) > 0) {
                            foreach($commandes as $id_commande) {
                                setElementElement("commande", "fichinter", $id_commande, $id_fi);
                            }
                        }
                        
                        setElementElement('contrat', "fichinter", $contrat->id, $id_fi);
                        
                        if($first_loop) {
                            $first_loop = false;
                            $emailRecipe .= $tech->email;
                        } else {
                            $emailRecipe .= ',' . $tech->email;
                        }
                    }
                }
            }
            
            $canPlanning = true;
            if($create) {
                if(!$created) {
                    $canPlanning = false;
                }
            }
            
            if(count($list_fi_tech_close) > 0) {
                $instance->find(['fk_contrat' => $contrat->id, 'fk_user_author' => $id]);
            }
            
            if($canPlanning) {
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
                $message.= "<p>Référence de la FI: ".$instance->getRef()."</p>";
                $message.= "<a href='".DOL_URL_ROOT."/bimptechnique/?fc=fi&id=".$instance->id."&navtab-maintabs=actioncomm' class='btn btn-primary'>Prendre en charge l'intervention</a>";
                
                $actioncomm->create($this->global_user);
                
                mailSyn2($sujet, $tech->email, $this->mailSender, $message);
                
            }
            
        }
       
        return $id_fi;
    }

    public function renderSignaturePad($addClass = '') {
        $displayStyle = '';
        $prefix = '';
        if($addClass == 'expand') {
            $displayStyle = 'display:none';
            $prefix = 'x_';
        }

        $html = '';
        $html .= '<div class="wrapper"> 
                    <canvas id="'.$prefix.'signature-pad" class="signature-pad '.$addClass.'" style="border: solid 1px; '.$displayStyle.'" width=400 height=200></canvas>
                  </div>
                  <div>
                    <button id="save" class="btn btn-success btn-large">'.BimpRender::renderIcon("thumbs-up").' Signer la fiche d\'intervention</button>
                    <button id="clear" class="btn btn-danger btn-large" >'.BimpRender::renderIcon("retweet").' Refaire la signature</button>
                    <button id="expand" class="btn btn-default btn-large" >'.BimpRender::renderIcon("expand").'</button>
                  </div>
                ';
        return $html;
    }
    
    
    
    public function getListExtraButtons()
    {
        global $user;
        $buttons = [];
                
            
            if(($this->getData('fk_statut') == self::STATUT_BROUILLON)) {
                $buttons[] = array(
                    'label'   => 'Prendre en compte',
                    'icon'    => 'fas_thumbs-up',
                    'onclick' => $this->getJsActionOnclick('prendreEnCompte', array(), array()));
            }
        
        return $buttons;
        
    }
    
    public function getListFilterDefault(){
        return Array(
          Array(
              'name' => 'fk_contrat',
              'filter' => $_REQUEST['id']
          )  
        );
    }
    
    public function getListFilterTech() {
        global $user;
        return Array(
            Array(
                'name' => 'fk_user_author',
                'filter' => $user->id
            )
        );
    }
    
    public function getNextNumRef($soc) {
        return $this->dol_object->getNextNumRef($soc);
    }
    
    public function canDelete() {
        
        if(($this->getData('fk_statut') == 0) && $this->getData('fk_user_author') == $this->global_user->id) {
           return 1;
        }
        
        return 0;
    }
    
    
    public function getActionsButtons() {
        global $conf, $langs, $user;
        $buttons = Array();
        $statut = $this->getData('fk_statut');
        
        if($statut != self::STATUT_VALIDER) {
            if($statut != self::STATUT_TERMINER) {
                $buttons[] = array(
                    'label' => 'Lier une commande client',
                    'icon' => 'links',
                    'onclick' => $this->getJsActionOnclick('linked_commande_client', array(), array(
                        'form_name' => 'linked_commande_client'
                    ))
                );
            }

            if($statut == self::STATUT_BROUILLON) {
                $buttons[] = array(
                    'label' => 'Ajouter une/des ligne.s',
                    'icon' => 'fas_plus',
                    'onclick' => $this->getJsActionOnclick('addInter', array(), array(
                        'form_name' => 'addInter'
                    ))
                );
            }

            $buttons[] = array(
                'label' => 'Générer le PDF de la fiche d\'intervention',
                'icon' => 'fas_pdf',
                'onclick' => $this->getJsActionOnclick('generatePdf', array(), array(
                ))
            );
        }
        
        if($statut == self::STATUT_VALIDER) {
            $buttons[] = array(
                'label' => "Fiche d'intervention facturable",
                'icon' => 'euro',
                'onclick' => $this->getJsActionOnclick('sendFacturation', array(), array(
                ))
            );
        }
        
        
//        if($this->getData('signed') && !in_array($statut, $wrongStatutForReopen)) {
//            $buttons[] = array(
//                'label' => 'Ré-Ouvrir',
//                'icon' => 'fas_retweet',
//                'onclick' => $this->getJsActionOnclick('re_open', array(), array(
//                ))
//            );
//        }
        
        return $buttons;
    }
    
    public function actionSendfacturation($data, &$success) {
        
        $this->updateField('fk_statut', 2);
        
    }
    
    public function actionAddInter($data, &$success) {
        global $user, $db;
        $errors = [];
        $warnings = [];
        $description = "";
        $data = (object) $data;
        $objects = array();
        $new = $this->dol_object;
        $notField = array('inters_sub_object_idx_type', 'inters_sub_object_idx_date', 'inters_sub_object_idx_duree', 'inters_sub_object_idx_description');
        
        $allCommandesLinked = getElementElement('commande', "fichinter", null, $this->id);
        //echo '<pre>' . print_r($allCommandesLinked, 1);
        //return 0;
        
        foreach($data as $field => $val) {
            if(!in_array($field, $notField)) {
                $numInter = explode('_', $field);
                $objects[$numInter[1]][$field] = $val;
            }
        }    
        foreach($objects as $numeroInter => $value) {
            $date = new DateTime($value['inter_' . $numeroInter . '_date']);
            if($value['inter_' . $numeroInter . '_am_pm'] == 0) {
                $arrived = strtotime($value['inter_' . $numeroInter . '_global_arrived']);
                $departure = strtotime($value['inter_' . $numeroInter . '_global_quit']);
                $duration = $departure - $arrived;
            } else {
                $arrived_am = strtotime($value['inter_' . $numeroInter . '_am_arrived']);
                $departure_am = strtotime($value['inter_' . $numeroInter . '_am_quit']);
                $duration_am = $departure_am - $arrived_am;
                $arrived_pm = strtotime($value['inter_' . $numeroInter . '_pm_arrived']);
                $departure_pm = strtotime($value['inter_' . $numeroInter . '_pm_quit']);
                $duration_pm = $departure_pm - $arrived_pm;
                $duration = $duration_am + $duration_pm;
            }
            $desc = $value['inter_' . $numeroInter . '_description'];
            
            $new->addline($user, $this->id, $desc, $date->getTimestamp(), $duration);
            $lastIdLine = $this->db->getMax('fichinterdet', 'rowid', 'fk_fichinter = ' . $this->id);
            $line = $this->getInstance('bimptechnique', 'BT_ficheInter_det', $lastIdLine);
            
            $exploded_service = explode("_", $value['inter_' . $numeroInter . '_service']);
            $field = 'id_line_' . $exploded_service[0];
            
            $line->updateField('type', $value['inter_' . $numeroInter . '_type']);
            
            // Déplacements
            
            
            
            if($value['inter_' . $numeroInter . '_type'] == 0) {
                $line->updateField($field, $exploded_service[1]);
            } // Faire une exeption pour les lignes libres
            
            
            $line->updateField('forfait', $value['inter_' . $numeroInter . '_forfait']);

        }
        
        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'success' => $success
        ];
    }

    
    public function getHtLine($type_line, $id_line) {
        switch($type_line) {
            case 'contrat':
                $obj = $this->getInstance('bimpcontract', 'BContract_contratLine', $id_line);
                return $obj->getData('total_ht');
                break;
            case 'commande':
                BimpTools::loadDolClass('commande', 'commande');
                $obj = New OrderLine($this->db->db);
                $obj->fetch($id_line);
                return $obj->total_ht;
                break;
            default:
                return 0;
                break;
        }
    }
    
    public function getProductId($id_line, $type) {
        switch($type) {
            case 'contrat':
                $obj = $this->getInstance('bimpcontract', 'BContract_contratLine', $id_line);
                return $obj->getData('fk_product');
                break;
            case 'commande':
                BimpTools::loadDolClass('commande', 'commande');
                $obj = New OrderLine($this->db->db);
                $obj->fetch($id_line);
                return $obj->fk_product;
                break;
            default:
                return 0;
                break;
        }
    }
    
    public function switch_mode_facturation_deponds_type_and_service() {
        if(BimpTools::getPostFieldValue('inter_0_type')) {
            $type = BimpTools::getPostFieldValue('inter_0_type');
            switch($type) {
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
        if(BimpTools::getPostFieldValue('inter_0_service')) {
            $service = BimpTools::getPostFieldValue('inter_0_service');
            $explode = explode('_', $service);
            switch($explode[0]) {
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
    
    public function canCreate() {
        return 1;
    }
    
    public function getLinesForInter() {
        $return = [];
        $parent = $this->getParentInstance();
        $list = $parent->getChildrenList("inters");
        $product = $this->getInstance('bimpcore', 'Bimp_Product');
        $obj = $this->getInstance('bimpcontract', 'BContract_contratLine');
        foreach($list as $id) {
            $det = $parent->getChildObject("lines", $id);
            if(in_array($this->getData('fk_user_author'), json_decode($det->getData('techs')))) {
                if($det->getData('fk_contratdet')) {
                    $obj->fetch($det->getData('fk_contratdet'));
                    $id_product = $obj->getData('fk_product');
                } else {
                    BimpTools::loadDolClass('commande', 'OrderLine');
                    $obj = new OrderLine($thhis->db->db);
                    $obj->fetch($det->getData('fk_commandedet'));
                    $id_product = $obj->fk_product;
                }
                $product->fetch($id_product);
                $return[$det->id] = $det->getData('date') . ' - ' . $product->getData('ref');
            }
        }
        return $return;
    }
    
    public function renderHeaderExtraLeft() {

        $html = '';

        if ($this->isLoaded()) {
            $tech = $this->getInstance('bimpcore', 'Bimp_User', (int) $this->getData('fk_user_author'));
            $client = $this->getinstance('bimpcore', 'Bimp_Societe', $this->getData('fk_soc'));
            $html .= '<div class="object_header_infos">';
            $html .= '<h4>Intervenant: ' . $tech->dol_object->getNomUrl(1,1,1) . ' </h4>';
            $html .= '<h4>Client: ' . $client->dol_object->getNomUrl(1) . ' </h4>';
            $html .= '</div>';
        }
        
        return $html;
    }
    
    public function renderHeaderStatusExtra() {

        $extra = '<br />';
        $u = $this->getData('urgent');
        $extra .= "<span>Interventions urgentes:<strong class='".self::$urgent[$u]['classes'][0]."'> ".self::$urgent[$u]['label']."</strong></span>";
        
        
        return $extra;
    }
    
//    public function renderActionComm() {
//        $actionComm = $this->getInstance('bimpcore', 'Bimp_ActionComm');
//        $html = $actionComm->renderList('ficheInter');
//        return $html;
//    }
    
    public function getServicesArray() {
        $services = [];
        BimpTools::loadDolClass("commande");
        $commande = New Commande($this->db->db);
        $product = $this->getInstance('bimpcore', 'Bimp_Product');
        $allCommandes = ($this->getData('commandes')) ? json_decode($this->getData('commandes')) : [];
        foreach($allCommandes as $id) {
            $commande->fetch($id);
            foreach ($commande->lines as $line){
                $product->fetch($line->fk_product);
                $services['commande_' . $line->id] = $product->getRef() . ' - <b>'.$commande->ref.'</b>';
            }
            
        }
        
        $contrat = $this->getInstance('bimpcontract', 'BContract_contrat', $this->getData('fk_contrat'));
        foreach($contrat->dol_object->lines as $line) {
            $product->fetch($line->fk_product);
            $services['contrat_'.$line->id] = $product->getRef() . ' - <strong>'.$contrat->getRef().'</strong>';
        }
       
        return $services;
    }

    public function haveContratLinked() {
        if($this->getData('fk_contrat'))
            return 1;
        return 0;
    }
    
    public function displayLinkedContratCard() {
        $html = "";
        
        if($this->haveContratLinked()) {
            $contrat = $this->getInstance('bimpcontract', 'BContract_contrat', $this->getData('fk_contrat'));
            $card = new BC_Card($contrat);
            $html .= $card->renderHtml();
        } else {
            $html .= BimpRender::renderAlerts("Il n'y à pas de contrat lié sur cette fiche d'intervention", "info", false);
        }
        
        return $html;
    }
    
    public function displayAllTicketsCards() {
        $html = "";
        
        $allTickets = json_decode($this->getData('tickets'));
        $ticket = $this->getInstance('bimpsupport', 'BS_Ticket');
        if(count($allTickets) > 0) {
            foreach($allTickets as $id) {
                $ticket->fetch($id);
                $card = new BC_Card($ticket);
                $html .= $card->renderHtml();
            }
        } else {
            $html .= BimpRender::renderAlerts("Il n'y à pas de commandes liées sur cette fiche d'intervention", "info", false);
        }

        return $html;
    }
    
    public function displayAllCommandesCards() {
        $html = "";
        
        $allCommandes = json_decode($this->getData('commandes'));
        $commande = $this->getInstance('bimpcommercial', 'Bimp_Commande');
        if(count($allCommandes) > 0) {
            foreach($allCommandes as $id) {
                $commande->fetch($id);
                $card = new BC_Card($commande);
                $html .= $card->renderHtml();
                //$html .= '<button class="btn btn-default" onclick="'.$this->getJsActionOnclick("unlinkCommande", ['commande' => $id]).'" >'.BimpRender::renderIcon('unlink').'</button>';
            }
        } else {
            $html .= BimpRender::renderAlerts("Il n'y à pas de commandes liées sur cette fiche d'intervention", "info", false);
        }
        
        
        return $html;
    }
    
    public function renderSignatureTab() {
        $html = "";
        if($this->isNotSign()) {
            
            $tickets = json_decode($this->getData('tickets'));
            
            if(count($tickets) > 0) {
                $html .= "<h3>Fermeture de tickets support</h3>";
                foreach($tickets as $id_ticket) {
                    $ticket = $this->getInstance('bimpsupport', 'BS_Ticket', $id_ticket);
                    $html .= '<h3><div class="check_list_item" id="checkList" >'
                . '<input type="checkbox" id="BimpTechniqueAttachedTicket_'.$id_ticket.'" class="check_list_item_input">'
                . '<label for="BimpTechniqueAttachedTicket_'.$id_ticket.'">'
                . $ticket->getRef()
                . '</label></div></h3>';
                }
            }
            
            
            
            $html .= '<h3><div class="check_list_item" id="checkList" >'
                . '<input type="checkbox" id="BimpTechniqueSignChoise" class="check_list_item_input">'
                . '<label for="BimpTechniqueSignChoise">'
                . BimpRender::renderIcon('paper-plane') . ' Signature papier'
                . '</label></div></h3>'
                . '<div class="row formRow">'
                . '<div class="inputLabel col-xs-2 col-sm-2 col-md-1" required>Nom du signataire</div>'
                . '<div class="formRowInput field col-xs-12 col-sm-6 col-md-9">'
                . '<div class="inputContainer label_inputContainer " data-field_name="label" data-initial_value="" data-multiple="0" data-field_prefix="" data-required="0" data-data_type="string">'
                . '<input style="font-size: 20px" type="text" id="BimpTechniqueFormName" name="label" value="" data-data_type="string" data-size="128" data-forbidden_chars="" data-regexp="" data-invalid_msg="" data-uppercase="0" data-lowercase="0">'
                . '</div></div></div>';
            $html .= '<div class="row formRow">'
                . '<div class="inputLabel col-xs-2 col-sm-2 col-md-1" required>Email client</div>'
                . '<div class="formRowInput field col-xs-12 col-sm-6 col-md-9">'
                . '<div class="inputContainer label_inputContainer " data-field_name="label" data-initial_value="" data-multiple="0" data-field_prefix="" data-required="0" data-data_type="string">'
                . '<input style="font-size: 20px" type="text" id="email_client" name="label" data-data_type="string" data-size="128" data-forbidden_chars="" data-regexp="" data-invalid_msg="" data-uppercase="0" data-lowercase="0" value="'.$this->getDataClient('email').'">'
                . '</div></div></div>';
            $html .= '<div class="row formRow">'
                . '<div class="inputLabel col-xs-2 col-sm-2 col-md-1" required>Préconisation technicien</div>'
                . '<div class="formRowInput field col-xs-12 col-sm-6 col-md-9">'
                . '<div class="inputContainer label_inputContainer " data-field_name="label" data-initial_value="" data-multiple="0" data-field_prefix="" data-required="0" data-data_type="string">'
                . '<textarea id="note_private" name="note_private" rows="4" style="margin-top: 5px; width: 90%;" class="flat"></textarea>'
                . '</div></div></div>';
            $html .= '<div class="row formRow">'
                . '<div class="inputLabel col-xs-2 col-sm-2 col-md-1" required>Attente client</div>'
                . '<div class="formRowInput field col-xs-12 col-sm-6 col-md-9">'
                . '<div class="inputContainer label_inputContainer " data-field_name="label" data-initial_value="" data-multiple="0" data-field_prefix="" data-required="0" data-data_type="string">'
                . '<textarea id="attente_client" name="note_private" rows="4" style="margin-top: 5px; width: 90%;" class="flat"></textarea>'
                . '</div></div></div>';
            $html .= '<div class="row formRow" >'
                . '<div class="inputLabel col-xs-2 col-sm-2 col-md-1" required>Signature de la fiche</div>'
                . '<div  class="formRowInput field col-xs-12 col-sm-6 col-md-9">'
                . '<div  class="inputContainer label_inputContainer " data-field_name="label" data-initial_value="" data-multiple="0" data-field_prefix="" data-required="0" data-data_type="string">'
                . $this->renderSignaturePad()
                . '</div></div></div>';
           
            $html .= '<br />';
            //$html .= ;
        } elseif($this->isSign()) {
            $html .= '<h3>Nom du signataire client: '.$this->displayDataTyped($this->getData('signataire')).'</h3>';
            if($this->haveSignatureElectronique()) {
                $html .= '<h3>Type de signature: '.$this->displayDataTyped("Signature électronique").'</h3>';
            } elseif($this->haveSignaturePapier()) {
                $html .= '<h3>Type de signature: '.$this->displayDataTyped("Signature papier").'</h3>';
            }
           // 
            global $conf;
            //$file = $conf->ficheinter->dir_output . "/" . $this->getRef() . '/' . $this->getRef() . '.pdf';
            //f(file_exists($file)) {
                $file =  DOL_URL_ROOT . "/document.php?modulepart=ficheinter&file=" . $this->getRef() . "/" . $this->getRef() . '.pdf';
                ///test_alexis/document.php?modulepart=contract&file=CT2006-002_1%2FContrat_CT2006-002_1_Ex_Client.pdf
                $html .= '<embed src="'.$file.'" type="application/pdf"   height="1000px" width="100%">';
            //}
        }
        
        return $html;
    }
    
    public function displayDataTyped($data, $balise = 'i', $color = "#EF7D00") {
        return '<'.$balise.' style="color:'.$color.'" >'.$data.'</'.$balise.'>';
    }
    
    public function getCommercialClient() {
        $client = $this->getInstance('bimpcore', 'Bimp_Societe', $this->getData('fk_soc'));
        return $client->getCommercial();
    }
    
    public function displayCommercial() {
        $commercial = $this->getCommercialClient();
        return $commercial->dol_object->getNomUrl(1,1,1);
    }

    public function getDataCommercialClient($field) {
        $commercial = $this->getCommercialClient();
        return $commercial->getData($field);
    }
    
    public function getDataClient($field) {
        $client = $this->getInstance('bimpcore', 'Bimp_Societe', $this->getData('fk_soc'));
        return $client->getData($field);
    }
    
    public function actionGeneratePdf($data, &$success = '', $errors = Array(), $warnings = Array()){
        //echo '<pre>' . print_r($this->module, 1);
        //$this->dol_object->generateDocument('fi', $this->global_langs);
        //global $conf;
        //echo '<pre>' . print_r($conf->ficheinter);
        return parent::actionGeneratePdf(['model' => 'fi']);
    }
    
    
    public function displayTypeSignature() {
        $html = "";
        global $user;
        if($this->haveSignaturePapier()) {
            $html .= 'Signature papier';
        } elseif($this->haveSignatureElectronique()) {
            $html .= "Signature Electronique";
            if($this->userHaveRight('view_signature_infos_fi') || $user->admin) {
                $html .= "\n" . '<img width="100%" src="'.$this->getData('base_64_signature').'">';
            }
        }
        return $html;
    }
    
    public function haveSignaturePapier() {
        if($this->getData('signataire') && $this->isNotSign()) {
            return 1;
        } elseif($this->getData('signataire') && !$this->getData('base_64_signature') && $this->isSign()) {
            return 1;
        }
        return 0;
    }
    
    public function haveSignatureElectronique() {
        if($this->isSign() && $this->getData('signataire') && $this->getData('base_64_signature'))
            return 1;
        return 0;
    }
    
    public function isSign() {
        if($this->getData('signed'))
            return 1;
        return 0;
    }
    
    public function isNotSign() {
        return !$this->isSign();
    }
    
    public function userHaveRight($right) {
        if($this->global_user->rights->bimptechnique->$right)
            return 1;
        return 0;
    }
    
    public function displayDuree() {
        return $this->timestamp_to_time($this->getData('duree'));
    }
    
    public function time_to_decimal($time) {
        $timeArr = explode(':', $time);
        $decTime = ($timeArr[0]*60) + ($timeArr[1]) + ($timeArr[2]/60);
     
        return $decTime;
    }

    public function timestamp_to_time($timestamp) {
        $heures = floor($timestamp / 3600);
        if(($timestamp % 3600) >= 60) {
            $minutes = floor(($timestamp % 3600) / 60);
        }
        return str_pad($heures, 2, "0", STR_PAD_LEFT) . ":" . str_pad($minutes, 2, "0", STR_PAD_LEFT);
    }
    
    public function actionRe_open($data, &$success) {
        $errors = Array();
        $warnings = Array();
        
        $success = "FI ré-ouverte avec succès";
        
        return Array(
            'errors' => $errors,
            'warnings' => $warnings,
            'success' => $success
        );
    }
    
    public function getCommandeClient() {
        
        $commandes = [];
        $my_commandes = ($this->getData('commandes')) ? json_decode($this->getData('commandes')) : [];
        
        $commande = $this->getInstance('bimpcommercial', 'Bimp_Commande');
        $search_commandes = $commande->getList(['fk_soc' => $this->getData('fk_soc')]);

        
        foreach($search_commandes as $index => $infos) {
            if(!in_array($infos['rowid'], $my_commandes)) {
                $commandes[$infos['rowid']] = $infos['ref'];
            }
        }
        
        return $commandes;
        
    }
    
    public function actionLinked_commande_client($data, &$success) {
        
        $errors = [];
        $warnings = [];
        
        if($data['linked']) {
            $my_commandes = json_decode($this->getData('commandes'));
            $my_commandes[] = $data['linked'];

            $errors = $this->updateField('commandes', json_encode($my_commandes));

            if(!count($errors)) {
                $success = 'Commande liée avec succès';
            }
        } else {
            $warnings[] = "Il n'y à pas de commande à lier";
        }

        return [
            'success' => $success,
            'errors' => $errors,
            'warnings' => $warnings
        ];

    } 

    
}
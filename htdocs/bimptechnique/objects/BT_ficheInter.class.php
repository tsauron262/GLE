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
    
    public function isOldFi() {
        return !$this->getData('new_fi');
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
    
    public function displayRatioTotal($display = true, $want = "") {
        if($this->getData('new_fi')) {
            global $db;
            BimpTools::loadDolClass('commande');
            $commande = new Commande($db);
            $commandes = json_decode($this->getData('commandes'));
            $service = $this->getInstance('bimpcore', 'Bimp_Product');
            $renta = [];

            $coup_technicien = BimpCore::getConf("bimptechnique_coup_horaire_technicien");

            foreach($commandes as $id_commande) {
                $commande->fetch($id_commande);
                $first_loop = true;
                foreach($commande->lines as $line) {
                    $service->fetch($line->fk_product);

                    $children = $this->getChildrenList("inters", ['id_line_commande' => $line->id]);
                    $qty = 0;
                    foreach($children as $id_child) {
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

            $children = $this->getChildrenList("inters");
            foreach($children as $id_child) {
                $child = $this->getChildObject('inters', $id_child);
                if(!$child->getData('id_line_commande') && !$child->getData('id_line_contrat')) {
                    if($child->getData('type') != 2) { // Exclude ligne libre (Juste ligne de commentaire)
                        $renta['hors_vente'][$child->getData('type')]['service'] = $child->displayData('type', 'default', true, true);
                        $renta['hors_vente'][$child->getData('type')]['vendu'] = 0;
                        $renta['hors_vente'][$child->getData('type')]['coup'] += $qty * $coup_technicien;
                        $duration = $child->getData('duree');
                        $time = $this->timestamp_to_time($duration);
                        $qty += $this->time_to_qty($time);

                    }

                }
            }

            foreach($renta as $title => $infos) {
                foreach($infos as $i) {
                    $total_vendu_commande += $i['vendu'];
                    $total_coup_commande += $i['cout'];
                }
            }

            $marge = ($total_vendu_commande - $total_coup_commande);

            $class = 'warning';
            $icone = 'arrow-right';

            if($marge > 0) {
                $class = 'success';
                $icone = 'arrow-up';
            } elseif($marge < 0) {
                $class = 'danger';
                $icone = 'arrow-down';
            }

            if($display) {
                $html = "<strong>"
                        . "Commande: <strong class='$class' >" . BimpRender::renderIcon($icone) . " " . price($marge) . "€</strong>"
                        . "</strong>";
                //$html .= '<pre>' . print_r($renta, 1) . '</pre>';
                return $html;
            } else {

            }

            return 0;
        } else {
            return BimpRender::renderAlerts("Calcule de la rentabilitée sur les anciennes FI en attente", "danger", false);
        }
      
    }
    
    public function time_to_qty($time) {
        $timeArr = explode(':', $time);
        if (count($timeArr) == 3) {
                $decTime = ($timeArr[0]*60) + ($timeArr[1]) + ($timeArr[2]/60);		
        } else if (count($timeArr) == 2) {
                $decTime = ($timeArr[0]) + ($timeArr[1]/60);
        } else if (count($timeArr) == 2) {
                $decTime = $time;	
        }
        return $decTime;
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
    
    public function getListFilterDefault(){
        return Array(
          Array(
              'name' => 'fk_contrat',
              'filter' => $_REQUEST['id']
          )
        );
    }
    
    public function getListFilterAll() {
        return Array(
            Array(
                'name' => 'new_fi',
                'filter' => 1
            )
        );
    }
    
    public function getListFilterHistorique() {
        return Array(
            Array(
                'name' => 'new_fi',
                'filter' => 0
            )
        );
    }
    
    public function getListFilterHistoriqueUser() {
        global $user;
        if(isset($_REQUEST['specialTech']) && $_REQUEST['specialTech'] > 0)
            $userId = $_REQUEST['specialTech'];
        else
            $userId = $user->id;
        return Array(
            Array(
                'name' => 'new_fi',
                'filter' => 0
            ),
            Array(
                'name' => 'fk_user_author',
                'filter' => $userId
            ),
        );
    }


    public function getListFilterTech() {
        global $user;
        if(isset($_REQUEST['specialTech']) && $_REQUEST['specialTech'] > 0)
            $userId = $_REQUEST['specialTech'];
        else
            $userId = $user->id;
        return Array(
            Array(
                'name' => 'fk_user_author',
                'filter' => $userId
            ),
            Array(
                'name' => 'new_fi',
                'filter' => 1
            )
        );
    }
    
    public function getNextNumRef($soc) {
        return $this->dol_object->getNextNumRef($soc);
    }
    
    public function canDelete() {
        
        if(($this->getData('fk_statut') == 0) && $this->getData('fk_user_author') == $this->global_user->id && !$this->isOldFi()) {
           return 1;
        }
        
        return 0;
    }
    
    public function canEdit() {
        if(!$this->isOldFi()) {
            return 1;
        }
        return 0;
    }

    public function getActionsButtons() {
        global $conf, $langs, $user;
        $buttons = Array();
        $statut = $this->getData('fk_statut');
        
        if(!$this->isOldFi()) {
            $buttons[] = array(
            'label' => 'Générer le PDF',
            'icon' => 'fas_file-pdf',
            'onclick' => $this->getJsActionOnclick('generatePdf', array(), array())
        );
        
        
        if($statut != self::STATUT_VALIDER) {
            if($statut == self::STATUT_BROUILLON) {
                $buttons[] = array(
                    'label' => 'Lier une ou plusieur commandes client',
                    'icon' => 'link',
                    'onclick' => $this->getJsActionOnclick('linked_commande_client', array(), array(
                        'form_name' => 'linked_commande_client'
                    ))
                );
                $buttons[] = array(
                    'label' => 'Lier un ou plusieur tickets support',
                    'icon' => 'link',
                    'onclick' => $this->getJsActionOnclick('linked_ticket_client', array(), array(
                        'form_name' => 'linked_ticket_client'
                    ))
                );
                if(!$this->getData('fk_contrat')) {
                    $buttons[] = array(
                        'label' => 'Lier un contrat client',
                        'icon' => 'link',
                        'onclick' => $this->getJsActionOnclick('linked_contrat_client', array(), array(
                            'form_name' => 'linked_contrat_client'
                        ))
                    );
                }
            }

            if($statut == self::STATUT_BROUILLON) {
                $buttons[] = array(
                    'label' => 'Ajouter une ligne',
                    'icon' => 'fas_plus',
                    'onclick' => $this->getJsActionOnclick('addInter', array(), array(
                        'form_name' => 'addInter'
                    ))
                );
            }

            }

            if($statut == self::STATUT_VALIDER) {
                $buttons[] = array(
                    'label' => "Fiche d'intervention facturable",
                    'icon' => 'euro',
                    'onclick' => $this->getJsActionOnclick('sendFacturation', array(), array(
                    ))
                );
            }
        }
        

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
            
            if($duration >= 60) {
                $desc = $value['inter_' . $numeroInter . '_description'];
            
                $new->addline($user, $this->id, $desc, $date->getTimestamp(), $duration);
                $lastIdLine = $this->db->getMax('fichinterdet', 'rowid', 'fk_fichinter = ' . $this->id);
                $line = $this->getInstance('bimptechnique', 'BT_ficheInter_det', $lastIdLine);

                $exploded_service = explode("_", $value['inter_' . $numeroInter . '_service']);
                $field = 'id_line_' . $exploded_service[0];

                $line->updateField('type', $value['inter_' . $numeroInter . '_type']);

                if($value['inter_' . $numeroInter . '_type'] == 0) {
                    $line->updateField($field, $exploded_service[1]);
                }

                $mode = 0;
                $facture = 0;
                switch($value['inter_'.$numeroInter.'_type']) {
                    case 1:
                    case 2:
                    case 3:
                    case 4:
                        $mode = 0;
                        $facture = 0;
                        break;
                    case 0:
                        $facture = 1;
                        if($exploded_service[0] == "contrat") {
                            $mode = 1;
                        } elseif($exploded_service[0] == "commande") {
                            $mode = 2;
                        }
                        break;
                }

                $line->updateField('forfait', $mode);
                $line->updateField('facturable', $facture);
            } else {
                $errors[] = "Le temps renseigné ne semble pas correcte";
            }

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
        $array = explode(',', BimpCore::getConf('bimptechnique_ref_temps_passe'));
        $tp = [];
        foreach($array as $code) {
            $tp[$code] = "Temps passé de niveau " . substr($code, -1, 1);
        }
        foreach($allCommandes as $id) {
            $commande->fetch($id);
            foreach ($commande->lines as $line){
                if($line->product_type == 1) {
                    $product->fetch($line->fk_product);
                    if($product->getRef() == BimpCore::getConf("bimptechnique_ref_deplacement")) {
                        $tp[$product->getRef()] = "Déplacement";
                    }
                    if(array_key_exists($product->getData('ref'), $tp)) {
                        $services['commande_' . $line->id] = $tp[$product->getRef()] . ' - <b>'.$commande->ref.'</b>';
                    } else {
                        $services['commande_' . $line->id] = $product->getRef() . ' - <b>'.$commande->ref.'</b>';
                    }
                    
                }
            }
            
        }
        
        if($this->getData('fk_contrat')) {
            $contrat = $this->getInstance('bimpcontract', 'BContract_contrat', $this->getData('fk_contrat'));
            foreach($contrat->dol_object->lines as $line) {
                $child = $contrat->getChildObject('lines', $line->id);
                if($child->getData('product_type') == 1) {
                    $product->fetch($line->fk_product);
                    $services['contrat_'.$line->id] = 'Intervention sous contrat - <strong>'.$contrat->getRef().'</strong> - ' . $line->description;
                }
            }
        }
        
       
        return $services;
    }

    public function haveContratLinked() {
        if($this->getData('fk_contrat'))
            return 1;
        return 0;
    }
    
    public function IsBrouillon() {
        if($this->getData('fk_statut') == self::STATUT_BROUILLON) {
            return 1;
        }
        return 0;
    }
    
    public function displayLinkedContratCard() {
        $html = "";
        
        if($this->haveContratLinked()) {
            $contrat = $this->getInstance('bimpcontract', 'BContract_contrat', $this->getData('fk_contrat'));
            $card = new BC_Card($contrat);
            $html .= $card->renderHtml();
            if($this->IsBrouillon()) {
                $html .= '<button class="btn btn-default" onclick="'.$this->getJsActionOnclick("unlinked_contrat_client", ['id_contrat' => $contrat->id]).'" >'.BimpRender::renderIcon('unlink').' Dé-lier le contrat '.$contrat->getData('ref').'</button>';
            }
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
                
                $html .= '<hr>';
                
                if($ticket->getData('sujet')) {
                    $html .= '<u><strong>';
                    $html .= 'Contenu du ticket';
                    $html .= '</strong></u><br />';
                    $html .= "<strong style='margin-left:10px'>".$ticket->getData('sujet')."</strong><br />";
                }
                
                if($this->IsBrouillon()) {
                    $html .= '<button class="btn btn-default" onclick="'.$this->getJsActionOnclick("unlinked_ticket_client", ['id_ticket' => $id]).'" >'.BimpRender::renderIcon('unlink').' Dé-lier le ticket '.$ticket->getRef().' </button>';
                }
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
                
                $html .= '<hr>';
                $html .= '<u><strong>';
                $html .= 'Contenu de la commande';
                $html .= '</strong></u><br />';
                
                
                $commandeAchanger = new Commande($this->db->db);
                $commandeAchanger->fetch($id);
                foreach($commandeAchanger->lines as $line) {
                    $service = $this->getInstance('bimpcore', 'Bimp_Product', $line->fk_product);
                    $html .= "- <strong style='color:#EF7D00;'>".$service->getRef()."</strong><stronng> - (".price($line->total_ht)."€ HT / ".price($line->total_ttc)."€ TTC)</strong>";
                    if($line->description)  {
                        $html .= "<br /><strong style='margin-left:10px'>".$line->description."</strong><br />";
                    } elseif($service->getData('description')) {
                        $html .= "<br /><strong style='margin-left:10px'>".$service->getData('description')."</strong><br />";
                    } else {
                        $html .= '<br />';
                    }
                }
                
                if($this->IsBrouillon() && !$this->isOldFi()) {
                    $html .= '<button class="btn btn-default" onclick="'.$this->getJsActionOnclick("unlinked_commande_client", ['id_commande' => $id]).'" >'.BimpRender::renderIcon('unlink').' Dé-lier la commande '.$commande->getData('ref').' </button>';
                }
                $html .= '<hr>';
            }
        } else {
            $html .= BimpRender::renderAlerts("Il n'y à pas de commandes liées sur cette fiche d'intervention", "info", false);
        }
        
        
        return $html;
    }
    
    public function renderSignatureTab() {
        $html = "";
        if(!$this->isOldFi()) {
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
            } elseif($this->isSign()) {
                $html .= '<h3>Nom du signataire client: '.$this->displayDataTyped($this->getData('signataire')).'</h3>';
                if($this->haveSignatureElectronique()) {
                    $html .= '<h3>Type de signature: '.$this->displayDataTyped("Signature électronique").'</h3>';
                } elseif($this->haveSignaturePapier()) {
                    $html .= '<h3>Type de signature: '.$this->displayDataTyped("Signature papier").'</h3>';
                }
                global $conf;
                    $file =  DOL_URL_ROOT . "/document.php?modulepart=ficheinter&file=" . $this->getRef() . "/" . $this->getRef() . '.pdf';
                    $html .= '<embed src="'.$file.'" type="application/pdf"   height="1000px" width="100%">';
            }
        } else {
            $html .= "<center><h3>Cette <strong style='color:#EF7D00' >Fiche d'intervention</strong> est une ancienne <strong style='color:#EF7D00' >version</strong></h3></center>";
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
    
    public function getContratClient() {
        
        $contrats = [];
        $fk_contrat = ($this->getData('fk_contrat')) ? $this->getData('fk_contrat') : 0;
        
        $contrat = $this->getInstance('bimpcontract', 'BContract_contrat');
        $liste = $contrat->getList(['statut' => 11, 'fk_soc' => $this->getData('fk_soc')]);
        if(count($liste) > 0) {
            foreach($liste as $index => $infos) {
                $contrat->fetch($infos['rowid']);
                $statut = $contrat->getData('statut');
                $display_statut = "<strong>";
                $display_statut.= BContract_contrat::$status_list[$statut]['label'] . "</strong>";
                $add_label = "";
                if($contrat->getData('label')) {
                    $add_label = " - " . $contrat->getData('label');
                }
                $contrats[$contrat->id] = $contrat->getRef() . " (".$display_statut.")" . $add_label;
            }
        }
        
        return $contrats;
        
    }
    
    public function getCommandeClient() {
        
        $commandes = [];
        $my_commandes = ($this->getData('commandes')) ? json_decode($this->getData('commandes')) : [];
        $excludeStatut = 3;
        $commande = $this->getInstance('bimpcommercial', 'Bimp_Commande');
        $search_commandes = $commande->getList(['fk_soc' => $this->getData('fk_soc')]);

        
        foreach($search_commandes as $index => $infos) {
            if(!in_array($infos['rowid'], $my_commandes)) {
                $commande->fetch($infos['rowid']);
                $statut = $commande->getData('fk_statut');
                if(BimpTools::getPostFieldValue('afficher_clos') && BimpTools::getPostFieldValue('afficher_clos') == 1) {
                    $excludeStatut = null;
                }
                
                if($statut !== $excludeStatut || is_null($excludeStatut)) {
                    $display_statut = "<strong class='".Bimp_Commande::$status_list[$statut]['classes'][0]."' >";
                    $display_statut.= BimpRender::renderIcon(Bimp_Commande::$status_list[$statut]['icon']);
                    $display_statut.= " " . Bimp_Commande::$status_list[$statut]['label'] . "</strong>";

                    $add_libelle = "";
                    if($commande->getdata('libelle')) {
                        $add_libelle = " - " . $commande->getData('libelle');
                    }
                    $commandes[$commande->id] = $commande->getRef() . " (".$display_statut.")" . $add_libelle;
                }
            }
        }
        
        return $commandes;
        
    }
    
    public function getTicketClient() {
        $tickets = [];
        $my_tickets = ($this->getData('tickets')) ? json_decode($this->getData('tickets')) : [];
        $excludeStatut = 999;
        $ticket = $this->getInstance('bimpsupport', 'BS_Ticket');
        $search_tickets = $ticket->getList(['id_client' => $this->getData('fk_soc')]);
        
        
        foreach($search_tickets as $index => $infos) {
            if(!in_array($infos['id'], $my_tickets)) {
                $ticket->fetch($infos['id']);
                $statut = $ticket->getData('status');
                
                if(BimpTools::getPostFieldValue('afficher_clos') && BimpTools::getPostFieldValue('afficher_clos') == 1) {
                    $excludeStatut = null;
                }
                if($statut !== $excludeStatut || is_null($excludeStatut)) {
                    $display_statut = " <strong class='". BS_Ticket::$status_list[$statut]['classes'][0]."' >";
                    $display_statut.= BimpRender::renderIcon(BS_Ticket::$status_list[$statut]['icon']);
                    $display_statut.= " " . BS_Ticket::$status_list[$statut]['label'] . "</strong>";
                    $tickets[$ticket->id] = $ticket->getRef() . " (".$display_statut.") <br /><small style='margin-left:10px'>" . $ticket->getData('sujet') . '</small>' ;
                }
            }
        }
        
        return $tickets;
    }
    
    public function actionUnLinked_ticket_client($data, &$success) {
        
        $errors = [];
        $warnings = [];
        $new_tickets = [];
        $my_tickets = json_decode($this->getData('tickets'));
        
        if(!in_array($my_tickets, $data['id_ticket'])) {
            foreach($my_tickets as $id_current_ticket) {
                if($id_current_ticket != $data['id_ticket']) {
                    $new_tickets[] = $id_current_ticket;
                }
            }
            $errors = $this->updateField('tickets', json_encode($new_tickets));
        } else {
            $errors[] = "Vous ne pouvez pas dé-lier un ticket qui n'apparait pas sur cette fiche d'intervention";
        }
        
        if(!count($errors)) {
            $success = "Ticket support dé-lié avec succès";
        }
        
        return [
            'success' => $success,
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
    
    public function actionUnLinked_contrat_client($data, &$success) {
        $errors = [];
        $warnings = [];
        $new_commandes = [];
        
        $inter_on_the_contrat = false;
        
        if($data['id_contrat'] == $this->getData('fk_contrat')) {
            $contrat = $this->getInstance('bimpcontract', 'BContract_contrat', $this->getData('fk_contrat'));
            $children = $this->getChildrenList('inters');
            $children_contrat = $contrat->getChildrenList('lines');

            if(count($children) > 0) {
                foreach($children as $is_child) {
                    $child = $this->getChildObject('inters', $id_child);
                    foreach($children_contrat as $id_child_contrat) {
                        $child_contrat = $contrat->getChildObject('lines', $id_child_contrat);
                        if($child_contrat->getData('id_lin e_contrat') == $child->id) {
                            $inter_on_the_contrat = true;
                        }
                    }
                }
            }
            if(!$inter_on_the_contrat) {
                $errors = $this->updateField('fk_contrat', null);
            } else {
                $errors[] = "Vous ne pouvez aps dé-lier ce contrat car une intervention est faite avec un code service de contrat sur cette fiche d'intervention";
            }
            
            if(!count($errors)) {
                $success = "Contrat dé-lié avec succès";
            }
        } else {
            $errors[] = "Vous ne pouvez pas dé-lié un contrat qui n'est pas lié à cette fiche";
        }
        
        return [
            'success' => $success,
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
    
    public function actionUnlinked_commande_client($data, &$success) {
        $errors = [];
        $warnings = [];
        $new_commandes = [];
        $my_commandes = json_decode($this->getData('commandes'));
        
        $inter_on_the_commande = false;
        
        if(!in_array($my_commandes, $data['id_commande'])) {            
            
            
            // Vérification si pas d'inter avec cette commande
            $commande = new Commande($this->db->db); $commande->fetch($data['id_commande']);
            $lines = $this->getChildrenList("inters");
            foreach($commande->lines as $line) {
                if(count($lines) > 0) {
                    foreach($lines as $id_line_fi) {
                        $child = $this->getChildObject('inters', $id_line_fi);
                        if($line->id == $child->getData('id_line_commande')) {
                            $inter_on_the_commande = true;
                        }
                    }
                }
            }

            if(count($my_commandes) > 0 && !count($errors)) {
                foreach($my_commandes as $id) {
                    if($id != $data['id_commande']) {
                        $new_commandes[] = $id;
                    }
                }
            }
            
            if($inter_on_the_commande) {
                $errors[] = "Cette commande ne peut être dé-liée car il existe une intervention de cette fiche sur cette commande";
            }
            
            if(!count($errors)) {
                $errors = $this->updateField('commandes', json_encode($new_commandes));
            }

            if(!count($errors)) {
                $success = "Commande dé-liée avec succès";
            }
            
        } else {
            $errors[] = "Vous ne pouvez pas dé-lier une commande qui ne figure pas sur la FI";
        }
        
        return [
            'success' => $success,
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
    
    public function actionLinked_contrat_client($data, &$success) {
        $errors = [];
        $warnings = [];
        
        if($data['linked']) {            
            if($this->getData('fk_contrat')) {
                $errors[] = "Il y à déjà un contrat lié a cette fiche";
            } else {
                $this->updateField('fk_contrat', $data['linked']);
            }
            if(!count($errors)) {
                $success = "Contrat lié avec succès";
            }
            
        } else {
            $warnings[] = "Il n'y à pas de contrat à lié";
        }
        
        return [
            'success' => $success,
            'errors' => $errors,
            'warnings' => $warnings
        ];

    }
    
    public function actionLinked_commande_client($data, &$success) {
        
        $errors = [];
        $warnings = [];
        
        if($data['linked']) {
            $my_commandes = json_decode($this->getData('commandes'));
            
            foreach($data['linked'] as $id) {
                $my_commandes[] = $id;
            }

            $errors = $this->updateField('commandes', json_encode($my_commandes));

            if(!count($errors)) {
                $success = 'Commande liée avec succès';
            }
        } else {
            $warnings[] = "Il n'y à pas de commande à liée";
        }

        return [
            'success' => $success,
            'errors' => $errors,
            'warnings' => $warnings
        ];

    } 
    
    public function actionLinked_ticket_client($data, &$success) {
        $errors = [];
        $warnings = [];
        
        if($data['linked']) {
            $my_tickets = json_decode($this->getData('tickets'));
            foreach($data['linked'] as $id) {
                $my_tickets[] = $id;
            }
            
            $errors = $this->updateField('tickets', json_encode($my_tickets));
            if(!count($errors)) {
                $success = "Ticket lié avec succès";
            }
        } else {
            $warnings[] = "Il n'y à pas de tickets support lié";
        }
        
        return [
            'success' => $success,
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
    
    public function displayNombreInters() {
        
        return count($this->getChildrenObjects('inters'));
        
    }

    
}
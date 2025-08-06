<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

class AlertProduit extends BimpObject
{

    // Type de pièce
    const TYPE_DEVIS = 0;
    const TYPE_FACTURE = 1;
    const TYPE_COMMANDE = 2;
    const TYPE_CONTRAT = 3;

    static $warnings = array();
    static $errors = array();

    const TYPE_DEVIS_LIGNE = 101;
    const TYPE_COMMANDE_LIGNE = 201;

    public static $type_piece = Array(
        self::TYPE_DEVIS       => Array('label' => 'Devis', 'icon' => 'fas_file-invoice', 'module' => 'bimpcommercial', 'obj_name' => 'Bimp_Propal', 'table' => 'propal'),
        self::TYPE_DEVIS_LIGNE => Array('label' => 'Ligne de Devis', 'icon' => 'fas_file-invoice', 'module' => 'bimpcommercial', 'obj_name' => 'Bimp_PropalLine', 'table' => 'propaldet'),
        self::TYPE_FACTURE     => Array('label' => 'Facture', 'icon' => 'fas_file-invoice-dollar', 'module' => 'bimpcommercial', 'obj_name' => 'Bimp_Facture', 'table' => 'facture'),
        self::TYPE_COMMANDE    => Array('label' => 'Commande', 'icon' => 'fas_dolly', 'module' => 'bimpcommercial', 'obj_name' => 'Bimp_Commande', 'table' => 'commande'),
		self::TYPE_COMMANDE_LIGNE => Array('label' => 'Ligne de Commande', 'icon' => 'fas_file-invoice', 'module' => 'bimpcommercial', 'obj_name' => 'Bimp_CommandeLine', 'table' => 'commandedet'),
		self::TYPE_CONTRAT     => Array('label' => 'Contrat', 'icon' => 'fas_retweet', 'module' => 'bimpcontract', 'obj_name' => 'BContract_contrat', 'table' => 'contrat'),
    );

    // Type de pièce
    const ACTION_CREATE = 'CREATE';
    const ACTION_VALIDATE = 'VALIDATE';
    const ACTION_UNVALIDATE = 'UNVALIDATE';
    const ACTION_DELETE = 'DELETE';
	const  ACTION_CREATEUPDATE = 'CREATEUPDATE';

    public static $type_action = Array(
        self::ACTION_CREATE     => Array('label' => 'Création', 'classes' => array('info'), 'icon' => 'fas_plus'),
        self::ACTION_CREATEUPDATE     => Array('label' => 'Création / Mise à jour', 'classes' => array('info'), 'icon' => 'fas_pencil-alt'),
        self::ACTION_VALIDATE   => Array('label' => 'Validation', 'classes' => array('success'), 'icon' => 'fas_check'),
        self::ACTION_UNVALIDATE => Array('label' => 'Dévalidation', 'classes' => array('danger'), 'icon' => 'fas_undo'),
        self::ACTION_DELETE     => Array('label' => 'Suppression', 'classes' => array('danger'), 'icon' => 'fas_trash'),
    );
    public static $type_notif = array(
        0 => 'Message',
        1 => 'Warnings',
        2 => 'Erreur'
    );

    // charge toutes les alerte active de ce type d'objet et ce type de trigger
    // et appel traiteAlerte sur chaque instance
    public static function traiteAlertes($object, $name_trigger, $errors, $warnings)
    {
        $id_type = null;
        foreach (self::$type_piece as $k => $t) {
            if ($t['obj_name'] == $object->object_name)
                $id_type = $k;
        }


        if (isset($id_type)) {
            $alerts = BimpCache::getBimpObjectObjects('bimpalert', 'AlertProduit', array('type_piece' => $id_type, array('custom' => 'type_action LIKE \'%'.$name_trigger.'%\'') /**/));

            foreach ($alerts as $a) {
                $a->traiteAlerte($object, $errors, $warnings);
            }
        }

        return 1;
    }

    // Appel isObjectQualified($object) si oui créer un note sur l'objet en question
    public function traiteAlerte($object, &$errors = array(), &$warnings = array())
    {
        if (!(int) $this->getData('active')) {
            return;
        }
        if ($this->isObjectQualified($object)) {
            if ($this->getData('type_notif') == 0) {
				$objectForMessage = $object;
				if ($this->getData('msgOnParent')) $objectForMessage = $object->getParentInstance();
				$this->traitement_variable_substitution($object, $errors, $warnings);
				$this->sendMessage($objectForMessage, $errors, $warnings);
            } else {
                $this->sendAlert($errors, $warnings);
            }
        }
    }

    // qui test est renvoie vrai ou faux
    public function isObjectQualified($object)
    {

        $filtre = $this->getData('filtre_piece');

		if($this->getData('searchByCallback') && method_exists($this, $this->getData('callbackFunction'))) {
			$nomFonction = $this->getData('callbackFunction');
			return $this->$nomFonction($object);
		}

        if (!isset($filtre[$object->getPrimary()]['values'])) {
            $filtre[$object->getPrimary()]['values'] = array();
        }

        $filtre[$object->getPrimary()]['values'][] = array(
            'value'     => $object->id,
            'part_type' => 'full'
        );
//        echo get_class($object).'<pre>';
//        print_r($filtre);die;
        return count(BC_FiltersPanel::getObjectListIdsFromFilters($object, $filtre));
    }

    public function getObjectInfo($key)
    {
        $type = $this->getData('type_piece');

        if (BimpTools::isSubmit('type_piece')) {
            $type = BimpTools::getValue('type_piece', '', 'alphanohtml');
        }

        return self::$type_piece[$type][$key];
    }

    public function sendMessage($object, &$errors = array(), &$warnings = array())
    {
		$notes = array();
		if ($this->getData('checkIfNoteNonLue')) {    // avant d'envoyer le message, on verifie si il n'y a pas de message non lu contenant le message de notification (bimpcore_note)
			$notes = $object->getNotes();
		}

		BimpObject::loadClass('bimpcore', 'BimpNote');
		$id_users = array();

        // Création des notes user
        foreach ($this->getData('notified_user') as $id_user) {
			$id_users[] = $id_user;
			if($this->envoiOK($notes, $id_user))
	            $object->addNote($this->getData('message_notif'),
                             BimpNote::BN_MEMBERS, 0, 1, '', BimpNote::BN_AUTHOR_USER,
                             BimpNote::BN_DEST_USER, 0, (int) $id_user);
        }

        // Création des notes Group
        foreach ($this->getData('notified_group') as $id_group) {
			if($this->envoiOK($notes, $id_group, BimpNote::BN_DEST_GROUP))
				$object->addNote($this->getData('message_notif'),
                             BimpNote::BN_MEMBERS, 0, 1, '', BimpNote::BN_AUTHOR_USER,
                             BimpNote::BN_DEST_GROUP, (int) $id_group, 0);
        }

		// msgToCreator: Notifier le créateur de la pièce
		 // exit();
		if ($this->getData('msgToCreator')) {
			$id_user = $object->getData('fk_user_author');
			if ($id_user && !in_array($id_user, $id_users)) {
				$id_users[] = $id_user;
				if($this->envoiOK($notes, $id_user))
					$object->addNote($this->getData('message_notif'),
								BimpNote::BN_MEMBERS, 0, 1, '', BimpNote::BN_AUTHOR_USER,
								BimpNote::BN_DEST_USER, 0, (int) $id_user);
			}
		}

		// msgToCommClient: Notifier le commercial client
		if ($this->getData('msgToCommClient')) {
			$fk_soc = $object->getData('fk_soc');
			$soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $fk_soc);
			$com = $soc->getCommercial(false);

			if ($com->id && !in_array($com->id, $id_users)) {
				$id_users[] = $com->id;
				if($this->envoiOK($notes, $com->id))
					$object->addNote($this->getData('message_notif'),
								BimpNote::BN_MEMBERS, 0, 1, '', BimpNote::BN_AUTHOR_USER,
								BimpNote::BN_DEST_USER, 0, (int) $com->id);
			}
		}
    }

    public function sendAlert(&$errors = array(), &$warnings = array())
    {
        BimpObject::loadClass('bimpcore', 'BimpNote');

        if ($this->getData('type_notif') == 1)
            static::$warnings[] = $this->getData('message_notif');
        elseif ($this->getData('type_notif') == 2)
            static::$errors[] = $this->getData('message_notif');
    }

    public static function getAlertes(&$errors, &$warnings)
    {
        $errors = BimpTools::merge_array($errors, static::$errors);
        $warnings = BimpTools::merge_array($warnings, static::$warnings);
    }

	private function traitement_variable_substitution($object, &$errors, &$warnings) {
		$msg = $this->getData('message_notif');
//		echo '<pre>' . print_r($object->getDataArray(true), true) . '</pre>';
		if (strstr($msg, '__REF_PRO__') !== false)	{
			if ($object->module == 'bimpcommercial' && $object->object_name = 'Bimp_Commande')	{
				$req = $this->db->getRow('commandedet', 'rowid = ' .$object->getData('id_line'), array('fk_product'));
				if ($idProduct = $req->fk_product)	{
					$prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $idProduct);
					if (BimpObject::objectLoaded($prod)) {
						$msg = str_replace('__REF_PRO__', $prod->getRef(), $msg);
					}
				}
			}
		}
		if (strstr($msg, '__QTY_MODIF__') !== false)	{
			$q = $object->getData('qty_modif');
			$s = '';
			if(abs($q)>1)	$s = 's';
			if ($q < 0)	$replace = abs($q) . ' pièce'.$s.' retirée'.$s;
			else		$replace = abs($q) . ' pièce'.$s.' ajoutée'.$s;
			$msg = str_replace('__QTY_MODIF__', $replace, $msg);
			$msg = str_replace('__QTY_MODIF__', $replace, $msg);
		}

		$this->set('message_notif', $msg);
	}

	public function isQuantityModifLogisitiq($object)
	{
		$is_qty_modif = $object->getData('qty_modif') != 0;
		$is_prod_serv = $object->getData('type') == 1;

		if( $is_qty_modif && $is_prod_serv ) {
			$req = $this->db->getRow('commandedet', 'rowid = ' . $object->getData('id_line'), array('fk_product'));
			if ($req) {
				$prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $req->fk_product);
				if (BimpObject::objectLoaded($prod)) {
					$fk_product_type = $prod->getData('fk_product_type');
					if ($fk_product_type == 0) {
						return true;
					}
				}
			}

		}
		return false;
	}

	private function envoiOK($notes, $id_user, $type_dest = BimpNote::BN_DEST_USER) {
		global $user;
		if($type_dest == BimpNote::BN_DEST_USER) {
			if($user->id == $id_user) return false; // Pas de note a l'utilisateur connecté
			$bimp_user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user);
			if (BimpObject::objectLoaded($bimp_user)) {
				if (!(int) $bimp_user->getData('statut')) {
					return false;
				}
			}
		}

		$envoiOK = true;
		$pattern = explode("\n", $this->getData('message_notif'))[0];
		foreach ($notes as $note) {
			$c = $note->getData('content');
			if($type_dest == BimpNote::BN_DEST_USER) {
				if ((strstr($c, $pattern) !== false) && $note->getData('viewed') == 0 && $note->getData('fk_user_dest') == $id_user) {
					$envoiOK = false;
				}
			}
			elseif ($type_dest == BimpNote::BN_DEST_GROUP) {
				if ((strstr($c, $pattern) !== false) && $note->getData('viewed') == 0 && $note->getData('fk_group_dest') == $id_user) {
					$envoiOK = false;
				}
			}
		}
		return $envoiOK;
	}
}

<?php

class Bimp_Entrepot extends BimpObject
{
    public $redirectMode = 4; //5;//1 btn dans les deux cas   2// btn old vers new   3//btn new vers old   //4 auto old vers new //5 auto new vers old

    public static $status_list = array(
        0 => array('label' => 'Désactivé', 'icon' => 'fas_times', 'classes' => array('danger')),
        1 => array('label' => 'Actif', 'icon' => 'fas_check', 'classes' => array('success')),
        2 => array('label' => 'Actif (en interne seulement)', 'icon' => 'fas_exclamation', 'classes' => array('warning'))
    );

    // Droits users:

    public function canCreate()
    {
        global $user;
        return (isset($user->rights->stock->creer) && $user->rights->stock->creer);
    }

    public function canDelete()
    {
        global $user;
        return (isset($user->rights->stock->supprimer) && $user->rights->stock->supprimer);
    }

//    public function iAmAdminRedirect()
//    {
//        return $this->canEdit();
//    }

    // Getters:

    public function getNameProperties()
    {
        return array('lieu');
    }

    public function getMail()
    {
        if(BimpCore::getExtendsEntity() == 'bimp'){
            $domaine = 'bimp.fr';
            $nbCaracdeps = array(3, 2);
            foreach ($nbCaracdeps as $nbCaracdep) {
                $dep = substr($this->getData('zip'), 0, $nbCaracdep);
                $name = 'boutique' . $dep;
                $sql = $this->db->db->query('SELECT mail FROM `llx_usergroup` u, llx_usergroup_extrafields ue WHERE ue.fk_object = u.rowid AND u.nom LIKE "' . $name . '"');
                while ($ln = $this->db->db->fetch_object($sql)) {
                    if ($ln->mail && $ln->mail != '' && stripos($ln->mail, "@") !== false)
                        return $ln->mail;
                    else {
                        require_once(DOL_DOCUMENT_ROOT . "/synopsistools/SynDiversFunction.php");
                        return str_replace(",", "", traiteCarac($name) . "@" . $domaine);
                    }
                }
            }
        }
    }

    // Affichages:

    public function displayFullAdress()
    {
        $html = '';

        if ($this->getData('address')) {
            $html .= $this->getData('address') . '<br/>';
        }

        if ($this->getData('zip')) {
            $html .= $this->getData('zip');

            if ($this->getData('town')) {
                $html .= ' ';
            }
        }

        $html .= $this->getData('town');

        return $html;
    }

    // Overrides:

    public function getDolObjectUpdateParams()
    {
        global $user;

        return array(
            ($this->isLoaded() ? (int) $this->id : 0),
            $user
        );
    }

	public function renderStocksView()
	{
		$html = '';

		$tabs = array();

		// Stocks par entrepôt:
		$tabs[] = array(
			'id'            => 'stocks_by_entrepots_tab',
			'title'         => BimpRender::renderIcon('fas_box-open', 'iconLeft') . 'Stocks par entrepôts',
			'ajax'          => 1,
			'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectsList', '$(\'#stocks_by_entrepots_tab .nav_tab_ajax_result\')', array('stocks_by_entrepots'), array('button' => ''))
		);

		// Mouvements de stock:
		$tabs[] = array(
			'id'            => 'stocks_mvts_tab',
			'title'         => BimpRender::renderIcon('fas_exchange-alt', 'iconLeft') . 'Mouvements de stock',
			'ajax'          => 1,
			'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectsList', '$(\'#stocks_mvts_tab .nav_tab_ajax_result\')', array('stocks_mvts'), array('button' => ''))
		);

		// Mouvements de stock d'équipements:
		$tabs[] = array(
			'id'            => 'stocks_equipment_tab',
			'title'         => BimpRender::renderIcon('fas_desktop', 'iconLeft') . 'Équipements en stock',
			'ajax'          => 1,
			'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectsList', '$(\'#stocks_equipment_tab .nav_tab_ajax_result\')', array('stocks_equipment'), array('button' => ''))
		);

		$html = BimpRender::renderNavTabs($tabs, 'stocks_view');

		return $html;
	}

	public function renderLinkedObjectsList($list_type)
	{
		global $conf;
		$errors = array();
		if (!$this->isLoaded($errors)) {
			return BimpRender::renderAlerts($errors);
		}

		$html = '';

		$list = null;
		$product_label = $this->getRef();

		switch ($list_type) {
			case 'stocks_by_entrepots':
				$list = new BC_ListTable(BimpObject::getInstance('bimpcommercial', 'Bimp_Product_Entrepot'), 'entrepot', 1, null, 'Stocks du produit "' . $product_label . '"', 'fas_box-open');
				$list->addFieldFilterValue('fk_entrepot', $this->id);
				break;

			case 'stocks_mvts':
				$list = new BC_ListTable(BimpObject::getInstance('bimpcore', 'BimpProductMouvement'), 'entrepot', 1, null, 'Mouvements stock du produit "' . $product_label . '"', 'fas_exchange-alt');
				$list->addFieldFilterValue('fk_entrepot', $this->id);
				break;

			case 'stocks_equipment':
				$list = new BC_ListTable(BimpObject::getInstance('bimpequipment', 'Equipment'), 'entrepot', 1, null, 'Equipements en stock du produit "' . $product_label . '"', 'fas_desktop');
					$list->addFieldFilterValue('epl.id_entrepot', $this->id);
				$list->addFieldFilterValue('epl.position', 1);
				$list->addFieldFilterValue('epl.type', BE_Place::BE_PLACE_ENTREPOT);
				$list->addJoin('be_equipment_place', 'a.id = epl.id_equipment', 'epl');

				break;


		}

		if (is_a($list, 'BC_ListTable')) {
			$html .= $list->renderHtml();
		} elseif ($list_type && !$html) {
			$html .= BimpRender::renderAlerts('La liste de type "' . $list_type . '" n\'existe pas');
		} elseif (!$html) {
			$html .= BimpRender::renderAlerts('Type de liste non spécifié');
		}

		return $html;
	}

	/*
	public function isValidate()
	{
		if (!(int) BimpCore::getConf('use_valid_product')) {
			return 1;
		}

		return $this->getData('validate');
	}
	*/

	public function isActionAllowed($action, &$errors = array())
	{
		switch ($action) {
			case 'CorrectionStock':
				return 1;
		}

		return (int) parent::isActionAllowed($action, $errors);
	}

	public function canSetAction($action)
	{
		global $user;
		switch ($action) {
			case 'CorrectionStock':
				return $user->rights->bimpcommercial->correct_stocks;
		}

		return (int) parent::canSetAction($action);
	}

	public function getActionsButtons()
	{
		// Boutons d'actions:
		$buttons = array();

		if ($this->isActionAllowed('CorrectionStock') && $this->canSetAction('CorrectionStock')) {
			$buttons['CorrectionStock'] = array(
				'label'   => 'Corriger le stock',
				'icon'    => 'fas_random',
				'onclick' => $this->getJsActionOnclick('CorrectionStock', array(
					'qty' => 1
				), array(
					'form_name' => 'CorrectionStock'
				))
			);
		}

		return $buttons;
	}

	// actions
	public function actionCorrectionStock($data, &$success)
	{
		global $user;
		$errors = array();
		$warnings = array();
		$success = '';

		$sens = BimpTools::getArrayValueFromPath($data, 'sens');
		$qty = BimpTools::getArrayValueFromPath($data, 'qty', 1);
		$id_product = BimpTools::getArrayValueFromPath($data, 'id_product');
		$comment = BimpTools::getArrayValueFromPath($data, 'comment');

		$product = BimpObject::getInstance('bimpcore', 'Bimp_Product', $id_product);
		$errors = $product->correctStocks($this->id, $qty, $sens, 'mouvement_manuel', $comment, 'user', $user->id);

		return array(
			'errors'   => $errors,
			'warnings' => $warnings
		);
	}
}

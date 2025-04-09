<?php

class BimpDictionnaryValue extends BimpObject
{
	public static $classes = array(
		''          => '',
		'info'      => array('label' => 'Information', 'classes' => array('info')),
		'warning'   => array('label' => 'Alerte', 'classes' => array('warning')),
		'danger'    => array('label' => 'Danger', 'classes' => array('danger')),
		'success'   => array('label' => 'Succès', 'classes' => array('success')),
		'important' => array('label' => 'Important', 'classes' => array('important'))
	);

	// Getters droits users :

	// Getters booléens:

	// Getters params:
	public function getCreateJsCallback()
	{
		$id_dictionary = (int) $this->getData('id_dict');

		if ($id_dictionary) {
			return 'onDictionnaryChange(' . $id_dictionary . ')';
		}

		return '';
	}

	public function getUpdateJsCallback() {
		return $this->getCreateJsCallback();
	}

	// Getters array:

	// Getters données:

	// Affichages:

	// Rendus HTML:

	// Traitements:

	// Actions:

	// Overrides:

}

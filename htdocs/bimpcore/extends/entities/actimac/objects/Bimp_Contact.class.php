<?php

class Bimp_Contact_ExtEntity extends Bimp_Contact
{
	public function checkPresTel()
	{
		$errors = array();
		$phone = BimpTools::getPostFieldValue('phone', '', 'alphanohtml');
		$phone_perso = BimpTools::getPostFieldValue('phone_perso', '', 'alphanohtml');
		$phone_mobile = BimpTools::getPostFieldValue('phone_mobile', '', 'alphanohtml');

		$client = $this->parent;
		if($client->isCompany() && empty($phone) && empty($phone_perso) && empty($phone_mobile)) {
			$errors[] = 'Merci de remplir au moins un des N° de Portable.';
		}
		return $errors;
	}

	public function create(&$warnings = array(), $force_create = false)
	{
		$errors = array();
		$errors = BimpTools::overrideArray($errors, $this->checkPresTel());

		if (!count($errors)) {
			$errors = parent::create($warnings, $force_create);
		}
		return $errors;
	}

	public function update(&$warnings = array(), $force_create = false)
	{
		$errors = array();
		$errors = BimpTools::overrideArray($errors, $this->checkPresTel());

		if (!count($errors)) {
			$errors = parent::update($warnings, $force_create);
		}
		return $errors;
	}
}

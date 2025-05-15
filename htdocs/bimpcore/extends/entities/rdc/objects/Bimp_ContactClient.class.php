<?php

class Bimp_ContactClient_ExtEntity extends Bimp_ContactClient	{
	public function getFlagImport()
	{
		$parent = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $this->getData('rowid'));
		if (BimpObject::objectLoaded($parent)) {
			return $parent->getFlagImport();
		}
		return '';
	}
}

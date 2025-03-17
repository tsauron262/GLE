<?php

class BimpCommElementElement extends BimpObject{
	function getObjectNameSource(){
		return $this->getObjectName($this->getData('sourcetype'));
	}
	function getObjectNameTarget(){
		return $this->getObjectName($this->getData('targettype'));
	}

	function getObjectName($name){
		switch ($name){
			case 'propal':
				return 'Bimp_Propal';
				break;
			case 'commande':
				return 'Bimp_Commande';
				break;
			case 'facture':
				return 'Bimp_Facture';
				break;
		}
	}
}

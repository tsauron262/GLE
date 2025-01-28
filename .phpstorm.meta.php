<?php
namespace PHPSTORM_META {

	override(\BimpCache::getBimpObjectInstance(0, 1, 2), map([
		'Bimp_User' => Bimp_User::class,
		'BIMP_Task' => \BIMP_Task::class,
		// Ajoutez d'autres mappings ici
	]));
}

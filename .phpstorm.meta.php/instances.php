<?php

namespace PHPSTORM_META {

	$instances = array(
		'Bimp_User' => \Bimp_User::class,
		'BIMP_Task' => \BIMP_Task::class
	);

	override(\BimpCache::getBimpObjectInstance(0, 1, 2), map($instances));
}

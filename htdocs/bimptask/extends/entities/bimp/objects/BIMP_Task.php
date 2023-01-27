<?php

// Entitié: bimp

require_once DOL_DOCUMENT_ROOT . '/bimptask/objects/BIMP_Task.class.php';

BIMP_Task::$valSrc = array(
    'task0001@bimp-groupe.net'           => 'Tâche test',
    'validationcommande@bimp-groupe.net' => "Validation commande",
    'Synchro-8SENS'                      => "Synchro-8SENS",
    'supportyesss@bimp-groupe.net'       => "Support YESS",
    'supportcogedim@bimp-groupe.net'     => "Support COGEDIM",
    'hotline@bimp-groupe.net'            => 'Hotline',
    'consoles@bimp-groupe.net'           => "Consoles",
    'licences@bimp-groupe.net'           => "Licences",
    'vols@bimp-groupe.net'               => "Vols",
    'sms-apple@bimp-groupe.net'          => "Code APPLE",
    'suivicontrat@bimp-groupe.net'       => "Suivi contrat",
    'facturation'                        => "Facturation",
    'dispatch@bimp.fr'                   => 'Dispatch',
    'suivicontrat@bimp.fr'               => 'Suivi contrat',
    'other'                              => 'Autre'
);

class BIMP_Task_ExtEntity extends BIMP_Task
{
    
}

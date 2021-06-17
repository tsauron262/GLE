<?php

/**
 *      \file       /htdocs/bimpgroupmanager/class/BimpGroupManager.class.php
 *      \ingroup    bimpgroupmanager
 *      \brief      Make interface between the class and the client
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/bimpgroupmanager/class/BimpGroupManager.class.php';

$staticGM = new BimpGroupManager($db);

switch (GETPOST('action')) {
    case 'getOldGroup': {
            $groups = $staticGM->getOldGroup();
            echo json_encode($groups);
            break;
        }
    case 'updateGroup': {
            $staticGM->updateGroup(GETPOST('groupId'), GETPOST('newGroupId'));
            break;
        }
    case 'setAllUsers': {
            if (defined('BIMP_LIB')) {
                BimpCore::setMaxExecutionTime(1200);
            } else {
                ini_set('max_execution_time', 1200);
            }
            $staticGM->setAllUsers();
            break;
        }
    default: break;
}


$db->close();

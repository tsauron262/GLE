<?php


define("NOLOGIN", 1);  // This means this output page does not require to be logged.
require_once("../../main.inc.php");


$action = BimpTools::getPostFieldValue('action', '');


if($action == 'confirmGrp'){
    $grp = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_UserGroup', BimpTools::getPostFieldValue('id', 0, 'int'));
    if(!$grp->isLoaded()){
        $errors[] = 'Groupe non valide';
    }
    else{
        $data = $grp->getData('data_revue');
        if(!isset($data['Y:'.date('Y')]))
            $errors[] = 'Pas de revue pour cette année';
        elseif(!isset($data['Y:'.date('Y')]['code']))
            $errors[] = 'Pas de code';
        elseif($data['Y:'.date('Y')]['code'] != BimpTools::getPostFieldValue('code'))
            $errors[] = 'Code incorrect';
        elseif(isset($data['Y:'.date('Y')]['validation_date']))
            $errors[] = 'Validation deja effectuée';
        else{
            $data['Y:'.date('Y')]['validation_date'] = date('Y-m-d');
            $errors = BimpTools::merge_array($errors, $grp->updateField('data_revue', $data));
            $success = 'Validation pris en compte';
        }
    }
}

if(count($errors))
    echo BimpRender::renderAlerts($errors);
else
    echo $success;


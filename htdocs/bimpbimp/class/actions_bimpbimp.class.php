<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of action_synopsisapple
 *
 * @author tijean
 */
class ActionsBimpbimp {
    
    
    function formObjectOptions($parameters, &$object, &$action, $hookmanager){
        global $langs;
        if($object->element == "contact")
		echo '<script>'. file_get_contents(DOL_DOCUMENT_ROOT."/bimpbimp/js/contact.js").'</script>';
    }
    
    function sendMail($parameters, &$object, &$action, $hookmanager){
        global $db;
        $tabDomainValid = array('bimp.fr', 'ldlc.com', 'bimp-groupe.net');
        if ($object->sendmode == 'smtps' && !in_array($object->smtps->getFrom('host'), $tabDomainValid))
        {
            $add = $object->smtps->getFrom('user')."@".$object->smtps->getFrom('host');
            if($add != '' && stripos($add, '@') !== false){
                $sql = $db->query("SELECT u.email, ue.alias FROM `llx_user` u, llx_user_extrafields ue WHERE u.rowid = ue.fk_object  
    AND (u.email = '".$add."' || ue.alias LIKE '%".$add."%')");
                while($ln = $db->fetch_object($sql)){
                    $mails = explode(",", $ln->alias);
                    $mails[] = $ln->email;
                    foreach($mails as $mailT){
                        $tabT = explode("@", $mailT);
                        if(isset($tabT[1])){
                            $domaine = $tabT[1];
                            if(in_array($domaine, $tabDomainValid)){
    //                            $object->smtps->setFrom('user') = $tabT[0];
    //                            $object->smtps->setFrom('host') = $tabT[1];

                                $object->smtps->setFrom($mailT);
                                $object->smtps->setReplyTo($mailT);
                                return 0;
                            }
                        }
                    }
                }
            }
            
            $this->error = "Adresse d'envoie non bimp ".$add." !";
            dol_syslog("Tentative d'envoie de mail non bimp : ".$add);
            return -1;
        }
        return 0;
    }
}

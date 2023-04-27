<?php

class generateDocController extends BimpPublicController
{

    public static $user_client_required = false;

    public function renderHtml()
    {
        $html = '';

        $html .= '<div class="container bic_container bic_main_panel">';
        $html .= '<div class="row">';
        $html .= '<div class="col-lg-12">';

        $errors = array();

        $doc_type = BimpTools::getValue('dt', '');
        if (!$doc_type) {
            $errors[] = 'Type de document à générer absent';
        } else {
            switch ($doc_type) {
                case 'sav_destruct':
                    $html .= '<h2 style="text-align: center">Bon de destruction</h2>';
                    $id_sav = (int) BimpTools::getValue('ids', 0);
                    $ref_sav = BimpTools::getValue('rs', '');

                    if (!$id_sav) {
                        $errors[] = 'Identifiant de votre dossier SAV absent';
                    }

                    if (!$ref_sav) {
                        $errors[] = 'Référence de votre dossier SAV absent';
                    }

                    if (!count($errors)) {
                        $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', $id_sav);
                        if (!BimpObject::objectLoaded($sav)) {
                            $errors[] = 'Identifiant de votre dossier SAV invalide';
                        } else {
                            if ($ref_sav != $sav->getRef()) {
                                $errors[] = 'Référence de votre dossier SAV invalide';
                            } else {
                                if (!in_array((int) $sav->getData('status'), array(BS_SAV::BS_SAV_ATT_CLIENT, BS_SAV::BS_SAV_ATT_CLIENT_ACTION, BS_SAV::BS_SAV_A_RESTITUER))) {
                                    $errors[] = 'Le dossier ' . $ref_sav . ' n\'est pas en attente de restitution, il n\'est donc pas possible de générer le bon de destruction de votre matériel';
                                } else {
                                    $bdb = BimpCache::getBdb();
                                    $bdb->db->begin();
                                    $doc_warnings = array();
                                    $doc_errors = $sav->processBonDesctruction(true, true, null, $doc_warnings);

                                    $html .= '<div style="text-align: center; font-size: 14px; margin: 30px 0">';
                                    if (count($doc_errors) || count($doc_warnings)) {
                                        $bdb->db->rollback();

                                        $html .= '<span class="danger">';
                                        $html .= 'Une erreur est survenue. Votre Bon de Destuction n\'a pas pu être généré correctement.<br/>';
                                        $html .= 'Veuillez nous excusez pour le désagrément occasionné.<br/>';
                                        $html .= 'Un technicien a été alerté, vous serez notifié par e-mail lorsque le problème aura été résolu.';
                                        $html .= '</span>';

                                        BimpCore::addlog('ECHEC création Bon de destruction SAV par client', 4, 'sav', $sav, array(
                                            'Info importante' => 'Ne pas oulier de recontacter le client après résolution du problème',
                                            'Erreurs'         => $doc_errors,
                                            'Warnings'        => $doc_warnings
                                                ), true);
                                    } else {
                                        $bdb->db->commit();
                                        $html .= '<span class="success" style="font-size: 16px">';
                                        $html .= BimpRender::renderIcon('fas_check', 'iconLeft') . 'Votre bon de destruction a été généré avec succès';
                                        $html .= '</span><br/><br/>';

                                        $html .= '<b>Veuillez accéder à votre espace client LDLC Apple (lien disponible ci-dessous) pour consulter et signer électroniquement ce document.</b><br/><br/>';
                                        $html .= 'Si vous ne disposiez pas encore d\'un accès à votre espace client LDLC Apple, vous allez recevoir par e-mail vos identifiants de connexion.<br/><br/>';
                                        $html .= 'Si vous disposiez déjà d\'un accès à votre espace client mais que vous avez oublié votre mot de passe,<br/>Il vous suffit de suivre le lien ci-dessous puis de cliquer sur "mot de passe oublié".';

                                        $html .= '<br/><br/><br/><a style="font-size: 16px; font-weight: bold" href="' . BimpObject::getPublicBaseUrl() . '">Accéder à votre espace client' . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight') . '</a>';

                                        

                                        $html .= '<div style="margin: 50px 0 0; padding: 15px; border: 1px solid #ccc">';
                                        $html .= '<b>Depuis la page d\'accueil de votre espace client, le document à signer apparaîtra de la manière suivante :<b><br/><br/>';
                                        
                                        $html .= '<img style="width: 100%; height: auto" src="' . DOL_URL_ROOT . '/bimpinterfaceclient/views/img/destruct_sample.jpg"/>';
                                        $html .= '</div>';
                                    }

                                    $html .= '</div>';
                                }
                            }
                        }
                    }
                    break;

                default:
                    $errors[] = 'Type de document à générer invalide';
                    break;
            }
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        }

        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}

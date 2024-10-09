<?php

class signatureController extends BimpPublicController
{

    public static $user_client_required = false;

    public function renderHtml()
    {
        $errors = array();
        $id_signataire = BimpTools::getValue('s', 0, 'int');
        $code = BimpTools::getValue('c', '', 'aZ09');
        $signataire = null;
        $signature = null;
        $status = null;

        if (!$id_signataire) {
            $errors[] = 'Identifiant du signataire absent';
        } else {
            if (!$code) {
                $errors[] = 'Code de sécurité absent';
            }

            $signataire = BimpCache::getBimpObjectInstance('bimpcore', 'BimpSignataire', $id_signataire);

            if (!BimpObject::objectLoaded($signataire)) {
                $errors[] = 'Identifiant du signataire invalide';
            } else {
                $signature = $signataire->getParentInstance();
                $status = (int) $signataire->getData('status');

                if (!BimpObject::objectLoaded($signature)) {
                    $errors[] = 'Cette signature n\'existe plus';
                }

                if (!(int) $signataire->getData('allow_dist')) {
                    $errors[] = 'La signature électronique à distance n\'est pas autorisée pour ce document';
                } elseif ($code) {
                    if ($code !== $signataire->getData('security_code')) {
                        $errors[] = 'Code de sécurité invalide';
                    }
                }
            }
        }

        $html = '';

        $html .= '<div class="container bic_container bic_main_panel">';
        $html .= '<div class="row">';
        $html .= '<div class="col-lg-12">';

        $html .= '<div id="siganture_form" class="bimp_public_form" style="text-align: center">';
        $html .= '<h2>Signature électronique</h2>';

        if (empty($errors)) {
            $html .= '<h3>';
            $html .= $signature->displayDocTitle();
            $html .= '</h3>';

            switch ($status) {
                case BimpSignataire::STATUS_SIGNED:
                    $html .= '<br/><h3 class="success">';
                    $html .= BimpRender::renderIcon('fas_check', 'iconLeft') . 'Signature de ce document effectuée';
                    $html .= '</h3>';
                    break;

                case BimpSignataire::STATUS_REFUSED:
                    $html .= '<br/><h3 class="danger">';
                    $html .= BimpRender::renderIcon('fas_times', 'iconLeft') . 'Signature de ce document refusée';
                    $html .= '</h3>';
                    break;

                case BimpSignataire::STATUS_CANCELLED:
                    $html .= '<br/><h3 class="danger">';
                    $html .= BimpRender::renderIcon('fas_times', 'iconLeft') . 'Signature de ce document annulée';
                    $html .= '</h3>';
                    break;
            }
        }

        $html .= '</div>';

        if (empty($errors)) {
            if (!$status) {
                $html .= '<div class="bimp_public_form">';
                $html .= '<div class="form_section" style="text-align: center">';

                $signataire->force_no_user_client = true;
                $html .= $signataire->dispayPublicSign(true, array(
                    'id_signataire' => $signataire->id,
                    'security_code' => $code
                ));

                $html .= '</div>';
                $html .= '</div>';
            }

            $html .= '<div class="bimp_public_form">';
            $html .= '<div class="form_section" style="text-align: center">';

            $doc_url = $signature->getDocumentUrl($signataire->isSigned(), 'public');
            if ($doc_url) {
                $doc_url .= '&ids=' . $signataire->id . '&sc=' . $code;
                $html .= '<embed src="' . $doc_url . '" width="100%" height="650" type="application/pdf"/>';
            } else {
                $html .= '<span class="danger">' . BimpRender::renderIcon('fas_times-circle', 'iconLeft') . 'Aperçu du document non disponible</span>';
            }

            $html .= '</div>';
            $html .= '</div>';
        }

        if (!empty($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        }

        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}

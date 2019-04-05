<?php

class public_indexController extends Bimp_user_client_controller {

    public function renderHtml() {
        global $langs, $userClient;

        $valide_contrat = $userClient->getContratVisible(true);
        // $valide_contrat = Array(); // Simuler que la société n'est pas sous contrat
        $all_contrat = $userClient->getContratVisible();
        $html = "";
        $soc = $userClient->getDolObjectInstance($userClient->getData('attached_societe'), 'societe');
        $html .= '<div class="row"><div class="col-md-12"><div class="header"><h3 class="title" data-color="bimp">' . $langs->trans('bonjour') . '</h3></div>';
        $html .= '<div class="content" ><div class="col-md-12">';
        $html .= 'La societé <b><i>' . $soc->nom . '</i></b> est actuellement ';
        $html .= ($valide_contrat) ? '<b style="color:green">sous contrat <i class="fa fa-check" ></i></b>' : '<b style="color:red">hors contrat <i class="fa fa-times" ></i></b>';
        $html .= '</div>';
        $html .= '<br /><br /><br />';
        $html .= '<h4>Mes contrats gérés valide</h4>';
        if ($valide_contrat) { $html .= $this->display_list_card($valide_contrat, true);}
        if ($all_contrat) {
            foreach ($all_contrat as $id_contrat => $ref) {
                if(!array_key_exists($id_contrat, $valide_contrat)) {
                    $contracts[$id_contrat] = $ref;
                }
                
            }
        }
        $html .= '</div>';
        $html .= '</div></div>';
        $html .= '<br /><br /><br />';
        $html .= '<h4>Mes contrats gérés échus</h4>';
        $html .= '<div class="row">';
        $html .= '<div class="content" >';
        $html .= $this->display_list_card($contracts);
        $html .= '</div></div>';
        return $html;
    }

    public function display_list_card($object, $valide = false) {
        $return = "";
        foreach ($object as $id_contrat => $ref) {
            $return .= '<div class="col-md-4">';
            $return .= '<div class="card">';
            $return .= '<div class="header">';
            $return .= '<h4 class="title">' . $ref . '</h4>';
            $return .= '<p class="category">';
            $return .= ($valide) ? 'Contrat en cours de vadité' : 'Contrat échu';
            $return .= '</p>';
            $return .= '</div>';
            $return .= '<div class="content"><div class="footer"><div class="legend">';
            $return .= ($valide) ? '<i class="fa fa-plus text-success"></i> <a href="'.DOL_URL_ROOT.'/bimpinterfaceclient/?fc=contrat_ticket&contrat='.$id_contrat.'">Créer un ticket support</a>' : '';
            $return .= '<i class="fa fa-eye text-info"></i> Voir le contrat</div><hr><div class="stats"></div></div></div>';
            $return .= '</div></div>';
        }
        return $return;
    }

}

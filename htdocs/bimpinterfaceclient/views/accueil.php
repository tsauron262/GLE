<?php
BimpCore::displayHeaderFiles();
define('BIMP_NO_HEADER', 1);

?>
<div class="row">
    <div class="col-md-12">
        <div class="header">
            <h3 class="title" data-color="bimp"><?= $langs->trans('bonjour') ?></h3>
        </div>
        <div class="content" >
            <div class="col-md-12">
                <h5>La sociétée <b><i><?= $userClient->attached_societe->nom ?></i></b> est actuellement <?= (count($couverture) > 0) ? "<b style='color:green'>couvert par contrat <i class='fa fa-check' ></i></b>" : "<b style='color:red'>hors contrat <i class='fa fa-times' ></i></b>" ?></h5>
            </div>
            <br /><br /><br />
            <?php
           //
           //echo $langs->trans('bonjour');
            //print_r($langs);
            if (count($couverture) == 0) {
                ?>
                
                <?php
            } else {

                if ($userClient->i_am_admin()) { // On affiche tous les contrats
                    foreach ($couverture as $id_contrat => $ref) {
                        echo '<div class="col-md-4">';
                        echo '<div class="card">';
                        echo '<div class="header">';
                        echo '<h4 class="title">' . $ref . '</h4>';
                        echo '<p class="category">Contrat en cours de validité</p>';
                        echo '</div>';
                        echo '<div class="content">
                                <div class="footer">
                                    <div class="legend">
                                        <i class="fa fa-plus text-success"></i> <a href="'.DOL_URL_ROOT.'/bimpinterfaceclient/?page=ticket&contrat='.$id_contrat.'">Créer un ticket support</a>
                                        <i class="fa fa-eye text-info"></i> Voir le contrat
                                    </div>
                                    <hr>
                                    <div class="stats">
                                        <i class="fa fa-user"></i> Mon commercial :
                                    </div>
                                </div>
                            </div>';
                        echo '</div></div>';
                    }
                } else { // On affiche les contrats qui sont assigné à l'utilisateur
                }
            }
            ?>
            
        </div>
    </div>
</div>


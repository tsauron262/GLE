<?php
global $userClient, $langs;

$content_request = $_REQUEST['fc'];
?>

<body>
    <div class="wrapper">
        <div class="sidebar" data-color="bimp" >
            <div class="sidebar-wrapper">
                <ul class="nav">
                    <li <?= ($content_request == "") ? 'class="active"' : "" ?> >
                        <a href="client.php?">
                            <i class="pe-7s-home"></i>
                            <p><?= $langs->trans('menuAccueil') ?></p>
                        </a>
                    </li>
                    <li <?= ($content_request == 'pageUser') ? 'class="active"' : "" ?> >
                        <a href="client.php?fc=pageUser&id=<?= $userClient->getData('id') ?>">
                            <i class="pe-7s-paperclip"></i>
                            <p><?= $langs->trans('menuUserPage') ?></p>
                        </a>
                    </li>
                    <?php
                    if ($userClient->it_is_admin()) {
                        ?>
                        <li <?= ($content_request == 'ticket') ? 'class="active"' : "" ?> >
                            <a href="client.php?fc=tickets">
                                <i class="pe-7s-ticket"></i>
                                <p><?= $langs->trans('menuTickets') ?></p>
                            </a>
                        </li>
                        <?php
                        if ($activate_page) {
                            ?>
                            <li <?= ($content_request == 'inter') ? 'class="active"' : "" ?> >
                            <a href="client.php?fc=inters">
                                <i class="pe-7s-config"></i>
                                <p><?= $langs->trans('menuInter') ?></p>
                            </a>
                        </li>
                        <li <?= ($content_request == 'facture') ? 'class="active"' : "" ?>>
                                <a href="client.php?fc=facture">
                                    <i class="pe-7s-file"></i>
                                    <p><?= $langs->trans('menuFacture') ?></p>
                                </a>
                            </li>
                        <li <?= ($content_request == 'serials_imei') ? 'class="active"' : "" ?>>
                                <a href="client.php?fc=serials_imei">
                                    <i class="pe-7s-search"></i>
                                    <p><?= $langs->trans('menuSerialsImei') ?></p>
                                </a>
                            </li>
                            <li <?= ($content_request == 'contrat') ? 'class="active"' : "" ?> >
                                <a href="client.php?fc=contrat">
                                    <i class="pe-7s-graph"></i>
                                    <p><?= $langs->trans('menuContrat') ?></p>
                                </a>
                            </li>
                            
                            <li <?= ($content_request == 'devis') ? 'class="active"' : "" ?>>
                                <a href="client.php?fc=devis">
                                    <i class="pe-7s-file"></i>
                                    <p><?= $langs->trans('menuDevis') ?></p>
                                </a>
                            </li>
                            <?php
                        }
                        ?>
                            
                        <?php
                        //if (count($couverture) > 0) {
                        ?>
                        <li <?= ($content_request == 'user') ? 'class="active"' : "" ?>>
                            <a href="client.php?fc=user">
                                <i class="pe-7s-users"></i>
                                <p><?= $langs->trans('menuUser') ?></p>
                            </a>
                        </li>
                        <?php
                        //}
                    }
                    ?>
                </ul>
            </div>
        </div>
        <!-- Changement du mot de passe -->
        <div id="passwd">
            <i class="fa fa-times close-passwd" style="color:red; font-size:25px" ></i>
            <h4>Changer de mot de passe</h4>
            <hr>
            <form method="post">
                <center><input type="password" class="form-control" style="width:80%" placeholder="Nouveau mot de passe" name="new_password" id="new_passwd" ></center>
                <br />
                <button type="submit" class="btn btn-warning btn-fill pull-center"><?= $langs->trans('menuChangePassword') ?></button>
            </form>
            <br /> <br />
        </div>
        <!-- Fin duchangement du mot de passe -->
        <div class="main-panel">
            <nav class="navbar navbar-default navbar-fixed" style="background: rgba(255, 255, 255, 0.96)">
                <div class="container-fluid">
                    <div class="navbar-header">
                        <button type="button" class="navbar-toggle" data-toggle="collapse">
                            <span class="sr-only">Toggle navigation</span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                        </button>
                        <a class="navbar-brand" href="#">
                            <img src="<?php
                            global $mysoc;
                            echo DOL_URL_ROOT . '/viewimage.php?cache=1&modulepart=mycompany&file=' . $mysoc->logo
                            ?>" style="width: 73%"> 
                        </a>
                    </div>
                    <div class="collapse navbar-collapse">
                        <ul class="nav navbar-nav navbar-right">
                            <li><a href="#" class="rgpd">Connecté en tant que : <i style="color:#EF7D00"><?= $userClient->getData('email') ?></i></a></li>'

                            <li><a href="#" class="passwd"><?= $langs->trans('changePassword') ?></a></li>
                            <li><a href="?action=deconnexion"><?= $langs->trans('deconnexion') ?></a></li>
                        </ul>
                        <ul class="nav navbar-nav navbar-right">
                            <?php
                            if (count(BIC_UserClient::$langs_list) > 1) {
                                foreach (BIC_UserClient::$langs_list as $idT => $valT) {
                                    echo '
                                            <div class="css-tooltip bottom">
                                                <li><a href="?new_lang=' . $idT . '" class=""><img width="20px" src="' . DOL_URL_ROOT . '/bimpinterfaceclient/views/img/lang/' . strtolower($valT) . '.png" /></a></li>
                                                <span class="tt-content">' . $langs->trans('menuLang-' . $valT) . '</span>
                                            </div>';
                                }
                            }
                            ?>

                        </ul>


                    </div>
                </div>
            </nav>
            <div class="content" style="background: rgba(203, 203, 210, 0.15)">
                <div class="container-fluid">

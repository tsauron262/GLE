

    <!doctype html>
    <html lang="fr">
        <head>
            <meta charset="utf-8" />
            <!--<script src="views/js/jquery.3.2.1.min.js" type="text/javascript"></script>-->
            <link rel="icon" type="image/png" href="<?php
            global $mysoc;
            echo DOL_URL_ROOT . '/viewimage.php?cache=1&modulepart=mycompany&file=' . $mysoc->logo
            ?>">
            <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
            <title>BIMP-ERP Interface Client</title>
            <meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0' name='viewport' />
            <meta name="viewport" content="width=device-width" />
            <link href="views/css/light-bootstrap-dashboard.css?v=1.4.0" rel="stylesheet"/>
            <link href="views/css/demo.css" rel="stylesheet" />

            <!-- DOL REQUIRE -->

            <link href="http://maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css" rel="stylesheet">
            <link href='http://fonts.googleapis.com/css?family=Roboto:400,700,300' rel='stylesheet' type='text/css'>
            <link href="views/css/pe-icon-7-stroke.css" rel="stylesheet" />
        </head>
        <body>
            <div class="wrapper">
                <div class="sidebar" data-color="bimp" data-image="assets/img/sidebar-5.jpg">
                    <div class="sidebar-wrapper">
                        <ul class="nav">
                            <li <?= (!$request) ? 'class="active"' : "" ?> >
                                <a href="?">
                                    <i class="pe-7s-home"></i>
                                    <p><?= $langs->trans('menuAccueil') ?></p>
                                </a>

                            </li>
                            <li <?= ($content_request == 'ticket') ? 'class="active"' : "" ?> >
                                <a href="?fc=ticket">
                                    <i class="pe-7s-paperclip"></i>
                                    <p><?= $langs->trans('menuTickets') ?></p>
                                </a>
                            </li>
                            <?php
                            if ($userClient->i_am_admin()) {
                                ?>
                                <li <?= ($content_request == 'contrat') ? 'class="active"' : "" ?> >
                                    <a href="?fc=contrat">
                                        <i class="pe-7s-graph"></i>
                                        <p><?= $langs->trans('menuContrat') ?></p>
                                    </a>
                                </li>
                                <li <?= ($content_request == 'facture') ? 'class="active"' : "" ?>>
                                    <a href="?fc=facture">
                                        <i class="pe-7s-file"></i>
                                        <p><?= $langs->trans('menuFacture') ?></p>
                                    </a>
                                </li>
                                <li <?= ($content_request == 'devis') ? 'class="active"' : "" ?>>
                                    <a href="?fc=devis">
                                        <i class="pe-7s-file"></i>
                                        <p><?= $langs->trans('menuDevis') ?></p>
                                    </a>
                                </li>
                                <?php
                                //if (count($couverture) > 0) {
                                ?>
                                <li <?= ($content_request == 'user') ? 'class="active"' : "" ?>>
                                    <a href="?fc=user">
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
                        <center><input type="password" class="form-control" style="width:80%" placeholder="Nouveau mot de passe" name="new_passwd" id="new_passwd" ></center>
                        <br />
                        <button type="submit" class="btn btn-warning btn-fill pull-center">Modifier mon mot de passe</button>
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
                                    <li><a href="#" class="passwd"><?= $langs->trans('changePassword') ?></a></li>
                                    <li><a href="?action=deconnexion"><?= $langs->trans('deconnexion') ?></a></li>
                                </ul>
                                <ul class="nav navbar-nav navbar-right">
                                    <?php
                                    foreach (BIC_UserClient::$langs_list as $idT => $valT) {
                                        echo '
                                    <div class="css-tooltip bottom">
                                        <li><a href="?lang=' . $idT . '" class=""><img width="20px" src="' . DOL_URL_ROOT . '/bimpinterfaceclient/views/img/lang/' . strtolower($valT) . '.png" /></a></li>
                                        <span class="tt-content">' . $langs->trans('menuLang-' . $valT) . '</span>
                                    </div>';
                                    }
                                    ?>

                                </ul>


                            </div>
                        </div>
                    </nav>
                    <div class="content" style="background: rgba(203, 203, 210, 0.15)">
                        <div class="container-fluid">

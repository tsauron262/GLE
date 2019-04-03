


<!DOCTYPE html>
<html lang="fr">
    <head>
        <title>BIMP ERP Connexion client</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" type="image/png" href="views/login_page/images/icons/favicon.ico"/>
        <link rel="stylesheet" type="text/css" href="views/login_page/vendor/bootstrap/css/bootstrap.min.css">
        <link rel="stylesheet" type="text/css" href="views/login_page/fonts/font-awesome-4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" type="text/css" href="views/login_page/vendor/animate/animate.css">
        <link rel="stylesheet" type="text/css" href="views/login_page/vendor/css-hamburgers/hamburgers.min.css">
        <link rel="stylesheet" type="text/css" href="views/login_page/vendor/select2/select2.min.css">
        <link rel="stylesheet" type="text/css" href="views/login_page/css/util.css">
        <link rel="stylesheet" type="text/css" href="views/login_page/css/main.css">
    </head>
    <body>

        <div class="limiter">
            <div class="container-login100">
                <div class="wrap-login100">
                    <div class="login100-pic">
                        <img src="<?php
                        global $mysoc;
                        echo DOL_URL_ROOT . '/viewimage.php?cache=1&modulepart=mycompany&file=' . $mysoc->logo
                        ?>" alt="IMG" style="width:120%">
                    </div>

                    <form class="login100-form validate-form" action="<?= DOL_URL_ROOT . '/bimpinterfaceclient/' ?>" method="POST" >
                        <span class="login100-form-title">Connexion</span>
                        <div class="wrap-input100 validate-input" data-validate = "Champs obligatoire">
                            <input class="input100 id_or_contrat" type="text" name="identifiant_contrat" placeholder="Email" style="border-radius:5px">
                            <span class="focus-input100"></span>
                            <span class="symbol-input100">
                                <i class="fa fa-at" id="icon_id_or_contrat" aria-hidden="true"></i>
                            </span>
                        </div>

                        <div class="wrap-input100 validate-input" data-validate = "Champs obligatoire">
                            <input class="input100" type="password" name="pass" placeholder="Mot de passe utilisateur" style="border-radius:5px">
                            <span class="focus-input100"></span>
                            <span class="symbol-input100">
                                <i class="fa fa-lock" aria-hidden="true"></i>
                            </span>
                        </div>

                        <div class="container-login100-form-btn">
                            <button class="login100-form-btn">
                                Connexion
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
        <script src="views/login_page/vendor/jquery/jquery-3.2.1.min.js"></script>
        <script src="views/login_page/vendor/bootstrap/js/popper.js"></script>
        <script src="views/login_page/vendor/bootstrap/js/bootstrap.min.js"></script>
        <script src="views/login_page/vendor/select2/select2.min.js"></script>
        <script src="views/login_page/vendor/tilt/tilt.jquery.min.js"></script>
        <script >
            $('.js-tilt').tilt({
                scale: 1.1
            })
        </script>
        <script src="views/login_page/js/main.js"></script>

    </body>
</html>
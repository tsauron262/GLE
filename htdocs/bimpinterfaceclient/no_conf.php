<?php 
    require '../main.inc.php';
?>
<!DOCTYPE html>
<html lang="id" dir="ltr">

    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />

        <!-- Title -->
        <title>Sorry, This Page Can&#39;t Be Accessed</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css" />
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" integrity="sha384-WskhaSGFgHYWDcbwN70/dfYBj47jz9qbsMId/iRN3ewGhXQFZCSftd1LZCfmhktB" crossorigin="anonymous" />
        <style type="text/css">
            #footer{
                text-align: center;
                position: fixed;
                margin-left: 530px;
                bottom: 0px
            }
        </style>
    </head>

    <body class="bg-dark text-white py-5">
        <div class="container py-5">
            <div class="row">
                <div class="col-md-2 text-center">
                    <p><i class="fa fa-exclamation-triangle fa-5x"></i><br/>Code erreur: 403</p>
                </div>
                <div class="col-md-10">
                    <h3>Accès interdit</h3>
                    <p>Désolé mais vous n'avez pas la permissions d'accéder à ce module pour le moment</p>
                    <a class="btn btn-danger" href="<?= DOL_URL_ROOT ?>/">Revenir à BIMP-ERP</a>
                </div>
            </div>
        </div>

        <div id="footer" class="text-center">
            Espase Client By BIMP-ERP
        </div>
    </body>

</html>

<?php 
    exit(0);
?>
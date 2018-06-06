<?php

session_start();
include_once '../param.inc.php';

header('Access-Control-Allow-Origin: *');  
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Content-Range, Content-Disposition, Content-Description');

function printHeader($title, $arrayofjs = array(), $arrayofcss = array()) {
    print '<!DOCTYPE html>';
    print '<html>';

    print '<head>';
    print '<title>' . $title . '</title>';
// CSS
    print '<link rel="stylesheet" href="../lib/css/jquery-ui.css">';
    print '<link rel="stylesheet" href="../css/styles.css">';
    print '<link rel="stylesheet" href="../lib/css/bootstrap.min.css">';
    print '<link rel="stylesheet" href="../lib/css/chosen.min.css">';
    foreach ($arrayofcss as $cssfile)
        print '<link rel="stylesheet" type="text/css" href="' . $cssfile . '">';

// JS
    print '<script>var URL_PRESTA="' . PRESTA_URL . '";'
            . '    var URL_CHECK ="' . URL_CHECK . '";</script>';

    print '<script src="../lib/js/jquery-3.3.1.min.js"></script>';
    print '<script src="../lib/js/jquery-ui.js"></script>';
    print '<script type="text/javascript" src="../lib/js/bootstrap.min.js"></script>';
    print '<script type="text/javascript" src="../lib/js/chosen.jquery.min.js"></script>';
    print '<script type="text/javascript" src="../js/annexes.js"></script>';
    print '<script type="text/javascript" src="../lib/js/tinymce/tinymce.min.js"></script>';

    foreach ($arrayofjs as $jsfile)
        print '<script type="text/javascript" src="' . $jsfile . '"></script>';

    print '<script>var id_event_session=parseInt(' . $_SESSION['id_event'] . ');</script>';

    print '<link rel="icon" href="../img/logo.png">';
    print '</head>';

    if (isset($_SESSION['user'])) {
        global $user;
        $user = json_decode($_SESSION['user']);
        print '<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="collapse navbar-collapse" id="navbarSupportedContent">';
        if (IS_MAIN_SERVER) {

            print '<div class="navbar-header">
      <a class="navbar-brand" href="home.php">Billetterie</a>
    </div>';
        }
        print'<ul class="navbar-nav mr-auto">';
        if ($user->status == 2 && IS_MAIN_SERVER)
            print '<li><a class="nav-link" href="manage_user.php">Gestion</a></li>';
        if (IS_MAIN_SERVER) {
            print '<li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Évènement</a>
        <div class="dropdown-menu" aria-labelledby="navbarDropdown">
          <a class="dropdown-item" href="create_event.php">Créer</a>
          <a class="dropdown-item" href="modify_event.php">Modifier</a>
          <a class="dropdown-item" href="stats_event.php">Statistique</a>
        </div>
      </li>';
            print '<li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Tariff</a>
        <div class="dropdown-menu" aria-labelledby="navbarDropdown">
          <a class="dropdown-item" href="create_tariff.php">Créer</a>
          <a class="dropdown-item" href="modify_tariff.php">Modifier</a>
        </div>
      </li>';
            print '<li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Ticket</a>
        <div class="dropdown-menu nav-item" aria-labelledby="navbarDropdown">
          <a class="dropdown-item" href="create_ticket.php">Réserver</a>
          <a class="dropdown-item" href="check_ticket.php">Valider</a>
          <a class="dropdown-item" href="list_ticket.php">Liste</a>
        </div>
      </li>';
        } else {
            print '<ul class="nav navbar-nav navbar-right">
      <li>
        <a class="nav-link" href="check_ticket.php">Valider</a>
      </li>
    </ul>';
        }
        print '</ul>
    <ul class="nav navbar-nav navbar-right">
      <li>
        <a class="nav-link" href="index.php">Se déconnecter</a>
      </li>
    </ul>
  </div>
</nav>';
    } else {
        $page = basename($_SERVER['PHP_SELF']);
        if ($page != 'index.php' && $page != 'register.php') {
            print '<fieldset class="container_form">';
            print '<h4>Veuillez vous connecter</h4>';
            print '<input type="button" class="btn btn-primary" value="Accéder à la page de connection" onClick="document.location.href=\'index.php\'"/>';
            print '</fieldset>';
            exit();
        }
    }
}

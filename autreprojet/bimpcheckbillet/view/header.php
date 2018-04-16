<?php

session_start();

function printHeader($title, $arrayofjs = array(), $arrayofcss = array()) {
    print '<!DOCTYPE html>';
    print '<html>';

    print '<head>';
    print '<title>' . $title . '</title>';
// CSS
    print '<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">';
    print '<link rel="stylesheet" href="../css/styles.css">';
    print '<link rel="stylesheet" href="../lib/css/bootstrap.min.css">';
    print '<link rel="stylesheet" href="../lib/css/chosen.min">';
    foreach ($arrayofcss as $cssfile)
        print '<link rel="stylesheet" type="text/css" href="' . $cssfile . '">';

// JS
    print '<script src="https://code.jquery.com/jquery-3.3.1.min.js"
    integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
    crossorigin="anonymous"></script>';
    print '<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>';
    print '<script type="text/javascript" src="../lib/js/bootstrap.min.js"></script>';
    print '<script type="text/javascript" src="../lib/js/chosen.jquery.min.js"></script>';
    print '<script type="text/javascript" src="../js/annexes.js"></script>';

    foreach ($arrayofjs as $jsfile)
        print '<script type="text/javascript" src="' . $jsfile . '"></script>';

    print '<link rel="icon" href="../img/logo.png">';
    print '</head>';

    if (isset($_SESSION['user'])) {
        global $user;
        $user = json_decode($_SESSION['user']);
//        var_dump($user);
        print '<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="collapse navbar-collapse" id="navbarSupportedContent">
    <div class="navbar-header">
      <a class="navbar-brand" href="home.php">Billetterie</a>
    </div>
    <ul class="navbar-nav mr-auto">
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          Utilisateur
        </a>
        <div class="dropdown-menu" aria-labelledby="navbarDropdown">
          <a class="dropdown-item" href="registration_user.php">S\'inscrire</a>
        </div>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          Évènement
        </a>
        <div class="dropdown-menu" aria-labelledby="navbarDropdown">
          <a class="dropdown-item" href="create_event.php">Créer</a>
          <a class="dropdown-item" href="create_tariff.php">Créer tarif</a>
          <a class="dropdown-item" href="create_ticket.php">Réserver ticket</a>
          <a class="dropdown-item" href="check_ticket.php">Valider ticket</a>
        </div>
      </li>
    </ul>
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

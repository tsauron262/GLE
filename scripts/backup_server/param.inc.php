<?php

//if (!isset($_SESSION))
session_start();

define('LOGIN', 'a');
define('PASSWORD', 'z');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '$mokinU2');
define('DB_NAME', 'test_import_atlantide');
define('DB_OPT', '--user=\'' . DB_USER . '\' --password=\'' . DB_PASSWORD . '\'  --host=\'' . DB_HOST . '\'');

define('PATH', realpath(dirname(__FILE__)));
define('URL_ROOT', '//' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']));

if (isset($_POST['login']) && isset($_POST['pw'])) {
    $_SESSION['login'] = $_POST['login'];
    $_SESSION['pw'] = $_POST['pw'];
} elseif (isset($_GET['login']) && isset($_GET['pw'])) {
    $_SESSION['login'] = $_GET['login'];
    $_SESSION['pw'] = $_GET['pw'];
}

if ($_SESSION['login'] != LOGIN or $_SESSION['pw'] != PASSWORD) {
    print '
    <!DOCTYPE html>
<head>
    <link rel="stylesheet" type="text/css" href="styles.css">
    <title>Authentification sauvegarde</title>
</head>
<body>
    <div class="container" style="text-align: center">
        <div class="greyBorder">
            <form action="index.php"  method="post">
                <label for="login"><b>Login</b></label>
                <input type="text" placeholder="Entrez le nom de l\'utilisateur" name="login" required><br/>
                <label for="pw"><b>Mot de passe</b></label>
                <input type="password" placeholder="Entrez le mot de passe" name="pw" required><br/>
                <button type="submit">Se connecter</button>
            </form>
        </div>
    </div>
</body>';
    die();
}


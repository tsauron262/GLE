<?php
    header("Access-Control-Allow-Origin: http://www.zoomdici.fr/");
?>
<html>
    <head>
        <title>Zoomdici Billetterie</title>

        <meta name="description" content="Événements en cours sur zoomdici.fr (Zoom43 et Zoom42), portail d'information locale pour le Puy-en-Velay, Yssingeaux, Monistrol, Langeac, Brioude, Saint Etienne, Firminy, Saint Chamond, Montbrison, Feurs, Rive de Gier, Givors, Roanne. Info, actualité, agenda, sport, hebergement, restaurant, cafe, commerce, meteo, tourisme...">
        <meta name="keywords" content="Événements, Zoom 43, Zoom 42, zoomdici.fr" />

        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

        <meta http-equiv="Content-Script-Type" content="text/javascript" />
        <meta http-equiv="Content-Style-Type" content="text/css" />
        <meta http-equiv="imagetoolbar" content="no" />
        <meta name="viewport" content="width=device-width">


        <meta name="google-site-verification" content="WwLfD-mNaYCxuzeAL1ZJ-qEWW9hzYoaW25TncUjJT18" />

        <!-- Favicon -->

        <link href="http://www.zoomdici.fr/images/favicon.ico" rel="shortcut icon" />

        <!-- CSS -->

        <!-- CSS -->
        <link media="all" type="text/css" rel="stylesheet" href="http://www.zoomdici.fr/_css/web.css">
        <link href='https://fonts.googleapis.com/css?family=Patua+One|Strait|Oxygen:400,300,700|Archivo+Narrow:400,400italic,700,700italic|Muli:400,300,300italic,400italic' rel='stylesheet' type='text/css'>
        <link href="http://www.zoomdici.fr/_css/foundation.css" rel="stylesheet" type="text/css" media="all" />
        <link href="font.css" rel="stylesheet" type="text/css" media="all" />
        <link href="http://www.zoomdici.fr/_css/owl.carousel.css" rel="stylesheet" type="text/css" media="all" />
        <link href="http://www.zoomdici.fr/_css/jquery.fancybox.css" type="text/css" rel="stylesheet" media="screen" />
        <link href="http://www.zoomdici.fr/_css/style.css" rel="stylesheet" type="text/css" media="all" />
        <link href="http://www.zoomdici.fr/_css/autre_pages.css" rel="stylesheet" type="text/css" media="all" />
        <link href="http://www.zoomdici.fr/_css/thematiques/thematique.css" rel="stylesheet" type="text/css" media="all" />
        <link href="http://www.zoomdici.fr/_css/style_agenda.css" rel="stylesheet" type="text/css" media="all" />
        <link href="http://www.zoomdici.fr/_css/thickbox.css" rel="stylesheet" type="text/css" />
        <link href="http://www.zoomdici.fr/_css/jquery.jcarousel.css" rel="stylesheet" type="text/css" />
        <link href="http://www.zoomdici.fr/_css/jquery.galleria.css" rel="stylesheet" type="text/css" />
        <link href="http://www.zoomdici.fr/_css/jquery.datepicker.css" rel="stylesheet" type="text/css" />
        <link href="http://www.zoomdici.fr/_css/global.css" rel="stylesheet" type="text/css" media="all" />

    </head>


    <div id="menutop" class="row collapse">

        <ul class="inline-list right">
            <li class="fi-social"><a href="https://www.facebook.com/pages/Zoom-Haute-Loire/275206082544706" target="_blank"><i class="fi-social-facebook"></i></a></li>
            <li class="fi-social"><a href="https://twitter.com/zoomdici" target="_blank"><i class="fi-social-twitter"></i></a></li>
            <!--<li class="fi-social"><a href="#"><i class="fi-social-google-plus"></i></a></li>-->
            <li>
                <a href="http://www.zoomdici.fr/lettre/gestion.php">Les Newsletters</a>
            </li>

            <li>
                <a href="http://www.zoomdici.fr/contact/contact.php">Contact</a>
            </li>

            <li>
                <a href="http://www.zoomdici.fr/contact/selection-contact.php"><span data-tooltip="" aria-haspopup="true" class="has-tip" data-selector="tooltip-jg3idlt90" aria-describedby="tooltip-jg3idlt90" title="">Envoyer une info</span></a>
            </li>

            <li>
                <!--<a href="http://www.zoomdici.fr/mon-compte/creation-identification.php">Se connecter</a>-->


                <a href="http://www.zoomdici.fr/mon-compte/creation-identification.php" class="createAccount">Se connecter</a>

            </li>
        </ul>
    </div>
    <div class="row collapse contentclrbkg">
        <div class="row">
            <div class="medium-6 columns text-center"  style="width: 100%;">
                <a href="http://www.zoomdici.fr/accueil/accueil.php"><img src="http://www.zoomdici.fr/images/logo_zoom.png" alt="logo_zoom" width="280" height="79" class="logo-header"></a>
                
            </div>
        </div>
        <div class="row top-bar-shadow">
            <div class="sticky">
                <nav class="top-bar text-center" data-topbar="navigation">
                    <ul class="title-area">
                        <li class="name"></li>
                        <li class="toggle-topbar menu-icon"><a href="#">Menu</a></li>
                    </ul>

                    <section class="top-bar-section">
                        <ul class="text-left">
                            <li>
                                <a href="http://www.zoomdici.fr/accueil/accueil.php#top" class="scrollTo"><i class="fi-home"></i></a>
                            </li>
                        </ul>
                        <ul>
                            <li>
                                <a href="http://www.zoomdici.fr/accueil/accueil.php#societe" class="scrollTo">Société</a>
                            </li>
                            <!-- On regarde s'il y'a un événement en ce moment pour afficher l'onglet spécial -->
                            <li>
                                <a href="http://www.zoomdici.fr/accueil/accueil.php#sports" class="scrollTo">Sports</a>
                            </li>
                            <li>
                                <a href="http://www.zoomdici.fr/accueil/accueil.php#faitsdivers" class="scrollTo">Faits divers</a>
                            </li>
                            <li>
                                <a href="http://www.zoomdici.fr/accueil/accueil.php#sorties" class="scrollTo">Sorties</a>
                            </li>
                            <li>
                                <a href="http://www.zoomdici.fr/cinema/cinema.php" class="scrollTo">Ciné</a>
                            </li>
                            <li>
                                <a href="http://www.zoomdici.fr/sortie-loisirs/recherche-restaurants.php" class="scrollTo">Restos</a>
                            </li>
                            <li>
                                <a href="http://www.zoomdici.fr/accueil/accueil.php#petitesannonces" class="scrollTo">Annonces</a>
                            </li>
                            <li>
                                <a href="http://www.zoomdici.fr/galeries/galeries-photo.php" class="scrollTo">Galeries</a>
                            </li>
                            <li>
                                <a href="http://www.zoomdici.fr/billetterie/" style="height:45px; background: #eeeeee;"><img style="margin:11px 0 12px 0;" src="http://www.zoomdici.fr/images/Billetterie-bouton.png"></a>
                            </li>
                            <li class="displayScroll">
                                <a href="http://www.zoomdici.fr/accueil/accueil.php#newsletter" class="scrollTo">Les Newsletters</a>
                            </li>
                            <li class="displayScroll">
                                <a href="http://www.zoomdici.fr/contact/contact.php">Contact</a>
                            </li>
                            <li class="displayScroll">
                                <a href="http://www.zoomdici.fr/contact/selection-contact.php">Envoyer une info</a>
                            </li>
                            <li class="displayScroll">

                                <a href="http://www.zoomdici.fr/mon-compte/creation-identification.php" class="createAccount"><i class="fi-torso"></i> Se connecter</a>

                        <!--<a href="http://www.zoomdici.fr/mon-compte/creation-identification.php"><i class="fi-torso"></i> Se connecter</a>-->
                            </li>
                        </ul>
                        <ul class="onRight">
                            <li class="fi-social displayScroll"><a href="https://www.facebook.com/pages/Zoom-Haute-Loire/275206082544706" target="_blank"><i class="fi-social-facebook"></i></a></li>
                            <li class="fi-social displayScroll"><a href="https://twitter.com/zoomdici" target="_blank"><i class="fi-social-twitter"></i></a></li>
                            <!--<li class="fi-social displayScroll"><a href="#"><i class="fi-social-google-plus"></i></a></li>-->
                        </ul>
                    </section></nav>
            </div>
        </div>
        <div id="breadcrumb">



    <?php
    echo "icicicic";  
    echo "<br/>";  
    echo "icicicic";
    echo "<br/>";  
    echo "icicicic";
    echo "<br/>";  
    echo "icicicic";
    echo "<br/>";  
    echo "icicicic";    
    ?>
        </div>
    </div>


    <div id="footer">
        <div id="footercontent" class="row">
            <div class="medium-12 columns">
                <ul class="footer-menu-list">
                    <li>© zoomdici</li>

                    <li>
                        <a href="http://www.zoomdici.fr/accueil/mentions-legales.php">Mentions légales</a>
                    </li>
                    <!--
                                <li>
                                    <a href="http://www.zoomdici.fr/accueil/foire-aux-questions.php">FAQ</a>
                                </li>
                    
                                <li>
                                    <a href="http://www.zoomdici.fr/regie/formats-publicitaires.php">Régie publicitaire</a>
                                </li>-->
                </ul>
            </div>

            <div class="medium-3 columns">
                <ul class="footer-item-nav">
                    <li>
                        <a href="http://www.zoomdici.fr/accueil/accueil.php#top">À la Une</a>
                    </li>

                    <li>
                        <a href="http://www.zoomdici.fr/accueil/accueil.php#faitsdivers">Faits divers</a>
                    </li>

                    <li>
                        <a href="http://www.zoomdici.fr/accueil/accueil.php#sports">Sports</a>
                    </li>
                </ul>
            </div>

            <div class="medium-3 columns">
                <ul class="footer-item-nav">
                    <li>
                        <a href="http://www.zoomdici.fr/accueil/accueil.php#societe">Société</a>
                    </li>

                    <li>
                        <a href="http://www.zoomdici.fr/">Près de chez vous</a>
                    </li>

                    <li>
                        <a href="http://www.zoomdici.fr/accueil/accueil.php#sorties">Sorties</a>
                    </li>
                </ul>
            </div>

            <div class="medium-3 columns">
                <ul class="footer-item-nav">
                    <li>
                        <a href="http://www.zoomdici.fr/accueil/accueil.php#cine">Cinéma</a>
                    </li>

                    <li>
                        <a href="http://www.zoomdici.fr/accueil/accueil.php#zoomresto">Restaurants</a>
                    </li>

                    <li>
                        <a href="http://www.zoomdici.fr/accueil/accueil.php#petitesannonces">Annonces</a>
                    </li>
                </ul>
            </div>

            <div class="medium-3 columns">
                <ul class="footer-item-nav">
                    <li>
                        <a href="http://www.zoomdici.fr/accueil/accueil.php#videos">Galeries</a>
                    </li>
                </ul>
            </div>
        </div></div>


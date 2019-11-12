<?php
/**
 * A landing page for non existant vhosts
 * Used by default Vhost
 */
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="fr">
<head>
    <title>HAL | Le Portail demandé n'est pas accessible</title>
    <link href="//static.ccsd.cnrs.fr" rel="dns-prefetch">
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="Content-Language" content="fr">

    <link rel="stylesheet" href="//static.ccsd.cnrs.fr/v3/css/bootstrap.min.css" type="text/css"
          media="screen">
    <link rel="stylesheet" href="//static.ccsd.cnrs.fr/css/ccsd.css" type="text/css" media="screen">

    <style>
    <?php
    $cssPath = __DIR__ . '/../css/hal.css';
    if (is_readable($cssPath)) {
        echo file_get_contents($cssPath);
    }
    ?>
    </style>
    <script type="text/javascript" src="//static.ccsd.cnrs.fr/js/jquery/min.1.9.1.js?"></script>
    <script type="text/javascript" src="//static.ccsd.cnrs.fr/v3/js/bootstrap.min.js?"></script>
</head>
<body>
<div class="navbar navbar-inverse navbar-fixed-top">
    <div class="navbar-header ">
        <button type="button" class="navbar-toggle" data-toggle="collapse"
                data-target="#nav-services">
            <span class="sr-only">Toggle navigation</span> <span class="icon-bar"></span> <span
                    class="icon-bar"></span> <span class="icon-bar"></span>
        </button>
        <div class="logo-ccsd">
            <a class="brand" href="https://www.ccsd.cnrs.fr/"
               title="Centre pour la communication Scientifique directe"><img
                        alt="CCSD"
                        src="//static.ccsd.cnrs.fr/img/logo-ccsd-navbar.png" /></a>
        </div>
    </div>
    <div class="collapse navbar-collapse" id="nav-services">
        <ul class="nav navbar-nav">
            <li class="dropdown active"><a
                        href="#" class="dropdown-toggle" data-toggle="dropdown">HAL <b class="caret"
                                                                                       style="border-top-color:#ee5a35;border-bottom-color:#ee5a35;"></b></a>
                <ul class="dropdown-menu">
                    <li><a href="https://hal.archives-ouvertes.fr">HAL</a></li>
                    <li><a href="https://halshs.archives-ouvertes.fr">HALSHS</a></li>
                    <li><a href="https://tel.archives-ouvertes.fr">TEL</a></li>
                    <li><a href="https://medihal.archives-ouvertes.fr">MédiHAL</a></li>
                    <li><a href="https://hal.archives-ouvertes.fr/browse/portal">Liste des portails</a></li>
                    <li class="divider"></li>
                    <li><a href="https://aurehal.archives-ouvertes.fr" target="_blank">AURéHAL</a></li>
                    <li><a href="http://api.archives-ouvertes.fr/docs">API</a></li>
                    <li><a href="https://doc.archives-ouvertes.fr/">Documentation</a></li>
                </ul>
            </li>
            <li class=""><a href="https://www.episciences.org">Episciences.org</a></li>
            <li class=""><a href="https://www.sciencesconf.org">Sciencesconf.org</a></li>
            <li><a href="https://support.ccsd.cnrs.fr">Support</a></li>
        </ul>
    </div>
</div>
<?php if ((!isset($_SERVER['HTTPS'])) || ($_SERVER['HTTPS'] == null)) {
    $scheme = 'http';
} else {
    $scheme = 'https';
}
$filteredHostname = filter_var($_SERVER['HTTP_HOST'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
?>
<div id="container" class="container">
    <div class="jumbotron" style="margin-top: 1em">
        <h1 style="font-size:200%;">Le portail HAL</h1>
        <h2><?php echo '<strong>' . $scheme . '</strong>://' . $filteredHostname; ?> n'est pas accessible</h2>
    </div>
    <?php if ($scheme == 'https') {
        echo '<div class="alert alert-success" role="alert" style="font-size:130%;">';
        echo "<p>Il est possible que ce portail n'ait pas encore été configuré en <code>HTTP<strong>S</strong></code>. ";
        echo "Vous pouvez essayer d'accéder au site avec le protocole <code>HTTP</code> à l'adresse :</p>";

        echo '<p><a class="btn btn-primary" href="http://' . $filteredHostname . '"><strong>';
        echo 'http://</strong>' . $filteredHostname . '/</a></p>';
        echo '</div>';
    }
    ?>
    <div class="alert alert-success" role="alert" style="font-size:130%;">
        <p>Vous pouvez vérifier que ce portail existe dans la liste des portails :</p>
        <p><a class="btn btn-primary" href="https://hal.archives-ouvertes.fr/browse/portal">Liste des portails HAL</a>
        </p>
    </div>
    <div class="alert alert-info" role="alert" style="font-size:130%;">
        <p>Si vous pensez que c'est une erreur, merci de contacter le support :
            <a href="mailto:hal.support@ccsd.cnrs.fr">hal.support@ccsd.cnrs.fr</a>
        </p>

    </div>
</div>
<div class="footer footer-default">
    <div class="footer-contact">
        <h4>Contact</h4>
        <a href="//support.ccsd.cnrs.fr/" onclick="this.target='_blank'">support.ccsd.cnrs.fr</a><br/>
        <a href="mailto:hal.support@ccsd.cnrs.fr" onclick="this.target='_blank'">hal.support@ccsd.cnrs.fr</a>
    </div>
    <div class="footer-ccsd">
        <a href="//ccsd.cnrs.fr" onclick="this.target='_blank'"><img
                    src="//static.ccsd.cnrs.fr/img/logo-ccsd-footer.jpg"
                    width="200" alt="Logo CCSD"/></a>
    </div>
</div>
</body>
</html>
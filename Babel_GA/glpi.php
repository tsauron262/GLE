<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 22 avr. 2011
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : glpi.php
  * GLE-1.2
  */


    require_once('pre.inc.php');
    if ($conf->global->MAIN_MODULE_BABELGA) require_once(DOL_DOCUMENT_ROOT."/Babel_GA/LocationGA.class.php");

//1 get Id
    $id = $_REQUEST['id'];
    if ($id > 0)
    {
        $loc = new LocationGA($db);
        $loc->fetch($id);

//TODO conf->global->glpi rel path

        $js = '<style type="text/css">.vmenu{ display: none;}</style>';
        $js .= "<script type='text/javascript' src='".DOL_URL_ROOT."/Synopsis_Common/jquery/jquery.ba-resize.min.js'></script>";
        $js .= <<<EOF
<script>
jQuery(document).ready(function(){
  // Append an iFrame to the page.
  var iframe = jQuery('#iframeGLPI');

  // Called once the Iframe's content is loaded.
  iframe.load(function(){
    // The Iframe's child page BODY element.
    var iframe_content = iframe.contents().find('body');

    // Bind the resize event. When the iframe's size changes, update its height as
    // well as the corresponding info div.
    iframe_content.resize(function(){
      var elem = jQuery(this);
      var size = elem.outerHeight( true );
      if (size < 610) size = 610;
      // Resize the IFrame.
      iframe.css({ height: size });

    });

    // Resize the Iframe and update the info div immediately.
    iframe_content.resize();
  });
});
    function iFrameHeight()
    {
        //find the height of the internal page
        var  h = jQuery('#iframeGLPI')[0].contentWindow.document.body.scrollHeight;
        var  w = document.documentElement.clientWidth;
        //change the height of the iframe
        jQuery('#iframeGLPI').css('height',h);
        jQuery('#iframeGLPI').css('width',w);
    }

</script>
EOF;
        llxHeader($js, "GLPI interface",1);

        $glpiUrl= "/glpi/front/computer.php?contains%5B0%5D=".urlencode($loc->serial)."&field%5B0%5D=5&sort=1&deleted=0&start=0";
        print "<iframe scrolling='NO'  frameborder='0'  onLoad='iFrameHeight();' src='".$glpiUrl."' id='iframeGLPI' style='width:1050px; '>";
        print "</iframe>";

    } else {
        header('location: index.php');
    }

?>

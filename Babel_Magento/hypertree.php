  <html>
   /*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Create on : 4-1-2009
  *
  * Infos on http://www.finapro.fr
  *
  */
    <head>
      <link type="text/css" rel="stylesheet" href="example.css" />
  <!--[if IE]>
  <script type="text/javascript" src="jit/Extras/excanvas.js"></script>
  <![endif]-->
      <script type="text/javascript" src="Jit/jit.js" ></script>
      <script type="text/javascript" src="example.js" ></script>
      <script type="text/javascript" src="js/json.js" ></script>
      <script type="text/javascript" src="jquery/jquery-1.3.2.js" ></script>
      <script type="text/javascript" src="js/jquery.bgiframe.js" ></script>
      <script type="text/javascript" src="js/jquery.dimensions.js" ></script>
      <script type="text/javascript" src="js/jquery.tooltip.js" ></script>
    </head>

    <body onload="init();">

      <div id="infovis"></div>
      <div id="log"></div>

        <h4>Change Tree Orientation</h4>
        <table>
            <tr>
                <td>
                    <label for="r-left">left </label>
                </td>
                <td>
                    <input type="radio" id="r-left" name="orientation" checked="checked" value="left" />
                </td>
            </tr>
            <tr>
                 <td>
                    <label for="r-top">top </label>
                 </td>
                 <td>
                    <input type="radio" id="r-top" name="orientation" value="top" />
                 </td>
            <tr>
                 <td>
                    <label for="r-bottom">bottom </label>
                  </td>
                  <td>
                    <input type="radio" id="r-bottom" name="orientation" value="bottom" />
                  </td>
            </tr>
            <tr>
                  <td>
                    <label for="r-right">right </label>
                  </td>
                  <td>
                   <input type="radio" id="r-right" name="orientation" value="right" />
                  </td>
            </tr>
        </table>
    </body>
  </html>
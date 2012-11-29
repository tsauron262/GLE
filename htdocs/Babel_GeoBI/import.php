<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 28 mai 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : import.php
  * GLE-1.1
  */

    require_once('pre.inc.php');
if ($user->rights->GeoBI->GeoBI->Modifier != 1){  accessforbidden(); }
    if ($_REQUEST['socid'] > 0)
    {
        $param = '&placetype=&country=&lang=&format=XML&style=FULL&indent=true&socid='.$_REQUEST['socid'];
        if ($_REQUEST['id'] > 0)
        {
            $param .= '&id='.$_REQUEST['id'];
        }
        $js = <<<EOF
           <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
           <script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=true"></script>
    <script type='text/javascript'>
    var url='http://maps.google.com/maps?f=q&ie=UTF-8&iwloc=addr&om=1&z=12q=Aix-en-Provence&';
EOF;
$js .= "var defaultLat = ".$conf->global->MAIN_MODULE_GEOBI_LATDEFAULT.";";
$js .= "var defaultLng = ".$conf->global->MAIN_MODULE_GEOBI_LNGDEFAULT.";";
$js .= "var socid = ".$_REQUEST['socid'].";";
if ($_REQUEST['id']."x" != "x")
{
    $js .= "var Graphid = ".$_REQUEST['id'].";";
} else {
    $js .= "var Graphid = false;";
}


$js .= <<< EOF
var contentString = '<div id="content">'+
    ''+
    '</div>';

var infowindow = new google.maps.InfoWindow({
    content: contentString,
});



var map;
var myLatlng = new google.maps.LatLng(defaultLat, defaultLng);


ZoomControl.prototype.zoom_factor_ = null;
function ZoomControl(map, div,factor)
{
  var control = this;
      control.factor_ = factor;
  var controlDiv = div;
      controlDiv.style.padding = '5px';
      controlDiv.style.height = '30px';
      controlDiv.style.width = '193px';
      controlDiv.className = 'ui-widget ui-corner-all';
  var goHomeUI = document.createElement('DIV');
      goHomeUI.title = 'Contrôle du zoom';
      goHomeUI.className= 'ui-widget-header ui-state-default  ui-corner-all';
      goHomeUI.style.padding = '5px';
      goHomeUI.style.fontSize = '10px';
      goHomeUI.style.opacity = 0.5;
      goHomeUI.style.height = '20px';
      goHomeUI.style.zIndex=10;
  controlDiv.appendChild(goHomeUI);

  var zoomDiv = jQuery("<div style='width: 192px; height: 39px; position: absolute; margin-top: -29px; margin-left: 5px; z-index: 11;'>\
                               <button class='ui-widget-header ui-state-default ui-corner-all' style='float: left; padding: 3px 7px 3px 7px;'><span style='float: left;' class='ui-icon ui-icon-zoomin'></span><span style='float: left;'>Agrandir</span></button>\
                               <button class='ui-widget-header ui-state-default ui-corner-all' style='float: left; padding: 3px 7px 3px 7px; margin-left: 5px;'><span style='float: left;' class='ui-icon ui-icon-zoomout'></span><span style='float: left;'>R&eacute;duire</span></button>\
                               </div>");
  jQuery(controlDiv).append(zoomDiv);
  jQuery(zoomDiv).find('.ui-icon-zoomin').parent().click(function(){
      var val = map.getZoom();
          val ++;
      map.setZoom(val);
  });
  jQuery(zoomDiv).find('.ui-icon-zoomout').parent().click(function(){
      var val = map.getZoom();
          val --;
      map.setZoom(val);
  });
  jQuery(zoomDiv).find('button').mouseover(function(){
     jQuery(this).addClass('ui-state-hover');
  });
  jQuery(zoomDiv).find('button').mouseout(function(){
     jQuery(this).removeClass('ui-state-hover');
  });

}

TypeMapControl.prototype.type_ = null;
function TypeMapControl(map, div, home) {
  var control = this;
      control.type_ = home;
  var controlDiv = div;
      controlDiv.style.padding = '5px';
      controlDiv.style.height = '30px';
      controlDiv.style.width = '163px';
      controlDiv.className = 'ui-widget ui-corner-all';

  // Set CSS for the control border
  var goHomeUI = document.createElement('DIV');
      goHomeUI.title = 'Click to set the map to Home';
      goHomeUI.className= 'ui-widget-header ui-state-default  ui-corner-all';
      goHomeUI.style.padding = '5px';
      goHomeUI.style.fontSize = '10px';
      goHomeUI.style.opacity = 0.5;
      goHomeUI.style.height = '20px';
      goHomeUI.style.zIndex=10;
  controlDiv.appendChild(goHomeUI);

    //mapTypeId: google.maps.MapTypeId.HYBRID,
  var goHomeText = jQuery("<span style='position: absolute; margin-top: -27px; margin-left: 5px; z-index: 11;'>\
        <SELECT id='typeMap'><OPTION value='"+google.maps.MapTypeId.HYBRID+"'>Hybride</OPTION>\
                             <OPTION value='"+google.maps.MapTypeId.ROADMAP+"'>Carte routi&egrave;re</OPTION>\
                             <OPTION value='"+google.maps.MapTypeId.SATELLITE+"'>Satellite</OPTION>\
                             <OPTION value='"+google.maps.MapTypeId.TERRAIN+"'>Terrain</OPTION>\
        </SELECT></span>");
  jQuery(controlDiv).append(goHomeText);
  jQuery(goHomeText).find('select').selectmenu({style: 'dropdown', maxHeight: 300 });
  jQuery(goHomeText).find('select').change(function(){
    var val = jQuery(goHomeText).find('select :selected').val();
    map.setMapTypeId(val);//google.maps.MapTypeId.HYBRID

  });


}

// Define a property to hold the Home state
HomeControl.prototype.home_ = null;

// Define setters and getters for this property
HomeControl.prototype.getHome = function() {
  return this.home_;
}

HomeControl.prototype.setHome = function(home) {
  this.home_ = home;
}

function HomeControl(map, div, home) {

  // Get the control DIV. We'll attach our control
  // UI to this DIV.
  var controlDiv = div;

  // We set up a variable for the 'this' keyword
  // since we're adding event listeners later
  // and 'this' will be out of scope.
  var control = this;

  // Set the home property upon construction
  control.home_ = home;

  // Set CSS styles for the DIV containing the control
  // Setting padding to 5 px will offset the control
  // from the edge of the map
  controlDiv.style.padding = '5px';
  controlDiv.className = 'ui-widget';

  // Set CSS for the control border
  var goHomeUI = document.createElement('DIV');
      goHomeUI.title = 'Click to set the map to Home';
      goHomeUI.className= 'ui-widget-header ui-state-default';
      goHomeUI.style.padding = '5px';
      goHomeUI.style.fontSize = '10px';
      jQuery(goHomeUI).mouseover(function(){
        jQuery(this).addClass('ui-state-hover');
      });
      jQuery(goHomeUI).mouseout(function(){
        jQuery(this).removeClass('ui-state-hover');
      });
  controlDiv.appendChild(goHomeUI);

  // Set CSS for the control interior
  var goHomeText = document.createElement('DIV');
      goHomeText.innerHTML = 'Bookmark';
  goHomeUI.appendChild(goHomeText);

  // Set CSS for the setHome control border
  var setHomeUI = document.createElement('DIV');
      setHomeUI.title = 'Cr&eacute;e un bookmark';
      setHomeUI.className= 'ui-widget-header ui-state-default';
      setHomeUI.style.padding = '5px';
      setHomeUI.style.fontSize = '10px';
      jQuery(setHomeUI).mouseover(function(){
        jQuery(this).addClass('ui-state-hover');
      });
      jQuery(setHomeUI).mouseout(function(){
        jQuery(this).removeClass('ui-state-hover');
      });

  controlDiv.appendChild(setHomeUI);

  // Set CSS for the control interior
  var setHomeText = document.createElement('DIV');
  setHomeText.innerHTML = 'Cr&eacute;er un bookmark';
  setHomeUI.appendChild(setHomeText);

  // Setup the click event listener for Home:
  // simply set the map to the control's current home property.
  google.maps.event.addDomListener(goHomeUI, 'click', function() {
    var currentHome = control.getHome();
    map.setCenter(currentHome);
  });

  // Setup the click event listener for Set Home:
  // Set the control's home to the current Map center.
  google.maps.event.addDomListener(setHomeUI, 'click', function() {
    var newHome = map.getCenter();
    control.setHome(newHome);
  });
}


function initialize(latlng,name,country_name) {
      //var latlng = new google.maps.LatLng(<?php echo $lat?>, <?php echo $long ?>);
      curLatLng=latlng;
      Curname=name;
      jQuery('#name').val(Curname);
      infowindow.setContent('<div id="content"><H1>'+name+'</H1><br/><p>'+country_name+'</p></div>');
      Curcountry_name = country_name;
      var myOptions = {
            zoom: 8,
            disableDefaultUI: true,
            center: latlng,
            mapTypeId: google.maps.MapTypeId.HYBRID,
            navigationControl: false,
            mapTypeControl: false,
            scaleControl: true,
            scaleControlOptions: {
                position: google.maps.ControlPosition.TOP_RIGHT
            }

      };
      map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);

      var typeMapControlDiv = document.createElement('DIV');
      var typeMapControl = new TypeMapControl(map, typeMapControlDiv, 'HYBRID');

      typeMapControlDiv.index = 1;
      map.controls[google.maps.ControlPosition.TOP_LEFT].push(typeMapControlDiv);
      var homeControlDiv = document.createElement('DIV');
      var homeControl = new HomeControl(map, homeControlDiv, latlng);

      homeControlDiv.index = 1;
      map.controls[google.maps.ControlPosition.TOP_LEFT].push(homeControlDiv);


      var zoomControlDiv = document.createElement('DIV');
          zoomControl = new ZoomControl(map, zoomControlDiv, 8);

      zoomControl.index = 1;
      map.controls[google.maps.ControlPosition.BOTTOM_LEFT].push(zoomControlDiv);

     var marker = new google.maps.Marker({
          position: latlng,
          map: map,
          title: name,
          draggable: true,
//          dragend: dragEnd()
      });
//
        google.maps.event.addListener(marker, 'click', function() {
          infowindow.setContent('<div id="content"><H1>'+name+'</H1><br/><p>'+country_name+'</p></div>');
          infowindow.open(map,marker);
        });
    google.maps.event.addListener(marker, "dragstart", function() {
      infowindow.close();
      });

    google.maps.event.addListener(marker, "dragend", function(a) {
          infowindow.open(map,marker);
      dragEnd(a);
      });


}
var curLatLng = "";
function dragEnd(latlng)
{
    curLatLng=latlng;
}

function saveData()
{
    var lat = (curLatLng.latLng?curLatLng.latLng.b:curLatLng.b);
    var lng = (curLatLng.latLng?curLatLng.latLng.c:curLatLng.c);
    var param = "&lat="+lat+"&lng="+lng+'&socid='+socid+"&name="+Curname+'&countryCode='+Curcountry_name
    if (Graphid > 0)
    {
        param+='&id='+Graphid;
    }
    jQuery.ajax({
        url: 'ajax/saveImport-xmlresponse.php',
        data: param,
        type: 'post',
        datatype:'xml',
        success: function(msg){
            if (jQuery(msg).find('OK').text()=='OK')
            {
                jQuery('div.fiche:eq(0)').find('.ui-state-highlight').toggle('slide')
                jQuery('div.fiche:eq(0)').find('.ui-state-highlight').remove();
                jQuery('div.fiche:eq(0)').prepend('<div style="display: none; padding: 3px 10px;" class="ui-state-highlight"><span class="ui-icon ui-icon-info" style="margin-right: 3px; margin-top: -2px; float: left"></span>Enregistrement effectu&eacute;</div>');
                jQuery('div.fiche:eq(0)').find('.ui-state-highlight').toggle('slide');
            }  else {
                jQuery('div.fiche:eq(0)').find('.ui-state-highlight').toggle('slide')
                jQuery('div.fiche:eq(0)').find('.ui-state-highlight').remove();
                jQuery('div.fiche:eq(0)').prepend('<div style="display: none; padding: 3px 10px;" class="ui-state-highlight"><span class="ui-icon ui-icon-info" style="margin-right: 3px; margin-top: -2px; float: left"></span>Une erreur est survene lors de l\'enregistrement</div>');
                jQuery('div.fiche:eq(0)').find('.ui-state-highlight').toggle('slide');
            }
        },
        error: function(XMLHttpRequest, textStatus, errorThrown)
        {
            jQuery('div.fiche:eq(0)').find('.ui-state-highlight').toggle('slide')
            jQuery('div.fiche:eq(0)').find('.ui-state-highlight').remove();
            jQuery('div.fiche:eq(0)').prepend('<div style="display: none; padding: 3px 10px;" class="ui-state-highlight"><span class="ui-icon ui-icon-info" style="margin-right: 3px; margin-top: -2px; float: left"></span>Erreur : '+textStatus+' '+errorThrown+'</div>');
            jQuery('div.fiche:eq(0)').find('.ui-state-highlight').toggle('slide');
        },

    })
}
function moveToDarwin() {
    map.setCenter(myLatlng);
}


jQuery(document).ready(function(){
    jQuery('#saveData').click(function(){
        saveData();
    });
    jQuery('#name').blur(function(){
        Curname = jQuery('#name').val();
        infowindow.setContent('<div id="content"><H1>'+Curname+'</H1><br/><p>'+Curcountry_name+'</p></div>');
    });

    jQuery.ajax({
        url : 'gmaps-xmlresponse.php',
        datatype: 'xml',
        type: 'get',
        async: false,
        success: function(msg){
            if (jQuery(msg).find('status').text()=='ZERO_RESULTS' || jQuery(msg).find('status').text()=='INVALID_REQUEST'|| jQuery(msg).find('result').find('lat').length < 1)
            {
                var lat = defaultLat;
                var lng = defaultLng;
                var myLatlng = new google.maps.LatLng(lat, lng);
                initialize(myLatlng,'Non trouvé','FR');

            } else {
                jQuery(msg).find('result').each(function(){
                    var country_name = jQuery(this).find('countryCode').text();
                    var name = jQuery(this).find('name').text();
                    var lat = jQuery(this).find('lat').text();
                    var lng = jQuery(this).find('lng').text();
                    var googleUrl = jQuery(this).find('googleUrl').text();
                    var html = "<a href='"+googleUrl+"'>"+name+" - "+country_name+"</a><br>";
                    jQuery('#GeoResult').append(html);
                    var myLatlng = new google.maps.LatLng(lat, lng);
                    initialize(myLatlng,name,country_name);
                });
            }
        },
        data:
EOF;
 $js .= "'".$param."',";
$js.= <<<EOF
        error: function(a,b,c)
        {
        }
    });
});


</script>

EOF;
        llxHeader($js,'Import - GeoBI','',true);
        //1 rechercher via l'adresse
        print "<div id='fiche'>";

print <<<EOF
<button style='padding: 5px 10px;' class='butAction ui-corner-all ui-widget-header ui-state-default' onClick="location.href='index.php'">
        <span class='ui-icon ui-icon-arrowreturnthick-1-w' style='margin-top: -1px; margin-right: 3px; float: left;'></span>Retour
  </button><br/><br/>
EOF;


        //2 Afficher la maps google
        print '<div id="GeoResult"></div>';
        print '<div id="map_canvas" style="width: 600px; height: 400px"></div>';
        //3 Ajuster la maps google

        //4 Sauvegarder


        print "<form onsubmit='return false;' method='post' action='import.php'>";
        print "<input id='name'></input>";
        print "<button id='saveData' class='butAction ui-widget-header ui-state-default ui-corner-all' >Enregistrer</button>";
        print "</form>";
        print "</div>";

    } else {

        $requete = "SELECT ".MAIN_DB_PREFIX."societe.rowid,
                           ".MAIN_DB_PREFIX."societe.address,
                           ".MAIN_DB_PREFIX."societe.cp,
                           ".MAIN_DB_PREFIX."societe.ville,
                           ".MAIN_DB_PREFIX."societe.nom,
                           Babel_GeoBI.lat,
                           Babel_GeoBI.lng,
                           Babel_GeoBI.id as graphId,
                           Babel_GeoBI.countryCode
                      FROM ".MAIN_DB_PREFIX."societe
                    LEFT JOIN Babel_GeoBI on ".MAIN_DB_PREFIX."societe.rowid = Babel_GeoBI.socid";

        $sql = $db->query($requete);
        $js="";
        llxHeader($js,'Import - GeoBI','',true);
        print "<div id='fiche'>";
print <<<EOF
<button style='padding: 5px 10px;' class='butAction ui-corner-all ui-widget-header ui-state-default' onClick="location.href='index.php'">
        <span class='ui-icon ui-icon-arrowreturnthick-1-w' style='margin-top: -1px; margin-right: 3px; float: left;'></span>Retour
  </button><br/><br/>
EOF;

        print "<table cellpadding=15>";
        print "<tr><th class='ui-widget-header ui-state-default'>G&eacute;olocaliser ?
                   <th class='ui-widget-header ui-state-default'>Soci&eacute;t&eacute;
                   <th class='ui-widget-header ui-state-default'>Adresse
                   <th class='ui-widget-header ui-state-default'>Code pays
                   <th class='ui-widget-header ui-state-default'>Action";
        while ($res = $db->fetch_object($sql))
        {
            $rechercher = "<span><a href='import.php?socid=".$res->rowid."'>Rechercher</a></span>";
            $ajuster = "<span><a href='import.php?socid=".$res->rowid."&id=". $res->graphId."'>Ajuster</a></span>";
            print '<tr class="ui-widget-content"><td>'.($res->lat .'x' != 'x'?img_tick()." ".$ajuster:img_error(). " ".$rechercher).'<td>'.$res->nom."<td>".$res->address." ".$res->cp." ".$res->ville.'<td>'.$res->countryCode.'<td><a href="view.php?socid='.$res->rowid.'&id='. $res->graphId.'">Voir</a>';
        }
        print "</table>";
        print "</div>";

    }


?>

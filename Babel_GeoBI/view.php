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
  * Name : view.php
  * GLE-1.1
  */
    require_once('pre.inc.php');
if ($user->rights->GeoBI->GeoBI->Affiche != 1){  accessforbidden(); }
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
    var url='http://maps.google.com/maps?f=q&ie=UTF-8&iwloc=addr&om=1&z=12q=Aix-en-Provence';
EOF;
$js .=  "\n\n\n";
$js .= "var defaultLat = '".$conf->global->MAIN_MODULE_GEOBI_LATDEFAULT."';\n";
$js .=  "\n\n\n";
$js .= "var defaultLng = '".$conf->global->MAIN_MODULE_GEOBI_LNGDEFAULT."';\n";
$js .= "var socid = ".$_REQUEST['socid'].";\n";
if ($_REQUEST['id']."x" != "x")
{
    $js .= "var Graphid = ".$_REQUEST['id'].";\n";
} else {
    $js .= "var Graphid = false;\n";
}


$js .= <<< EOF
var contentString = '<div id="content"></div>';

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
            streetViewControl: true,
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
          draggable: false,
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
//var mapOptions = {
//  center: fenway,
//  zoom: 14,
//  mapTypeId: google.maps.MapTypeId.ROADMAP,
//  streetViewControl: true
//};
//var map = new google.maps.Map(
//    document.getElementById("map_canvas"), mapOptions);
    var panoramaOptions = {
      position: curLatLng,
      pov: {
        heading: 34,
        pitch: 10,
        zoom: 1
      }
    };
    var panorama = new  google.maps.StreetViewPanorama(document.getElementById("pano"), panoramaOptions);
    map.setStreetView(panorama)

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
                initialize(myLatlng,'Non trouv&eacute;','FR');

            } else {
                jQuery(msg).find('result').each(function(){
                    var country_name = jQuery(this).find('countryCode').text();
                    var name = jQuery(this).find('name').text();
                    var lat = jQuery(this).find('lat').text();
                    var lng = jQuery(this).find('lng').text();
                    var googleUrl = jQuery(this).find('googleUrl').text();
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

        if ($_REQUEST['from']=='societe')
        {
            require_once(DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php');
            $tmpSoc = new Societe($db);
            $tmpSoc->fetch($_REQUEST['socid']);
            $head = societe_prepare_head($tmpSoc);

            dol_fiche_head($head, 'Geolocalisation', $langs->trans("ThirdParty"));

        }

        //1 rechercher via l'adresse
        print "<div id='fiche'>";

        print "<table><tr><td><button style='padding: 5px 10px;' onclick='location.href=\"index.php\"' class='butAction ui-state-default ui-widget-header ui-corner-all'><span class='ui-icon ui-icon-image' style='float: left; margin-top: -1px; margin-right: 3px;'></span>Geo - BI</button><td>&nbsp;<td>&nbsp;<td>";
        if ($user->rights->GeoBI->GeoBI->Modifie != 1){
            print "<div><button style='padding: 5px 10px;' onclick='location.href=\"import.php?socid=".$_REQUEST['socid']."\"'  class='butAction ui-state-default ui-widget-header ui-corner-all'><span class='ui-icon ui-icon-wrench' style='float: left; margin-top: -1px; margin-right: 3px;'></span>Configuration</button>";
        }
        print "</table>";

        print "<br/>";

        //2 Afficher la maps google
        print '<div id="GeoResult"></div>';
        print '<div id="map_canvas" style="width: 600px; height: 400px"></div>';
        //3 Ajuster la maps google


        print "</div>";
        print "<div id='pano' style='width: 600px; height: 400px;'></div>";

    }

?>

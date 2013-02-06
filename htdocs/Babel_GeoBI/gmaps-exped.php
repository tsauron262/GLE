<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 1 avr. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : gmaps.php
  * GLE-1.1
  */


$key = "ABQIAAAAENh_drcI93tG2EgxZCrmbxRi_j0U6kJrkFvY4-OX2XYmEAa76BRK1ueHpqfmxZCotDvL2bmAs1MLmA";


require_once('../main.inc.php');
if ($user->rights->GeoBI->GeoBI->Affiche != 1){  accessforbidden(); }
$requete = "SELECT * FROM ".MAIN_DB_PREFIX."c_ville WHERE nom ='".preg_replace('/\ /',"-",$mysoc->ville)."'";
$sql = $db->query($requete);
$res = $db->fetch_object($sql);
$lat = ($res->latitude."x"=="x"?"43.533329":$res->latitude);
$long = ($res->longitude."x"=="x"?"5.433330":$res->longitude);
$js='<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />';
$js.='<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false&region=FR"></script>';
$js.='<script type="text/javascript" src="'.DOL_URL_ROOT.'/Synopsis_Common/js/markerclusterer.js"></script>';
$js.='<script type="text/javascript" src="'.DOL_URL_ROOT.'/Synopsis_Common/jquery/visualize.jQuery.js"></script>';

$js.='<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/Synopsis_Common/css/basic.css" />';
$js.='<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/Synopsis_Common/css/visualize.css" />';

top_menu($js, "GeoBI");

?>
<script type="text/javascript">



      var markerClusterer = null;
      var imageUrl = 'http://chart.apis.google.com/chart?cht=mm&chs=24x32&' +
          'chco=FFFFFF,008CFF,000000&ext=.png';

var data = "";
jQuery(document).ready(function(){
//    jQuery.getJSON('ajax/test.json.php', function(pData) {
    jQuery.getJSON('ajax/expedition.json.php', function(pData) {
      data = pData;
      //console.log("data",data.results);
      initialize();
    });
})



var zoomControl;
var contentString = '<div id="content">'+
    ''+
    '</div>';

var infowindow = new Array();


        var marker= new Array();
        var infowindow = new Array();
 function refreshMap() {
        if (markerClusterer) {
          markerClusterer.clearMarkers();
        }
        var markers = [];

        var markerImage = new google.maps.MarkerImage(imageUrl,
            new google.maps.Size(16, 32));
            //console.log(data.count);
        for (var i=0 ; i<data.count; ++i) {
            var latLng = new google.maps.LatLng(data.results[i].lat,
            data.results[i].lng);
            marker[i] = new google.maps.Marker({
                position: latLng,
                draggable: false,
                icon: markerImage,
                title: ''+i

            });


             infowindow[i] = new google.maps.InfoWindow({  content: '<div id="content">\
                        <H1>'+data.results[i].nom+'</H1>\
                        <p><div style="height: 340px; width: 450px;"><table border=0>\
                            <tr>\
                                <td colspan=2 style="padding-left: 25px">'+data.results[i].stat+'\
                            <tr><td colspan=2><br/>\
                            <tr class="ui-widget-header ui-state-default"><td>Nombre d\'exp&eacute;ditions\
                                <td><span>'+data.results[i].cnt+'</span>\
                            <tr class="ui-widget-header ui-state-default"><td>'+data.results[i].y2+'\
                                <td><span>'+data.results[i].y2c+'</span>\
                            <tr class="ui-widget-header ui-state-default"><td>'+data.results[i].y1+'\
                                <td><span>'+data.results[i].y1c+'</span>\
                            <tr class="ui-widget-header ui-state-default"><td>'+data.results[i].y0+'\
                                <td><span>'+data.results[i].y0c+'</span>\
                        </table></div></p>\
                    </div>',
                });
                marker[i].iter = i;
      google.maps.event.addListener(marker[i], 'click', function() {
        for (var j=0;j<data.count;j++)
        {
            infowindow[j].close()
        }
         var index = parseInt( (this).getTitle(),10 );
         infowindow[index].open(map,this);
         jQuery('#expedTable'+data.results[index].socid).visualize({
            width:380,
            height:200,
            appendTitle: true,
            title: "Exp&eacute;ditions",
         });
         jQuery('#expedTable'+data.results[index].socid).parent().parent().parent().parent().parent().parent().parent().css('overflow','hidden');
      });
            markers.push(marker[i]);

        }


      var styles = [[{
        url: '../images/people35.png',
        height: 35,
        width: 35,
        opt_anchor: [16, 0],
        opt_textColor: '#ff00ff',
        opt_textSize: 10
      }, {
        url: '../images/people45.png',
        height: 45,
        width: 45,
        opt_anchor: [24, 0],
        opt_textColor: '#ff0000',
        opt_textSize: 11
      }, {
        url: '../images/people55.png',
        height: 55,
        width: 55,
        opt_anchor: [32, 0],
        opt_textSize: 12
      }], [{
        url: '../images/conv30.png',
        height: 27,
        width: 30,
        anchor: [3, 0],
        textColor: '#ff00ff',
        opt_textSize: 10
      }, {
        url: '../images/conv40.png',
        height: 36,
        width: 40,
        opt_anchor: [6, 0],
        opt_textColor: '#ff0000',
        opt_textSize: 11
      }, {
        url: '../images/conv50.png',
        width: 50,
        height: 45,
        opt_anchor: [8, 0],
        opt_textSize: 12
      }], [{
        url: '../images/heart30.png',
        height: 26,
        width: 30,
        opt_anchor: [4, 0],
        opt_textColor: '#ff00ff',
        opt_textSize: 10
      }, {
        url: '../images/heart40.png',
        height: 35,
        width: 40,
        opt_anchor: [8, 0],
        opt_textColor: '#ff0000',
        opt_textSize: 11
      }, {
        url: '../images/heart50.png',
        width: 50,
        height: 44,
        opt_anchor: [12, 0],
        opt_textSize: 12
      }]];

        var zoom = zoomControl.factor_;
//        var size = parseInt(document.getElementById('size').value, 10);
//        var style = parseInt(document.getElementById('style').value, 10);
        zoom = zoom == -1 ? null : zoom;
//        size = size == -1 ? null : size;
size=40;
//        style = style == -1 ? null: style;
style=null;
        markerClusterer = new MarkerClusterer(map, markers, {
          maxZoom: zoom,
          gridSize: size,
          styles: null
        });
      }


var map;
var myLatlng = new google.maps.LatLng(-12.461334, 130.841904);

/**
 * The HomeControl adds a control to the map that
 * returns the user to the control's defined home.
 */

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
function initialize() {
    var latlng = new google.maps.LatLng(<?php echo $lat?>, <?php echo $long ?>);
    var myOptions = {
        zoom: 8,
        disableDefaultUI: true,
        center: latlng,
        mapTypeId: google.maps.MapTypeId.HYBRID,
        navigationControl: false,
//        navigationControlOptions: {
//            style: google.maps.NavigationControlStyle.ANDROID,
//            position: google.maps.ControlPosition.BOTTOM_LEFT
//        },
        mapTypeControl: false,
        scaleControl: true,
        scaleControlOptions: {
            position: google.maps.ControlPosition.TOP_RIGHT
        }

    };
    map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
//
//    var marker = new google.maps.Marker({
//        position: myLatlng,
//        map: map,
//        title:"Hello World!"
//    });
//
//    google.maps.event.addListener(marker, 'click', function() {
//        map.setZoom(8);
//    });
//google.maps.event.addListener(map, 'click', function(event) {
//    placeMarker(event.latLng);
//  });
// Add 5 markers to the map at random locations
//  var southWest = new google.maps.LatLng(-31.203405,125.244141);
//  var northEast = new google.maps.LatLng(-25.363882,131.044922);
//  var bounds = new google.maps.LatLngBounds(southWest,northEast);
//  map.fitBounds(bounds);
//  var lngSpan = northEast.lng() - southWest.lng();
//  var latSpan = northEast.lat() - southWest.lat();
//  for (var i = 0; i < 5; i++) {
//    var location = new google.maps.LatLng(southWest.lat() + latSpan * Math.random(),
//        southWest.lng() + lngSpan * Math.random());
//    var marker = new google.maps.Marker({
//        position: location,
//        map: map
//    });
//    var j = i + 1;
//    marker.setTitle(j.toString());
//  }
//

  var typeMapControlDiv = document.createElement('DIV');
  var typeMapControl = new TypeMapControl(map, typeMapControlDiv, 'HYBRID');

  typeMapControlDiv.index = 1;
  map.controls[google.maps.ControlPosition.TOP_LEFT].push(typeMapControlDiv);


// Create the DIV to hold the control and
  // call the HomeControl() constructor passing
  // in this DIV.
  var homeControlDiv = document.createElement('DIV');
  var homeControl = new HomeControl(map, homeControlDiv, myLatlng);

  homeControlDiv.index = 1;
  map.controls[google.maps.ControlPosition.TOP_LEFT].push(homeControlDiv);


  var zoomControlDiv = document.createElement('DIV');
      zoomControl = new ZoomControl(map, zoomControlDiv, 8);

  zoomControl.index = 1;
  map.controls[google.maps.ControlPosition.BOTTOM_LEFT].push(zoomControlDiv);

        refreshMap();


}


function placeMarker(location) {
  var clickedLocation = new google.maps.LatLng(location);
  var marker = new google.maps.Marker({
      position: location,
      map: map
  });

  map.setCenter(location);
}
</script>
  <button style='padding: 5px 10px;' class='butAction ui-corner-all ui-widget-header ui-state-default' onClick="location.href='index.php'">
        <span class='ui-icon ui-icon-arrowreturnthick-1-w' style='margin-top: -1px; margin-right: 3px; float: left;'></span>Retour
  </button><br/><br/>

  <div id="map_canvas" style="width:100%; height:100%; min-height: 800px; "></div>


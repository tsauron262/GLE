<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 29 avr. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : gisgraphy.php
  * GLE-1.1
  */


$url = "http://services.gisgraphy.com/fulltext/fulltextsearch";
$param = '&placetype=&country=&lang=&format=XML&style=FULL&indent=true&socid=229';


?>
<HTML>
<head>
<script type="text/javascript" src="../Synopsis_Common/jquery/jquery-1.3.2.js"></script>

<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />

<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=true"></script>
</head>
<body>
<script type='text/javascript'>
var url='http://maps.google.com/maps?f=q&ie=UTF-8&iwloc=addr&om=1&z=12q=Aix-en-Provence&';

var contentString = '<div id="content">'+
    '<div id="siteNotice">'+
    '</div>'+
    '<h1 id="firstHeading" class="firstHeading">Uluru</h1>'+
    '<div id="bodyContent">'+
    '<p><b>Uluru</b>, also referred to as <b>Ayers Rock</b>, is a large ' +
    'sandstone rock formation in the southern part of the '+
    'Northern Territory, central Australia. It lies 335 km (208 mi) '+
    'south west of the nearest large town, Alice Springs; 450 km '+
    '(280 mi) by road. Kata Tjuta and Uluru are the two major '+
    'features of the Uluru - Kata Tjuta National Park. Uluru is '+
    'sacred to the Pitjantjatjara and Yankunytjatjara, the '+
    'Aboriginal people of the area. It has many springs, waterholes, '+
    'rock caves and ancient paintings. Uluru is listed as a World '+
    'Heritage Site.</p>'+
    '<p>Attribution: Uluru, <a href="http://en.wikipedia.org/w/index.php?title=Uluru&oldid=297882194">'+
    'http://en.wikipedia.org/w/index.php?title=Uluru</a> (last visited June 22, 2009).</p>'+
    '</div>'+
    '</div>';

var infowindow = new google.maps.InfoWindow({
    content: contentString,
});


jQuery(document).ready(function(){
    jQuery('#go').click(function(){
        jQuery.ajax({
            url : 'gmaps-xmlresponse.php',
            datatype: 'xml',
            type: 'get',
            success: function(msg){
                //console.log(msg);
                jQuery(msg).find('result').each(function(){
                    var country_name = jQuery(this).find('countryCode').text();
                    var name = jQuery(this).find('name').text();
                    var lat = jQuery(this).find('lat').text();
                    var lng = jQuery(this).find('lng').text();
                    var googleUrl = jQuery(this).find('googleUrl').text();
                    var html = "<a href='"+googleUrl+"'>"+name+" - "+country_name+"</a><br>";
                    jQuery('#test').append(html);
                    console.info(html);
                    var myLatlng = new google.maps.LatLng(lat, lng);
                    var myOptions = {
                      zoom: 16,
                      center: myLatlng,
                      mapTypeId: google.maps.MapTypeId.ROADMAP
                    };
                    var map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
                     var marker = new google.maps.Marker({
                          position: myLatlng,
                          map: map,
                          title:"Hello World! " + name
                      });

                    google.maps.event.addListener(marker, 'click', function() {
                      infowindow.setContent('<div id="content"><H1>'+name+'</H1><br/><p>'+country_name+'</p></div>');
                      infowindow.open(map,marker);
                    });
                });
            },
            data:'<?php print $param; ?>',
            error: function(a,b,c)
            {
                console.log(a);
                console.log(b);
                console.log(c);
            }
        });
    });
});


</script>
<body>
<div id='test'></div>
<button id='go'>Go</button>
<div id="map_canvas" style="width: 600px; height: 400px"></div>
</body>
</html>
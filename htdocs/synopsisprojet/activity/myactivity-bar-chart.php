<?php
/*
  ** BIMP-ERP by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 24 juil. 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : myactivity-bar-chart.php
  * BIMP-ERP-1.0
  */


print <<<EOF

{

  "title":{
    "text":"Test stacked bar charts",
    "style":"{font-size:16px;}"
  },

  "elements":[
    {
      "type":      "bar_stack",
      "keys": [
        {"colour":"#FFB900", "text": "Key 1", "font-size": 10},
        {"colour":"#FFB9F0", "text": "Key 2", "font-size": 16}
      ],
      "tip":       "#total#<br>(bar total)",
      "on-show":    {"type": "drop", "delay":0.5, "cascade":0.6},
      "values": [
        [2.5,{"val":5}],
        [{"val":2.5},{"val":5},{"val":2.5}],
        null,
        [{"val":5},{"val":5},{"val":2},{"val":2},{"val":2,"colour":"#ff00ff","tip":"hello"},{"val":2},{"val":2}],
        [{"val":5},{"val":5},{"val":2},{"val":2},{"val":2,"colour":"#ff00ff","tip": "Click me!", "on-click": "trace:clicked!!"},{"val":2},{"val":2}]
        ]
    },
    {
      "type":      "bar",
      "colour":    "#9933CC",
      "text":      "Bar",
      "font-size": 10,
      "values" :   [9,6,7,9],
      "on-click":  "trace:http://example.com",
      "on-show":    {"type": "drop", "delay":0.5, "cascade":0.6},
      "tip":       "#total#<br>(bar te total)"

    }
  ],

  "x_axis":{
    "max":4,
    "steps": 1,
    "labels": {
      "labels": ["January","February","March","April"]
    },
    "stroke": 12,
    "tick-height": 6
  },

  "y_axis":{
    "max": 20
  },

  "tooltip":{
    "mouse": 2,
    "stroke":1
  }
}



EOF;


?>
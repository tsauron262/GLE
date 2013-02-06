/**
 * @file
 *    Demo implementation of jQuery.dashboard() plugin.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
function serialize (mixed_value) {
    // Returns a string representation of variable (which can later be unserialized)
    //
    // version: 1006.1915
    // discuss at: http://phpjs.org/functions/serialize
    // +   original by: Arpad Ray (mailto:arpad@php.net)
    // +   improved by: Dino
    // +   bugfixed by: Andrej Pavlovic
    // +   bugfixed by: Garagoth
    // +      input by: DtTvB (http://dt.in.th/2008-09-16.string-length-in-bytes.html)
    // +   bugfixed by: Russell Walker (http://www.nbill.co.uk/)
    // +   bugfixed by: Jamie Beck (http://www.terabit.ca/)
    // +      input by: Martin (http://www.erlenwiese.de/)
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // -    depends on: utf8_encode
    // %          note: We feel the main purpose of this function should be to ease the transport of data between php & js
    // %          note: Aiming for PHP-compatibility, we have to translate objects to arrays
    // *     example 1: serialize(['Kevin', 'van', 'Zonneveld']);
    // *     returns 1: 'a:3:{i:0;s:5:"Kevin";i:1;s:3:"van";i:2;s:9:"Zonneveld";}'
    // *     example 2: serialize({firstName: 'Kevin', midName: 'van', surName: 'Zonneveld'});
    // *     returns 2: 'a:3:{s:9:"firstName";s:5:"Kevin";s:7:"midName";s:3:"van";s:7:"surName";s:9:"Zonneveld";}'
    var _getType = function (inp) {
        var type = typeof inp, match;
        var key;
        if (type == 'object' && !inp) {
            return 'null';
        }
        if (type == "object") {
            if (!inp.constructor) {
                return 'object';
            }
            var cons = inp.constructor.toString();
            match = cons.match(/(\w+)\(/);
            if (match) {
                cons = match[1].toLowerCase();
            }
            var types = ["boolean", "number", "string", "array"];
            for (key in types) {
                if (cons == types[key]) {
                    type = types[key];
                    break;
                }
            }
        }
        return type;
    };
    var type = _getType(mixed_value);
    var val, ktype = '';

    switch (type) {
        case "function":
            val = "";
            break;
        case "boolean":
            val = "b:" + (mixed_value ? "1" : "0");
            break;
        case "number":
            val = (Math.round(mixed_value) == mixed_value ? "i" : "d") + ":" + mixed_value;
            break;
        case "string":
            /*mixed_value = this.utf8_encode(mixed_value);*/
            val = "s:" + encodeURIComponent(mixed_value).replace(/%../g, 'x').length + ":\"" + mixed_value + "\"";
            break;
        case "array":
        case "object":
            val = "a";
            /*
            if (type == "object") {
                var objname = mixed_value.constructor.toString().match(/(\w+)\(\)/);
                if (objname == undefined) {
                    return;
                }
                objname[1] = this.serialize(objname[1]);
                val = "O" + objname[1].substring(1, objname[1].length - 1);
            }
            */
            var count = 0;
            var vals = "";
            var okey;
            var key;
            for (key in mixed_value) {
                ktype = _getType(mixed_value[key]);
                if (ktype == "function") {
                    continue;
                }

                okey = (key.match(/^[0-9]+$/) ? parseInt(key, 10) : key);
                vals += this.serialize(okey) +
                this.serialize(mixed_value[key]);
                count++;
            }
            val += ":" + count + ":{" + vals + "}";
            break;
        case "undefined": // Fall-through
        default: // if the JS object has a property which contains a null value, the string cannot be unserialized by PHP
            val = "N";
            break;
    }
    if (type != "object" && type != "array") {
        val += ";";
    }
    return val;
}
// Create closure, so that we don't accidentally spoil the global/window namespace.
function addWidget()
{
    jQuery('#addWidgetDialog').dialog("open");
}

function dragDropLi()
{
    jQuery('#Dispo').find('li.pasT').click(function(){
        jQuery(this).addClass("pasT");
        jQuery(this).clone().appendTo('#Ajoute');
        jQuery(this).remove();
        dragDropLi();
    });
    jQuery('#Ajoute').find('li.pasT').click(function(){
        jQuery(this).addClass("pasT");
        jQuery(this).clone().appendTo('#Dispo');
        jQuery(this).remove();
        dragDropLi();
    });
    jQuery(".pasT").removeClass("pasT");
}
$(function() {
    if ('x'+userid == "x"){
        alert('missing userid');
    }
    if ('x'+dashtype == "x"){
        alert('missing dashtype');
    }
    jQuery('body').prepend('<div id="addWidgetDialog"><table width=100% cellpadding=5>\
                                                         <tr><th width=50% class="ui-state-default ui-widget-header">Disponible\
                                                             <th width=50% class="ui-state-default ui-widget-header">Ajouter\
                                                         <tr><td class="ui-widget-content" valign="top">\
                                                                <ul class="list" id="Dispo">\
                                                              <td class="ui-widget-content" valign="top">\
                                                                <ul class="list" id="Ajoute">\
                                                    </table><em>Cliquer sur le nom du module que vous voulez ajouter<em></div>');
    jQuery('#addWidgetDialog').dialog({
        autoOpen: false,
        buttons: {
            "Ok": function() {
                var arr1 = new Array();
                jQuery('#addWidgetDialog').find('#Ajoute li').each(function(){
                    arr1.push(jQuery(this).attr('id'));
                }
                );
                var data = "ajoute="+serialize(arr1)+"&type="+dashtype+"&userid="+userid;
                jQuery.ajax({
                    url: DOL_URL_ROOT+"/Synopsis_Tools/dashboard/ajax/addWidget-xml_response.php",
                    data: data,
                    type: "POST",
                    cache: false,
                    dataTypeString: "xml",
                    success: function(msg){
                        jQuery('#addWidgetDialog').dialog("close");
                        location.reload();
                    }
                });
            },
            "Annuler": function() {
                $(this).dialog("close");
            }
        },
    draggable: true,
    modal: true,
    position: "center",
    show: 'slide',
    hide: 'slide',
    minWidth: 800,
    width: 800,
    title: "Ajouter un widget",
    open: function(){

        jQuery('ul#Ajoute').find('li').remove();
        jQuery('ul#Dispo').find('li').remove();


        jQuery.ajax({
            url: DOL_URL_ROOT+"/Synopsis_Tools/dashboard/ajax/listWidget-xml_response.php",
            cache: false,
            data: 'userid='+userid+"&type="+dashtype,
            dataTypeString: "xml",
            type: 'POST',
            success: function(msg){
                var html = "";
                var tmp = "";
                var rem = "";
                var rem1 = "blablabla";
                var iter = 0;
                jQuery(msg).find('disabled').each(function(){
                    rem =jQuery(this).text().replace(/[ ]?-[\w\W]*$/,'');


                    if(rem.toLowerCase() != rem1.toLowerCase())
                    {
                        if (rem1 != 'blablabla')
                        {
                            html += '<h3><a href="#">'+rem1+'</a></h3>';
                            html += '<div>';
                            html += tmp;
                            html += "</div>";
                            tmp = "";
                        }
                    }
                    tmp += '<li class="ui-state-hover ui-widget-header" id="'+jQuery(this).attr('id')+'"><span class="ui-icon ui-icon-cart" style="float: left; margin: -2px 3px 0 0"></span>'+jQuery(this).text()+'</li>';
                    rem1 = rem;
                });
                html += '<h3><a href="#">'+rem+'</a></h3>';
                html += '<div>';
                html += tmp;
                html += "</div>";
                tmp = "";
                jQuery('#Dispo').append("<div id='dashAccordion'>"+html+"</div>");
                jQuery('#Dispo').find('li').addClass("pasT");
                dragDropLi();
                jQuery('#dashAccordion').accordion({
                    animated: 'bounceslide', 
                    autoHeight: false, 
                    collapsible: true
                });
            }
        })
    }
    });



// The set of options we can use to initialize jQuery.dashboard().
var options = {
    // Optional. Defaults to 3.  You'll need to change the width of columns in CSS too.
    columns: 3,

    // Set this to a link to your server-side script that adds widgets to the dashboard.
    // The server will need to choose a column to add it to, and change the user's settings
    // stored server-side.
    // Required.
    emptyPlaceholderInner: '<a href="#" onClick="addWidget()">Ajouter des widgets &agrave; votre tableau de bord.</a>',

    // These define the urls and data objects used for all of the ajax requests to the server.
    // data objects are extended with more properties, as defined below.
    // All are required.  All should return JSON.
    ajaxCallbacks: {

        // Server returns the configuration of widgets for this user;
        // An array (keyed by zero-indexed column ID), of arrays (keyed by widget ID) of
        // booleans; true if the widget is minimized.  False if not.
        // E.g. [{ widgetID: isMinimized, ...}, ...]
        getWidgetsByColumn: {
            url: DOL_URL_ROOT+'/Synopsis_Tools/dashboard/ajaxData.php?type='+dashtype,
            data: {
                op: 'get_widgets_by_column'
            }
        },

        // Given the widget ID, the server returns the widget object as an associative array.
        // E.g. {content: '<p>Widget content</p>', title: 'Widget Title', }
        //
        // Required properties:
        //  * title: Text string.  Widget title
        //  * content: HTML string.  Widget content
        //
        // Optional properties:
        //  * classes: String CSS classes that will be added to the widget's <li>
        //  * fullscreen: HTML string for the content of the widget's full screen display.
        //  * settings: Boolean.  True if widget has settings pane/display and server-side
        //    callback.
        //
        // Server-side executable script callbacks are called and executed on certain
        // events.  They can use the widgets property of the dashboard object returned
        // from jQuery.dashboard().  E.g. dashboard.widgets[widgetID].  They should be
        // javascript files on the server.  Set the property to the path of the js file:
        //  * initScript:  Called when dashboard is initialising (but not finished).
        //  * fullscreenInitScript:  Called when the full screen element is initialising
        //    (being created for the first time).
        //  * fullscreenScript:  Called when switching into full screen mode.  Executed
        //    every time the widget goes into full-screen mode.
        //  * reloadContentScript:  Called when the widget's reloadContent() method is
        //    called.  (The widget.reloadContent() method is not used internally so must
        //    have either the callback function or this server-side executable javascript
        //    file implemented for the method to do anything.)
        //
        // The 'id' property of data is reserved for the widget ID – a string.
        getWidget: {
            url: DOL_URL_ROOT+'/Synopsis_Tools/dashboard/ajaxData.php?type='+dashtype,
            data: {
                // id: widgetID,
                op: 'get_widget'
            }
        },

        // jQuery.dashboard() POSTs the widget-to-column settings here.  The server's
        // response is sent to console.log() (if it exists), but is not used.  No checks
        // for errors have been implemented yet.
        // The 'columns' property of data is reserved for the widget-to-columns settings:
        //    An array (keyed by zero-indexed column ID), of arrays (keyed by widget ID)
        //    of ints; 1 if the widget is minimized.  0 if not.
        saveColumns: {
            url: DOL_URL_ROOT+'/Synopsis_Tools/dashboard/ajaxData.php?type='+dashtype,
            data: {
                // columns: array(0 => array(widgetId => isMinimized, ...), ...),
                op: 'save_columns'
            }
        },

        // jQuery.dashboard() GETs a widget's settings object and POST's a users submitted
        // settings back to the server.  The return, in both cases, is an associative
        // array with the new settings markup and other info:
        //
        // Required properties:
        //  * markup: HTML string.  The inner HTML of the settings form.  jQuery.dashboard()
        //    provides the Save and Cancel buttons and wrapping <form> element.  Can include
        //    <input>s of any standard type and <select>s, nested in <div>s etc.
        //
        // Server-side executable script callbacks (See documentation for
        // ajaxCallbacks.getWidgets):
        //  * initScript:  Called when widget settings are initialising.
        //  * script:  Called when switching into settings mode.  Executed every time
        //    the widget goes into settings-edit mode.
        //
        // The 'id' property of data is reserved for the widget ID.
        // The 'settings' property of data is reserved for the user-submitted settings.
        //    An array (keyed by the name="" attributes of <input>s), of <input> values.
        widgetSettings: {
            url: DOL_URL_ROOT+'/Synopsis_Tools/dashboard/ajaxData.php?type='+dashtype,
            data: {
                // id: widgetId,
                // settings: array(name => value, ...),
                op: 'widget_settings'
            }
        }
    },

    // Optional javascript callbacks for dashboard events.
    // All callbacks have the dashboard object available as the 'this' variable.
    // This property and all of it's members are optional.
    callbacks: {
        // Called when dashboard is initializing.
        init: function() {
            var dashboard = this;
            log('init');
            debug(dashboard);
        },

        // Called when dashboard has finished initializing.
        ready: function() {
            var dashboard = this;
            log('ready');
            debug(dashboard);
        },

        // Called when dashboard has saved columns to the server.
        saveColumns: function() {
            var dashboard = this;
            log('saveColumns');
            debug(dashboard);
        },

        // Called when a widget has gone into fullscreen mode.
        // Takes one argument for the widget.
        enterFullscreen: function(widget) {
            var dashboard = this;
            jQuery('#dashboard').addClass('box_shadow');
            log('enterFullscreen');
            debug(dashboard);
            debug(widget);
        },

        // Called when a widget has come out of fullscreen mode.
        // Takes one argument for the widget.
        exitFullscreen: function(widget) {
            var dashboard = this;
            jQuery('#dashboard').removeClass('box_shadow');
            log('exitFullscreen');
            debug(dashboard);
            debug(widget);
        }
    },

    // Optional javascript callbacks for widget events.
    // All callbacks have the respective widget object available as the 'this' variable.
    // This property and all of it's members are optional.
    widgetCallbacks: {
        // Called when a widget has been gotten from the server.
        get: function() {
            var widget = this;
            log('get');
            debug(widget);
        },

        // Called when an external script has invoked the widget.reloadContent() method.
        // (The widget.reloadContent() method is not used internally so must have either
        // this callback function or a server-side executable javascript file implemented
        // for the method to do anything.)
        reloadContent: function() {
            var widget = this;
            log('reloadContent');
            debug(widget);
        },

        // Called when the widget has gone into settings-edit mode.
        showSettings: function() {
            var widget = this;
            log('showSettings');
            debug(widget);
        },

        // Called when the widget's settings have been saved to the server.
        saveSettings: function() {
            var widget = this;
            log('saveSettings');
            debug(widget);
        },

        // Called when the widget has gone out of settings-edit mode.
        hideSettings: function() {
            var widget = this;
            log('hideSettings');
            debug(widget);
        },

        // Called when the widget has been removed from the dashboard.
        remove: function() {
            var widget = this;
            log('remove');
            debug(widget);
        }
    }
};

// Initialize the dashboard using these options, and save to the global/window
// namesapace so that server-side executable js file callbacks can access
// window.dashboardDemo.widgets by ID.
window.dashboardDemo = $('#dashboard').dashboard(options);
    log('executed');
    debug(window.dashboardDemo);

    // Wraps the log function in a check for it.
    function log(message) {
        if (window.console && console.log) {
            console.log(message);
        }
    }

    // Wraps the debug function in a check for it.
    function debug(variable) {
        if (window.console && console.debug) {
            console.debug(variable);
        }
    }
}); // End closure.


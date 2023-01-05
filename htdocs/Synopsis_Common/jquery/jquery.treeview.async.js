/*
 * Async Treeview 0.1 - Lazy-loading extension for Treeview
 *
 * http://bassistance.de/jquery-plugins/jquery-plugin-treeview/
 *
 * Copyright (c) 2007 Jörn Zaefferer
 *
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 *
 * Revision: $Id$
 *
 */

;(function($) {

function load(settings, root, child, container)
{
    //JM patch
    if (typeof(settings.beforeLoad) == 'function')
    {
        settings.beforeLoad(root,child,container);
    }
    //end JM patch
    $.getJSON(settings.url, {root: root}, function(response) {
        function createNode(parent) {
            var current = $("<li/>").attr("id", this.id || "").html("<span>" + this.text + "</span>").appendTo(parent);
            if (this.classes) {
                current.children("span").addClass(this.classes);
            }
            if (this.expanded) {
                current.addClass("open");
            }
            if (this.hasChildren || this.children && this.children.length) {
                var branch = $("<ul/>").appendTo(current);
                if (this.hasChildren) {
                    current.addClass("hasChildren");
                    createNode.call({
                        text:"placeholder",
                        id:"placeholder",
                        children:[]
                    }, branch);
                }
                if (this.children && this.children.length) {
                    $.each(this.children, createNode, [branch])
                }
            }
        }
        $.each(response, createNode, [child]);
        $(container).treeview({add: child});
        //JM patch
        if (typeof(settings.afterLoad) == 'function')
        {
            settings.afterLoad(root,child,container);
        }
        //end JM patch

    });
}

var proxied = $.fn.treeview;
$.fn.treeview = function(settings) {
    if (!settings.url) {
        return proxied.apply(this, arguments);
    }
    var container = this;

    //Patch eos to change the default source name
    //orig : load(settings, "source", this, container);
    var src = "source";
    if (settings.source) src = settings.source;
    load(settings, src, this, container);
    //End patch

    var userToggle = settings.toggle;
    return proxied.call(this, $.extend({}, settings, {
        collapsed: true,
        toggle: function() {
            var $this = $(this);
            if ($this.hasClass("hasChildren")) {
                var childList = $this.removeClass("hasChildren").find("ul");
                childList.empty();
                load(settings, this.id, childList, container);
            }
            if (userToggle) {
                userToggle.apply(this, arguments);
            }
        }
    }));
};

})(jQuery);
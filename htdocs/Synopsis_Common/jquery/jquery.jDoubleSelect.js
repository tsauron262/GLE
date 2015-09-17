/*
 * jDoubleSelect jQuery plugin
 *
 * Copyright (c) 2010 Giovanni Casassa (senamion.com - senamion.it)
 *
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LICENSE.txt) licenses.
 *
 * http://www.senamion.com
 *
 */

(function($)
{
    jQuery.fn.jDoubleSelect = function (o) {

        o = $.extend({
            text: "",
            finish: function(){ },
            el1_change: function(){ },
            destName:"",
            el2_dest:false
        }, o);

        return this.each(function () {
            var el = $(this);
            var name1 = (el.attr('id') || el.attr('name') || el.attr('class') || 'internalName') + '_jDS';
            var name2 = name1 + "_2";
            var name2opt = (o.destName||name1 + "_2");
            var className = (el.attr('class')|| "");

            groupSel = "";
            $(this).children("optgroup").each(function(i) {
                // Verify disabled or selected group
                if ($(this).attr("disabled"))
                    s = " disabled ";
                else if ($(this).find(":selected").attr("value")) {
                    s = " selected ";
                    }
                else s = "";

                groupSel += "<option "+s+" value='"+$(this).attr("label")+"'>"+$(this).attr("label")+"</option>";
            });
            $(this).hide().after("<select class='"+className+"' name='"+name1+"' id='"+name1+"'>"+groupSel+"</select> <span>"+o.text+"</span>");
            if(typeof(o.finish) === 'function'){
                o.finish();
            }

            $("#"+name1).change(function(){
                // REMOVE OLD ELEMENT, ADD NEW SELECT, BIND CHANGE EVENT AND TRIGGER IT
                $("#"+name2).remove();
                if($(this).val()+"x" != " x"){
                if(o.el2_dest){
                    o.el2_dest.append("<select class='"+className+"' id='"+name2+"' name='"+name2opt+"' >"+el.find("optgroup[label="+$(this).val()+"]").html()+"</select>");
                } else {
                    el.next().next().after("<select class='"+className+"' id='"+name2+"' name='"+name2opt+"' >"+el.find("optgroup[label="+$(this).val()+"]").html()+"</select>");
                }
                //el.val($("#"+name2).val());
                $("#"+name2).trigger("change");
                //CallBack fct2
                if(typeof(o.el1_change) === 'function'){
                    o.el1_change();
                }
            }
            });

            $("#"+name2).change(function(){
                // THIS IS VERY VERY SLOW IN FIREFOX
                //el.val($(this).val());
                el.attr("value", $(this).val());
            }).trigger("change");

            $("#"+name1).trigger("change")
        });
    };
})(jQuery);
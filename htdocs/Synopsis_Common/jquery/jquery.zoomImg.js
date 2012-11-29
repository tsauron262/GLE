/*
 * jquery.zoomImg 0.1
 * By Maro(Hidemaro Mukai)  - http://www.maro-z.com/
 * Copyright (c) 2008 Hidemaro Mukai
 * Licensed under the MIT License: http://www.opensource.org/licenses/mit-license.php
 */
(function($) {
    $.extend({
        zoomImg : new function(){
            var zi = this;
            zi.defaultWinSize = {"top" : 100, "left" : 100};
            zi.constract = function(c){
                return this.each(function(){
                    var ziParam = new Object;
                    ziParam.stage = $("img:first", this);
                    if (!c){
                        ziParam.zoomMode = 0;
                        ziParam.target = "#ZI_window";
                    }else{
                        ziParam.zoomMode = 1;
                        ziParam.target = "#"+c;
                    }
                    ziParam.img = new Image();
                    ziParam.img.src = this.href;
                    $.data(this, "ziParam", ziParam);
                    $(this).click(function(){
                        this.blur();
                        return false;
                    });
                    $(this).mouseover(zi.startZoom);
                });
            }
            zi.startZoom = function(e){
                zi.removeWindow();
                var obj = this;
                var ziParam = $.data(this, "ziParam");
                $("body").append('<div id="ZI_cursor"></div>');
                if (typeof document.body.style.maxHeight == "undefined"){
                    $("#ZI_cursor").append('<iframe id="ZI_dummy"></iframe><div id="ZI_window"></div>');
                }else{
                    $("#ZI_cursor").append('<div id="ZI_window"></div>');
                }
                if (ziParam.zoomMode == 0){
                    $("#ZI_cursor").addClass("ZI_innerView");
                }else{
                    $("#ZI_cursor").css({
                        "width": $(ziParam.target).width()/ziParam.img.width*ziParam.stage.width(),
                        "height": $(ziParam.target).height()/ziParam.img.height*ziParam.stage.height()
                    });
                    $("#ZI_window").addClass("ZI_outerView");
                }
                $("#ZI_dummy, #ZI_window").css({
                    "width": $("#ZI_cursor").width(),
                    "height": $("#ZI_cursor").height()
                });
                $(ziParam.target).append('<div id="ZI_view"></div>');
                $("#ZI_view").css({
                    "width": $(ziParam.target).width(),
                    "height": $(ziParam.target).height(),
                    "background-image": "url("+ziParam.img.src+")",
                    "background-repeat": "no-repeat"
                });
                zi.execZoom(e, obj);
                $("body").mousemove(function(e){
                    zi.execZoom(e, obj);
                });
                $("#ZI_window").mouseout(function(e){
                    zi.endZoom();
                });
            }
            zi.execZoom = function(e, obj){
                $("#ZI_view").html("&nbsp;");   //-- for winIE7 evaded to delay
                var ziParam = $.data(obj, "ziParam");
                var sPos = ziParam.stage.offset();
                if (e.pageX < sPos.left || e.pageX > sPos.left+ziParam.stage.width() || e.pageY < sPos.top || e.pageY > sPos.top+ziParam.stage.height()){
                    zi.endZoom();
                }
                var wSize = zi.getObjSize($("#ZI_window"));
                var winX = e.pageX-(wSize.width/2);
                var winY = e.pageY-(wSize.height/2);
                if (ziParam.zoomMode == 0){
                    var imgParX = (e.pageX-sPos.left)/ziParam.stage.width();
                    var imgParY = (e.pageY-sPos.top)/ziParam.stage.height();
                    var imgX = 0-ziParam.img.width * imgParX+($("#ZI_view").width()/2);
                    var imgY = 0-ziParam.img.height * imgParY+($("#ZI_view").height()/2);

                }else if (ziParam.zoomMode == 1){
                    if (winX < sPos.left){
                        winX = sPos.left;
                    }
                    if (winX > sPos.left+ziParam.stage.width()-wSize.width){
                        winX = sPos.left+ziParam.stage.width()-wSize.width;
                    }
                    if (winY < sPos.top){
                        winY = sPos.top;
                    }
                    if (winY > sPos.top+ziParam.stage.height()-wSize.height){
                        winY = sPos.top+ziParam.stage.height()-wSize.height;
                    }
                    var imgParX = (winX-sPos.left)/ziParam.stage.width();
                    var imgParY = (winY-sPos.top)/ziParam.stage.height();
                    var imgX = 0-Math.floor(ziParam.img.width * imgParX);
                    var imgY = 0-Math.floor(ziParam.img.height * imgParY);
                }
                $("#ZI_cursor").css({"left" : winX, "top": winY});
                $("#ZI_view").css({"background-position" : imgX+"px "+imgY+"px"});
            }
            zi.endZoom = function(){
                zi.removeWindow();
                $("body").unbind('mousemove');
            }
            zi.removeWindow = function(){
                $("#ZI_cursor").remove();
                $("#ZI_view").remove();
            }
            zi.getObjSize = function(obj){
                var size = new Object();
                size.width = obj.width();
                size.width += parseInt(obj.css("border-left-width"));
                size.width += parseInt(obj.css("border-right-width"));
                size.height = obj.height();
                size.height += parseInt(obj.css("border-top-width"));
                size.height += parseInt(obj.css("border-bottom-width"));
                return size;
            }
        }
    });
    $.fn.extend({
        zoomImg: $.zoomImg.constract
    });
})(jQuery);

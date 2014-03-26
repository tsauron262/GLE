$(window).load(function() {
    if(document.URL.search("comm/propal.php") > 0){
        $("a.butAction").each(function(){
            if($(this).html() == "Modifier")
                $(this).hide();
        });
    }
});

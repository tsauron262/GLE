


var validator=false;

jQuery(document).ready(function(){
    jQuery('#tabs').tabs({
        spinner: 'Chargement', 
        cache: true, 
        fx: {
            opacity:'toggle'
        }
    });
    jQuery('.tb').each(function(){
        jQuery(this).rotate('45deg');
    });
    jQuery('#accordion').accordion({
        animated: 'bounceslide',  
        navigation: true,
        autoHeight: false, 
        fillSpace: false
    });
    jQuery('#modForm').validate({
        invalidHandler: handleError()
    });
    jQuery('#Modifier').click(function(){
        if (jQuery('#modForm').validate({
            invalidHandler: handleError()
        }).form()){
            jQuery('#modForm').submit();
        }
    });
    jQuery('SELECT.double').each(function(){ 
        jQuery(this).jDoubleSelect({
            text:'', 
            finish: function(){ 
                jQuery('SELECT.double').each(function(){ 
                    jQuery(this).selectmenu({
                        style:'dropdown',
                        maxHeight: 300
                    });
                });
            }, 
            el1_change: function(){ 
                jQuery('SELECT.double').each(function(){ 
                    jQuery(this).selectmenu({
                        style:'dropdown',
                        maxHeight: 300
                    });
                });
            }, 
            destName:'trigger_refid',
            el2_dest:jQuery('#dest2el') 
        });
    });

});
                   





function handleError()
{
    if(jQuery('#jsMsg').length >0) {
        jQuery('#jsMsg').remove();
    }
    if (!jQuery('#modForm').validate().form()){
        jQuery('#tabs').prepend('<div id=\"jsMsg\" class=\"ui-error error\">'+jQuery('#modForm').validate().numberOfInvalids() + ' champ(s) est(sont) incomplet(s)</div>');
        setTimeout(function()
        {
            jQuery('#jsMsg').fadeOut(
                'slow',
                function ()
                {
                    jQuery('#jsMsg').remove();
                }
                );
        }, 3500);
    }
}

function activatePdf(str){
    jQuery.ajax({
        url:'ajax/activatePdf-xml_response.php' ,
        cache:false,
        datatype:'xml',
        type:'POST',
        data: 'pdf='+str,
        success: function(msg){
            if (jQuery(msg).find('OK') && jQuery(msg).find('OK').text()=='OK')
            {
                location.href='processBuilder.php?id=" . $process->id . "&action=Modify&tabs=pdf';
            } else {
                console.log(msg);
            }
        }
    });
}
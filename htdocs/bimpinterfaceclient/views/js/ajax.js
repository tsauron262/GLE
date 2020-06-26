$(document).ready(function(){
    
    function borderToRed(id_element) {
       $('#' + id_element).css('border', "2px solid red");
    }
    
    function borderToGreen(element) {
        $(element).css('border', "2px solid green");
    }
    
    function display_error_from_create(text) {
        $("#alert_error").fadeIn();
       $('#alert_error').html(text);
       setTimeout(function(){
           $("#alert_error").fadeOut();
       }, 20000);
    }
   
   $('#createTicketFromAll').click(function(){
      var serial = $('#theSerial').val();
      var canPost = true;
      
      var postedSerial = $('#theSerial').val();
      var postedDesc = $('#desc').val();
      
      if(postedSerial.length == 0) {
          canPost = false;
          borderToRed('theSerial');
      }
      if(postedDesc.length == 0) {
          canPost = false;
          borderToRed('desc');
      }
      
      if(canPost) {
          $.ajax({
            type: 'POST',
            url: DOL_URL_ROOT + '/bimpinterfaceclient/views/interfaces.php',
            data: {
                action: 'createTicketFromAll',
                serial: serial,
                socid: $('#createTicketFromAll').attr('socid'),
                description: $("#desc").val(),
                userClient: $('#demandeur').attr('content_id'),
                adresse_envois: $('#adresse_envois').val(),
                utilisateur: $('#utilisateur').val(),
                email_retour: $('#email_retour').val()
            },
            success: function(data) {
                console.log(data);
                $("#alert_error").fadeOut();
                if(data == 0) {
                    $("#alert_error").fadeIn();
                    $('#alert_error').html("Le numéro de série <b>"+serial+"</b> n'est dans aucun contrat");
                    setTimeout(function(){
                        $("#alert_error").fadeOut();
                    }, 20000);
                } else {
                    window.location.href = DOL_URL_ROOT + "/bimpinterfaceclient/index.php?fc=ticket&id=" + data;
                }
            }
         });
      } else {
          display_error_from_create("Des éléments nécéssaires à la création du ticket sont manquants");
      }

   });
   
   $('#fadeInAddForm').click(function(){
        
        if($('#addContratIn').is(':visible')) {
            $('#addContratIn').fadeOut();
        } else {
            $('#addContratIn').fadeIn();
        }
   });
   
   $('.want_input').change(function(){
        var value = $(this).val();
        console.log(value);
        if(value == "")
            borderToRed($(this).attr('id'));
        else
            borderToGreen($(this));
        
   });
    
});
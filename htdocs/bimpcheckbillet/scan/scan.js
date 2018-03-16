$(document).ready(function () {
    $("#code").on('keyup', function (e) {
        if (e.keyCode === 13) {
            traiteCode($("#code").val());
            $("#code").val("");
        }
    });
    
    
    $("#result").click(function(){
        $(this).hide();
        
        Quagga.init(this.state, function(err) {
            if (err) {
                return self.handleError(err);
            }
            Quagga.start();
        });
    });
});



function traiteCode(code) {
    $.ajax({
        type: "POST",
        url: "./ajax.php",
        data: {
            code: code
        },
        error: function () {
            alert("Probléme de connexion");
        },
        success: function (json) {
            obj = JSON.parse(json);
            Quagga.stop();
            $("#result").removeClass("red green");
            $("#result").html("Code : " + code+ "<br/>Nom : "+obj.billet.nom);
            if(obj.billet.valid != "OK")
                $("#result").addClass("red");
            else
                $("#result").addClass("green");
            $("#result").show();
        }
    });
    
    
    
}
//var decoder;

function checkTicket(barcode) {
    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            barcode: barcode,
            action: 'check_ticket'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 1895.', 'error');
        },
        success: function (json) {
            decoder.stop();
            var out = JSON.parse(json);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.id_inserted > 0) {
                setMessage('alertSubmit', "Ticket OK, id : " + out.id_inserted, 'msg');
            } else {
                setMessage('alertSubmit', 'Erreur serveur 1495.', 'error');
            }
        }
    });
}


$(document).ready(function () {
    $('#barcode').on('keyup', function (e) {
        if (e.keyCode === 13) {
            checkTicket($('#barcode').val());
            $('#barcode').val("");
        }
    });


//    $("#result").click(function () {
//        $(this).hide();
//        decoder.play();
//    });
});

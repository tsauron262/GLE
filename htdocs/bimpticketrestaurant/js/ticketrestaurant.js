
function getTicketsRestaurants(id_user) {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpticketrestaurant/interface.php",
        data: {
            id_user: id_user,
            action: 'get_ticket'
        },
        error: function () {
            console.log("Erreur PHP");
        },
        success: function (objOut) {

            groupes = JSON.parse(objOut);

        }
    });
}




$(document).ready(function () {
    initEvents();
});

function initEvents() {
    $('input#get_ticket').click(function () {
        getTicketsRestaurants($('#userid').val());
    });
}
var audio = null;
var play = false;
$(document).ready(function() { 
    audio = new Audio(DOL_URL_ROOT+'/bimptask/sons/alert.mp3');
});


function playAlert(){
    play = true;
    cronAlert();
}

function stopAlert(){
    play = false;
}


function cronAlert(){
    console.log("cron");
    if(audio)
        audio.play();
    setTimeout(function(){
        if(play)
            playAlert();
    },4000);
}

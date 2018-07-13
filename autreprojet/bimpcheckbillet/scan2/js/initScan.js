var txt = "innerText" in HTMLElement.prototype ? "innerText" : "textContent";
var arg = {
    resultFunction: function (result) {
        alert(result.code);
        traiteCode(result.code);
    }, // string, DecoderWorker file location
};
var decoder = new WebCodeCamJS("canvas").buildSelectMenu(document.createElement('select'), 'environment|back').init(arg).play();

console.log('ok');
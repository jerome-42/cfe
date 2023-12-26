$(document).ready(function() {
    $('#logout').click(function() {
        window.location = '/logout';
    });
    $("#NewRec").click(function() {
        window.location = "/NewRec";
    });
    $("#iindex").click(function() {
        window.location = "/index";
    });
    $("#Abandon").click(function() {
	history.back();
    });
});

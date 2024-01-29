$(document).ready(function() {
    $('.logout').click(function() {
        window.location = '/deconnexion';
    });
    $(".declaration").click(function() {
        window.location = "/declaration";
    });
    $(".listeCFE").click(function() {
        window.location = "/listeCFE";
    });
    $(".membres").click(function() {
        window.location = "/listeMembres";
    });
});

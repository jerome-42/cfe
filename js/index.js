$(document).ready(function() {
    $(".declaration").click(function() {
        window.location = "/declaration";
    });
    $(".export").click(function() {
        window.location = "/exportAllData";
    });
    $(".listeCFE").click(function() {
        window.location = "/listeCFE";
    });
    $('.logout').click(function() {
        window.location = '/deconnexion';
    });
    $(".membres").click(function() {
        window.location = "/listeMembres";
    });
    $(".validation").click(function() {
        window.location = "/validation";
    });
});

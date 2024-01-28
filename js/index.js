$(document).ready(function() {
    $('.logout').click(function() {
        window.location = '/deconnexion';
    });
    $(".declaration").click(function() {
        window.location = "/declaration";
    });
    $(".membres").click(function() {
        window.location = "/listeMembres";
    });
});

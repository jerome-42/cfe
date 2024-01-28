$(document).ready(function() {
    $('.logout').click(function() {
        window.location = '/logout';
    });
    $(".declaration").click(function() {
        window.location = "/declaration";
    });
    $(".membres").click(function() {
        window.location = "/listeMembres";
    });
});

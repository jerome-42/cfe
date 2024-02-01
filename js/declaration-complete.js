$(document).ready(function() {
    $('.back').click(function() {
        history.back(2);
    });
    $(".declaration").click(function() {
        window.location = "/declaration";
    });
});

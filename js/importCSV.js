$(document).ready(function() {
    $(".abandon").click(function(event) {
	window.location = '/';
    });
    $(".import").click(function() {
	$('#importCSV').trigger('submit');
    });
});

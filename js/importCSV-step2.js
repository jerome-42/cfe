$(document).ready(function() {
    $(".abandon").click(function(event) {
	window.location = '/importCSV';
    });
    $(".import").click(function() {
	$('#importCSV').trigger('submit');
    });
});

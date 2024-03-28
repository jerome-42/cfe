$(document).ready(function() {
    $('.back').click(function() {
	window.location = '/listeMachines';
    });
    $('.editComment').click(function() {
	$('#modalEditComment').modal('show');
    });

    $('.clearComment').click(function() {
	$('#comment').val('');
        $('#addComment').click();
    });

    $('#modalEditComment').on('shown.bs.modal', function() {
        $('#comment').focus();
    });

    $('#addComment').click(function() {
        $('#formEditComment').trigger('submit');
    });

});

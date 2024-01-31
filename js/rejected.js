var sampleCauses = {
    "double": "cette déclaration est une copie d'une déclaration déjà vue",
    "false": "fausse déclaration, elle est donc rejetée",
};

var updateLine = function(id, status, rejectedCause, cb) {
    $.ajax({
        url: '/updateCFELine',
        data: { id: id, status: status, rejectedCause: rejectedCause },
        type: 'POST',
        error: function() {
	    alert("Impossible");
        },
        success: function(res) {
	    cb();
        }
    });
};

var initRejectedModal = function() {
    $('#modalRejected').on('shown.bs.modal', function() {
	$('#rejectedCause').focus();
    });
    $('.sampleResponse').click(function() {
	var no = $(this).attr('x-cause');
	$('#rejectedCause').val(sampleCauses[no]);
    });

    $('#rejectedConfirm').click(function() {
	$('.invalid-feedback').remove();
	if ($('#rejectedCause').val() === '') {
	    $('#rejectedCause').after($('<div class="invalid-feedback">').text("La cause est obligatoire"));
	} else if ($('#rejectedCause').val().length <6) {
	    $('#rejectedCause').after($('<div class="invalid-feedback">').text("La cause est trop courte"));
	}

	$('.invalid-feedback').css({ 'display': 'initial' });
	if ($('.invalid-feedback').length === 0) {
	    changeStatus(currentElem, 'rejected', $('#rejectedCause').val());
	    $('#modalRejected').modal('hide');
	}
    });
};

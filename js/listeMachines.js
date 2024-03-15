String.prototype.replaceSpecialChars = function() {
    var newString = this;
    newString = newString
	.replace(/[âäà]/gm, 'a')
	.replace(/[êëéèê]/gm, 'e')
	.replace(/[îï]/gm, 'o')
	.replace(/[ôö]/gm, 'o')
	.replace(/[ù]/gm, 'u');
    return newString;
};

var updateList = function() {
    var search = $('#search').val().toLowerCase().replaceSpecialChars();
    $('#list > tbody > tr').each(function() {
	var displayLine = false;
	if (search === '')
	    displayLine = true;
	else {
	    $(this).find('td').each(function() {
		if ($(this).text().toLowerCase().replaceSpecialChars().indexOf(search) !== -1)
		    displayLine = true;
	    });
	}
	if (displayLine === false)
	    $(this).hide();
	else
	    $(this).show();
    });
};

$(document).ready(function() {
    $('.back').click(function() {
	window.location = '/';
    });

    $(".declarerFLARM").click(function() {
        window.location = "/declarerFLARM";
    });

    $('.parametrage').click(function() {
	window.location = '/parametresFlarm';
    });

    $('.displayDetails').click(function() {
	var id = $(this).parents('tr').attr('x-num');
	window.location = '/detailsMachine?numero='+id;
    });

    $('.addGlider').click(function() {
	$('#immat').val('');
	$('#concours').val('');
	$('#type').val('');
	$('#modalAddGlider').modal('show');
    });

    $('#modalAddGlider').on('shown.bs.modal', function() {
	$('#immat').focus();
    });

    $('#addGlider').click(function() {
	if ($('#immat').val() === '') {
	    alert("L'immatriculation doit être saisie");
	    $('#immat').focus();
	    return;
	}
	if ($('#type').val() === '') {
	    alert("La machine doit avoir un modèle");
	    $('#type').focus();
	    return;
	}
	$('#formAddGlider').trigger('submit');
    });

    $('#filter').on('change', function() {
	updateList();
    });

    $('#search').on('keyup', function() {
	updateList();
    });
    $('#search').focus();
    updateList();
});

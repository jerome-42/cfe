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

var initTooltips = function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
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

    $('.displayDetails').click(function() {
	var id = $(this).parents('tr').attr('x-id');
	window.location = '/detailsProposition?num='+id;
    });

    $('.enregistrerNouvelle').click(function() {
        $('.modalTitle').text("Ajouter une proposition de CFE")
        $('#addProposal').text("Ajouter cette tâche");
	$('#title, #details, #notes, #id, #withMaxDate, #notValidAfterDate, #notValidAfterDateTimestamp').val('');
        $('#withMaxDate').prop('checked', false);
        $('#isActive').prop('checked', true);
        $('#canBeClosedByMember').prop('checked', false);
        $('#notValidAfterDate').val('');
        $('.maxDate').addClass('visually-hidden');
	$('#modalEditProposal').modal('show');
    });

    $(document).on('click', '.edit', function() {
        let tr = $(this).parents('tr');
        $('.modalTitle').text("Editer une proposition de CFE")
        $('#addProposal').text("Modifier cette tâche");
        $('#id').val(tr.attr('x-id'));
        $('#type').val(tr.attr('x-workType'));
        $('#details').val(tr.attr('x-details'));
        $('#priority').val(tr.attr('x-priority'));
        $('#notes').val(tr.attr('x-notes'));
        $('#title').val(tr.attr('x-title'));
        if (tr.attr('x-notValidAfterDate') != undefined) {
            $('#withMaxDate').prop('checked', true);
            $('#notValidAfterDate').val(tr.attr('x-notValidAfterDate'));
            $('.maxDate').removeClass('visually-hidden');
        } else {
            $('#withMaxDate').prop('checked', false);
            $('#notValidAfterDate').val('');
            $('.maxDate').addClass('visually-hidden');
        }
        if (tr.attr('x-isActive') === '1')
            $('#isActive').prop('checked', true);
        else
            $('#isActive').prop('checked', false);
        console.log(tr.attr('x-isActive'), tr.attr('x-canBeClosedByMember'));
        if (tr.attr('x-canBeClosedByMember') === '1')
            $('#canBeClosedByMember').prop('checked', true);
        else
            $('#canBeClosedByMember').prop('checked', false);
        $('#modalEditProposal').modal('show');
    });
    $('#modalEditProposal').on('shown.bs.modal', function() {
        $('#type').focus();
    });

    $('#withMaxDate').change(function() {
        if ($(this).is(':checked')) {
            $('.maxDate').removeClass('visually-hidden');
            $('#notValidAfterDate').val('');
        } else {
            $('.maxDate').addClass('visually-hidden');
            $('#notValidAfterDate').val('');
        }
    });

    $('#addProposal').click(function() {
	if ($('#title').val() === '') {
	    alert("Le titre doit être saisi");
	    $('#title').focus();
	    return;
	}
	if ($('#details').val() === '') {
	    alert("Il doit y avoir des détails");
	    $('#details').focus();
	    return;
	}
	if ($('#ntoes').val() === '') {
	    alert("Les notes sont obligatoires");
	    $('#notes').focus();
	    return;
	}
        if ($('#withMaxDate').is(':checked') && $('#notValidAfterDate').val() === '') {
	    alert("Il doit y avoir une date maximale");
	    $('#notValidAfterDate').focus();
	    return;
        }
        if ($('#withMaxDate').is(':checked')) {
            let d = new Date($('#notValidAfterDate').val());
            $('#notValidAfterDateTimestamp').val(+d/1000);
        }
	$('#formEditProposal').trigger('submit');
    });

    $('#filter').on('change', function() {
	updateList();
    });

    $('#search').on('keyup', function() {
	updateList();
    });
    initTooltips();
    $('#search').focus();
    updateList();
});

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

let download = function() {
    var formSelected = $('#formSelection').val();
    var csvContent = "data:text/csv;charset=utf-8,";
    // gt(0) on ne prend pas la 1ère colonne (Edition)
    var row = $('#list > thead > tr > th:gt(0)').map(function() {
	return $(this).text();
    });
    csvContent += row.get().toString() + "\r\n";
    $('#list > tbody > tr').each(function() {
	var row = $(this).find('td:gt(0)').map(function(id) {
	    return $(this).text();
	});
	csvContent += row.get().toString() + "\r\n";
    });
    var encodedUri = encodeURI(csvContent);
    var link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "formulaire-"+formSelected+".csv");
    document.body.appendChild(link); // Required for FF
    link.click();
};

let getColumsName = function(formName) {
    var columns = {};
    answers.forEach(function(answer) {
        if (answer.name === formName) {
            Object.keys(answer.data).forEach(function(col) {
                columns[col] = true;
            });
        }
    });
    return Object.keys(columns);
};

let getAnswerFromId = function(id) {
    for (i = 0; i < answers.length; i++) {
        if (answers[i].id == id)
            return answers[i];
    }
    return undefined;
};

let getFormsName = function() {
    var names = {};
    answers.forEach(function(answer) {
        names[answer.name] = true;
    });
    return Object.keys(names);
};

let getUrlParameter = function(param) {
    let urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
}

let timestampToDate = function(timestamp) {
    let d = new Date(timestamp * 1000);
    return d.toLocaleDateString('fr-FR');
};

let updateList = function() {
    $('#list').empty();
    var formSelected = $('#formSelection').val();
    if (getUrlParameter('formulaire') !== formSelected)
        window.history.pushState(null,"", '/listeFormulaires?formulaire='+formSelected);

    var columns = getColumsName(formSelected);
    $('#editControls').empty();
    columns.forEach(function(col) {
        var input = $('<input class="form-control" type="text">');
        input
            .attr('name', col)
            .attr('id', col);
        $('#editControls')
            .append($('<div class="row mb-2">')
                    .append($('<div class="col">')
                            .append($('<label class="form-label">', { for: col }).text(col+' :'))
                            .append(input)
                           ));
    });

    var header = $('<tr>');
    header
        .append($('<th>').text('Edition'))
        .append($('<th>').text('Commentaire'))
        .append($('<th>').text('Date'));
    columns.forEach(function(column) {
        header.append($('<th>').text(column));
    });
    var tbody = $('<tbody>');
    answers.forEach(function(answer) {
        if (answer.name === formSelected) {
            var tr = $('<tr>');
            tr
                .append($('<td>')
                        .append($('<button>', { class: 'btn btn-primary edit', 'x-id': answer.id }).
                                append($('<i class="bi bi-pencil">'))));
            if (answer.comment != null) {
                tr.append($('<td>')
                          .append($('<span>').text(answer.comment))
                          .append($('<span class="small">').text(' (par '+answer.commentBy+' le '+timestampToDate(answer.commentWhen)+')'))
                         );
            } else
                tr.append($('<td>'));

            tr.append($('<td>').text(timestampToDate(answer.when)));
            columns.forEach(function(column) {
                tr.append($('<td>').text(answer.data[column]));
            });
            tbody.append(tr);
        }
    });
    $('#list')
        .append($('<thead>').append(header))
        .append(tbody)
        .append($('<tfoot>').append(header.clone()));

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
    answers.forEach(function(answer, idx) {
        answers[idx].data = JSON.parse(answers[idx].data);
    });

    $('.back').click(function() {
	window.location = '/';
    });

    getFormsName().forEach(function(name) {
        $('#formSelection').append($('<option>', { value: name }).text(name));
    });
    if (getUrlParameter('formulaire') !== null)
        $('#formSelection').val(getUrlParameter('formulaire'));

    $(document).on('click', '.edit', function() {
	var id = $(this).attr('x-id');
        $('#answerId').val(id);
        var answer = getAnswerFromId(id);
        if (answer === undefined)
            return alert("Réponse inconnue");
        var formSelected = $('#formSelection').val();
        var columns = getColumsName(formSelected);
        columns.forEach(function(col) {
            $('#'+col).val(answer.data[col]);
        });
	$('#comment').val(answer.comment);
	$('#modalEdit').modal('show');
    });

    $('.clearComment').click(function() {
	$('#comment').val('');
        $('#addComment').click();
    });

    $('#modalEdit').on('shown.bs.modal', function() {
        $('#formEdit').find('input[type!="hidden"]').first().focus();
    });

    $('#addComment').click(function() {
        $('#formEdit').trigger('submit');
    });

    $('#deleteAnswer').click(function() {
        if (confirm("Êtes-vous sûr de vouloir supprimer cette réponse ?") === false)
            return;
        window.location = '/deleteFormAnswer?id='+$('#answerId').val();
    });

    $('#filter, #formSelection').on('change', function() {
	updateList();
    });

    $('#search').on('keyup', function() {
	updateList();
    });

    $('.download').click(function() {
        download();
    });
    $('#search').focus();
    updateList();
});

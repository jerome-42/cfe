$(document).ready(function() {
    $('.back').click(function() {
	window.location = '/listePropositions';
    });

    $('.download').click(function() {
	var csvContent = "data:text/csv;charset=utf-8,";
	var row = $('#list > thead > tr > th').map(function() {
	    return $(this).text();
	});
	csvContent += row.get().toString() + "\r\n";
	$('#list > tbody > tr').each(function() {
	    if ($(this).attr('x-type') !== undefined)
		return;
	    var row = $(this).find('td').map(function() {
		if ($(this).attr('x-value') !== undefined)
		    return $(this).attr('x-value');
		return $(this).text();
	    });
	    csvContent += row.get().toString() + "\r\n";
	});
	var encodedUri = encodeURI(csvContent);
	var link = document.createElement("a");
	link.setAttribute("href", encodedUri);
	link.setAttribute("download", "tache - "+$(this).attr('x-title')+".csv");
	document.body.appendChild(link); // Required for FF
	link.click();
    });

});

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

let timestampToDate = function(timestamp) {
    let d = new Date(timestamp * 1000);
    return d.toLocaleDateString('fr-FR');
};

let mimeToHuman = function(mime) {
    if (mime.indexOf('image') !== -1)
        return 'image';
    if (mime.indexOf('pdf') !== -1)
        return 'pdf';
    return mime;
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

let sizeToHuman = function(bytes, decimals) {
    const K_UNIT = 1024;
    const SIZES = ["Octets", "Ko", "Mo"];

    if (bytes== 0)
        return "0 Octet";
  
    let i = Math.floor(Math.log(bytes) / Math.log(K_UNIT));
    let resp = parseFloat((bytes / Math.pow(K_UNIT, i)).toFixed(decimals)) + " " + SIZES[i];
  
    return resp;
};

$(document).ready(function() {
    $('.back').click(function() {
        window.location = '/';
    });
    $('#search').on('keyup', function() {
	updateList();
    });
    $('#search').focus();
    $('.delete').click(function() {
        if (confirm("Êtes-vous sûr de vouloir supprimer ce devis ?") === false)
            return;
        let tr = $(this).parents('tr');
        let id = tr.attr('x-id');
        $.ajax({
            url: '/supprimerDevis',
            data: { id: id },
            type: 'POST',
            error: function() {
	        alert("Impossible");
            },
            success: function(res) {
                tr.remove();
            }
        });
    });

    $('.displayDetails').click(function() {
        let id = $(this).parents('tr').attr('x-id');
        $.ajax({
            url: '/detailsDevis',
            data: { id: id },
            type: 'POST',
            dataType: 'json',
            error: function() {
	        alert("Impossible");
            },
            success: function(res) {
                if (res.error !== undefined)
                    return alert(res.error);
                $('#date').text("Le "+timestampToDate(res.when));
                $('#details').text(res.details);
                $('#fileList > tbody > tr').remove();
                res.files.forEach(function(file) {
                    $('#fileList > tbody').append($('<tr>')
                                                  .append($('<td>').append($('<div>')
                                                                           .append($('<a>', { target: '_blank', href: '/fichierDevis?id='+file.id }).text('Voir'))
                                                                           .append(' ')
                                                                           .append($('<a>', { target: '_blank', href: '/fichierDevis?id='+file.id+'&telecharger=true' }).text('Télécharger'))))
                                                  .append($('<td>').text(file.filename))
                                                  .append($('<td>').text(mimeToHuman(file.mime)))
                                                  .append($('<td>').text(sizeToHuman(file.size, 0)))
                                                 );
                });
                $('#modalDetails').modal('show');
            }
        });
    });
});

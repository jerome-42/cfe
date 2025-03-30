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

let addSubscriber = function() {
    let listName = $('#mailingList').val();
    let email = prompt("Saisir l'adresse email à ajouter à la mailing list "+listName, "exemple@domaine.com");
    if (email == null)
        return;
    $.ajax({
            url: '/mailingLists/addSubscriber',
            data: { listName: listName, subscriber: email },
            type: 'POST',
            error: function() {
	        alert("Impossible");
            },
            success: function(res) {
                alert("Ajout enregistré, l'ajout peut prendre quelques minutes pour être effectif (OVH est lent)");
            }});
};

var download = function() {
    var csvContent = "data:text/csv;charset=utf-8,";
    var row = [ 'email', 'inscrit cette année', 'inscrit sur les 3 dernières années' ];
    csvContent += row.join(';') + "\r\n";
    $('#list > tbody > tr').each(function() {
        var row = [ $(this).attr('x-email'), $(this).attr('x-inscritCetteAnnee'),
                    $(this).attr('x-inscrit3Ans') ];
	csvContent += row.join(';') + "\r\n";
    });
    var encodedUri = encodeURI(csvContent);
    var link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", $('#mailingList').val()+".csv");
    document.body.appendChild(link); // Required for FF
    link.click();
};

let displaySubscriberForList = function(listName) {
    if (getUrlParameter('liste') !== listName)
        window.history.pushState(null,"", '/mailingLists?liste='+listName);
    $('#list > tbody').empty();
    $.ajax({
        url: '/mailingLists/getSubscribers',
        data: { listName: listName },
        type: 'POST',
        dataType: 'json',
        error: function() {
	    alert("Impossible");
        },
        success: function(res) {
            res.subscribers.forEach(function(subscriber) {
                let tr = $('<tr>', { 'x-email': subscriber.email, 'x-inscritCetteAnnee': subscriber.inscritGivavCetteAnnee ? "oui" : "non", 'x-inscrit3Ans': subscriber.inscritGivav3Ans ? "oui" : "non" })
                    .append($('<td>').append($('<button type="button" class="btn btn-danger remove">').append('<i class="bi bi-trash">')))
                    .append($('<td>')
                            .append($('<a>', { href: 'mailto:'+subscriber.email }).text(subscriber.email)));
                if (subscriber.inscritGivavCetteAnnee)
                    tr.append($('<td>').append($('<i class="bi bi-patch-check" style="color: green">')));
                else
                    tr.append($('<td>'));

                if (subscriber.inscritGivav3Ans)
                    tr.append($('<td>').append($('<i class="bi bi-patch-check" style="color: green">')));              
                else
                    tr.append($('<td>'));
                $('#list > tbody').append(tr);
            });
            $('#total').empty()
                .append($('<b>').text('Total: '))
                .append($('<span>').text(res.subscribers.length+' abonnés'));
        }
    });
};

let getUrlParameter = function(param) {
    let urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
};

let removeSubscriber = function(tr) {
    let listName = $('#mailingList').val();
    let email = tr.attr('x-email');
    let question = "Êtes-vous sûr de vouloir désabonner "+email+" ?";
    if (confirm(question)) {
        $.ajax({
            url: '/mailingLists/removeSubscriber',
            data: { listName: listName, subscriber: email },
            type: 'POST',
            error: function() {
	        alert("Impossible");
            },
            success: function(res) {
                alert("Suppression enregistrée, elle peut prendre quelques minutes (OVH est lent)");
                tr.remove();
            }});
    }
};

var updateList = function() {
    var search = $('#search').val().toLowerCase().replaceSpecialChars();
    $('#list > tbody > tr').each(function() {
	var displayLine = false;
	if ($(this).hasClass('sum')) // on ne filtre pas la dernière ligne qui affiche le total
	    return;
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
	else {
	    $(this).show();
	}
    });
};

$(document).ready(function() {
    if (getUrlParameter('liste') !== null)
        $('#mailingList').val(getUrlParameter('liste'));
    displaySubscriberForList($('#mailingList').val());
    $('#mailingList').change(function() {
        displaySubscriberForList($('#mailingList').val());
        $('#search').focus();
    });
    $('.back').click(function() {
	window.location = '/';
    });
    $('.download').click(function() {
        download();
    });

    $('#filter').on('change', function() {
	updateList();
    });

    $('#search').on('keyup', function() {
	updateList();
    });
    $(document.body).on('click', '.remove', function() {
        removeSubscriber($(this).parents('tr'));
    });
    $('.add').click(function() {
        addSubscriber();
    });
    $('#search').focus();
});

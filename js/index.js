$(document).ready(function() {
    $(".declaration").click(function() {
        window.location = "/declaration";
    });
    $(".export").click(function() {
        window.location = "/exportAllData";
    });
    $(".listeCFE").click(function() {
        window.location = "/listeCFE";
    });
    $(".listeFormulaires").click(function() {
        window.location = "/listeFormulaires";
    });
    $(".listeMachines").click(function() {
        window.location = "/listeMachines";
    });
    $('.logout').click(function() {
        window.location = '/deconnexion';
    });
    $(".membres").click(function() {
        window.location = "/listeMembres";
    });
    $(".propositions").click(function() {
        window.location = "/listePropositions";
    });
    $(".validation").click(function() {
        window.location = "/validation";
    });

    $('.proposal').click(function() {
        let span = $(this);
        $('.modalProposalTitle').text(span.attr('x-title'));
        $('.modalProposalWorktype').text('Catégorie : '+span.attr('x-workType'));
        $('.modalProposalDetails').text('Détails : '+span.attr('x-details'));
        $('.modalProposalWho').html(
            $('<div>')
                .append($('<pan>').text('Proposé par : '))
                .append($('<a>', { href: 'mailto:'+span.attr('x-whoemail') }).text(span.attr('x-who')))
        );
        $('#modalDisplayProposal').modal('show');
    });
});

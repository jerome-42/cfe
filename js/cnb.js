$(document).ready(function() {
    $('.back').click(function() {
	window.location = '/';
    });
    stats.CNB.data.forEach(function(l) {
        let tr = $('<tr>')
            .append($('<td>').text(l.immatriculation))
            .append($('<td>').text(l.nom_type))
            .append($('<td>').text(l.temps_vol_hors_proprietaire));
        let color = 'orange';
        if (l.temps_cnb_a_realiser == '00:00:00')
            color = 'green';
        else if (l.temps_cnb_a_realiser == stats.CNB.params[':cnb'])
            color = 'red';
        tr.append($('<td>', { style: 'color: '+color }).text(l.temps_cnb_a_realiser));
        $('#list > tbody').append(tr);
    });
});

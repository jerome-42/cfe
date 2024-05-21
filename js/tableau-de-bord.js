let getStatsByMonth = function(s, mois) {
    if (mois < 10)
        mois = '0'+mois;
    for (let i = 0; i < s.data.length; i++) {
        if (s.data[i].d.indexOf('-'+mois+'-')) {
            return s.data[i].stats;
        }
    }
    throw new Error("impossible de trouver le mois "+mois+" dans stats");
};

let displayMoyensLancement = function() {
    let getNbVols = function(s, mois) {
        let s2 = getStatsByMonth(s, mois);
        return s2.M.nb_vol + s2.R.nb_vol + s2.T.nb_vol;
    };
    let data = {
        'février 2023': getNbVols(stats.statsAuCoursAnneePrecedente, 2),
        'février 2024': getNbVols(stats.statsAuCoursAnnee, 2),
    };
    //console.log(data);
};

let resolvePtr = function(d, ptr) {
    let keys = ptr.split('.');
    let key = keys.shift();
    if (d[key] === undefined)
        return null;
    if (keys.length === 0)
        return d[key];
    return resolvePtr(d[key], keys.join('.'));
};

let getColumns = function(columnsDefs, partialStats) {
    let columns = [];

    columnsDefs.forEach(function(e) {
        if (e.target !== undefined)
            columns.push(e);
        if (e.targets !== undefined) {
            //DEBUG console.log('on traite', e);
            let subColumnsName = {};
            partialStats.data.forEach(function(l) {
                let ptr = resolvePtr(l, e.targets);
                //DEBUG console.log('après resolvePtr', ptr);
                if (ptr !== null) {
                    Object.keys(ptr).forEach(function(key) {
                        subColumnsName[key] = key;
                    });
                }
            });
            let subColumnsNameArray = Object.keys(subColumnsName).sort(function(a, b) {
                if (e.sortPriority !== undefined) {
                    if ($.inArray(a, e.sortPriority) != -1 && $.inArray(b, e.sortPriority) != -1)
                        return a > b;
                    if ($.inArray(a, e.sortPriority) != -1)
                        return -1;
                    if ($.inArray(b, e.sortPriority) != -1)
                        return 1;
                }
                return a > b;
            });
            subColumnsNameArray.forEach(function(l) {
                let target = e.targets+'.'+l;
                if (e.postfix !== undefined)
                    target += '.'+e.postfix;
                columns.push({ 'label': e.label + ' ' + l, 'target': target  });
            });
        }
    });
    return columns;
};

let displayMisesEnLAir = function(target, partialStats) {
    let columnsDef = [
        { 'label': "Immat", 'target': 'immatriculation' },
        { 'label': 'Nombre', 'target': 'stats.global.nb_vol' },
        { 'label': 'Type', 'targets': 'stats.type_mise_en_l_air', 'postfix': 'nb_vol', 'sortPriority': [ 'Remorqué standard - 500m', 'Demi-remorqué - 250m' ] },
    ];
    var columns = getColumns(columnsDef, partialStats);
    var thead = $('<tr>');
    columns.forEach(function(col) {
        thead.append($('<th>').text(col.label));
    });
    target.append($('<thead>').append(thead));
    var tbody = $('<tbody>');
    partialStats.data.forEach(function(l) {
        var tr = $('<tr>');
        columns.forEach(function(col) {
            var data = resolvePtr(l, col.target);
            if (data !== null)
                tr.append($('<td>').text(data));
            else
                tr.append($('<td>'));
        });
        tbody.append(tr);
    });
    target.append(tbody);
    target.append($('<tfoot>').append(thead.clone()));
    let endDate = Date.parse(partialStats.params.date_fin);
    let d = new Date();
    d.setTime(endDate);
    let opt = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    };
    $('#misesEnLAirTitle').text(' '+d.toLocaleDateString('fr-FR', opt));
};

$(document).ready(function() {
    displayMoyensLancement();

    displayMisesEnLAir($('#misesEnLAir'), stats.statsMisesEnLAir);
});

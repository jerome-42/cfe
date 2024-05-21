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
    if (d === undefined || d === null)
        return null;
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
                let columnAlreadyExists = false;
                columns.forEach(function(c2) {
                    if (c2.target == target)
                        columnAlreadyExists = true;
                });
                if (columnAlreadyExists === false)
                    columns.push({ 'label': e.label + ' ' + l, 'target': target  });
            });
        }
    });
    return columns;
};

// moche
let displayMisesEnLAir = function(target, masterKey, partialStatsCurrentYear, partialStatsPreviousYear) {
    let columnsDef = [
        { 'label': "Immat", 'target': 'immatriculation' },
        { 'label': 'Nombre', 'target': 'stats.global.nb_vol' },
        { 'label': 'Type', 'targets': 'stats.type_mise_en_l_air', 'postfix': 'nb_vol', 'sortPriority': [ 'Remorqué standard - 500m', 'Demi-remorqué - 250m' ] },
    ];
    let columns = getColumns(columnsDef, partialStatsCurrentYear);
    columns = getColumns(columns, partialStatsPreviousYear);
    let thead = $('<tr>');
    columns.forEach(function(col) {
        thead.append($('<th>').text(col.label));
    });
    target.append($('<thead>').append(thead));
    let tbody = $('<tbody>');
    let sumCurrentYear = [];
    let sumPreviousYear = [];
    partialStatsCurrentYear.data.forEach(function(l) {
        let tr = $('<tr>');
        let lPreviousYear = findStats(partialStatsPreviousYear, masterKey, l[masterKey]);
        columns.forEach(function(col, idx) {
            if (sumCurrentYear[idx] === undefined) {
                sumCurrentYear[idx] = 0;
                sumPreviousYear[idx] = 0;
            }
            let dataCurrentYear = resolvePtr(l, col.target);
            let dataPreviousYear = resolvePtr(lPreviousYear, col.target);
            let text = '';
            if (dataCurrentYear !== null) {
                text = dataCurrentYear;
                if ($.isNumeric(dataCurrentYear))
                    sumCurrentYear[idx] += dataCurrentYear;
            }
            if (dataPreviousYear !== null) {
                if ($.isNumeric(dataPreviousYear))
                    sumPreviousYear[idx] += dataPreviousYear;
                if (dataCurrentYear === null)
                    text = '0 ('+dataPreviousYear+')';
                else
                    text += ' ('+dataPreviousYear+')';
            }
            tr.append($('<td>').text(text));
        });
        tbody.append(tr);
    });
    tr = $('<tr>');
    for (let i = 0; i < sumCurrentYear.length; i++) {
        let text = '';
        if (i === 0)
            text = 'Total';
        else {
            if (sumCurrentYear[i] !== undefined)
                text += sumCurrentYear[i];
            if (sumPreviousYear[i] !== undefined && sumPreviousYear[i] !== 0) {
                if (sumCurrentYear[i] !== 0)
                    text += ' ('+sumPreviousYear[i]+')';
                else
                    text += '0 ('+sumPreviousYear[i]+')';
            }
        }
        tr.append($('<td>').append($('<b>').text(text)));
    }
    tbody.append(tr);
    target.append(tbody);
    target.append($('<tfoot>').append(thead.clone()));
    let endDate = Date.parse(partialStatsCurrentYear.params.date_fin);
    let d = new Date();
    d.setTime(endDate);
    let opt = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    };
    $('#misesEnLAirTitle').text(' '+d.toLocaleDateString('fr-FR', opt));
};

let findStats = function(s, masterKey, value) {
    for (let i = 0; i < s.data.length; i++) {
        if (s.data[i][masterKey] === value)
            return s.data[i];
    }
    return null;
};

$(document).ready(function() {
    displayMoyensLancement();

    displayMisesEnLAir($('#misesEnLAir'), 'immatriculation', stats.statsMisesEnLAir, stats.statsMisesEnLAirAnneePrecedente);
});

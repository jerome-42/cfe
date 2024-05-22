let getStatsByMonth = function(s, mois) {
    if (mois < 10)
        mois = '0'+mois;
    for (let i = 0; i < s.data.length; i++) {
        if (s.data[i].d.indexOf('-'+mois+'-') !== -1) {
            //DEBUG console.log(s.data[i].stats, i, mois);
            return s.data[i].stats;
        }
    }
    throw new Error("impossible de trouver le mois "+mois+" dans stats");
};

let getDateFromD = function(dString) {
    let d = new Date(0);
    let s = Date.parse(dString);
    d.setUTCSeconds(s/1000);
    return d;
};

let displayLicence = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('licence').getContext('2d');
    const passion_plus_25 = 'Passion +25 ans (Annuelle)';
    const passion_moins_25 = 'Passion -25 ans (Annuelle)';
    const asso = 'Asso - Non volants (Annuelle)';
    const duo = 'Duo (Annuelle)';
    const decouverte_3j = 'Découverte 3 jours (consécutifs ou non)';
    const decouverte_6j = 'Découverte 6 jours (consécutifs ou non)';
    const decouverte_12j = 'Découverte 12 jours (consécutifs ou non)';
    const esport = 'esport';
    let dataCetteAnnee = [
        stats.tableauDeBord.data.licence_cette_annee[passion_plus_25],
        stats.tableauDeBord.data.licence_cette_annee[passion_moins_25],
        stats.tableauDeBord.data.licence_cette_annee[asso],
        stats.tableauDeBord.data.licence_cette_annee[duo],
        stats.tableauDeBord.data.licence_cette_annee[decouverte_3j],
        stats.tableauDeBord.data.licence_cette_annee[decouverte_6j],
        stats.tableauDeBord.data.licence_cette_annee[decouverte_12j],
        stats.tableauDeBord.data.licence_cette_annee[esport],
    ];
    dataCetteAnnee.push(dataCetteAnnee.reduce(function(accumulator, a) {
        if (a !== undefined)
            return accumulator + a;
        return accumulator;
    }, 0));
    let dataAnneeDerniere = [
        stats.tableauDeBord.data.licence_annee_derniere[passion_plus_25],
        stats.tableauDeBord.data.licence_annee_derniere[passion_moins_25],
        stats.tableauDeBord.data.licence_annee_derniere[asso],
        stats.tableauDeBord.data.licence_annee_derniere[duo],
        stats.tableauDeBord.data.licence_annee_derniere[decouverte_3j],
        stats.tableauDeBord.data.licence_annee_derniere[decouverte_6j],
        stats.tableauDeBord.data.licence_annee_derniere[decouverte_12j],
        stats.tableauDeBord.data.licence_annee_derniere[esport],
    ];
    dataAnneeDerniere.push(dataAnneeDerniere.reduce(function(accumulator, a) {
        if (a !== undefined)
            return accumulator + a;
        return accumulator;
    }, 0));
    let dataAnneeDerniereComplete = [
        stats.tableauDeBord.data.licence_annee_derniere_complete[passion_plus_25],
        stats.tableauDeBord.data.licence_annee_derniere_complete[passion_moins_25],
        stats.tableauDeBord.data.licence_annee_derniere_complete[asso],
        stats.tableauDeBord.data.licence_annee_derniere_complete[duo],
        stats.tableauDeBord.data.licence_annee_derniere_complete[decouverte_3j],
        stats.tableauDeBord.data.licence_annee_derniere_complete[decouverte_6j],
        stats.tableauDeBord.data.licence_annee_derniere_complete[decouverte_12j],
        stats.tableauDeBord.data.licence_annee_derniere_complete[esport],
    ];
    dataAnneeDerniereComplete.push(dataAnneeDerniereComplete.reduce(function(accumulator, a) {
        if (a !== undefined)
            return accumulator + a;
        return accumulator;
    }, 0));
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [
                'Passion +25',
                'Passion -25',
                'Asso',
                'Duo',
                'Découverte 3j',
                'Découverte 6j',
                'Découverte 12j',
                'Esport',
                'Total',
            ],
            datasets: [
                {
                    label: formatter.format(getDateFromD(stats.tableauDeBord.data.dates.pas_apres_cette_date_cette_annee)),
                    data: dataCetteAnnee,
                },
                {
                    label: formatter.format(getDateFromD(stats.tableauDeBord.data.dates.pas_apres_cette_date_annee_derniere)),
                    data: dataAnneeDerniere,
                },
                {
                    label: getDateFromD(stats.tableauDeBord.data.dates.pas_apres_cette_date_annee_derniere).getFullYear(),
                    data: dataAnneeDerniereComplete,
                }
            ],
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Licences',
                    font: { size: 24 },
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function (value, context) {
                        return value;
                    }
                },
            }
        }
    });
};

let displayMoyensLancement = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('lancements').getContext('2d');
    let dataCetteAnnee = [
        stats.tableauDeBord.data.mise_en_l_air_cette_annee.T,
        stats.tableauDeBord.data.mise_en_l_air_cette_annee.R,
        stats.tableauDeBord.data.mise_en_l_air_cette_annee.T + stats.tableauDeBord.data.mise_en_l_air_cette_annee.R,
    ];
    let dataAnneeDerniere = [
        stats.tableauDeBord.data.mise_en_l_air_annee_derniere.T,
        stats.tableauDeBord.data.mise_en_l_air_annee_derniere.R,
        stats.tableauDeBord.data.mise_en_l_air_annee_derniere.T + stats.tableauDeBord.data.mise_en_l_air_annee_derniere.R,
    ];
    let dataAnneeDerniereComplete = [
        stats.tableauDeBord.data.mise_en_l_air_annee_derniere_complete.T,
        stats.tableauDeBord.data.mise_en_l_air_annee_derniere_complete.R,
        stats.tableauDeBord.data.mise_en_l_air_annee_derniere_complete.T + stats.tableauDeBord.data.mise_en_l_air_annee_derniere_complete.R,
    ];
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [
                'Treuillées',
                'Remorqués',
                'Total',
            ],
            datasets: [
                {
                    label: formatter.format(getDateFromD(stats.tableauDeBord.data.dates.pas_apres_cette_date_cette_annee)),
                    data: dataCetteAnnee,
                },
                {
                    label: formatter.format(getDateFromD(stats.tableauDeBord.data.dates.pas_apres_cette_date_annee_derniere)),
                    data: dataAnneeDerniere,
                },
                {
                    label: getDateFromD(stats.tableauDeBord.data.dates.pas_apres_cette_date_annee_derniere).getFullYear(),
                    data: dataAnneeDerniereComplete,
                }
            ],
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Lancements hors autonomes',
                    font: { size: 24 },
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function (value, context) {
                        return value;
                    }
                },
            }
        }
    });
};

let displayHdv = function(subStats, target, title, max) {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById(target).getContext('2d');
    let dataCetteAnnee = [
        stats.tableauDeBord.data[subStats+'_cette_annee'].cdb,
        stats.tableauDeBord.data[subStats+'_cette_annee'].instruction,
        stats.tableauDeBord.data[subStats+'_cette_annee'].total,
    ];
    let dataAnneeDerniere = [
        stats.tableauDeBord.data[subStats+'_annee_derniere'].cdb,
        stats.tableauDeBord.data[subStats+'_annee_derniere'].instruction,
        stats.tableauDeBord.data[subStats+'_annee_derniere'].total,
    ];
    let dataAnneeDerniereComplete = [
        stats.tableauDeBord.data[subStats+'_annee_derniere_complete'].cdb,
        stats.tableauDeBord.data[subStats+'_annee_derniere_complete'].instruction,
        stats.tableauDeBord.data[subStats+'_annee_derniere_complete'].total,
    ];
    //DEBUG console.log(data);
    let opts = {
        type: 'bar',
        data: {
            labels: [
                'CDB',
                'Instruction',
                'Total',
            ],
            datasets: [
                {
                    label: formatter.format(getDateFromD(stats.tableauDeBord.data.dates.pas_apres_cette_date_cette_annee)),
                    data: dataCetteAnnee,
                },
                {
                    label: formatter.format(getDateFromD(stats.tableauDeBord.data.dates.pas_apres_cette_date_annee_derniere)),
                    data: dataAnneeDerniere,
                },
                {
                    label: getDateFromD(stats.tableauDeBord.data.dates.pas_apres_cette_date_annee_derniere).getFullYear(),
                    data: dataAnneeDerniereComplete,
                }
            ],
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: title,
                    font: { size: 24 },
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function (value, context) {
                        return value;
                    }
                },
            }
        }
    };
    if (max !== undefined)
        opts.options.scales = { y: { suggestedMax: max }};
    return new Chart(ctx, opts);
};

let displayViClub = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('vi_club').getContext('2d');
    let dataCetteAnnee = [
        stats.tableauDeBord.data.vi_club_cette_annee.nb_vi,
    ];
    let dataAnneeDerniere = [
        stats.tableauDeBord.data.vi_club_annee_derniere.nb_vi,
    ];
    let dataAnneeDerniereComplete = [
        stats.tableauDeBord.data.vi_club_annee_derniere_complete.nb_vi,
    ];
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [
                'VI Club',
            ],
            datasets: [
                {
                    label: formatter.format(getDateFromD(stats.tableauDeBord.data.dates.pas_apres_cette_date_cette_annee)),
                    data: dataCetteAnnee,
                },
                {
                    label: formatter.format(getDateFromD(stats.tableauDeBord.data.dates.pas_apres_cette_date_annee_derniere)),
                    data: dataAnneeDerniere,
                },
                {
                    label: getDateFromD(stats.tableauDeBord.data.dates.pas_apres_cette_date_annee_derniere).getFullYear(),
                    data: dataAnneeDerniereComplete,
                }
            ],
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'VI CLUB',
                    font: { size: 24 },
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function (value, context) {
                        return value;
                    }
                },
            }
        }
    });
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
    Chart.register(ChartDataLabels);
    displayLicence();
    displayMoyensLancement();
    let hdv_club_et_banalise = displayHdv('hdv_club_et_banalise', 'hdv_club_et_banalise', 'Heures de vol club+banalisé');
    displayHdv('hdv_club', 'hdv_club', 'Heures de vol club', hdv_club_et_banalise.scales.y.max);
    displayViClub();

    displayMisesEnLAir($('#misesEnLAir'), 'immatriculation', stats.statsMisesEnLAir, stats.statsMisesEnLAirAnneePrecedente);
});

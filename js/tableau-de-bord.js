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

let findStats = function(s, masterKey, value) {
    for (let i = 0; i < s.data.length; i++) {
        if (s.data[i][masterKey] === value)
            return s.data[i];
    }
    return null;
};

// ANNUEL
let displayLicenceAnnuel = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('licenceAnnuel').getContext('2d');
    let dataCetteAnnee = stats.tableauDeBordAnnuel.data.licences;
    let dataNAnneesPrecedantes = stats.tableauDeBordAnnuel.data.licences_n_annees_precedantes;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'Licences '+stats.tableauDeBordAnnuel.params.annee,
                    data: dataCetteAnnee,
                },
                {
                    label: 'moyenne sur les '+stats.tableauDeBordAnnuel.data.moyenne_sur_nb_annee+' dernières années',
                    data: dataNAnneesPrecedantes,
                },
            ],
        },
        options: {
            //maintainAspectRatio: false,
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

let displayValoInfraAnnuel = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('valoFraisInfraAnnuel').getContext('2d');
    let dataCetteAnnee = stats.tableauDeBordAnnuel.data.valo_revenu_infra_membre;
    let dataNAnneesPrecedantes = stats.tableauDeBordAnnuel.data.valo_revenu_infra_membre_n_anneesPrecedantes;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'Revenu cotisations '+stats.tableauDeBordAnnuel.params.annee,
                    data: dataCetteAnnee,
                },
                {
                    label: 'moyenne sur les '+stats.tableauDeBordAnnuel.data.moyenne_sur_nb_annee+' dernières années',
                    data: dataNAnneesPrecedantes,
                },
            ],
        },
        options: {
            scales: {
                y: {
                    ticks: {
                        // Include a dollar sign in the ticks
                        callback: function(value, index, ticks) {
                            return Chart.Ticks.formatters.numeric.apply(this, [value, index, ticks]) + ' €';
                        },
                    },
                },
            },
            //maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Revenu cotisations',
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
                        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                    }
                },
            }
        }
    });
};

let displayHDVClubCDBAnnuel = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('hdvClubCDBAnnuel').getContext('2d');
    let dataCetteAnnee = stats.tableauDeBordAnnuel.data.HDVClubCDB;
    let dataNAnneesPrecedantes = stats.tableauDeBordAnnuel.data.HDVClubCDB_n_anneesPrecedantes;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'HDV machines club CDB '+stats.tableauDeBordAnnuel.params.annee,
                    data: dataCetteAnnee,
                },
                {
                    label: 'moyenne sur les '+stats.tableauDeBordAnnuel.data.moyenne_sur_nb_annee+' dernières années',
                    data: dataNAnneesPrecedantes,
                },
            ],
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Heures de vol machines CLUB en CDB',
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

let displayHDVClubInstructionAnnuel = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('hdvClubInstructionAnnuel').getContext('2d');
    let dataCetteAnnee = stats.tableauDeBordAnnuel.data.HDVClubInstruction;
    let dataNAnneesPrecedantes = stats.tableauDeBordAnnuel.data.HDVClubInstruction_n_anneesPrecedantes;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'HDV d\'instruction sur machines club '+stats.tableauDeBordAnnuel.params.annee,
                    data: dataCetteAnnee,
                },
                {
                    label: 'moyenne sur les '+stats.tableauDeBordAnnuel.data.moyenne_sur_nb_annee+' dernières années',
                    data: dataNAnneesPrecedantes,
                },
            ],
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Heures de vol instruction sur machines CLUB',
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

let displayHDVBanaliseCDBAnnuel = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('hdvBanaliseCDBAnnuel').getContext('2d');
    let dataCetteAnnee = stats.tableauDeBordAnnuel.data.HDVBanaliseCDB;
    let dataNAnneesPrecedantes = stats.tableauDeBordAnnuel.data.HDVBanaliseCDB_n_anneesPrecedantes;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'HDV machines banalisées CDB '+stats.tableauDeBordAnnuel.params.annee,
                    data: dataCetteAnnee,
                },
                {
                    label: 'moyenne sur les '+stats.tableauDeBordAnnuel.data.moyenne_sur_nb_annee+' dernières années',
                    data: dataNAnneesPrecedantes,
                },
            ],
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Heures de vol machines banalisées en CDB',
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

let displayHDVBanaliseInstructionAnnuel = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('hdvBanaliseInstructionAnnuel').getContext('2d');
    let dataCetteAnnee = stats.tableauDeBordAnnuel.data.HDVClubInstruction;
    let dataNAnneesPrecedantes = stats.tableauDeBordAnnuel.data.HDVClubInstruction_n_anneesPrecedantes;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'HDV d\'instruction sur machines banalisées '+stats.tableauDeBordAnnuel.params.annee,
                    data: dataCetteAnnee,
                },
                {
                    label: 'moyenne sur les '+stats.tableauDeBordAnnuel.data.moyenne_sur_nb_annee+' dernières années',
                    data: dataNAnneesPrecedantes,
                },
            ],
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Heures de vol instruction sur machines banalisées',
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

let displayHDVPilotesDansForfaitAnnuel = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('hdvPilotesDansForfait').getContext('2d');
    let dataCetteAnnee = stats.tableauDeBordAnnuel.data.HDVPilotesDansForfait;
    let dataNAnneesPrecedantes = stats.tableauDeBordAnnuel.data.HDVPilotesDansForfait_n_anneesPrecedantes;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'HDV des pilotes au forfait '+stats.tableauDeBordAnnuel.params.annee,
                    data: dataCetteAnnee,
                },
                {
                    label: 'moyenne sur les '+stats.tableauDeBordAnnuel.data.moyenne_sur_nb_annee+' dernières années',
                    data: dataNAnneesPrecedantes,
                },
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
                    text: 'Heures de vol des pilotes au forfait sur les machines incluses dans le forfait (consommation des forfaits)',
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

let displayHDVPilotesHorsForfaitAnnuel = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('hdvPilotesHorsForfait').getContext('2d');
    let dataCetteAnnee = stats.tableauDeBordAnnuel.data.HDVPilotesHorsForfait;
    let dataNAnneesPrecedantes = stats.tableauDeBordAnnuel.data.HDVPilotesHorsForfait_n_anneesPrecedantes;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'HDV hors forfait '+stats.tableauDeBordAnnuel.params.annee,
                    data: dataCetteAnnee,
                },
                {
                    label: 'moyenne sur les '+stats.tableauDeBordAnnuel.data.moyenne_sur_nb_annee+' dernières années',
                    data: dataNAnneesPrecedantes,
                },
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
                    text: 'Heures de vol hors forfait (vols solo, instruction, partagé, VI club, VI perso ...)',
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

let displayLancementRemorqueAnnuel = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('lancementRemorqueAnnuel').getContext('2d');
    let dataCetteAnnee = stats.tableauDeBordAnnuel.data.lancementR;
    let dataNAnneesPrecedantes = stats.tableauDeBordAnnuel.data.lancementR_n_anneesPrecedantes;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'Nombre de remorqué avec correction '+stats.tableauDeBordAnnuel.params.annee,
                    data: dataCetteAnnee,
                },
                {
                    label: 'moyenne sur les '+stats.tableauDeBordAnnuel.data.moyenne_sur_nb_annee+' dernières années',
                    data: dataNAnneesPrecedantes,
                },
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
                    text: 'Nombre de remorqués corrigés',
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

let displayLancementAnnuel = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('lancementAnnuel').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'Nombre de remorqué '+stats.tableauDeBordAnnuel.params.annee,
                    data: stats.tableauDeBordAnnuel.data.lancementR,
                },
                {
                    label: 'Nombre de treuillées '+stats.tableauDeBordAnnuel.params.annee,
                    data: stats.tableauDeBordAnnuel.data.lancementT,
                },
                {
                    label: 'Nombre de lancement autonome '+stats.tableauDeBordAnnuel.params.annee,
                    data: stats.tableauDeBordAnnuel.data.lancementA,
                },
            ],
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    stacked: true,
                },
                y: {
                    stacked: true,
                },
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Détails par type de mise en l\'air',
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

let displayValoCelluleAnnuel = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    let dataCetteAnnee = stats.tableauDeBordAnnuel.data.valo_hdv;
    let dataNAnneesPrecedantes = stats.tableauDeBordAnnuel.data.valo_hdv_n_anneesPrecedantes;
    let opts = {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'Revenu heure de vol '+stats.tableauDeBordAnnuel.params.annee,
                    data: dataCetteAnnee,
                },
                {
                    label: 'moyenne sur les '+stats.tableauDeBordAnnuel.data.moyenne_sur_nb_annee+' dernières années',
                    data: dataNAnneesPrecedantes,
                },
            ],
        },
        options: {
            scales: {
                y: {
                    ticks: {
                        // Include a dollar sign in the ticks
                        callback: function(value, index, ticks) {
                            return Chart.Ticks.formatters.numeric.apply(this, [value, index, ticks]) + ' €';
                        },
                    },
                },
            },
            maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Revenu heure de vol',
                    font: { size: 24 },
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function(value, context) {
                        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                    }
                },
            }
        }
    };
    var ctx = document.getElementById('valoCelluleAnnuel').getContext('2d');
    new Chart(ctx, opts);

    ctx = document.getElementById('valoCelluleAnnuel2').getContext('2d');
    opts.options.maintainAspectRatio = true;
    new Chart(ctx, opts);
};

let displayValoForfaitAnnuel = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('valoForfaitAnnuel').getContext('2d');
    let dataCetteAnnee = stats.tableauDeBordAnnuel.data.valo_forfait;
    let dataNAnneesPrecedantes = stats.tableauDeBordAnnuel.data.valo_forfait_n_anneesPrecedantes;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'Revenu forfait '+stats.tableauDeBordAnnuel.params.annee,
                    data: dataCetteAnnee,
                },
                {
                    label: 'moyenne sur les '+stats.tableauDeBordAnnuel.data.moyenne_sur_nb_annee+' dernières années',
                    data: dataNAnneesPrecedantes,
                },
            ],
        },
        options: {
            scales: {
                y: {
                    ticks: {
                        // Include a dollar sign in the ticks
                        callback: function(value, index, ticks) {
                            return Chart.Ticks.formatters.numeric.apply(this, [value, index, ticks]) + ' €';
                        },
                    },
                },
            },
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Revenu forfait',
                    font: { size: 24 },
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function(value, context) {
                        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                    }
                },
            }
        }
    });
};

let displayValoMoteurAnnuel = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('valoMoteurAnnuel').getContext('2d');
    let dataCetteAnnee = stats.tableauDeBordAnnuel.data.valo_moteur;
    let dataNAnneesPrecedantes = stats.tableauDeBordAnnuel.data.valo_moteur_n_anneesPrecedantes;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'Revenu temps moteur SF28 '+stats.tableauDeBordAnnuel.params.annee,
                    data: dataCetteAnnee,
                },
                {
                    label: 'moyenne sur les '+stats.tableauDeBordAnnuel.data.moyenne_sur_nb_annee+' dernières années',
                    data: dataNAnneesPrecedantes,
                },
            ],
        },
        options: {
            scales: {
                y: {
                    ticks: {
                        // Include a dollar sign in the ticks
                        callback: function(value, index, ticks) {
                            return Chart.Ticks.formatters.numeric.apply(this, [value, index, ticks]) + ' €';
                        },
                    },
                },
            },
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Revenu temps moteur',
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
                        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                    }
                },
            }
        }
    });
};

let displayValoLancementAnnuel = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('valoLancementAnnuel').getContext('2d');
    let dataCetteAnnee = stats.tableauDeBordAnnuel.data.valo_lancement;
    let dataNAnneesPrecedantes = stats.tableauDeBordAnnuel.data.valo_lancement_n_anneesPrecedantes;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'Revenu moyens de lancement '+stats.tableauDeBordAnnuel.params.annee,
                    data: dataCetteAnnee,
                },
                {
                    label: 'moyenne sur les '+stats.tableauDeBordAnnuel.data.moyenne_sur_nb_annee+' dernières années',
                    data: dataNAnneesPrecedantes,
                },
            ],
        },
        options: {
            scales: {
                y: {
                    ticks: {
                        // Include a dollar sign in the ticks
                        callback: function(value, index, ticks) {
                            return Chart.Ticks.formatters.numeric.apply(this, [value, index, ticks]) + ' €';
                        },
                    },
                },
            },
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Revenu moyens de lancement',
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
                        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                    }
                },
            }
        }
    });
};

$(document).ready(function() {
    Chart.register(ChartDataLabels);

    let formatter = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
    let d = getDateFromD(stats.tableauDeBord.data.dates.pas_apres_cette_date_cette_annee);
    $('#activity').text("Activité arrêtée au "+formatter.format(d));
    displayLicence();
    displayMoyensLancement();
    let hdv_club_et_banalise = displayHdv('hdv_club_et_banalise', 'hdv_club_et_banalise', 'Heures de vol club+banalisé');
    displayHdv('hdv_club', 'hdv_club', 'Heures de vol club', hdv_club_et_banalise.scales.y.max);
    displayViClub();

    displayLicenceAnnuel();
    displayValoInfraAnnuel();
    displayHDVClubCDBAnnuel();
    displayHDVClubInstructionAnnuel();

    displayHDVBanaliseCDBAnnuel();
    displayHDVBanaliseInstructionAnnuel();

    displayHDVPilotesDansForfaitAnnuel();
    displayHDVPilotesHorsForfaitAnnuel();

    displayLancementRemorqueAnnuel();
    displayLancementAnnuel();

    displayValoCelluleAnnuel();
    displayValoForfaitAnnuel();
    displayValoMoteurAnnuel();
    displayValoLancementAnnuel();
});

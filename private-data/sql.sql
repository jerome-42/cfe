CREATE OR REPLACE FUNCTION setVarInData(data JSONB, key TEXT, value NUMERIC) RETURNS JSONB AS $$
BEGIN
IF value IS NULL THEN
   data := data - key;
ELSE
   data := data || CONCAT('{"', key, '": ', to_json(value), '}')::jsonb;
END IF;
RETURN data;
END;
$$ LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION setVarInData(data JSONB, key TEXT, value TEXT) RETURNS JSONB AS $$
BEGIN
IF value IS NULL THEN
   data := data - key;
ELSE
   data := data || CONCAT('{"', key, '": ', to_json(value), '}')::jsonb;
END IF;
RETURN data;
END;
$$ LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION setVarInData(data JSONB, key TEXT, value INTERVAL) RETURNS JSONB AS $$
BEGIN
IF value IS NULL THEN
   data := data - key;
ELSE
   data := data || CONCAT('{"', key, '": ', to_json(value), '}')::jsonb;
END IF;
RETURN data;
END;
$$ LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION setVarInData(data JSONB, key TEXT, value JSONB) RETURNS JSONB AS $$
BEGIN
IF value IS NULL THEN
   data := data - key;
ELSE
   data := data || CONCAT('{"', key, '": ', to_json(value), '}')::jsonb;
END IF;
RETURN data;
END;
$$ LANGUAGE plpgsql VOLATILE;


-- date_debut est inclusif
-- date_fin est inclusif
-- select * from statsMachines('2024-01-01', '2024-12-31');
-- D-KBJP          | {"R": {"nb_vol": 2, "temps_vol": "09:02:00"}, "global": {"nb_vol": 2, "temps_vol": "09:02:00"}, "1 Vol en solo": {"nb_vol": 2, "temps_vol": "09:02:00"}}
-- D-KOCM          | {"M": {"nb_vol": 3, "temps_vol": "06:38:00"}, "R": {"nb_vol": 2, "temps_vol": "06:27:00"}, "global": {"nb_vol": 5, "temps_vol": "13:05:00"}, "1 Vol en solo": {"nb_vol": 5, "temps_vol": "13:05:00"}}
--  F-CHIC         | {"R": {"ca": 3605.72, "nb_vol": 89, "temps_vol": "47:18:00"}, "T": {"ca": 625.22, "nb_vol": 39, "temps_vol": "10:38:00"}, "global": {"ca": 4230.94, "nb_vol": 128, "temps_vol": "57:56:00"}, "41 VI perso": {"nb_vol": 1, "temps_vol": "00:21:00"}, "1 Vol en solo": {"nb_vol": 11, "temps_vol": "05:26:00"}, "3 Vol partagé": {"nb_vol": 4, "temps_vol": "03:57:00"}, "2 Vol d'instruction": {"nb_vol": 110, "temps_vol": "47:21:00"}, "9 Vol journée découverte": {"nb_vol": 2, "temps_vol": "00:51:00"}}
create or replace function statsMachines(date_debut date, date_fin date) returns table (
  immatriculation varchar,
  stats jsonb
  ) AS
$$
DECLARE
  r record;
  r_vol record;
  js jsonb;
  types_vol TEXT[];
  type_vol TEXT;
  machines TEXT[];
  mise_en_l_air TEXT;
  mises_en_l_air TEXT[] := '{"R", "T", "M"}';
  sub_json jsonb;
BEGIN
  FOR r IN SELECT vfr_vol.nom_type_vol FROM vfr_vol WHERE date_vol BETWEEN date_debut AND date_fin GROUP BY vfr_vol.nom_type_vol
  LOOP
    types_vol := array_append(types_vol, r.nom_type_vol);
  END LOOP;

  FOR r IN SELECT vfr_vol.id_aeronef, vfr_vol.immatriculation FROM vfr_vol
    JOIN aeronef ON aeronef.id_aeronef = vfr_vol.id_aeronef
    WHERE
    date_fin BETWEEN date_debut AND date_fin AND aeronef.actif IS true
    GROUP BY vfr_vol.id_aeronef, vfr_vol.immatriculation ORDER BY vfr_vol.immatriculation
  LOOP
    immatriculation := r.immatriculation;
    stats := '{}';

    -- nb vol & CA global
    SELECT INTO r_vol COUNT(*) AS nb_vol, SUM(temps_vol) AS temps_vol,
      SUM(COALESCE(prix_vol_elv, 0)) + SUM(COALESCE(prix_treuil_elv, 0)) + SUM(COALESCE(prix_remorque_elv, 0)) + SUM(COALESCE(prix_moteur_elv, 0)) +
        SUM(COALESCE(prix_vol_cdb, 0)) + SUM(COALESCE(prix_treuil_cdb, 0)) + SUM(COALESCE(prix_remorque_cdb, 0)) + SUM(COALESCE(prix_moteur_cdb, 0)) +
        SUM(COALESCE(prix_vol_co, 0)) + SUM(COALESCE(prix_treuil_co, 0)) + SUM(COALESCE(prix_remorque_co, 0)) + SUM(COALESCE(prix_moteur_co, 0)) AS ca FROM vfr_vol
        WHERE date_vol BETWEEN date_debut AND date_fin AND vfr_vol.id_aeronef = r.id_aeronef;
        sub_json := '{}';
        sub_json := setVarInData(sub_json, 'nb_vol', r_vol.nb_vol);
        sub_json := setVarInData(sub_json, 'temps_vol', r_vol.temps_vol);
        sub_json := setVarInData(sub_json, 'ca', r_vol.ca);
        stats := setVarInData(stats, 'global', sub_json);

    -- stats par moyen de mise en l'air
    FOREACH mise_en_l_air IN ARRAY mises_en_l_air
    LOOP
      SELECT INTO r_vol COUNT(*) AS nb_vol, SUM(temps_vol) AS temps_vol,
        SUM(COALESCE(prix_vol_elv, 0)) + SUM(COALESCE(prix_treuil_elv, 0)) + SUM(COALESCE(prix_remorque_elv, 0)) + SUM(COALESCE(prix_moteur_elv, 0)) +
          SUM(COALESCE(prix_vol_cdb, 0)) + SUM(COALESCE(prix_treuil_cdb, 0)) + SUM(COALESCE(prix_remorque_cdb, 0)) + SUM(COALESCE(prix_moteur_cdb, 0)) +
          SUM(COALESCE(prix_vol_co, 0)) + SUM(COALESCE(prix_treuil_co, 0)) + SUM(COALESCE(prix_remorque_co, 0)) + SUM(COALESCE(prix_moteur_co, 0)) AS ca FROM vfr_vol
          WHERE date_vol BETWEEN date_debut AND date_fin AND vfr_vol.id_aeronef = r.id_aeronef AND vfr_vol.mode_decollage = mise_en_l_air;
      IF r_vol.nb_vol > 0 OR r_vol.ca > 0 THEN
        sub_json := '{}';
        sub_json := setVarInData(sub_json, 'nb_vol', r_vol.nb_vol);
        sub_json := setVarInData(sub_json, 'temps_vol', r_vol.temps_vol);
        sub_json := setVarInData(sub_json, 'ca', r_vol.ca);
        stats := setVarInData(stats, mise_en_l_air, sub_json);
      END IF;
    END LOOP;
      

    -- stats par type de vol
    FOREACH type_vol IN ARRAY types_vol
    LOOP
      SELECT INTO r_vol COUNT(*) AS nb_vol, SUM(temps_vol) AS temps_vol,
        SUM(COALESCE(prix_vol_elv, 0)) + SUM(COALESCE(prix_treuil_elv, 0)) + SUM(COALESCE(prix_remorque_elv, 0)) + SUM(COALESCE(prix_moteur_elv, 0)) +
          SUM(COALESCE(prix_vol_cdb, 0)) + SUM(COALESCE(prix_treuil_cdb, 0)) + SUM(COALESCE(prix_remorque_cdb, 0)) + SUM(COALESCE(prix_moteur_cdb, 0)) +
          SUM(COALESCE(prix_vol_co, 0)) + SUM(COALESCE(prix_treuil_co, 0)) + SUM(COALESCE(prix_remorque_co, 0)) + SUM(COALESCE(prix_moteur_co, 0)) AS ca FROM vfr_vol
          WHERE date_vol BETWEEN date_debut AND date_fin AND vfr_vol.id_aeronef = r.id_aeronef AND vfr_vol.nom_type_vol = type_vol;
      IF r_vol.nb_vol > 0 OR r_vol.ca > 0 THEN
        sub_json := '{}';
        sub_json := setVarInData(sub_json, 'nb_vol', r_vol.nb_vol);
        sub_json := setVarInData(sub_json, 'temps_vol', r_vol.temps_vol);
        sub_json := setVarInData(sub_json, 'ca', r_vol.ca);
        stats := setVarInData(stats, type_vol, sub_json);
      END IF;
    END LOOP;
    return NEXT;
  END LOOP;
END;
$$ LANGUAGE plpgsql VOLATILE;

-- select * from statsMisesEnLAir('2024-01-01', '2024-12-31');
--  immatriculation |                                                                                                                                                                     stats                                                                                                                                                                      
-- -----------------+------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
--  F-JDTX          | {"750m": {"ca": 79.80, "nb_vol": 3}, "global": {"ca": 585.00, "nb_vol": 26}, "Remorqué standard - 500m": {"ca": 505.20, "nb_vol": 23}}
--  F-GEKY          | {"750m": {"ca": 2920.34, "nb_vol": 97}, "1000m": {"ca": 378.27, "nb_vol": 11}, "global": {"ca": 10479.80, "nb_vol": 405}, "voltige - 1300m": {"ca": 136.01, "nb_vol": 3}, "Demi-remorqué - 250m": {"ca": 164.43, "nb_vol": 11}, "Dépannage Etrépagny": {"ca": 58.00, "nb_vol": 1}, "Remorqué standard - 500m": {"ca": 6822.75, "nb_vol": 282}}
--  treuil          | {"ca": 1308.00, "nb_vol": 165}
create or replace function statsMisesEnLAir(date_debut date, date_fin date) returns table (
  immatriculation varchar,
  stats jsonb
  ) AS
$$
DECLARE
  r record;
  r_vol record;
  js jsonb;
  sub_js jsonb;
  machines TEXT[];
  sub_json jsonb;
BEGIN
  FOR r IN SELECT aeronef.id_aeronef, aeronef.immatriculation FROM aeronef WHERE actif IS TRUE AND remorqueur IS TRUE
  LOOP
    immatriculation := r.immatriculation;
    stats := '{}';
    SELECT INTO r_vol COUNT(*) AS nb_vol,
      SUM(COALESCE(prix_remorque_elv, 0)) + SUM(COALESCE(prix_remorque_cdb, 0)) + SUM(COALESCE(prix_remorque_co, 0)) AS ca FROM vfr_vol
        WHERE date_vol BETWEEN date_debut AND date_fin AND vfr_vol.id_remorqueur = r.id_aeronef;
    sub_js := '{}';
    sub_js := setVarInData(sub_js, 'nb_vol', r_vol.nb_vol);
    sub_js := setVarInData(sub_js, 'ca', r_vol.ca);
    stats := setVarInData(stats, 'global', sub_js);
    FOR r_vol IN SELECT libelle_remorque, COUNT(*) AS nb_vol,
      SUM(COALESCE(prix_remorque_elv, 0)) + SUM(COALESCE(prix_remorque_cdb, 0)) + SUM(COALESCE(prix_remorque_co, 0)) AS ca FROM vfr_vol
        WHERE date_vol BETWEEN date_debut AND date_fin AND vfr_vol.id_remorqueur = r.id_aeronef GROUP BY libelle_remorque
    LOOP
      sub_js := '{}';
      sub_js := setVarInData(sub_js, 'nb_vol', r_vol.nb_vol);
      sub_js := setVarInData(sub_js, 'ca', r_vol.ca);
      stats := setVarInData(stats, r_vol.libelle_remorque, sub_js);
    END LOOP;

    return NEXT;
  END LOOP;

  immatriculation := 'treuil';
  stats := '{}';
  SELECT INTO r_vol COUNT(*) AS nb_vol,
    SUM(COALESCE(prix_treuil_elv, 0)) + SUM(COALESCE(prix_treuil_cdb, 0)) + SUM(COALESCE(prix_treuil_co, 0)) AS ca FROM vfr_vol
      WHERE date_vol BETWEEN date_debut AND date_fin AND mode_decollage = 'T';
  stats := setVarInData(stats, 'nb_vol', r_vol.nb_vol);
  stats := setVarInData(stats, 'ca', r_vol.ca);
  return NEXT;
END;
$$ LANGUAGE plpgsql VOLATILE;

-- on retourne le id_tarif_type_date qui correspond à un vol
CREATE OR REPLACE FUNCTION getTarifTypeDate(input_nom_type TEXT, input_id_aeronef INT, input_date_vol DATE) RETURNS NUMERIC AS $$
DECLARE
  r record;
  last_id NUMERIC;
BEGIN

  FOR r IN SELECT id_tarif_type_date, tarif_type_date.date_application FROM tarif_type_date
    JOIN tarif_cat_aeronef ON tarif_cat_aeronef.id_tarif_cat_aeronef = tarif_type_date.id_tarif_cat_aeronef
    JOIN tarif_type ON tarif_type.id_tarif_type = tarif_type_date.id_tarif_type
    JOIN aeronef_situation ON aeronef_situation.id_tarif_cat_aeronef = tarif_type_date.id_tarif_cat_aeronef
    WHERE aeronef_situation.id_aeronef = input_id_aeronef AND tarif_type.nom_type = input_nom_type
    ORDER BY date_application ASC
  LOOP
    IF r.date_application >= input_date_vol THEN
      RETURN last_id;
    END IF;
    last_id := r.id_tarif_type_date;
  END LOOP;
  RETURN last_id;
END;
$$ LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION calculePrixVol(input_id_tarif_type_date NUMERIC, temps_vol INTERVAL) RETURNS NUMERIC AS $$
DECLARE
  r_tarif_type_cond record;
  r_tranche_item record;
  prix NUMERIC := 0;
  temps_vol_dans_item INTERVAL;
  tarif record;
BEGIN

  SELECT * INTO r_tarif_type_cond FROM tarif_type_cond
    JOIN tarif_type_vol ON tarif_type_vol.id_tarif_type_vol = tarif_type_cond.id_tarif_type_vol
    WHERE id_tarif_type_date = input_id_tarif_type_date AND tarif_type_vol.nom_type_vol = '1 Vol en solo' LIMIT 1;
  RAISE NOTICE 'prix 1 heure de vol: %', r_tarif_type_cond.prix_heure;
  FOR r_tranche_item IN SELECT * FROM tarif_tranche_item WHERE id_tarif_tranche = r_tarif_type_cond.id_tarif_tranche_vol
  LOOP
    IF temps_vol > '0:0:0'::interval THEN
      IF temps_vol > r_tranche_item.plafond THEN
        temps_vol_dans_item := r_tranche_item.plafond;
        temps_vol := temps_vol - r_tranche_item.plafond;
      ELSE
        temps_vol_dans_item := temps_vol;
        temps_vol := 0;
      END IF;
      RAISE NOTICE 'prix pour % coef %: %', temps_vol_dans_item, r_tranche_item.coefficient, r_tarif_type_cond.prix_heure * r_tranche_item.coefficient * EXTRACT(epoch FROM temps_vol_dans_item)/3600;
      prix := prix + r_tarif_type_cond.prix_heure * r_tranche_item.coefficient * EXTRACT(epoch FROM temps_vol_dans_item)/3600;
    END IF;
  END LOOP;

  return prix;
END;
$$ LANGUAGE plpgsql VOLATILE;

-- pour chaque vol où le tarif est à 0 car forfait
CREATE OR REPLACE FUNCTION calculeTarifSiHordForfait(input_id_pilote INT, annee INT) RETURNS NUMERIC AS $$
DECLARE
  r_pilote record;
  nom_type TEXT;
  r_vol record;
  id_tarif_type_date NUMERIC;
  prix_vols NUMERIC := 0;
  prix_du_vol NUMERIC;
BEGIN
  -- on récupère la catégorie du pilote (-25 ans ou +25 ans)
  SELECT * INTO r_pilote FROM vfr_pilote WHERE id_personne = input_id_pilote LIMIT 1;
  --RAISE NOTICE 'categorie: % %: %', r_pilote.nom, r_pilote.prenom, r_pilote.cat_age;
  IF r_pilote.cat_age = '-25 ans' THEN
    nom_type = 'Tarif général junior';
  ELSE
    nom_type = 'Tarif général';
  END IF;

  RAISE NOTICE '% categorie: %', input_id_pilote, nom_type;
  FOR r_vol IN SELECT * FROM vfr_vol
    WHERE saison = 2023 and id_cdt_de_bord = input_id_pilote and prix_vol_cdb = 0
  LOOP
    RAISE NOTICE 'calcul du prix pour % % %: %', r_vol.cdt_de_bord, r_vol.id_aeronef, r_vol.immatriculation, r_vol.temps_vol;
    id_tarif_type_date := getTarifTypeDate(nom_type, r_vol.id_aeronef, r_vol.date_vol);
    RAISE NOTICE 'id_tarif_type_date: %', id_tarif_type_date;
    -- on récupère le tarif
    prix_du_vol := calculePrixVol(id_tarif_type_date, r_vol.temps_vol);
    RAISE NOTICE 'prix_du_vol: %', prix_du_vol;
    prix_vols := prix_vols + prix_du_vol;
  END LOOP;

  RETURN prix_vols;
END;
$$ LANGUAGE plpgsql VOLATILE;

create or replace function statsForfait(annee int) returns table (
  nom_forfait varchar,
  date_debut date,
  date_fin date,
  montant_forfait numeric,
  conso_forfait interval,
  prix_vols numeric,
  pilote varchar,
  ca_eleve numeric,
  ca_cdb numeric, -- prix mise en l'air + prix vols hors forfait
  ca_co numeric,
  ca_cette_annee numeric,
  ca_si_pas_forfait numeric
  ) AS
$$
DECLARE
  r_forfait record;
  r_vol record;
  ca NUMERIC;
BEGIN
  -- parmis les forfaits des pilotes, il y a des forfaits stage découverte, JD ...
  -- les JD ... ont des hrs_cellules fixés et pas illimités
  -- donc pour sortir les forfaits qui intéressant il faut filtrer par hrs_cellules
  FOR r_forfait IN SELECT * FROM vfr_forfait_pilote
    JOIN vfr_gv_personne ON vfr_forfait_pilote.id_personne = vfr_gv_personne.id_personne
    WHERE EXTRACT(YEAR FROM vfr_forfait_pilote.date_debut) = annee AND vfr_forfait_pilote.hrs_cellule = '999:00:00' ORDER BY vfr_forfait_pilote.nom_forfait
  LOOP
    nom_forfait := r_forfait.nom_forfait;
    date_debut := r_forfait.date_debut;
    date_fin := r_forfait.date_fin;
    montant_forfait := r_forfait.montant;
    conso_forfait := r_forfait.conso_hrs_cellule;
    prix_vols := calculeTarifSiHordForfait(r_forfait.id_personne, annee);
    pilote := CONCAT(r_forfait.prenom, ' ', r_forfait.nom)::varchar;

    -- CA élève
    SELECT INTO r_vol SUM(COALESCE(prix_vol_elv, 0)) + SUM(COALESCE(prix_treuil_elv, 0)) + SUM(COALESCE(prix_remorque_elv, 0)) + SUM(COALESCE(prix_moteur_elv, 0)) AS ca
    FROM vfr_vol
    JOIN pilote ON pilote.id_personne = vfr_vol.id_eleve
    WHERE saison = annee AND pilote.id_personne = r_forfait.id_personne;
    ca_eleve := r_vol.ca;

    SELECT INTO r_vol SUM(COALESCE(prix_vol_cdb, 0)) + SUM(COALESCE(prix_treuil_cdb, 0)) + SUM(COALESCE(prix_remorque_cdb, 0)) + SUM(COALESCE(prix_moteur_cdb, 0)) AS ca
    FROM vfr_vol
    JOIN pilote ON pilote.id_personne = vfr_vol.id_cdt_de_bord
    WHERE saison = annee AND pilote.id_personne = r_forfait.id_personne;
    ca_cdb := r_vol.ca;

    SELECT INTO r_vol SUM(COALESCE(prix_vol_co, 0)) + SUM(COALESCE(prix_treuil_co, 0)) + SUM(COALESCE(prix_remorque_co, 0)) + SUM(COALESCE(prix_moteur_co, 0)) AS ca
    FROM vfr_vol
    JOIN pilote ON pilote.id_personne = vfr_vol.id_co_pilote
    WHERE saison = annee AND pilote.id_personne = r_forfait.id_personne;
    ca_co := r_vol.ca;

    ca_cette_annee := COALESCE(ca_eleve, 0) + COALESCE(ca_cdb, 0) + COALESCE(ca_co, 0) + COALESCE(montant_forfait, 0);
    ca_si_pas_forfait := COALESCE(ca_eleve, 0) + COALESCE(ca_cdb, 0) + COALESCE(ca_co, 0) + COALESCE(prix_vols, 0);

    RETURN next;
  END LOOP;
END;
$$ LANGUAGE plpgsql VOLATILE;

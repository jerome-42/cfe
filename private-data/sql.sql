CREATE OR REPLACE FUNCTION array_distinct(anyarray) RETURNS anyarray AS $$
  SELECT array_agg(DISTINCT x) FROM unnest($1) t(x);
$$ LANGUAGE SQL IMMUTABLE;

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

CREATE OR REPLACE FUNCTION setVarInData(data JSONB, key TEXT, value NUMERIC[]) RETURNS JSONB AS $$
BEGIN
IF value IS NULL THEN
   data := data - key;
ELSE
   data := data || CONCAT('{"', key, '": ', to_json(value), '}')::jsonb;
END IF;
RETURN data;
END;
$$ LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION setVarInData(data JSONB, key TEXT, value INT[]) RETURNS JSONB AS $$
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

CREATE OR REPLACE FUNCTION setVarInData(data JSONB, key TEXT, value DATE) RETURNS JSONB AS $$
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

CREATE OR REPLACE FUNCTION setVarInData(data JSONB, key TEXT, value JSONB[]) RETURNS JSONB AS $$
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
-- ca correspond au chiffre d'affaire du club en heures de vol
-- pour les privés le calcul est fait même s'il n'y a pas de rétrocession
-- lorsque le propriétaire vole dessus
CREATE OR REPLACE FUNCTION statsMachines(date_debut date, date_fin date) returns table (
  immatriculation varchar,
  stats jsonb
  ) AS
$$
DECLARE
  r record;
  r_machine RECORD;
  r2 RECORD;
  r_vol record;
  js jsonb;
  types_vol TEXT[];
  type_vol TEXT;
  machines TEXT[];
  pilotes JSONB[];
  somme_tous_les_vols INTERVAL;
  mise_en_l_air TEXT;
  mises_en_l_air TEXT[] := '{"R", "T", "M"}';
  sub_json jsonb;
  frais_hangar NUMERIC;
  ca NUMERIC;
  decollage_autonome NUMERIC;
  machine_est_privee BOOLEAN;
BEGIN
  FOR r IN SELECT vfr_vol.nom_type_vol FROM vfr_vol WHERE date_vol BETWEEN date_debut AND date_fin GROUP BY vfr_vol.nom_type_vol
  LOOP
    types_vol := array_append(types_vol, r.nom_type_vol);
  END LOOP;

  FOR r IN SELECT vfr_vol.id_aeronef, vfr_vol.immatriculation
    FROM vfr_vol
    JOIN aeronef ON aeronef.id_aeronef = vfr_vol.id_aeronef
    WHERE date_fin BETWEEN date_debut AND date_fin
    AND aeronef.actif IS true
--    AND vfr_vol.id_aeronef = 18867 -- KAKO
--    AND vfr_vol.id_aeronef = 36 -- F-CGYG
--    AND vfr_vol.id_aeronef = 18860 -- F-CFDM
--    AND vfr_vol.id_aeronef = 18841 -- F-CCER pas dans les hangars et pas de vol
--    AND vfr_vol.immatriculation = 'D-KOCM'  -- F-KOCM décollage autonome facturés à la fin de l'année comme des treuillées
--    AND vfr_vol.immatriculation = 'D-3743' -- situation aeronef en cours d'année
--    AND vfr_vol.immatriculation = 'F-CVOL' -- frais hangar car machine Extérieure, devrait être Privée
    GROUP BY vfr_vol.id_aeronef, vfr_vol.immatriculation ORDER BY vfr_vol.immatriculation
  LOOP
    immatriculation := r.immatriculation;
    stats := '{}';

    SELECT INTO r_machine * FROM aeronef WHERE id_aeronef = r.id_aeronef;
    machine_est_privee := NULL;
    -- situation
    SELECT INTO r2 CASE WHEN situation = 'C' THEN false ELSE true END AS machine_est_privee, situation FROM aeronef_situation WHERE id_aeronef = r.id_aeronef
    AND EXTRACT(YEAR FROM date_application) <= EXTRACT(YEAR FROM date_debut)
    ORDER BY date_application DESC LIMIT 1;
    machine_est_privee := r2.machine_est_privee;
    RAISE NOTICE '% machine_est_privee=%', immatriculation, machine_est_privee;
    
    stats := setVarInData(stats, 'id_aeronef', r.id_aeronef);
    CASE r2.situation
      WHEN 'C' THEN stats := setVarInData(stats, 'situation', 'CLUB');
      WHEN 'B' THEN stats := setVarInData(stats, 'situation', 'BANALISE');
      WHEN 'P' THEN stats := setVarInData(stats, 'situation', 'PRIVE');
      WHEN 'E' THEN stats := setVarInData(stats, 'situation', 'EXTERIEUR');
      ELSE stats := setVarInData(stats, 'situation', r2.situation);
    END CASE;
    SELECT INTO r2 * FROM aeronef WHERE id_aeronef = r.id_aeronef;
    stats := setVarInData(stats, 'nb_place', r2.nb_places);

    -- nb vol & CA heure de vol
    IF machine_est_privee IS false THEN -- machine club, on compte prix vol
      SELECT INTO r_vol COUNT(*) AS nb_vol, SUM(temps_vol) AS temps_vol,
        SUM(COALESCE(prix_vol_elv, 0)) + SUM(COALESCE(prix_vol_cdb, 0)) + SUM(COALESCE(prix_vol_co, 0)) AS cellule,
        SUM(COALESCE(prix_moteur_elv, 0)) + SUM(COALESCE(prix_moteur_cdb, 0)) + SUM(COALESCE(prix_moteur_co, 0)) AS moteur FROM vfr_vol
          WHERE date_vol BETWEEN date_debut AND date_fin AND vfr_vol.id_aeronef = r.id_aeronef;
          sub_json := '{}';
          sub_json := setVarInData(sub_json, 'nb_vol', r_vol.nb_vol);
          sub_json := setVarInData(sub_json, 'temps_vol', r_vol.temps_vol);
          sub_json := setVarInData(sub_json, 'revenus_cellule', r_vol.cellule);
          sub_json := setVarInData(sub_json, 'revenus_moteur', r_vol.moteur);
          sub_json := setVarInData(sub_json, 'ca', r_vol.cellule + r_vol.moteur);
          stats := setVarInData(stats, 'global', sub_json);
    ELSE -- privé
      RAISE NOTICE '% (id_aeronef=%) est une machine banalisée', r.immatriculation, r.id_aeronef;
      SELECT INTO r_vol COUNT(*) AS nb_vol, SUM(temps_vol) AS temps_vol FROM vfr_vol
          WHERE date_vol BETWEEN date_debut AND date_fin AND vfr_vol.id_aeronef = r.id_aeronef;
          sub_json := '{}';
          sub_json := setVarInData(sub_json, 'nb_vol', r_vol.nb_vol);
          sub_json := setVarInData(sub_json, 'temps_vol', r_vol.temps_vol);
          frais_hangar := getFraisHangarParMachine(r_machine.id_aeronef, EXTRACT(YEAR FROM date_debut));
          -- NULL ça veut dire que l'on n'a pas trouvé, donc potentiellement une erreur
          -- on ne lève pas l'erreur, on met à 0 comme si on n'avait pas trouvé
          IF frais_hangar IS NULL THEN
            frais_hangar := 0;
          END IF;
          --DEBUG RAISE NOTICE 'frais de hangar: %', frais_hangar;
          stats := setVarInData(stats, 'frais_hangar', frais_hangar);
          -- on veut avoir le coût des heures de vol pour savoir quel chiffre d'affaire le club verrai
          -- si la machine était dans le parc club
          ca := 0;
          FOR r_vol IN SELECT * FROM vfr_vol WHERE date_vol BETWEEN date_debut AND date_fin AND vfr_vol.id_aeronef = r.id_aeronef LOOP
            ca := ca + CASE WHEN r_vol.id_cdt_de_bord IS NOT NULL THEN calculPrixVol(r_vol.id_cdt_de_bord, r_vol.id_aeronef, r_vol.date_vol, r_vol.id_tarif_type_vol, r_vol.temps_vol)
            WHEN r_vol.id_eleve IS NOT NULL THEN calculPrixVol(r_vol.id_eleve, r_vol.id_aeronef, r_vol.date_vol, r_vol.id_tarif_type_vol, r_vol.temps_vol)
            END;
          END LOOP;
          sub_json := setVarInData(sub_json, 'ca_si_club', ca);
          stats := setVarInData(stats, 'global', sub_json);
    END IF;

    -- stats revenu mises en l'air
    SELECT INTO r_vol COALESCE(SUM(COALESCE(prix_treuil_elv, 0)) + SUM(COALESCE(prix_remorque_elv, 0)) +
      SUM(COALESCE(prix_treuil_cdb, 0)) + SUM(COALESCE(prix_remorque_cdb, 0)) +
      SUM(COALESCE(prix_treuil_co, 0)) + SUM(COALESCE(prix_remorque_co, 0)), 0) AS ca FROM vfr_vol
      WHERE date_vol BETWEEN date_debut AND date_fin AND vfr_vol.id_aeronef = r.id_aeronef;
    stats := setVarInData(stats, 'revenus_mise_en_l_air', r_vol.ca);
    IF r_machine.autonome IS TRUE THEN
      RAISE NOTICE '% est autonome, on récupère les revenus des décollages autonomes transformés en treuillées', immatriculation;
      decollage_autonome := getRefactuDecollagesAutonome(r_machine.id_aeronef, EXTRACT(YEAR FROM date_debut));
      stats := setVarInData(stats, 'revenu_decollage_autonome', decollage_autonome);
      stats := setVarInData(stats, 'revenus_mise_en_l_air', r_vol.ca + decollage_autonome);
    END IF;

    -- stats par moyen de mise en l'air
    FOREACH mise_en_l_air IN ARRAY mises_en_l_air
    LOOP
      IF machine_est_privee IS false THEN -- machine club, on compte tout
        SELECT INTO r_vol COUNT(*) AS nb_vol, SUM(temps_vol) AS temps_vol,
          SUM(COALESCE(prix_treuil_elv, 0)) + SUM(COALESCE(prix_remorque_elv, 0)) +
            SUM(COALESCE(prix_treuil_cdb, 0)) + SUM(COALESCE(prix_remorque_cdb, 0)) +
            SUM(COALESCE(prix_treuil_co, 0)) + SUM(COALESCE(prix_remorque_co, 0)) AS ca FROM vfr_vol
            WHERE date_vol BETWEEN date_debut AND date_fin AND vfr_vol.id_aeronef = r.id_aeronef AND vfr_vol.mode_decollage = mise_en_l_air;
        IF r_vol.nb_vol > 0 OR r_vol.ca > 0 THEN
          sub_json := '{}';
          sub_json := setVarInData(sub_json, 'nb_vol', r_vol.nb_vol);
          sub_json := setVarInData(sub_json, 'temps_vol', r_vol.temps_vol);
          sub_json := setVarInData(sub_json, 'revenus_mise_en_l_air', r_vol.ca);
          stats := setVarInData(stats, mise_en_l_air, sub_json);
        END IF;
      ELSE -- machine privée, on ne compte pas les heures de vol
        SELECT INTO r_vol COUNT(*) AS nb_vol, SUM(temps_vol) AS temps_vol,
          SUM(COALESCE(prix_treuil_elv, 0)) + SUM(COALESCE(prix_remorque_elv, 0)) +
            SUM(COALESCE(prix_treuil_cdb, 0)) + SUM(COALESCE(prix_remorque_cdb, 0)) +
            SUM(COALESCE(prix_treuil_co, 0)) + SUM(COALESCE(prix_remorque_co, 0)) AS ca FROM vfr_vol
            WHERE date_vol BETWEEN date_debut AND date_fin AND vfr_vol.id_aeronef = r.id_aeronef AND vfr_vol.mode_decollage = mise_en_l_air;
        -- si la machine est autonome on cherche la facturation "Décollages Autonomes LFFC"
        IF r_vol.nb_vol > 0 OR r_vol.ca > 0 THEN
          sub_json := '{}';
          sub_json := setVarInData(sub_json, 'nb_vol', r_vol.nb_vol);
          sub_json := setVarInData(sub_json, 'temps_vol', r_vol.temps_vol);
          sub_json := setVarInData(sub_json, 'revenus_mise_en_l_air', r_vol.ca);
          stats := setVarInData(stats, mise_en_l_air, sub_json);
        END IF;
      END IF;
    END LOOP;


    -- stats par type de vol
    FOREACH type_vol IN ARRAY types_vol
    LOOP
      IF machine_est_privee IS false THEN -- machine club, on compte tout
        SELECT INTO r_vol COUNT(*) AS nb_vol, SUM(temps_vol) AS temps_vol,
          SUM(COALESCE(prix_vol_elv, 0)) + SUM(COALESCE(prix_vol_cdb, 0)) + SUM(COALESCE(prix_vol_co, 0)) AS ca FROM vfr_vol
            WHERE date_vol BETWEEN date_debut AND date_fin AND vfr_vol.id_aeronef = r.id_aeronef AND vfr_vol.nom_type_vol = type_vol;
        IF r_vol.nb_vol > 0 OR r_vol.ca > 0 THEN
          sub_json := '{}';
          sub_json := setVarInData(sub_json, 'nb_vol', r_vol.nb_vol);
          sub_json := setVarInData(sub_json, 'temps_vol', r_vol.temps_vol);
          sub_json := setVarInData(sub_json, 'ca', r_vol.ca);
          stats := setVarInData(stats, type_vol, sub_json);
        END IF;
      ELSE -- machine privée
        SELECT INTO r_vol COUNT(*) AS nb_vol, SUM(temps_vol) AS temps_vol FROM vfr_vol
          WHERE date_vol BETWEEN date_debut AND date_fin AND vfr_vol.id_aeronef = r.id_aeronef AND vfr_vol.nom_type_vol = type_vol;
        IF r_vol.nb_vol > 0 THEN
          sub_json := '{}';
          sub_json := setVarInData(sub_json, 'nb_vol', r_vol.nb_vol);
          sub_json := setVarInData(sub_json, 'temps_vol', r_vol.temps_vol);
          -- on veut avoir le coût des heures de vol pour savoir quel chiffre d'affaire le club verrai
          -- si la machine était dans le parc club
          ca := 0;
          FOR r_vol IN SELECT * FROM vfr_vol WHERE date_vol BETWEEN date_debut AND date_fin AND vfr_vol.id_aeronef = r.id_aeronef AND vfr_vol.nom_type_vol = type_vol LOOP
            ca := ca + CASE WHEN r_vol.id_cdt_de_bord IS NOT NULL THEN calculPrixVol(r_vol.id_cdt_de_bord, r_vol.id_aeronef, r_vol.date_vol, r_vol.id_tarif_type_vol, r_vol.temps_vol)
            WHEN r_vol.id_eleve IS NOT NULL THEN calculPrixVol(r_vol.id_eleve, r_vol.id_aeronef, r_vol.date_vol, r_vol.id_tarif_type_vol, r_vol.temps_vol)
            END;
          END LOOP;
          sub_json := setVarInData(sub_json, 'ca_si_club', ca);
        END IF;
      END IF;
    END LOOP;


    SELECT * INTO r2 FROM aeronef WHERE id_aeronef = r.id_aeronef;
    -- on ne fait les stats sur les pilotes qui volent sur cette machine uniquement
    -- sur les planeurs et pas les remorqueurs
    IF r2.remorqueur IS false THEN
      SELECT SUM(temps_vol) INTO somme_tous_les_vols
        FROM vfr_vol
        WHERE date_vol BETWEEN date_debut AND date_fin
          AND id_aeronef = r.id_aeronef;
      --DEBUG RAISE NOTICE '% %', r2.immatriculation, somme_tous_les_vols;
      pilotes := '{}';
      FOR r2 IN
          WITH cdt AS (SELECT CONCAT(nom, ' ', prenom) AS nom, SUM(temps_vol) AS temps_vol
            FROM vfr_vol
            JOIN gv_personne ON gv_personne.id_personne = vfr_vol.id_cdt_de_bord
            WHERE date_vol BETWEEN date_debut AND date_fin
              AND id_aeronef = r.id_aeronef
              AND id_cdt_de_bord IS NOT NULL
              GROUP BY nom, prenom
              ORDER BY temps_vol DESC),
          co AS (SELECT CONCAT(nom, ' ', prenom) AS nom, SUM(temps_vol) AS temps_vol
            FROM vfr_vol
            JOIN gv_personne ON gv_personne.id_personne = vfr_vol.id_co_pilote
            WHERE date_vol BETWEEN date_debut AND date_fin
              AND id_aeronef = r.id_aeronef
              AND id_co_pilote IS NOT NULL
              GROUP BY nom, prenom
              ORDER BY temps_vol DESC),
          eleve AS (SELECT CONCAT(nom, ' ', prenom) AS nom, SUM(temps_vol) AS temps_vol
            FROM vfr_vol
            JOIN gv_personne ON gv_personne.id_personne = vfr_vol.id_eleve
            WHERE date_vol BETWEEN date_debut AND date_fin
              AND id_aeronef = r.id_aeronef
              AND id_eleve IS NOT NULL
              GROUP BY nom, prenom
              ORDER BY temps_vol DESC)
           SELECT
           COALESCE(cdt.nom, co.nom, eleve.nom) AS nom,
           COALESCE(cdt.temps_vol, '0'::interval)+COALESCE(co.temps_vol, '0'::interval)+COALESCE(eleve.temps_vol, '0'::interval) AS temps_vol
           FROM cdt
           FULL JOIN co ON cdt.nom = co.nom
           FULL JOIN eleve ON cdt.nom = eleve.nom
           ORDER BY temps_vol DESC
      LOOP
        IF cardinality(pilotes) < 10 THEN
          sub_json := '{}';
          sub_json := setVarInData(sub_json, 'membre', r2.nom);
          sub_json := setVarInData(sub_json, 'temps_vol', r2.temps_vol);
          sub_json := setVarInData(sub_json, 'percent', ROUND((EXTRACT(EPOCH FROM r2.temps_vol) / EXTRACT(EPOCH FROM somme_tous_les_vols)) * 100, 0));
          pilotes := array_append(pilotes, sub_json);
        END IF;
      END LOOP;
      stats := setVarInData(stats, 'pilotes', pilotes);
    END IF;

    return NEXT;
  END LOOP;
END;
$$ LANGUAGE plpgsql VOLATILE;

-- select * from statsMisesEnLAir('2024-01-01', '2024-12-31');
-- ca comprend la mise en l'air et pas les heures de vol
--  immatriculation |                                                                                                                                                                     stats
-- -----------------+------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
--  F-JDTX          | {"750m": {"ca": 79.80, "nb_vol": 3}, "global": {"ca": 585.00, "nb_vol": 26}, "Remorqué standard - 500m": {"ca": 505.20, "nb_vol": 23}}
--  F-GEKY          | {"750m": {"ca": 2920.34, "nb_vol": 97}, "1000m": {"ca": 378.27, "nb_vol": 11}, "global": {"ca": 10479.80, "nb_vol": 405}, "voltige - 1300m": {"ca": 136.01, "nb_vol": 3}, "Demi-remorqué - 250m": {"ca": 164.43, "nb_vol": 11}, "Dépannage Etrépagny": {"ca": 58.00, "nb_vol": 1}, "Remorqué standard - 500m": {"ca": 6822.75, "nb_vol": 282}}
--  treuil          | {"ca": 1308.00, "nb_vol": 165}
CREATE OR REPLACE FUNCTION statsMisesEnLAir(date_debut date, date_fin date) returns table (
  immatriculation varchar,
  stats jsonb
  ) AS
$$
DECLARE
  r record;
  r_vol record;
  js jsonb;
  sub_js jsonb;
  sub_js2 jsonb;
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
    sub_js2 := '{}';
    FOR r_vol IN SELECT libelle_remorque, COUNT(*) AS nb_vol,
      SUM(COALESCE(prix_remorque_elv, 0)) + SUM(COALESCE(prix_remorque_cdb, 0)) + SUM(COALESCE(prix_remorque_co, 0)) AS ca FROM vfr_vol
        WHERE date_vol BETWEEN date_debut AND date_fin AND vfr_vol.id_remorqueur = r.id_aeronef GROUP BY libelle_remorque
    LOOP
      sub_js := '{}';
      sub_js := setVarInData(sub_js, 'nb_vol', r_vol.nb_vol);
      sub_js := setVarInData(sub_js, 'ca', r_vol.ca);
      sub_js2 := setVarInData(sub_js2, r_vol.libelle_remorque, sub_js);
    END LOOP;
    stats := setVarInData(stats, 'type_mise_en_l_air', sub_js2);

    sub_js2 := '{}';
    FOR r_vol IN SELECT nom_type_vol, COUNT(*) AS nb_vol,
      SUM(COALESCE(prix_remorque_elv, 0)) + SUM(COALESCE(prix_remorque_cdb, 0)) + SUM(COALESCE(prix_remorque_co, 0)) AS ca FROM vfr_vol
        WHERE date_vol BETWEEN date_debut AND date_fin AND vfr_vol.id_remorqueur = r.id_aeronef GROUP BY nom_type_vol
    LOOP
      sub_js := '{}';
      sub_js := setVarInData(sub_js, 'nb_vol', r_vol.nb_vol);
      sub_js := setVarInData(sub_js, 'ca', r_vol.ca);
      sub_js2 := setVarInData(sub_js2, r_vol.nom_type_vol, sub_js);
    END LOOP;
    stats := setVarInData(stats, 'type_vol', sub_js2);

    return NEXT;
  END LOOP;

  immatriculation := 'treuil';
  stats := '{}';
  SELECT INTO r_vol COUNT(*) AS nb_vol,
    SUM(COALESCE(prix_treuil_elv, 0)) + SUM(COALESCE(prix_treuil_cdb, 0)) + SUM(COALESCE(prix_treuil_co, 0)) AS ca FROM vfr_vol
      WHERE date_vol BETWEEN date_debut AND date_fin AND mode_decollage = 'T';
  sub_js := '{}';
  sub_js := setVarInData(sub_js, 'nb_vol', r_vol.nb_vol);
  sub_js := setVarInData(sub_js, 'ca', r_vol.ca);
  stats := setVarInData(stats, 'global', sub_js);

  sub_js2 := '{}';
  FOR r_vol IN SELECT nom_type_vol, COUNT(*) AS nb_vol,
    SUM(COALESCE(prix_treuil_elv, 0)) + SUM(COALESCE(prix_treuil_cdb, 0)) + SUM(COALESCE(prix_treuil_co, 0)) AS ca FROM vfr_vol
      WHERE date_vol BETWEEN date_debut AND date_fin AND mode_decollage = 'T' GROUP BY nom_type_vol
  LOOP
    sub_js := '{}';
    sub_js := setVarInData(sub_js, 'nb_vol', r_vol.nb_vol);
    sub_js := setVarInData(sub_js, 'ca', r_vol.ca);
    sub_js2 := setVarInData(sub_js2, r_vol.nom_type_vol, sub_js);
  END LOOP;
  stats := setVarInData(stats, 'type_vol', sub_js2);

  return NEXT;
END;
$$ LANGUAGE plpgsql VOLATILE;

-- on retourne le id_tarif_type_date qui correspond à un vol
CREATE OR REPLACE FUNCTION getTarifType(input_id_aeronef INT, input_id_tarif_type INT, input_date_vol DATE, input_id_tarif_type_vol INT) RETURNS RECORD AS
$$
DECLARE
  r RECORD;
  last_id NUMERIC := NULL;
  v_id_tarif_cat_aeronef INT;
  ret RECORD;
BEGIN
  --DEBUG RAISE NOTICE 'getTarifType(%, %, %, %)', input_id_aeronef, input_id_tarif_type, input_date_vol, input_id_tarif_type_vol;
  -- on va chercher la catégorie de l'aéronef à la date du vol
  SELECT INTO r id_tarif_cat_aeronef, date_application FROM aeronef_situation WHERE id_aeronef = input_id_aeronef AND input_date_vol >= date_application ORDER BY date_application ASC LIMIT 1;
  v_id_tarif_cat_aeronef := r.id_tarif_cat_aeronef;
  --DEBUG RAISE NOTICE 'id_tarif_cat_aeronef: %', v_id_tarif_cat_aeronef;
  SELECT INTO r tarif_type_cond.id_tarif_type_date, tarif_type_date.date_application, tarif_type_cond.prix_heure, tarif_type_cond.id_tarif_tranche_vol FROM tarif_type_cond
    JOIN tarif_type_date ON tarif_type_date.id_tarif_type_date = tarif_type_cond.id_tarif_type_date
    WHERE (id_tarif_cat_aeronef = v_id_tarif_cat_aeronef OR tarif_type_date.id_aeronef = input_id_aeronef) AND tarif_type_date.id_tarif_type = input_id_tarif_type
    AND tarif_type_cond.id_tarif_type_vol = input_id_tarif_type_vol
    AND tarif_type_date.date_application <= input_date_vol
    ORDER BY tarif_type_date.date_application ASC LIMIT 1;
  --DEBUG RAISE NOTICE 'retour: %', r;
  IF r.date_application IS NOT NULL THEN
    --DEBUGU RAISE NOTICE 'on a trouvé un prix %/heure et id_tarif_tranche_vol: %', r.prix_heure, r.id_tarif_tranche_vol;
    SELECT r.id_tarif_tranche_vol AS id_tarif_tranche_vol, r.prix_heure AS prix_heure INTO ret;
    RETURN ret;
  END IF;
  --DEBUG RAISE NOTICE 'pas de tarif trouvé';
  SELECT NULL::INT AS id_tarif_tranche_vol, NULL::NUMERIC AS prix_heure INTO ret;
  RETURN ret;
END;
$$ LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION getTarifDetails(input_id_aeronef INT, input_id_tarif_type INT, input_date_vol DATE) RETURNS INT AS $$
DECLARE
  r RECORD;
  last_id INT;
BEGIN
  FOR r IN
    SELECT id_tarif_type_date, tarif_type_date.date_application FROM tarif_type_date
      JOIN tarif_cat_aeronef ON tarif_cat_aeronef.id_tarif_cat_aeronef = tarif_type_date.id_tarif_cat_aeronef
      JOIN aeronef_situation ON aeronef_situation.id_tarif_cat_aeronef = tarif_type_date.id_tarif_cat_aeronef
      WHERE aeronef_situation.id_aeronef = input_id_aeronef AND tarif_type.id_tarif_type = id_tarif_type
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

CREATE OR REPLACE FUNCTION getPrixHorairePourVol(input_nom_type VARCHAR, input_id_aeronef INT, input_date_vol DATE, input_id_tarif_type_vol INT) RETURNS RECORD AS $$
DECLARE
  v_id_tarif_type INT := NULL;
  v_parent_id INT;
  r RECORD;
  v_id_tarif_type_cond INT;
  r_tarif_type_cond RECORD;
BEGIN
  SELECT INTO r id_tarif_type, id_tarif_type_maitre FROM tarif_type WHERE nom_type = input_nom_type LIMIT 1;
  v_id_tarif_type := r.id_tarif_type;
  v_parent_id := r.id_tarif_type_maitre;
  --DEBUG RAISE NOTICE 'le tarif de base est: % (id: % parent: %)', input_nom_type, v_id_tarif_type, v_parent_id;
  WHILE true LOOP
    -- on charge id_tarif_type_cond
    r_tarif_type_cond := getTarifType(input_id_aeronef, v_id_tarif_type, input_date_vol, input_id_tarif_type_vol);
    IF r_tarif_type_cond.prix_heure IS NOT NULL THEN
      RETURN r_tarif_type_cond;
    END IF;
    --DEBUG RAISE NOTICE 'pas de tarif pour ce vol on charge le tarif parent';

    -- si on n'a pas trouvé, on charge le tarif parent
    IF v_parent_id IS NULL THEN -- si pas de tarif parent, on ne peut pas caluler le tarif du vol !
      SELECT * INTO r FROM aeronef WHERE id_aeronef = input_id_aeronef;
      RAISE WARNING 'pas de tarif pour nom_type=% id_aeronef=% [% - %] date_vol=%', input_nom_type, input_id_aeronef, r.immatriculation, r.nom_type, input_date_vol;
      RETURN NULL;
    END IF;

    -- on charge le tarif parent
    SELECT INTO r id_tarif_type, id_tarif_type_maitre, nom_type FROM tarif_type WHERE id_tarif_type = v_parent_id LIMIT 1;
    --DEBUG RAISE NOTICE '% chargé', r.nom_type;
    v_id_tarif_type := r.id_tarif_type;
    v_parent_id := r.id_tarif_type_maitre;
  END LOOP;
END;
$$ LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION calculPrixVol(input_id_pilote INT, input_id_aeronef INT, input_date_vol DATE, input_id_tarif_type_vol INT, temps_vol INTERVAL) RETURNS NUMERIC AS $$
DECLARE
  nom_type TEXT;
  temps_vol_consomme INTERVAL;
  r_pilote record;
  r_prix RECORD;
  r_tarif_type_cond record;
  r_tranche_item record;
  prix NUMERIC := 0;
  temps_vol_dans_item INTERVAL;
  tarif record;
BEGIN
  RAISE NOTICE 'calculPrixVol(%, %, %, %, %)', input_id_pilote, input_id_aeronef, input_date_vol, input_id_tarif_type_vol, temps_vol;
  -- on récupère la catégorie du pilote (-25 ans ou +25 ans)
  SELECT * INTO r_pilote FROM vfr_pilote WHERE id_personne = input_id_pilote LIMIT 1;
  --DEBUG RAISE NOTICE 'categorie: % %: %', r_pilote.nom, r_pilote.prenom, r_pilote.cat_age;
  IF r_pilote.cat_age = '-25 ans' THEN
    nom_type = 'Tarif général junior';
  ELSE
    nom_type = 'Tarif général';
  END IF;

  --DEBUG RAISE NOTICE 'id_pilote=% categorie: %', input_id_pilote, nom_type;

  RAISE NOTICE 'getPrixHorairePourVol(%, %, %, %)', nom_type, input_id_aeronef, input_date_vol, input_id_tarif_type_vol;
  SELECT * INTO r_prix FROM getPrixHorairePourVol(nom_type, input_id_aeronef, input_date_vol, input_id_tarif_type_vol) AS (id_tarif_tranche_vol INT, prix_heure NUMERIC);
  RAISE NOTICE 'prix heure de vol: id_tarif_tranche_vol=% prix_heure=%', r_prix.id_tarif_tranche_vol, r_prix.prix_heure;
  IF r_prix IS NULL THEN
    RETURN 0;
  END IF;

  IF r_prix.id_tarif_tranche_vol IS NULL THEN -- pas de pondération
    prix := ROUND(r_prix.prix_heure * EXTRACT(epoch FROM temps_vol)/3600, 2);
    RETURN prix;
  END IF;

  temps_vol_consomme := '0:0:0'::interval;
  FOR r_tranche_item IN SELECT * FROM tarif_tranche_item WHERE id_tarif_tranche = r_prix.id_tarif_tranche_vol
  LOOP
    IF temps_vol_consomme < temps_vol THEN -- si on doit encore facturer des heures de vol
      IF temps_vol > r_tranche_item.plafond THEN
        temps_vol_dans_item := r_tranche_item.plafond - temps_vol_consomme;
      ELSE
        temps_vol_dans_item := temps_vol - temps_vol_consomme;
      END IF;
      temps_vol_consomme := temps_vol_consomme + temps_vol_dans_item;
      RAISE NOTICE 'on est dans %', r_tranche_item;
      RAISE NOTICE 'prix pour % coef %: %', temps_vol_dans_item, r_tranche_item.coefficient, r_prix.prix_heure * r_tranche_item.coefficient * EXTRACT(epoch FROM temps_vol_dans_item)/3600;
      RAISE NOTICE 'reste à facturer: %', temps_vol - temps_vol_consomme;
      prix := prix + r_prix.prix_heure * r_tranche_item.coefficient * EXTRACT(epoch FROM temps_vol_dans_item)/3600;
    END IF;
  END LOOP;

  RAISE NOTICE 'calculPrixVol(%, %, %, %, %) = %', input_id_pilote, input_id_aeronef, input_date_vol, input_id_tarif_type_vol, temps_vol, prix;
  return ROUND(prix, 2);
END;
$$ LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION calculVolsSiHorsForfait(input_id_pilote INT, annee INT) RETURNS NUMERIC AS $$
DECLARE
  r_vol record;
  id_tarif_type_date NUMERIC;
  prix_vols NUMERIC := 0;
  prix_du_vol NUMERIC;
BEGIN
  -- prix_vol est à 0 lorsque le vol est gratuit et c'est saisi par les secrétaires (par exemple: casse cable, vol d'essai)
  FOR r_vol IN SELECT * FROM vfr_vol
    WHERE saison = annee and id_cdt_de_bord = input_id_pilote and prix_vol_cdb = 0 AND prix_vol IS NULL
  LOOP
    --RAISE NOTICE 'calcul du prix pour date=% pilote=[%] id_aeronef=% (%): temps_vol=%', r_vol.date_vol, r_vol.cdt_de_bord, r_vol.id_aeronef, r_vol.immatriculation, r_vol.temps_vol;
    prix_du_vol := calculPrixVol(r_vol.id_cdt_de_bord, r_vol.id_aeronef, r_vol.date_vol, r_vol.id_tarif_type_vol, r_vol.temps_vol);
    --RAISE NOTICE 'prix_du_vol: %', prix_du_vol;
    prix_vols := prix_vols + prix_du_vol;
  END LOOP;

  RETURN prix_vols;
END;
$$ LANGUAGE plpgsql VOLATILE;

-- calcule des stats sur les forfaits
--givav=> select * from statsForfait(2021);
-- cout_vol_sans_forfait: montant que le pilote aurait payé s'il n'avait pas souscrit le forfait
-- cout_horaire_vol_dans_forfait: on prend le montant du forfait que l'on divise par le nombre d'heure, ça donne le coût horaire des vols du pilote (grâce au forfait le pilote paye l'équivalent de xx € / h)
-- prix_cellule_sans_forfait: montant que le pilote a payé pour ces vols hors forfait (lorsque le pilote a volé sur des machines qui n'était pas dans son forfait), ça répond à la question est-ce que le pilote vole exclusivement sur les machines comprises dans le forfait ou pas
-- temps_vol_sans_forfait: idem que prix_cellule_sans_forfait mais au temps
--       nom_forfait        | montant_forfait | cout_vol_sans_forfait | conso_forfait | id_personne |           pilote           | cout_horaire_vol_dans_forfait | prix_cellule_sans_forfait | temps_vol_sans_forfait
----------------------------+-----------------+-----------------------+---------------+-------------+----------------------------+-------------------------------+---------------------------+------------------------
-- Forfait école campagne   |         1100.00 |               1446.91 | 80:53:00      |        xxxx | xxxxxxxxxxxxxxxxxxxxxxxx   |                         13.60 |                     79.08 |
-- Forfait école campagne   |         1100.00 |                422.62 | 34:13:30      |        xxxx | xxxxxxxxxxxxxxxxxxxxxxxx   |                         32.14 |                         0 |
-- Forfait école de base    |         1000.00 |                     0 | 10:14:00      |        xxxx | xxxxxxxxxxxxxxxxxxxxxxxx   |                         97.72 |                         0 |
-- Forfait école de base    |         1000.00 |                     0 | 32:47:00      |        xxxx | xxxxxxxxxxxxxxxxxxxxxxxx   |                         30.50 |                         0 |
-- Forfait école de base    |         1000.00 |                     0 | 16:12:00      |        xxxx | xxxxxxxxxxxxxxxxxxxxxxxx   |                         61.73 |                         0 |
-- Forfait école de base    |         1000.00 |                     0 | 11:14:00      |        xxxx | xxxxxxxxxxxxxxxxxxxxxxxx   |                         89.02 |                         0 |
-- Forfait Loisir  Campagne |          900.00 |                948.36 | 45:25:00      |        xxxx | xxxxxxxxxxxxxxxxxxxxxxxx   |                         19.82 |                         0 |
-- Forfait Loisir  Campagne |          900.00 |               2056.55 | 82:29:00      |        xxxx | xxxxxxxxxxxxxxxxxxxxxxxx   |                         10.91 |                    410.74 | 13:03:00
-- Forfait Loisir  Campagne |          900.00 |                385.52 | 20:39:00      |        xxxx | xxxxxxxxxxxxxxxxxxxxxxxx   |                         43.58 |                     12.90 |
-- Forfait Loisir  Campagne |          900.00 |                 36.92 | 69:09:30      |        xxxx | xxxxxxxxxxxxxxxxxxxxxxxx   |                         13.01 |                         0 |
-- Forfait Loisir  Campagne |          900.00 |                492.54 | 34:39:00      |        xxxx | xxxxxxxxxxxxxxxxxxxxxxxx   |                         25.97 |                    335.00 | 18:00:00
-- Forfait Loisirs          |          520.00 |               1093.06 | 88:17:30      |        xxxx | xxxxxxxxxxxxxxxxxxxxxxxx   |                          5.89 |                     71.40 |
-- Forfait Perfo            |         1300.00 |                111.87 | 28:35:00      |        xxxx | xxxxxxxxxxxxxxxxxxxxxxxx   |                         45.48 |                         0 |
-- Forfait Perfo            |         1300.00 |               3363.02 | 125:40:30     |        xxxx | xxxxxxxxxxxxxxxxxxxxxxxx   |                         10.34 |                         0 |
CREATE OR REPLACE FUNCTION statsForfait(input_annee int) returns table (
  nom_forfait varchar,
  montant_forfait numeric,
  cout_vol_sans_forfait NUMERIC, -- coût des vols si le membre n'avait pas pris le forfait
  conso_forfait interval,
  id_personne INT,
  pilote varchar,
  cout_horaire_vol_dans_forfait NUMERIC,
  prix_cellule_sans_forfait NUMERIC,
  temps_vol_sans_forfait INTERVAL -- en tant que cdb ou co pilote
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
    WHERE EXTRACT(YEAR FROM vfr_forfait_pilote.date_debut) = input_annee AND vfr_forfait_pilote.hrs_cellule = '999:00:00' ORDER BY vfr_forfait_pilote.nom_forfait
  LOOP
    id_personne := r_forfait.id_personne;
    nom_forfait := r_forfait.nom_forfait;
    montant_forfait := r_forfait.montant;
    conso_forfait := r_forfait.conso_hrs_cellule;
    cout_vol_sans_forfait := calculVolsSiHorsForfait(r_forfait.id_personne, input_annee);
    pilote := CONCAT(r_forfait.prenom, ' ', r_forfait.nom)::varchar;

    -- calcul du coût horaire des vols dans le forfait
    IF r_forfait.conso_hrs_cellule > '0:0:0'::interval THEN
      cout_horaire_vol_dans_forfait := ROUND(r_forfait.montant / (EXTRACT(epoch FROM r_forfait.conso_hrs_cellule)/3600), 2);
    END IF;

    -- calcul du nombre de vol et du nombre d'heure réalisées hors forfait (pour voir si le pilote vole sur des machines hors forfait)
    SELECT INTO r_vol SUM(COALESCE(prix_vol_cdb, 0)) AS prix, SUM(temps_vol) AS duree FROM vfr_vol
      WHERE vfr_vol.id_cdt_de_bord = r_forfait.id_personne AND vfr_vol.prix_vol_cdb > 0 AND saison = input_annee;
    prix_cellule_sans_forfait := COALESCE(r_vol.prix, 0);
    temps_vol_sans_forfait := r_vol.duree;
    SELECT INTO r_vol SUM(COALESCE(prix_vol_co, 0)) AS prix, SUM(temps_vol) AS duree FROM vfr_vol
      WHERE r_forfait.id_personne = id_co_pilote AND vfr_vol.prix_vol_co > 0 AND saison = input_annee;
    prix_cellule_sans_forfait := prix_cellule_sans_forfait + COALESCE(r_vol.prix, 0);
    temps_vol_sans_forfait := temps_vol_sans_forfait + r_vol.duree;

    RETURN next;
  END LOOP;
END;
$$ LANGUAGE plpgsql VOLATILE;


CREATE OR REPLACE FUNCTION statsMembre(annee INT) returns table (
  nom varchar,
  stats jsonb
  ) AS
$$
DECLARE
  sub_json jsonb;
  r RECORD;
  r2 RECORD;
  somme_tous_les_vols INTERVAL;
  r_type_vol RECORD;
  cout_vol_si_machine_club NUMERIC := 0;
  loyer NUMERIC;
  montant_vol NUMERIC;
  prix_du_vol NUMERIC;
  a_deduire NUMERIC;
  nb_vols NUMERIC;
  temps_vols INTERVAL;
  machines JSONB[];
  nb_vol INT;
BEGIN
  FOR r IN SELECT pi.id_pilote, pe.id_personne, pe.nom, pe.prenom, pi.cat_age, pi.id_compte, pi.licence_saison, pi.licence_nom, pi.solde
    FROM vfr_pilote pi
    JOIN gv_personne pe ON pe.id_personne = pi.id_personne
    WHERE pilote_actif_3 IS TRUE
    --DEBUG AND pi.id_pilote = 811
    ORDER BY pe.nom -- TODO randomize
    LOOP
    SELECT COUNT(*) INTO nb_vol
      FROM vfr_vol
      WHERE EXTRACT(YEAR FROM date_vol) = annee
        AND (id_cdt_de_bord = r.id_personne OR id_co_pilote = r.id_personne OR id_eleve = r.id_personne);
    IF nb_vol = 0 THEN
      CONTINUE;
    END IF;

    stats := '{}';
    -- TODO anonymisation
    nom := r.nom;
    IF r.prenom IS NOT NULL THEN
      nom := r.nom || ' ' || r.prenom;
    END IF;
    --DEBUG RAISE NOTICE '%', nom;
    stats := setVarInData(stats, 'solde', r.solde);
    stats := setVarInData(stats, 'id_compte', r.id_compte);
    -- pour les privés qui pratiquent la rétrocession, comme c'est un jeu à somme nulle, on la sort
    -- des montants facturés
    -- 1/ sortir les vols où c'est le propriétaire qui vole sur sa machine -> ça donne le montant perçu par la location de la machine par les propriétaires
    -- 2/ isoler les vols où c'est le propriétaire qui vole sur sa machine -> ça donne le montant qu'aurait payé le propriétaire pour voler sur la machine si la machine était club
    -- pour chaque vol on va voir si le pilote est propriétaire de la machine
    cout_vol_si_machine_club := 0;
    loyer := 0;
    a_deduire := 0; -- le pilote a des retrocessions vers son propre compte
    -- c'est un jeu à somme nulle, donc on doit déduire ces montants
    FOR r2 IN SELECT
        tv.nom_type_vol,
        cp_piece_ligne.montant,
        pvc.id_personne AS id_cdt_de_bord,
        piloteEstProprietaireDeMachine(pvc.id_personne, vol.id_aeronef, vol_pilote.date_vol) AS cdt_est_proprietaire,
        piloteProprietaireDeMachinePourcentageRetribution(pvc.id_personne, vol.id_aeronef, vol_pilote.date_vol) AS cdt_pourcentage,
        pvo.id_personne AS id_co,
        piloteEstProprietaireDeMachine(pvo.id_personne, vol.id_aeronef, vol_pilote.date_vol) AS co_est_proprietaire,
        piloteProprietaireDeMachinePourcentageRetribution(pvo.id_personne, vol.id_aeronef, vol_pilote.date_vol) AS co_pourcentage,
	vol.id_aeronef, vol_pilote.date_vol
        FROM cp_piece
        JOIN cp_piece_ligne ON cp_piece_ligne.id_piece = cp_piece.id_piece
        JOIN vol_pilote ON vol_pilote.id_vol_pilote = cp_piece.id_vol_pilote
        JOIN vol ON vol.id_vol = vol_pilote.id_vol
        JOIN tarif_type_vol tv ON vol.id_tarif_type_vol = tv.id_tarif_type_vol
        LEFT JOIN vol_pilote pvc ON vol_pilote.id_vol = pvc.id_vol AND pvc.fonction = 1
        LEFT JOIN vol_pilote pvo ON vol_pilote.id_vol = pvo.id_vol AND pvo.fonction = 2
        WHERE cp_piece_ligne.id_compte = r.id_compte AND sens = 'C'
        AND EXTRACT(YEAR FROM cp_piece_ligne.date_piece) = annee AND cp_piece.type = 'RETRO_CELLULE'
    LOOP
      --DEBUG RAISE NOTICE '%', r2;
      IF r2.cdt_est_proprietaire IS TRUE OR r2.co_est_proprietaire IS TRUE THEN
        a_deduire := a_deduire + r2.montant;
        -- le pilote est propriaitaire donc on calcule le coût de ces vols
        IF r2.cdt_est_proprietaire THEN
          prix_du_vol := (100 * r2.montant) / r2.cdt_pourcentage;
          --DEBUG RAISE NOTICE 'proprio cdb: % montant=% pourcentage=% prix_vol: %', r2, r2.montant, r2.cdt_pourcentage, prix_du_vol;
        ELSE
          prix_du_vol := (100 * r2.montant) / r2.co_pourcentage;
          --DEBUG RAISE NOTICE 'proprio co: % montant=% pourcentage=% prix_vol: %', r2, r2.montant, r2.co_pourcentage, prix_du_vol;
        END IF;
        IF r2.nom_type_vol = '3 Vol partagé' THEN
          --DEBUG RAISE NOTICE 'vol partagé donc prix_du_vol / 2';
          prix_du_vol := prix_du_vol / 2;
        END IF;
        cout_vol_si_machine_club := cout_vol_si_machine_club + ROUND(prix_du_vol, 2);

      ELSE -- le pilote n'est pas propriétaire donc c'est un loyer
        loyer := loyer + r2.montant;
      END IF;
    END LOOP;
    loyer := ROUND(loyer, 2);
    IF loyer > 0 THEN
      -- les pilotes non-propriétaires qui volent sur des machines proprio ont payés au proprio
      stats := setVarInData(stats, 'loyer', loyer);
    END IF;

    -- pour ceux qui n'ont pas de rétrocession il faut calculer le prix de leur vol comme si leurs machines
    -- étaient club
    FOR r2 IN SELECT * FROM vfr_vol
      WHERE saison = annee
      AND id_cdt_de_bord = r.id_personne
      AND prix_vol_cdb = 0
      AND prix_vol IS NULL
      AND piloteEstProprietaireDeMachine(id_cdt_de_bord, id_aeronef, date_vol) IS TRUE
    LOOP
      --DEBUG RAISE NOTICE 'calcul du prix pour date=% pilote=[%] id_aeronef=% (%): temps_vol=%', r2.date_vol, r2.cdt_de_bord, r2.id_aeronef, r2.immatriculation, r2.temps_vol;
      prix_du_vol := calculPrixVol(r.id_pilote, r2.id_aeronef, r2.date_vol, r2.id_tarif_type_vol, r2.temps_vol);
      --DEBUG RAISE NOTICE 'prix: %', prix_du_vol;
      cout_vol_si_machine_club := cout_vol_si_machine_club + prix_du_vol;
    END LOOP;


    cout_vol_si_machine_club := ROUND(cout_vol_si_machine_club, 2);
    IF cout_vol_si_machine_club > 0 THEN
      -- combien le propriétaire payerait si sa machine appartenait au club
      stats := setVarInData(stats, 'cout_vol_si_machine_club', cout_vol_si_machine_club);
    END IF;
    -- on sort:
    -- RE5_TIERS les remboursements de frais
    -- TRCLU les transferts entre comptes
    SELECT INTO r2 COALESCE(SUM(montant), 0) AS montant FROM cp_piece
      JOIN cp_piece_ligne ON cp_piece_ligne.id_piece = cp_piece.id_piece
      WHERE id_compte = r.id_compte AND sens = 'D' AND EXTRACT(YEAR FROM cp_piece_ligne.date_piece) = annee
      AND type NOT IN ('RE5_TIERS', 'TRCLU');
    stats := setVarInData(stats, 'debit', r2.montant - a_deduire);

    SELECT INTO r2 COALESCE(SUM(montant), 0) AS montant FROM cp_piece
      JOIN cp_piece_ligne ON cp_piece_ligne.id_piece = cp_piece.id_piece
      WHERE id_compte = r.id_compte AND sens = 'C' AND EXTRACT(YEAR FROM cp_piece_ligne.date_piece) = annee
      AND type NOT IN ('RE5_TIERS', 'TRCLU');
    stats := setVarInData(stats, 'crédit', r2.montant - a_deduire);

    nb_vols := 0;
    temps_vols := 0;
    FOR r_type_vol IN SELECT vfr_vol.nom_type_vol
      FROM vfr_vol
      WHERE EXTRACT(YEAR FROM date_vol) = annee
      AND (id_cdt_de_bord = r.id_personne OR id_co_pilote = r.id_personne OR id_eleve = r.id_personne)
      GROUP BY vfr_vol.nom_type_vol
    LOOP
      sub_json := '{}';
      -- heures de vols
      SELECT INTO r2 COUNT(*) AS nombre, SUM(temps_vol) AS duree
        FROM vfr_vol
        WHERE EXTRACT(YEAR FROM date_vol) = annee
        AND (id_cdt_de_bord = r.id_personne OR id_co_pilote = r.id_personne OR id_eleve = r.id_personne)
        AND nom_type_vol = r_type_vol.nom_type_vol;

      sub_json := setVarInData(sub_json, 'nb_vol', r2.nombre);
      sub_json := setVarInData(sub_json, 'duree_vol', r2.duree);
      stats := setVarInData(stats, r_type_vol.nom_type_vol, sub_json);
      nb_vols := nb_vols + r2.nombre;
      temps_vols := temps_vols + r2.duree;
    END LOOP;
    stats := setVarInData(stats, 'nb_vol', nb_vols);
    stats := setVarInData(stats, 'duree_vol', temps_vols);

    -- nombre de remorqués
      SELECT INTO r2 COUNT(*) AS nombre
        FROM vfr_vol
        WHERE EXTRACT(YEAR FROM date_vol) = annee
        AND (id_cdt_de_bord = r.id_personne OR id_co_pilote = r.id_personne OR id_eleve = r.id_personne)
        AND mode_decollage = 'R';
      IF r2.nombre > 0 THEN
        stats := setVarInData(stats, 'nb_remorques', r2.nombre);
      END IF;

    -- nombre de treuillées
      SELECT INTO r2 COUNT(*) AS nombre
        FROM vfr_vol
        WHERE EXTRACT(YEAR FROM date_vol) = annee
        AND (id_cdt_de_bord = r.id_personne OR id_co_pilote = r.id_personne OR id_eleve = r.id_personne)
        AND mode_decollage = 'T';
      IF r2.nombre > 0 THEN
        stats := setVarInData(stats, 'nb_treuillees', r2.nombre);
      END IF;

    -- nombre autonome
      SELECT INTO r2 COUNT(*) AS nombre
        FROM vfr_vol
        WHERE EXTRACT(YEAR FROM date_vol) = annee
        AND (id_cdt_de_bord = r.id_personne OR id_co_pilote = r.id_personne OR id_eleve = r.id_personne)
        AND mode_decollage = 'M';
      IF r2.nombre > 0 THEN
        stats := setVarInData(stats, 'nb_autonome', r2.nombre);
      END IF;

    -- sur quelles machines ce pilote vole-t'il le plus
    -- on fait la somme des temps de vol sur toutes les machines dans somme_tous_les_vols
    SELECT SUM(temps_vol) INTO somme_tous_les_vols
      FROM vfr_vol
      WHERE EXTRACT(YEAR FROM date_vol) = annee
        AND (id_cdt_de_bord = r.id_personne OR id_co_pilote = r.id_personne OR id_eleve = r.id_personne);
    machines := '{}';
    FOR r2 IN SELECT immatriculation, SUM(temps_vol) AS temps_vol
      FROM vfr_vol
      WHERE EXTRACT(YEAR FROM date_vol) = annee
        AND (id_cdt_de_bord = r.id_personne OR id_co_pilote = r.id_personne OR id_eleve = r.id_personne)
        GROUP BY immatriculation
        ORDER BY temps_vol DESC
    LOOP
      sub_json := '{}';
      sub_json := setVarInData(sub_json, 'immatriculation', r2.immatriculation);
      sub_json := setVarInData(sub_json, 'temps_vol', r2.temps_vol);
      sub_json := setVarInData(sub_json, 'percent', ROUND((EXTRACT(EPOCH FROM r2.temps_vol) / EXTRACT(EPOCH FROM somme_tous_les_vols)) * 100, 0));
      machines := array_append(machines, sub_json);
    END LOOP;
    stats := setVarInData(stats, 'machines', machines);

    RETURN next;
  END LOOP;
END;
$$ LANGUAGE plpgsql VOLATILE;

-- on retourne le montant des frais de hangar par machine
-- comme il n'y a pas de relation entre le compte pilote et la machine
-- - on compte le montant total des frais de hangar (au total)
-- - on compte combien le propriétaire a de machine
-- et on fait (montant total des frais de hangar) / (nombre de machine)
CREATE OR REPLACE FUNCTION getFraisHangarParMachine(input_id_aeronef INT, input_annee NUMERIC) RETURNS NUMERIC AS $$
DECLARE
  r RECORD;
  r2 RECORD;
  r_machine RECORD;
  last_id_aeronef_situation NUMERIC;
  v_id_aeronef_sitation NUMERIC;
  montant_frais_hangar NUMERIC;
  nb_machine NUMERIC;
  beneficiares_personnes INT[];
  v_id_personne INT;
  immatriculations TEXT[];
  v_situation TEXT;
BEGIN
  -- F-CCER n'est pas dans le hangar hors son propriétaire a un autre planeur
  -- qui lui est dans le hangar, ça fausse tout
  SELECT INTO r_machine * FROM aeronef WHERE id_aeronef = input_id_aeronef;
  IF r_machine.immatriculation = 'F-CCER' THEN
    RETURN 0;
  END IF;
  montant_frais_hangar := 0;
  FOR r IN SELECT * FROM aeronef_situation
    WHERE aeronef_situation.id_aeronef = input_id_aeronef ORDER BY date_application ASC
  LOOP
    IF input_annee >= EXTRACT(YEAR FROM r.date_application) THEN
      v_id_aeronef_sitation = r.id_aeronef_situation;
    END IF;
  END LOOP;
  IF v_id_aeronef_sitation IS NULL THEN
    RETURN 0;
  END IF;

  FOR r IN SELECT aeronef.immatriculation, gv_personne.nom, gv_personne.id_personne FROM gv_personne
    JOIN aeronef_situation_benef ON aeronef_situation_benef.id_personne = gv_personne.id_personne
    JOIN aeronef_situation ON aeronef_situation.id_aeronef_situation = aeronef_situation_benef.id_aeronef_situation
    JOIN aeronef ON aeronef.id_aeronef = input_id_aeronef
    WHERE aeronef_situation.id_aeronef = input_id_aeronef
    AND CONCAT(input_annee, '-01-01')::date >= aeronef_situation.date_application
    ORDER BY aeronef_situation.date_application ASC LIMIT 1
  LOOP
    RAISE NOTICE 'bénéficiaires de %: % (id_personne=%)', r.immatriculation, r.nom, r.id_personne;
  END LOOP;
  
  -- on cherche tous les propriétaires de cette machine
  FOR r IN SELECT * FROM aeronef_situation_benef
    JOIN gv_personne ON gv_personne.id_personne = aeronef_situation_benef.id_personne
    LEFT JOIN club ON club.id_personne = gv_personne.id_personne
    WHERE aeronef_situation_benef.id_aeronef_situation = v_id_aeronef_sitation
  LOOP
    RAISE NOTICE 'annee=% id_personne=% id_club=%', input_annee, r.id_personne, r.id_club;
    beneficiares_personnes := array_append(beneficiares_personnes, r.id_personne);
    IF r.id_club IS NOT NULL THEN
      -- c'est une personne morale
      SELECT INTO r2 COALESCE(SUM(montant), 0) AS montant FROM cp_piece_ligne li
        JOIN cp_piece pi ON pi.id_piece = li.id_piece
        WHERE (pi.type = 'FVTE' OR (pi.type = 'ODBR+' AND libelle LIKE 'frais hangar%'))
          AND li.id_compte = r.id_compte
          AND EXTRACT(YEAR FROM li.date_piece) = input_annee;
      RAISE NOTICE 'frais hangar %', r2;
      montant_frais_hangar := montant_frais_hangar + r2.montant;
    ELSE
      -- c'est un pilote
      SELECT INTO r2 COALESCE(SUM(montant), 0) AS montant FROM cp_piece_ligne li
        JOIN cp_piece pi ON pi.id_piece = li.id_piece
        JOIN pilote ON pilote.id_compte = li.id_compte
        WHERE (pi.type = 'FVTE' OR (pi.type = 'ODBR+' AND libelle LIKE 'frais hangar%'))
          AND EXTRACT(YEAR FROM li.date_piece) = input_annee
          AND pilote.id_personne = r.id_personne;
      --DEBUG RAISE NOTICE 'frais hangar %', r2;
      montant_frais_hangar := montant_frais_hangar + r2.montant;
    END IF;
  END LOOP;

  IF beneficiares_personnes IS NULL THEN
    RAISE NOTICE 'pas de bénéficiaire enregistré';
    RETURN 0;
  END IF;

  -- on cherche le nombre de machine dont sont bénéficiaires les personnes
  FOREACH v_id_personne IN ARRAY beneficiares_personnes LOOP
    RAISE NOTICE 'on cherche les machines dont est bénéficiare id_personne=%', v_id_personne;
    FOR r IN SELECT aeronef_situation.id_aeronef_situation, aeronef_situation.date_application, aeronef.immatriculation FROM aeronef
      JOIN aeronef_situation ON aeronef_situation.id_aeronef = aeronef.id_aeronef
      JOIN aeronef_situation_benef ON aeronef_situation_benef.id_aeronef_situation = aeronef_situation.id_aeronef_situation
      WHERE CONCAT(input_annee, '-01-01')::date >= aeronef_situation.date_application
        AND aeronef_situation_benef.id_personne = v_id_personne
        AND aeronef_situation.situation IN ('B', 'P', 'E') -- F-CVOL est Extérieure alors que ça devrait être Privée
        AND aeronef.actif IS TRUE
        AND aeronef.immatriculation NOT IN ('F-CCER') -- F-CCER n'est pas dans le hangar ni en remorque
      ORDER BY aeronef_situation.date_application DESC LOOP
      RAISE NOTICE 'id_personne=% est potentiellement bénéficiaire de % a la date % (debut situation %) %', v_id_personne, r.immatriculation, input_annee, r.date_application, r;
      -- on vérifie que cette situation est la situation du moment (input_annee)
      -- s'il y a une situation plus récente, on ne traite pas la situation que l'on vient de trouver
      SELECT INTO r2 aeronef_situation.* FROM aeronef
      JOIN aeronef_situation ON aeronef_situation.id_aeronef = aeronef.id_aeronef
      JOIN aeronef_situation_benef ON aeronef_situation_benef.id_aeronef_situation = aeronef_situation.id_aeronef_situation
      WHERE aeronef_situation.date_application <= CONCAT(input_annee, '-01-01')::date AND aeronef_situation.date_application >= r.date_application
        AND aeronef_situation_benef.id_personne != v_id_personne
        AND aeronef.actif IS TRUE
        AND aeronef.immatriculation = r.immatriculation
        AND aeronef_situation.id_aeronef_situation != r.id_aeronef_situation
        LIMIT 1;
      -- il est possible que la machine ai été banalisée mais est maintenant club donc on ne filtre pas sur la situation B, P ou E
      -- DEBUG RAISE NOTICE 'trouve: %', r2;
      IF NOT FOUND THEN -- c'est ok, on a bien pris la bonne situation
        RAISE NOTICE 'id_personne=% est bénéficiaire de %', v_id_personne, r.immatriculation;
        immatriculations := array_append(immatriculations, r.immatriculation);
      END IF;
    END LOOP;
  END LOOP;
  RAISE NOTICE 'finalement id_personne=% est beneficiaire de immatriculations: %', v_id_personne, immatriculations;
  -- unique
  SELECT INTO immatriculations ARRAY(SELECT DISTINCT v FROM UNNEST(immatriculations) as b(v));
  RAISE NOTICE 'after unique: %', immatriculations;
  -- nombre de machine
  nb_machine := array_length(immatriculations, 1);

  RAISE NOTICE 'total frais hangar: % nb_machine=%', montant_frais_hangar, nb_machine;
  RETURN ROUND(montant_frais_hangar / nb_machine, 2);
END;
$$ LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION getProprietaireMachine(input_id_aeronef INT, input_date DATE) RETURNS TEXT[] AS $$
DECLARE
  r RECORD;
  r2 RECORD;
  r3 RECORD;
  last_id_aeronef_situation NUMERIC;
  v_id_aeronef_sitation NUMERIC;
  proprietaires TEXT[];
BEGIN
  FOR r IN SELECT * FROM aeronef_situation
    WHERE aeronef_situation.id_aeronef = input_id_aeronef
  LOOP
    IF input_date >= r.date_application THEN
      v_id_aeronef_sitation = r.id_aeronef_situation;
    END IF;
  END LOOP;
  IF v_id_aeronef_sitation IS NULL THEN
    RETURN '{}';
  END IF;
  FOR r2 IN SELECT * FROM aeronef_situation_benef WHERE id_aeronef_situation = r.id_aeronef_situation LOOP
    IF r2.id_personne = 235 THEN -- EDF-GDF (ANEG)
      FOR r3 IN SELECT * FROM vfr_pilote WHERE tarif LIKE '%EGF%' AND vfr_pilote.pilote_actif IS true LOOP
        proprietaires := array_append(proprietaires, CONCAT(r3.nom, ' ', r3.prenom));
      END LOOP;
    END IF;
    IF r2.id_personne = 246 THEN -- CORMEILLES
      FOR r3 IN SELECT * FROM vfr_pilote WHERE tarif LIKE '%CORMEILLES%' AND vfr_pilote.pilote_actif IS true LOOP
        proprietaires := array_append(proprietaires, CONCAT(r3.nom, ' ', r3.prenom));
      END LOOP;
    END IF;
    FOR r3 IN SELECT * FROM aeronef_situation_benef
      JOIN vfr_pilote ON vfr_pilote.id_personne = aeronef_situation_benef.id_personne
      WHERE aeronef_situation_benef.id_aeronef_situation = v_id_aeronef_sitation LOOP
        proprietaires := array_append(proprietaires, CONCAT(r3.nom, ' ', r3.prenom));
    END LOOP;
  END LOOP;
  RETURN array_distinct(proprietaires);
END;
$$ LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION getRefactuDecollagesAutonome(input_id_aeronef INT, input_annee NUMERIC) RETURNS NUMERIC AS $$
DECLARE
  r RECORD;
  r2 RECORD;
  last_id_aeronef_situation NUMERIC;
  v_id_aeronef_sitation NUMERIC;
  montant_decollage NUMERIC := 0;
BEGIN
  FOR r IN SELECT * FROM aeronef_situation
    WHERE aeronef_situation.id_aeronef = input_id_aeronef ORDER BY date_application ASC
  LOOP
    IF input_annee >= EXTRACT(YEAR FROM r.date_application) THEN
      v_id_aeronef_sitation = r.id_aeronef_situation;
    END IF;
  END LOOP;
  IF v_id_aeronef_sitation IS NULL THEN
    RETURN 0;
  END IF;

  FOR r IN SELECT aeronef.immatriculation, gv_personne.nom, gv_personne.id_personne FROM gv_personne
    JOIN aeronef_situation_benef ON aeronef_situation_benef.id_personne = gv_personne.id_personne
    JOIN aeronef_situation ON aeronef_situation.id_aeronef_situation = aeronef_situation_benef.id_aeronef_situation
    JOIN aeronef ON aeronef.id_aeronef = input_id_aeronef
    WHERE aeronef_situation.id_aeronef = input_id_aeronef
    AND CONCAT(input_annee, '-01-01')::date >= aeronef_situation.date_application
    ORDER BY aeronef_situation.date_application ASC LIMIT 1
  LOOP
    RAISE NOTICE 'bénéficiaires de %: % (id_personne=%)', r.immatriculation, r.nom, r.id_personne;
  END LOOP;

  -- on cherche tous les propriétaires de cette machine et ce qui est facturé en tant
  -- que décollage autonome
  FOR r IN SELECT * FROM aeronef_situation_benef
    JOIN gv_personne ON gv_personne.id_personne = aeronef_situation_benef.id_personne
    LEFT JOIN club ON club.id_personne = gv_personne.id_personne
    WHERE aeronef_situation_benef.id_aeronef_situation = v_id_aeronef_sitation
  LOOP
    RAISE NOTICE 'annee=% id_personne=% id_club=%', input_annee, r.id_personne, r.id_club;
    IF r.id_club IS NOT NULL THEN
      -- c'est une personne morale
      SELECT INTO r2 COALESCE(SUM(montant), 0) AS montant FROM cp_piece_ligne li
        JOIN cp_piece pi ON pi.id_piece = li.id_piece
        WHERE pi.type = 'ODBR+' AND libelle LIKE 'Décollages Autonomes LFFC%'
          AND li.id_compte = r.id_compte
          AND EXTRACT(YEAR FROM li.date_piece) = input_annee;
      RAISE NOTICE 'montant décollages %', r2;
      montant_decollage := montant_decollage + r2.montant;
    ELSE
      -- c'est un pilote
      SELECT INTO r2 COALESCE(SUM(montant), 0) AS montant FROM cp_piece_ligne li
        JOIN cp_piece pi ON pi.id_piece = li.id_piece
        JOIN pilote ON pilote.id_compte = li.id_compte
        WHERE pi.type = 'ODBR+' AND libelle LIKE 'Décollages Autonomes LFFC%'
          AND EXTRACT(YEAR FROM li.date_piece) = input_annee
          AND pilote.id_personne = r.id_personne;
      RAISE NOTICE 'montant décollages %', r2;
      montant_decollage := montant_decollage + r2.montant;
    END IF;
  END LOOP;

  RAISE NOTICE 'total montant décollages: %', montant_decollage;
  RETURN ROUND(montant_decollage, 2);
END;
$$ LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION piloteEstProprietaireDeMachine(input_id_personne INT, input_id_aeronef INT, input_date_vol DATE) RETURNS BOOLEAN AS $$
DECLARE
  r RECORD;
  r2 RECORD;
  r3 RECORD;
  last_id_aeronef_situation NUMERIC;
  v_id_aeronef_sitation NUMERIC;
BEGIN
  FOR r IN SELECT * FROM aeronef_situation
    WHERE aeronef_situation.id_aeronef = input_id_aeronef
  LOOP
    IF input_date_vol >= r.date_application THEN
      v_id_aeronef_sitation = r.id_aeronef_situation;
    END IF;
  END LOOP;
  IF v_id_aeronef_sitation IS NULL THEN
    RETURN false;
  END IF;
  FOR r2 IN SELECT * FROM aeronef_situation_benef WHERE id_aeronef_situation = r.id_aeronef_situation LOOP
    IF r2.id_personne = 235 THEN -- EDF-GDF (ANEG)
      SELECT INTO r3 * FROM vfr_pilote WHERE tarif LIKE '%EGF%' AND vfr_pilote.id_personne = input_id_personne;
      IF FOUND THEN
        RETURN TRUE;
      END IF;
    END IF;
    IF r2.id_personne = 246 THEN -- CORMEILLES
      SELECT INTO r3 * FROM vfr_pilote WHERE tarif LIKE '%CORMEILLES%' AND vfr_pilote.id_personne = input_id_personne;
      IF FOUND THEN
        RETURN TRUE;
      END IF;
    END IF;
  END LOOP;
  SELECT INTO r * FROM aeronef_situation_benef
    WHERE aeronef_situation_benef.id_aeronef_situation = v_id_aeronef_sitation
    AND aeronef_situation_benef.id_personne = input_id_personne;
  --DEBUG RAISE NOTICE 'id_aeronef_situation: %: %', v_id_aeronef_sitation, r;
  IF FOUND THEN
    RETURN TRUE;
  END IF;
  RETURN FALSE;
END;
$$ LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION piloteProprietaireDeMachinePourcentageRetribution(input_id_personne INT, input_id_aeronef INT, input_date_vol DATE) RETURNS NUMERIC AS $$
DECLARE
  r RECORD;
  last_id_aeronef_situation NUMERIC;
  v_id_aeronef_sitation NUMERIC;
BEGIN
  FOR r IN SELECT * FROM aeronef_situation
    WHERE aeronef_situation.id_aeronef = input_id_aeronef
  LOOP
    IF input_date_vol >= r.date_application THEN
      v_id_aeronef_sitation = r.id_aeronef_situation;
    END IF;
  END LOOP;
  IF v_id_aeronef_sitation IS NULL THEN
    RETURN 0;
  END IF;
  SELECT INTO r * FROM aeronef_situation_benef
    WHERE aeronef_situation_benef.id_aeronef_situation = v_id_aeronef_sitation
    AND aeronef_situation_benef.id_personne = input_id_personne;
  --DEBUG RAISE NOTICE 'id_aeronef_situation: %: %', v_id_aeronef_sitation, r;
  IF FOUND THEN
    RETURN r.pourcentage;
  END IF;
  RETURN 0;
END;
$$ LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION tableauDeBord_licence(annee INT, pas_apres_cette_date DATE) RETURNS JSONB AS $$
DECLARE
  r RECORD;
  stats JSONB;
BEGIN
  stats := '{}';
  FOR r IN SELECT licence_nom, COUNT(*) AS nb FROM pilote
    JOIN cp_piece_ligne li ON li.id_compte = pilote.id_compte
    JOIN cp_piece pi ON pi.id_piece = li.id_piece
    WHERE type = 'LICENCE_FFVP' AND EXTRACT(YEAR FROM li.date_piece) = annee AND li.date_piece <= pas_apres_cette_date
    GROUP BY licence_nom
  LOOP
    stats := setVarInData(stats, r.licence_nom, r.nb);
  END LOOP;
  RETURN stats;
END;
$$ LANGUAGE plpgsql VOLATILE;

-- on ne prend pas en compte les décollages autonomes, car ce qui nous intéresse pour les tableaux de bord
-- ce sont les rentrées d'argent vu du club
CREATE OR REPLACE FUNCTION tableauDeBord_mise_en_l_air(annee INT, pas_apres_cette_date DATE) RETURNS JSONB AS $$
DECLARE
  r_vol RECORD;
  stats JSONB;
  mise_en_l_air TEXT;
  mises_en_l_air TEXT[] := '{"R", "T"}';
BEGIN
  stats := '{}';
  FOREACH mise_en_l_air IN ARRAY mises_en_l_air
  LOOP
    SELECT INTO r_vol COUNT(*) AS nb_vol FROM vfr_vol WHERE saison = annee AND date_vol <= pas_apres_cette_date AND mode_decollage = mise_en_l_air;
    stats := setVarInData(stats, mise_en_l_air, r_vol.nb_vol);
  END LOOP;

  RETURN stats;
END;
$$ LANGUAGE plpgsql VOLATILE;

-- on calcule les heures de vol club et banalisé
-- on ne prend que les heures "vol solo" et "vol partagé" pour les CDB
CREATE OR REPLACE FUNCTION tableauDeBord_hdv(annee INT, pas_apres_cette_date DATE, inclure_banalise BOOLEAN) RETURNS JSONB AS $$
DECLARE
  r_vol RECORD;
  stats JSONB;
  situations TEXT[] := '{"C"}';
BEGIN
  IF inclure_banalise IS true THEN
    situations := '{"C", "B"}';
  END IF;
  stats := '{}';

  SELECT INTO r_vol SUM(temps_vol) AS duree FROM vfr_vol WHERE saison = annee AND date_vol <= pas_apres_cette_date AND situation = ANY(situations) AND nom_type_vol IN ('1 Vol en solo', '3 Vol partagé');
  stats := setVarInData(stats, 'cdb', ROUND(EXTRACT(epoch FROM r_vol.duree)/3600));

  SELECT INTO r_vol SUM(temps_vol) AS duree FROM vfr_vol WHERE saison = annee AND date_vol <= pas_apres_cette_date AND situation = ANY(situations) AND nom_type_vol = '2 Vol d''instruction';
  stats := setVarInData(stats, 'instruction', ROUND(EXTRACT(epoch FROM r_vol.duree)/3600));
  stats := setVarInData(stats, 'total', (stats->>'cbd')::numeric + (stats->>'instruction')::numeric);

  RETURN stats;
END;
$$ LANGUAGE plpgsql VOLATILE;

-- on calcule le nombre de vol vi club
CREATE OR REPLACE FUNCTION tableauDeBord_vi_club(annee INT, pas_apres_cette_date DATE) RETURNS JSONB AS $$
DECLARE
  r_vol RECORD;
  stats JSONB;
BEGIN
  stats := '{}';

  SELECT INTO r_vol COUNT(*) AS nb_vol FROM vfr_vol WHERE saison = annee AND date_vol <= pas_apres_cette_date AND nom_type_vol IN ('40 VI club', '42 VI Ca plane pour Elles', '43 VI Intercommune');
  stats := setVarInData(stats, 'nb_vi', r_vol.nb_vol);

  RETURN stats;
END;
$$ LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION tableauDeBord() RETURNS JSONB AS $$
DECLARE
  stats JSONB;
  r RECORD;
  r2 RECORD;
  r_vol RECORD;
  sub_json JSONB;
  mise_en_l_air TEXT;
  mises_en_l_air TEXT[] := '{"R", "T", "M"}';
  pas_apres_cette_date_cette_annee DATE;
  pas_apres_cette_date_annee_derniere DATE;
  pas_apres_cette_date_annee_derniere_complete DATE;
  cette_annee INT;
  annee_derniere INT;
BEGIN
  stats := '{}';

  -- on regarde la date du dernier vol enregistré
  SELECT INTO r MAX(date_vol) AS date_vol FROM vfr_vol WHERE saison = EXTRACT(YEAR FROM NOW());
  IF r.date_vol IS NULL THEN
    SELECT INTO r MAX(date_vol) AS date_vol FROM vfr_vol WHERE saison = EXTRACT(YEAR FROM NOW() - INTERVAL '1 year');
  END IF;
  pas_apres_cette_date_cette_annee := r.date_vol;
  pas_apres_cette_date_annee_derniere := r.date_vol - INTERVAL '1 year';
  cette_annee := EXTRACT(YEAR FROM NOW());
  annee_derniere := EXTRACT(YEAR FROM pas_apres_cette_date_annee_derniere);
  RAISE NOTICE 'cette_annee: % annee_derniere: %', cette_annee, annee_derniere;
  pas_apres_cette_date_annee_derniere_complete := CONCAT(annee_derniere, '-12-31');
  RAISE NOTICE 'pas_apres_cette_date_cette_annee: % pas_apres_cette_date_annee_derniere: % pas_apres_cette_date_annee_derniere_complete: %', pas_apres_cette_date_cette_annee, pas_apres_cette_date_annee_derniere, pas_apres_cette_date_annee_derniere_complete;
  sub_json := '{}';
  sub_json := setVarInData(sub_json, 'pas_apres_cette_date_cette_annee', pas_apres_cette_date_cette_annee);
  sub_json := setVarInData(sub_json, 'pas_apres_cette_date_annee_derniere', pas_apres_cette_date_annee_derniere);
  sub_json := setVarInData(sub_json, 'pas_apres_cette_date_annee_derniere_complete', pas_apres_cette_date_annee_derniere_complete);
  stats := setVarInData(stats, 'dates', sub_json);

  -- moyens de lancement
  sub_json := tableauDeBord_mise_en_l_air(cette_annee, pas_apres_cette_date_cette_annee);
  stats := setVarInData(stats, 'mise_en_l_air_cette_annee', sub_json);

  sub_json := tableauDeBord_mise_en_l_air(annee_derniere, pas_apres_cette_date_annee_derniere);
  stats := setVarInData(stats, 'mise_en_l_air_annee_derniere', sub_json);

  sub_json := tableauDeBord_mise_en_l_air(annee_derniere, pas_apres_cette_date_annee_derniere_complete);
  stats := setVarInData(stats, 'mise_en_l_air_annee_derniere_complete', sub_json);

  -- heures de vol club + banalisé
  sub_json := tableauDeBord_hdv(cette_annee, pas_apres_cette_date_cette_annee, true);
  stats := setVarInData(stats, 'hdv_club_et_banalise_cette_annee', sub_json);
  sub_json := tableauDeBord_hdv(annee_derniere, pas_apres_cette_date_annee_derniere, true);
  stats := setVarInData(stats, 'hdv_club_et_banalise_annee_derniere', sub_json);
  sub_json := tableauDeBord_hdv(annee_derniere, pas_apres_cette_date_annee_derniere_complete, true);
  stats := setVarInData(stats, 'hdv_club_et_banalise_annee_derniere_complete', sub_json);

  -- heures de vol club
  sub_json := tableauDeBord_hdv(cette_annee, pas_apres_cette_date_cette_annee, false);
  stats := setVarInData(stats, 'hdv_club_cette_annee', sub_json);
  sub_json := tableauDeBord_hdv(annee_derniere, pas_apres_cette_date_annee_derniere, false);
  stats := setVarInData(stats, 'hdv_club_annee_derniere', sub_json);
  sub_json := tableauDeBord_hdv(annee_derniere, pas_apres_cette_date_annee_derniere_complete, false);
  stats := setVarInData(stats, 'hdv_club_annee_derniere_complete', sub_json);

  -- nombre de VI club
  sub_json := tableauDeBord_vi_club(cette_annee, pas_apres_cette_date_cette_annee);
  stats := setVarInData(stats, 'vi_club_cette_annee', sub_json);
  sub_json := tableauDeBord_vi_club(annee_derniere, pas_apres_cette_date_annee_derniere);
  stats := setVarInData(stats, 'vi_club_annee_derniere', sub_json);
  sub_json := tableauDeBord_vi_club(annee_derniere, pas_apres_cette_date_annee_derniere_complete);
  stats := setVarInData(stats, 'vi_club_annee_derniere_complete', sub_json);

  -- licences
  sub_json := tableauDeBord_licence(cette_annee, pas_apres_cette_date_cette_annee);
  stats := setVarInData(stats, 'licence_cette_annee', sub_json);
  sub_json := tableauDeBord_licence(annee_derniere, pas_apres_cette_date_annee_derniere);
  stats := setVarInData(stats, 'licence_annee_derniere', sub_json);
  sub_json := tableauDeBord_licence(annee_derniere, pas_apres_cette_date_annee_derniere_complete);
  stats := setVarInData(stats, 'licence_annee_derniere_complete', sub_json);

  RETURN stats;
END;
$$ LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION etatMachineCNB(duree_cnb_a_faire_par_machine INTERVAL, date_start DATE, date_end DATE, inclure_instruction BOOLEAN) RETURNS TABLE (
  immatriculation VARCHAR,
  nom_type VARCHAR,
  temps_vol_proprietaire INTERVAL,
  temps_vol_hors_proprietaire INTERVAL,
  temps_cnb_a_realiser INTERVAL,
  proprietaires VARCHAR,
  non_proprietaire_a_vole_sur VARCHAR
) AS $$
DECLARE
  r RECORD;
  r2 RECORD;
  liste_pilotes TEXT[];
BEGIN
  FOR r IN SELECT * FROM aeronef WHERE categorie = 'P' AND actif IS true LOOP
    immatriculation := r.immatriculation;
    nom_type := r.nom_type;
    liste_pilotes := '{}';
    temps_vol_proprietaire := '0:0:0'::interval;
    temps_vol_hors_proprietaire := '0:0:0'::interval;
    FOR r2 IN SELECT * FROM vfr_vol WHERE vfr_vol.immatriculation = r.immatriculation
      AND ((inclure_instruction IS TRUE AND vfr_vol.nom_type_vol IN ('1 Vol en solo', '2 Vol d''instruction', '3 Vol partagé')) OR (inclure_instruction IS FALSE AND vfr_vol.nom_type_vol IN ('1 Vol en solo', '3 Vol partagé')))
      AND date_vol BETWEEN date_start AND date_end LOOP
      IF r2.nom_type_vol = '1 Vol en solo' THEN
        IF piloteEstProprietaireDeMachine(r2.id_cdt_de_bord, r.id_aeronef, r2.date_vol) IS true THEN
          temps_vol_proprietaire := temps_vol_proprietaire + r2.temps_vol;
        ELSE
          liste_pilotes := array_append(liste_pilotes, r2.cdt_de_bord);
          temps_vol_hors_proprietaire := temps_vol_hors_proprietaire + r2.temps_vol;
        END IF;
      END IF;
      -- pour que le vol soit comptabilisé comme propriétaire il faut que les 2 pilotes soient propriétaires
      -- (le vol est dit partagé lorsqu'un seul des pilotes n'est pas propriétaire)
      IF r2.nom_type_vol = '3 Vol partagé' THEN
        IF piloteEstProprietaireDeMachine(r2.id_cdt_de_bord, r.id_aeronef, r2.date_vol) IS true AND piloteEstProprietaireDeMachine(r2.id_co_pilote, r.id_aeronef, r2.date_vol) IS true THEN
          temps_vol_proprietaire := temps_vol_proprietaire + r2.temps_vol;
        ELSE
          temps_vol_hors_proprietaire := temps_vol_hors_proprietaire + r2.temps_vol;
          IF piloteEstProprietaireDeMachine(r2.id_cdt_de_bord, r.id_aeronef, r2.date_vol) IS false THEN
            liste_pilotes := array_append(liste_pilotes, r2.cdt_de_bord);
          END IF;
          IF piloteEstProprietaireDeMachine(r2.id_co_pilote, r.id_aeronef, r2.date_vol) IS false THEN
            liste_pilotes := array_append(liste_pilotes, r2.co_pilote);
          END IF;
        END IF;
      END IF;
      -- pour que le vol soit comptabilisé comme propriétaire il faut que les 2 pilotes soient propriétaires
      -- (le vol est dit partagé lorsqu'un seul des pilotes n'est pas propriétaire)
      IF r2.nom_type_vol = '2 Vol d''instruction' THEN
        IF piloteEstProprietaireDeMachine(r2.id_instructeur, r.id_aeronef, r2.date_vol) IS true AND piloteEstProprietaireDeMachine(r2.id_eleve, r.id_aeronef, r2.date_vol) IS true THEN
          temps_vol_proprietaire := temps_vol_proprietaire + r2.temps_vol;
        ELSE
          temps_vol_hors_proprietaire := temps_vol_hors_proprietaire + r2.temps_vol;
        END IF;
        IF piloteEstProprietaireDeMachine(r2.id_instructeur, r.id_aeronef, r2.date_vol) IS false THEN
          liste_pilotes := array_append(liste_pilotes, r2.instructeur);
        END IF;
        IF piloteEstProprietaireDeMachine(r2.id_eleve, r.id_aeronef, r2.date_vol) IS false THEN
          liste_pilotes := array_append(liste_pilotes, r2.eleve);
        END IF;
      END IF;
    END LOOP;
    -- on va chercher le(s) propriétaire(s)
    proprietaires := array_to_string(getProprietaireMachine(r.id_aeronef, date_start), ', ');
    SELECT COUNT(*) AS nbVol INTO r FROM vfr_vol WHERE vfr_vol.immatriculation = r.immatriculation AND date_vol BETWEEN date_start AND date_end;
    IF temps_vol_proprietaire = '0:0:0'::interval AND r.nbVol > 0 THEN
      CONTINUE;
    END IF;
    IF temps_vol_hors_proprietaire < duree_cnb_a_faire_par_machine THEN
      temps_cnb_a_realiser := duree_cnb_a_faire_par_machine - temps_vol_hors_proprietaire;
      ELSE
      temps_cnb_a_realiser := '0:0:0'::interval;
    END IF;
    non_proprietaire_a_vole_sur := array_to_string(array_distinct(liste_pilotes), ', ');
    return NEXT;
  END LOOP;
END;
$$ LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION tableauDeBordAnnuel(annee INT, last_computation_date DATE, moyenne_sur_nb_annee INT) RETURNS JSONB AS $$
DECLARE
  stats JSONB;
  rDate RECORD;
  r RECORD;
  r2 RECORD;
  r_vol RECORD;
  sub_json JSONB;
  i INT;
  mise_en_l_air TEXT;
  mises_en_l_air TEXT[] := '{"R", "T", "M"}';
  cette_annee INT;
  licences INT[] := '{}';
  cumulLicence INT := 0;
  licences_n_anneesPrecedantes INT[] := '{}';
  cumulLicenceAnneesPrecedantes INT := 0;

  -- HDV MACHINES CLUB
  HDVClubCDB INT[] := '{}';
  cumulHDVClubCDB INT := 0;
  HDVClubInstruction INT[] := '{}';
  cumulHDVClubInstruction INT := 0;
  HDVClubCDB_n_anneesPrecedantes INT[] := '{}';
  cumulHDVClubCDB_n_anneesPrecedantes INT := 0;
  HDVClubInstruction_n_anneesPrecedantes INT[] := '{}';
  cumulHDVClubInstruction_n_anneesPrecedantes INT := 0;

  -- HDV MACHINES BANALISEES
  HDVBanaliseCDB INT[] := '{}';
  cumulHDVBanaliseCDB INT := 0;
  HDVBanaliseInstruction INT[] := '{}';
  cumulHDVBanaliseInstruction INT := 0;
  HDVBanaliseCDB_n_anneesPrecedantes INT[] := '{}';
  cumulHDVBanaliseCDB_n_anneesPrecedantes INT := 0;
  HDVBanaliseInstruction_n_anneesPrecedantes INT[] := '{}';
  cumulHDVBanaliseInstruction_n_anneesPrecedantes INT := 0;
  HDVBanaliseNonProprietaire INT[] := '{}';
  cumulHDVBanaliseNonProprietaire INT := 0;
  HDVBanaliseNonProprietaire_n_anneesPrecedantes INT[] := '{}';
  cumulHDVBanaliseNonProprietaire_n_anneesPrecedantes INT := 0;

  -- HDV pilotes forfait (uniquement vol CDB, si un forfait école existe il faudra modifier la requête)
  HDVPilotesDansForfait INT[] := '{}';
  cumulHDVPilotesDansForfait INT := 0;
  HDVPilotesDansForfait_n_anneesPrecedantes INT[] := '{}';
  cumulHDVPilotesDansForfait_n_anneesPrecedantes INT := 0;

  -- HDV pilotes hors forfait (CDB + instruction + élève)
  HDVPilotesHorsForfait INT[] := '{}';
  cumulHDVPilotesHorsForfait INT := 0;
  HDVPilotesHorsForfait_n_anneesPrecedantes INT[] := '{}';
  cumulHDVPilotesHorsForfait_n_anneesPrecedantes INT := 0;

  -- lancements
  lancementR INT[] := '{}';
  cumulLancementR INT := 0;
  lancementRCorrige INT[] := '{}';
  cumulLancementRCorrige INT := 0;
  lancementT INT[] := '{}';
  cumulLancementT INT := 0;
  lancementA INT[] := '{}';
  cumulLancementA INT := 0;

  lancementRCumul INT[] := '{}';
  lancementRCorrigeCumul INT[] := '{}';
  lancementTCumul INT[] := '{}';
  lancementACumul INT[] := '{}';

  lancementR_n_anneesPrecedantes INT[] := '{}';
  cumulLancementR_n_anneesPrecedantes INT := 0;
  lancementRCorrige_n_anneesPrecedantes INT[] := '{}';
  cumulLancementRCorrige_n_anneesPrecedantes INT := 0;
  lancementT_n_anneesPrecedantes INT[] := '{}';
  cumulLancementT_n_anneesPrecedantes INT := 0;
  lancementA_n_anneesPrecedantes INT[] := '{}';
  cumulLancementA_n_anneesPrecedantes INT := 0;

  lancementRCumul_n_anneesPrecedantes INT[] := '{}';
  lancementRCorrigeCumul_n_anneesPrecedantes INT[] := '{}';
  lancementTCumul_n_anneesPrecedantes INT[] := '{}';
  lancementACumul_n_anneesPrecedantes INT[] := '{}';

  -- liste des remorqueurs sur la période
  rRemorqueurs RECORD;
  rRemorqueursStats RECORD;
  rMonth RECORD;
  remorqueursCount JSONB;
  cumulRemorqueurCount INT := 0;
  remorqueursCount1 INT[] := '{}';

  -- VALORISATION
    -- cellule
  valo_hdv NUMERIC[] := '{}';
  valo_cumulHDV NUMERIC := 0;
  valo_hdv_n_anneesPrecedantes NUMERIC[] := '{}';
  valo_cumulHDV_n_anneesPrecedantes NUMERIC := 0;
    -- moteur
  valo_moteur NUMERIC[] := '{}';
  valo_cumulMoteur NUMERIC := 0;
  valo_moteur_n_anneesPrecedantes NUMERIC[] := '{}';
  valo_cumulMoteur_n_anneesPrecedantes NUMERIC := 0;
    -- forfait
  valo_forfait NUMERIC[] := '{}';
  valo_cumulForfait NUMERIC := 0;
  valo_forfait_n_anneesPrecedantes NUMERIC[] := '{}';
  valo_cumulForfait_n_anneesPrecedantes NUMERIC := 0;
    -- journees decouvertes et stages
  valo_jdStages NUMERIC[] := '{}';
  valo_cumulJdStages NUMERIC := 0;
  valo_jdStages_n_anneesPrecedantes NUMERIC[] := '{}';
  valo_cumulJdStages_n_anneesPrecedantes NUMERIC := 0;
    -- cellule pilotes (solo, partagé et vi perso)
  valo_cellulePilotes NUMERIC[] := '{}';
  valo_cumulCellulePilotes NUMERIC := 0;
  valo_cellulePilotes_n_anneesPrecedantes NUMERIC[] := '{}';
  valo_cumulCellulePilotes_n_anneesPrecedantes NUMERIC := 0;
    -- cellule instruction
  valo_celluleInstruction NUMERIC[] := '{}';
  valo_cumulCelluleInstruction NUMERIC := 0;
  valo_celluleInstruction_n_anneesPrecedantes NUMERIC[] := '{}';
  valo_cumulCelluleInstruction_n_anneesPrecedantes NUMERIC := 0;
    -- VI
  valo_VI NUMERIC[] := '{}';
  valo_cumulVI NUMERIC := 0;
  valo_VI_n_anneesPrecedantes NUMERIC[] := '{}';
  valo_cumulVI_n_anneesPrecedantes NUMERIC := 0;
    -- lancement
  valo_lancement NUMERIC[] := '{}';
  valo_cumulLancement NUMERIC := 0;
  valo_lancement_n_anneesPrecedantes NUMERIC[] := '{}';
  valo_cumulLancement_n_anneesPrecedantes NUMERIC := 0;

  -- dépenses générales
  depenses_generales NUMERIC[] := '{}';
  depenses_generales_cumul NUMERIC := 0;
  depenses_generales_n_anneesPrecedantes NUMERIC[] := '{}';
  depenses_generales_cumul_n_anneesPrecedantes NUMERIC := 0;

  -- dépenses moyens de lancement
  depenses_moyens_lancement NUMERIC[] := '{}';
  depenses_moyens_lancement_cumul NUMERIC := 0;
  depenses_moyens_lancement_n_anneesPrecedantes NUMERIC[] := '{}';
  depenses_moyens_lancement_cumul_n_anneesPrecedantes NUMERIC := 0;

  -- dépenses entretien planeurs
  depenses_entretien_planeurs NUMERIC[] := '{}';
  depenses_entretien_planeurs_cumul NUMERIC := 0;
  depenses_entretien_planeurs_n_anneesPrecedantes NUMERIC[] := '{}';
  depenses_entretien_planeurs_cumul_n_anneesPrecedantes NUMERIC := 0;

  -- dépenses mairie
  depenses_mairie NUMERIC[] := '{}';
  depenses_mairie_cumul NUMERIC := 0;
  depenses_mairie_n_anneesPrecedantes NUMERIC[] := '{}';
  depenses_mairie_cumul_n_anneesPrecedantes NUMERIC := 0;

  -- revenus générales
  revenus_generales NUMERIC[] := '{}';
  revenus_generales_cumul NUMERIC := 0;
  revenus_generales_n_anneesPrecedantes NUMERIC[] := '{}';
  revenus_generales_cumul_n_anneesPrecedantes NUMERIC := 0;

  -- revenus moyens de lancement
  revenus_moyens_lancement NUMERIC[] := '{}';
  revenus_moyens_lancement_cumul NUMERIC := 0;
  revenus_moyens_lancement_n_anneesPrecedantes NUMERIC[] := '{}';
  revenus_moyens_lancement_cumul_n_anneesPrecedantes NUMERIC := 0;

  -- revenus entretien planeurs
  revenus_entretien_planeurs NUMERIC[] := '{}';
  revenus_entretien_planeurs_cumul NUMERIC := 0;
  revenus_entretien_planeurs_n_anneesPrecedantes NUMERIC[] := '{}';
  revenus_entretien_planeurs_cumul_n_anneesPrecedantes NUMERIC := 0;

  -- revenus mairie
  revenus_mairie NUMERIC[] := '{}';
  revenus_mairie_cumul NUMERIC := 0;
  revenus_mairie_n_anneesPrecedantes NUMERIC[] := '{}';
  revenus_mairie_cumul_n_anneesPrecedantes NUMERIC := 0;

BEGIN
  stats := '{}';
  stats := setVarInData(stats, 'moyenne_sur_nb_annee', moyenne_sur_nb_annee);
  DROP TABLE IF EXISTS remorqueursCount;
  CREATE TEMPORARY TABLE remorqueursCount(id SERIAL PRIMARY KEY, immatriculation VARCHAR, m INT, nb INT);
  FOR rDate IN SELECT t AS start, t + interval '1 month' - interval '1 second' AS stop FROM generate_series(DATE_TRUNC('day', make_date(annee, 1, 1)), make_date(annee, 12, 31), interval '1 month') AS t(day)
  LOOP
    cette_annee := EXTRACT(YEAR FROM rDate.start);

    -- ============ ACTIVITES ============
      -- ============ LICENCES ============
        -- licence
        -- le mois de janvier prend en compte les mois d'octobre, novembre, décembre et janvier, donc on fait un CASE dédié à ça
        SELECT INTO r COALESCE(COUNT(*), 0) AS nb FROM pilote
          JOIN cp_piece_ligne li ON li.id_compte = pilote.id_compte
          JOIN cp_piece pi ON pi.id_piece = li.id_piece
          WHERE type IN ('LICENCE_FFVP', 'LFFVV', 'LICVV')
          AND licence_nom NOT LIKE '%Découverte%'
          AND pi.date_echeance BETWEEN (CASE WHEN EXTRACT(MONTH FROM rDate.start) = 1 THEN rDate.start - interval '3 months' ELSE rDate.start END) AND rDate.stop;
        IF EXTRACT(MONTH FROM rDate.start) < 10 THEN -- en octobre on commence les licences de l'année prochaine ce qu'on ne veut pas prendre en compte ici
          cumulLicence := cumulLicence + r.nb;
        END IF;
        IF EXTRACT(MONTH FROM rDate.stop) <= EXTRACT(MONTH FROM last_computation_date) AND rDate.stop::date <= last_computation_date THEN
          licences := array_append(licences, cumulLicence);
        END IF;

        -- moyenne des licences sur les 5 dernières années
        IF EXTRACT(MONTH FROM rDate.start) = 1 THEN
          -- le mois de janvier prend en compte les mois d'octobre, novembre, décembre et janvier
          SELECT INTO r ROUND(COALESCE(COUNT(*), 0)/moyenne_sur_nb_annee) AS nb FROM pilote
            JOIN cp_piece_ligne li ON li.id_compte = pilote.id_compte
            JOIN cp_piece pi ON pi.id_piece = li.id_piece
            WHERE type IN ('LICENCE_FFVP', 'LFFVV', 'LICVV')
            AND licence_nom NOT LIKE '%Découverte%' AND
            (
              (EXTRACT(MONTH FROM pi.date_echeance) BETWEEN 10 AND 12 AND EXTRACT(YEAR FROM pi.date_echeance) BETWEEN cette_annee - moyenne_sur_nb_annee - 1 AND cette_annee - 1)
            OR
            (
              (EXTRACT(YEAR FROM pi.date_echeance) >= cette_annee - moyenne_sur_nb_annee AND EXTRACT(YEAR FROM pi.date_echeance) < cette_annee
              AND EXTRACT(MONTH FROM pi.date_echeance) = EXTRACT(MONTH FROM rDate.start)))
            );
        ELSE
          SELECT INTO r ROUND(COALESCE(COUNT(*), 0)/moyenne_sur_nb_annee) AS nb FROM pilote
            JOIN cp_piece_ligne li ON li.id_compte = pilote.id_compte
            JOIN cp_piece pi ON pi.id_piece = li.id_piece
            WHERE type IN ('LICENCE_FFVP', 'LFFVV', 'LICVV')
            AND licence_nom NOT LIKE '%Découverte%'
            AND EXTRACT(YEAR FROM pi.date_echeance) >= cette_annee - moyenne_sur_nb_annee AND EXTRACT(YEAR FROM pi.date_echeance) < cette_annee
            AND EXTRACT(MONTH FROM pi.date_echeance) = EXTRACT(MONTH FROM rDate.start);
        END IF;
        IF EXTRACT(MONTH FROM rDate.start) < 10 THEN -- en octobre on commence les licences de l'année prochaine ce qu'on ne veut pas prendre en compte ici
          cumulLicenceAnneesPrecedantes := cumulLicenceAnneesPrecedantes + r.nb;
        END IF;
        licences_n_anneesPrecedantes := array_append(licences_n_anneesPrecedantes, cumulLicenceAnneesPrecedantes);

      -- ============ HEURES DE VOLS SUR LES MACHINES ============
        -- ============ MACHINES CLUB ============
          -- heures de vol club CDB
          SELECT INTO r ROUND(EXTRACT(EPOCH FROM COALESCE(SUM(temps_vol), '0'::interval)/3600)) AS duree FROM vfr_vol
            WHERE date_vol BETWEEN rDate.start AND rDate.stop AND situation = 'C' AND nom_type_vol IN ('1 Vol en solo', '3 Vol partagé')
            AND categorie != 'U'; -- 'U' = remorqueur
          cumulHDVClubCDB := cumulHDVClubCDB + r.duree;
          IF EXTRACT(MONTH FROM rDate.stop) <= EXTRACT(MONTH FROM last_computation_date) AND rDate.stop::date <= last_computation_date THEN
            HDVClubCDB := array_append(HDVClubCDB, cumulHDVClubCDB);
          END IF;

          -- heure de vol club CDB sur les 5 dernières années
          SELECT INTO r ROUND((EXTRACT(EPOCH FROM COALESCE(SUM(temps_vol), '0'::interval))/3600)/moyenne_sur_nb_annee) AS duree FROM vfr_vol
            WHERE EXTRACT(YEAR FROM date_vol) >= cette_annee - moyenne_sur_nb_annee AND EXTRACT(YEAR FROM date_vol) < cette_annee
            AND EXTRACT(MONTH FROM date_vol) = EXTRACT(MONTH FROM rDate.start)
            AND situation = 'C' AND nom_type_vol IN ('1 Vol en solo', '3 Vol partagé')
            AND categorie != 'U'; -- 'U' = remorqueur
          cumulHDVClubCDB_n_anneesPrecedantes := cumulHDVClubCDB_n_anneesPrecedantes + r.duree;
          HDVClubCDB_n_anneesPrecedantes := array_append(HDVClubCDB_n_anneesPrecedantes, cumulHDVClubCDB_n_anneesPrecedantes);

          -- heures de vol club instruction
          SELECT INTO r ROUND(EXTRACT(EPOCH FROM COALESCE(SUM(temps_vol), '0'::interval))/3600) AS duree FROM vfr_vol
            WHERE date_vol BETWEEN rDate.start AND rDate.stop AND situation = 'C' AND nom_type_vol = '2 Vol d''instruction'
            AND categorie != 'U'; -- 'U' = remorqueur
          cumulHDVClubInstruction := cumulHDVClubInstruction + r.duree;
          IF EXTRACT(MONTH FROM rDate.stop) <= EXTRACT(MONTH FROM last_computation_date) AND rDate.stop::date <= last_computation_date THEN
            HDVClubInstruction := array_append(HDVClubInstruction, cumulHDVClubInstruction);
          END IF;

          -- heure de vol club instruction sur les 5 dernières années
          SELECT INTO r ROUND((EXTRACT(EPOCH FROM COALESCE(SUM(temps_vol), '0'::interval))/3600)/moyenne_sur_nb_annee) AS duree FROM vfr_vol
            WHERE EXTRACT(YEAR FROM date_vol) >= cette_annee - moyenne_sur_nb_annee AND EXTRACT(YEAR FROM date_vol) < cette_annee
            AND EXTRACT(MONTH FROM date_vol) = EXTRACT(MONTH FROM rDate.start)
            AND situation = 'C' AND nom_type_vol = '2 Vol d''instruction'
            AND categorie != 'U'; -- 'U' = remorqueur
          cumulHDVClubInstruction_n_anneesPrecedantes := cumulHDVClubInstruction_n_anneesPrecedantes + r.duree;
          HDVClubInstruction_n_anneesPrecedantes := array_append(HDVClubInstruction_n_anneesPrecedantes, cumulHDVClubInstruction_n_anneesPrecedantes);

        -- ============ MACHINES BANALISEES ============
          -- heures de vol banalisé CDB
          SELECT INTO r ROUND(EXTRACT(EPOCH FROM COALESCE(SUM(temps_vol), '0'::interval)/3600)) AS duree FROM vfr_vol
            WHERE date_vol BETWEEN rDate.start AND rDate.stop AND situation = 'B' AND nom_type_vol IN ('1 Vol en solo', '3 Vol partagé');
          cumulHDVBanaliseCDB := cumulHDVBanaliseCDB + r.duree;
          IF EXTRACT(MONTH FROM rDate.stop) <= EXTRACT(MONTH FROM last_computation_date) AND rDate.stop::date <= last_computation_date THEN
            HDVBanaliseCDB := array_append(HDVBanaliseCDB, cumulHDVBanaliseCDB);
          END IF;

          -- heure de vol club CDB sur les 5 dernières années
          SELECT INTO r ROUND((EXTRACT(EPOCH FROM COALESCE(SUM(temps_vol), '0'::interval))/3600)/moyenne_sur_nb_annee) AS duree FROM vfr_vol
            WHERE EXTRACT(YEAR FROM date_vol) >= cette_annee - moyenne_sur_nb_annee AND EXTRACT(YEAR FROM date_vol) < cette_annee
            AND EXTRACT(MONTH FROM date_vol) = EXTRACT(MONTH FROM rDate.start)
            AND situation = 'B' AND nom_type_vol IN ('1 Vol en solo', '3 Vol partagé');
          cumulHDVBanaliseCDB_n_anneesPrecedantes := cumulHDVBanaliseCDB_n_anneesPrecedantes + r.duree;
          HDVBanaliseCDB_n_anneesPrecedantes := array_append(HDVBanaliseCDB_n_anneesPrecedantes, cumulHDVBanaliseCDB_n_anneesPrecedantes);

          -- heures de vol club instruction
          SELECT INTO r ROUND(EXTRACT(EPOCH FROM COALESCE(SUM(temps_vol), '0'::interval))/3600) AS duree FROM vfr_vol
            WHERE date_vol BETWEEN rDate.start AND rDate.stop AND situation = 'B' AND nom_type_vol = '2 Vol d''instruction';
          cumulHDVBanaliseInstruction := cumulHDVBanaliseInstruction + r.duree;
          IF EXTRACT(MONTH FROM rDate.stop) <= EXTRACT(MONTH FROM last_computation_date) AND rDate.stop::date <= last_computation_date THEN
            HDVBanaliseInstruction := array_append(HDVBanaliseInstruction, cumulHDVBanaliseInstruction);
          END IF;

          -- heure de vol club instruction sur les 5 dernières années
          SELECT INTO r ROUND((EXTRACT(EPOCH FROM COALESCE(SUM(temps_vol), '0'::interval))/3600)/moyenne_sur_nb_annee) AS duree FROM vfr_vol
            WHERE EXTRACT(YEAR FROM date_vol) >= cette_annee - moyenne_sur_nb_annee AND EXTRACT(YEAR FROM date_vol) < cette_annee
            AND EXTRACT(MONTH FROM date_vol) = EXTRACT(MONTH FROM rDate.start)
            AND situation = 'B' AND nom_type_vol = '2 Vol d''instruction';
          cumulHDVBanaliseInstruction_n_anneesPrecedantes := cumulHDVBanaliseInstruction_n_anneesPrecedantes + r.duree;
          HDVBanaliseInstruction_n_anneesPrecedantes := array_append(HDVBanaliseInstruction_n_anneesPrecedantes, cumulHDVBanaliseInstruction_n_anneesPrecedantes);

          -- heure de vol non-propriétaire sur machine banalisée
          SELECT INTO r ROUND(EXTRACT(EPOCH FROM COALESCE(SUM(etatMachineCNB.temps_vol_hors_proprietaire), '0'::interval)/3600)) AS duree FROM etatMachineCNB('0:0:0'::interval, rDate.start::date, rDate.stop::date, false);
          cumulHDVBanaliseNonProprietaire := cumulHDVBanaliseNonProprietaire + r.duree;
          IF EXTRACT(MONTH FROM rDate.stop) <= EXTRACT(MONTH FROM last_computation_date) AND rDate.stop::date <= last_computation_date THEN
            HDVBanaliseNonProprietaire := array_append(HDVBanaliseNonProprietaire, cumulHDVBanaliseNonProprietaire);
          END IF;

          -- heure de vol non-propriétaire sur machine banalisée sur les 5 dernières années
          FOR i IN 1..moyenne_sur_nb_annee LOOP
            SELECT INTO r ROUND(EXTRACT(EPOCH FROM COALESCE(SUM(etatMachineCNB.temps_vol_hors_proprietaire), '0'::interval)/3600)) AS duree FROM etatMachineCNB('0:0:0'::interval, (rDate.start - (interval '1 YEAR' * i))::date, (rDate.stop - (interval '1 YEAR' * i))::date, false);
            cumulHDVBanaliseNonProprietaire_n_anneesPrecedantes := cumulHDVBanaliseNonProprietaire_n_anneesPrecedantes + r.duree;
          END LOOP;
          HDVBanaliseNonProprietaire_n_anneesPrecedantes := array_append(HDVBanaliseNonProprietaire_n_anneesPrecedantes, cumulHDVBanaliseNonProprietaire_n_anneesPrecedantes);

      -- ============ HEURES DE VOLS PILOTE ============
        -- heures de vol dans le forfait
        SELECT INTO r ROUND(EXTRACT(EPOCH FROM COALESCE(SUM(temps_vol), '0:0:0'::interval))/3600) AS duree FROM vfr_vol
          JOIN vfr_forfait_pilote ON vfr_forfait_pilote.id_personne = vfr_vol.id_cdt_de_bord
          WHERE vfr_vol.date_vol BETWEEN vfr_forfait_pilote.date_debut AND vfr_forfait_pilote.date_fin
          AND EXTRACT(YEAR FROM vfr_forfait_pilote.date_debut) = EXTRACT(YEAR FROM vfr_vol.date_vol)
          AND date_vol BETWEEN rDate.start AND rDate.stop
          AND vfr_forfait_pilote.hrs_cellule = '999:00:00'
          AND situation IN ('B', 'C')
          AND NOT EXISTS(
            SELECT 1
            FROM forfait_modele_aeronef_exclu
            JOIN forfait_modele ON forfait_modele.id_forfait_modele = forfait_modele_aeronef_exclu.id_forfait_modele
            JOIN aeronef ON aeronef.id_aeronef = forfait_modele_aeronef_exclu.id_aeronef
            WHERE forfait_modele.id_forfait_modele = vfr_forfait_pilote.id_forfait_modele AND aeronef.id_aeronef = vfr_vol.id_aeronef);
        cumulHDVPilotesDansForfait := cumulHDVPilotesDansForfait + r.duree;
        IF EXTRACT(MONTH FROM rDate.stop) <= EXTRACT(MONTH FROM last_computation_date) AND rDate.stop::date <= last_computation_date THEN
          HDVPilotesDansForfait := array_append(HDVPilotesDansForfait, cumulHDVPilotesDansForfait);
        END IF;

        -- heures de vol dans le forfait sur les 5 dernières années
        SELECT INTO r ROUND((EXTRACT(EPOCH FROM COALESCE(SUM(temps_vol), '0:0:0'::interval))/3600)/moyenne_sur_nb_annee) AS duree FROM vfr_vol
          JOIN vfr_forfait_pilote ON vfr_forfait_pilote.id_personne = vfr_vol.id_cdt_de_bord
          WHERE vfr_vol.date_vol BETWEEN vfr_forfait_pilote.date_debut AND vfr_forfait_pilote.date_fin
          AND EXTRACT(YEAR FROM vfr_vol.date_vol) >= cette_annee - moyenne_sur_nb_annee AND EXTRACT(YEAR FROM vfr_vol.date_vol) < cette_annee
          AND EXTRACT(MONTH FROM date_vol) = EXTRACT(MONTH FROM rDate.start)
          AND vfr_forfait_pilote.hrs_cellule = '999:00:00'
          AND situation IN ('B', 'C')
          AND NOT EXISTS(
            SELECT 1
            FROM forfait_modele_aeronef_exclu
            JOIN forfait_modele ON forfait_modele.id_forfait_modele = forfait_modele_aeronef_exclu.id_forfait_modele
            JOIN aeronef ON aeronef.id_aeronef = forfait_modele_aeronef_exclu.id_aeronef
            WHERE forfait_modele.id_forfait_modele = vfr_forfait_pilote.id_forfait_modele AND aeronef.id_aeronef = vfr_vol.id_aeronef);
        cumulHDVPilotesDansForfait_n_anneesPrecedantes := cumulHDVPilotesDansForfait_n_anneesPrecedantes + r.duree;
        HDVPilotesDansForfait_n_anneesPrecedantes := array_append(HDVPilotesDansForfait_n_anneesPrecedantes, cumulHDVPilotesDansForfait_n_anneesPrecedantes);

        -- heures de vol qui ne sont pas dans le forfait
        SELECT INTO r ROUND(EXTRACT(EPOCH FROM COALESCE(SUM(temps_vol), '0:0:0'::interval))/3600) AS duree FROM vfr_vol
          WHERE date_vol BETWEEN rDate.start AND rDate.stop
          AND (
            (vfr_vol.prix_vol_cdb > 0 AND vfr_vol.id_cdt_de_bord IS NOT NULL)
            OR (vfr_vol.prix_vol_elv IS NOT NULL)
            OR (vfr_vol.prix_vol_co IS NOT NULL)
          )
          AND situation IN ('B', 'C')
          AND categorie != 'U'; -- 'U' = remorqueur
        cumulHDVPilotesHorsForfait := cumulHDVPilotesHorsForfait + r.duree;
        IF EXTRACT(MONTH FROM rDate.stop) <= EXTRACT(MONTH FROM last_computation_date) AND rDate.stop::date <= last_computation_date THEN
          HDVPilotesHorsForfait := array_append(HDVPilotesHorsForfait, cumulHDVPilotesHorsForfait);
        END IF;

        -- heures de vol qui ne sont pas dans le forfait sur les 5 dernières années
        SELECT INTO r ROUND((EXTRACT(EPOCH FROM COALESCE(SUM(temps_vol), '0:0:0'::interval))/3600)/moyenne_sur_nb_annee) AS duree FROM vfr_vol
          WHERE EXTRACT(YEAR FROM vfr_vol.date_vol) >= cette_annee - moyenne_sur_nb_annee AND EXTRACT(YEAR FROM vfr_vol.date_vol) < cette_annee
          AND EXTRACT(MONTH FROM date_vol) = EXTRACT(MONTH FROM rDate.start)
          AND (
            (vfr_vol.prix_vol_cdb > 0 AND vfr_vol.id_cdt_de_bord IS NOT NULL)
            OR (vfr_vol.prix_vol_elv IS NOT NULL)
            OR (vfr_vol.prix_vol_co IS NOT NULL)
          )
          AND situation IN ('B', 'C')
          AND categorie != 'U'; -- 'U' = remorqueur
        cumulHDVPilotesHorsForfait_n_anneesPrecedantes := cumulHDVPilotesHorsForfait_n_anneesPrecedantes + r.duree;
        HDVPilotesHorsForfait_n_anneesPrecedantes := array_append(HDVPilotesHorsForfait_n_anneesPrecedantes, cumulHDVPilotesHorsForfait_n_anneesPrecedantes);

      -- ============ MOYENS DE LANCEMENT ============
        -- année n
        SELECT INTO r SUM(CASE
          WHEN libelle_remorque = 'Demi-remorqué - 250m' THEN 0.5
          WHEN libelle_remorque = 'Remorqué standard - 500m' THEN 1
          WHEN libelle_remorque = '750m' THEN 1.5
          WHEN libelle_remorque = '1000m' THEN 2
          ELSE 0 END) AS nbR_corrige, SUM(CASE WHEN mode_decollage = 'R' THEN 1 ELSE 0 END) AS nbR, SUM(CASE WHEN mode_decollage = 'T' THEN 1 ELSE 0 END) AS nbT, SUM(CASE WHEN mode_decollage = 'M' THEN 1 ELSE 0 END) AS nbA
          FROM vfr_vol
          WHERE date_vol BETWEEN rDate.start AND rDate.stop
          AND categorie != 'U'; -- 'U' = remorqueur
        cumulLancementR := cumulLancementR + r.nbR;
        cumulLancementRCorrige := cumulLancementRCorrige + r.nbR_corrige;
        cumulLancementT := cumulLancementT + r.nbT;
        cumulLancementA := cumulLancementA + r.nbA;
        IF EXTRACT(MONTH FROM rDate.stop) <= EXTRACT(MONTH FROM last_computation_date) AND rDate.stop::date <= last_computation_date THEN
          -- ici on veut avoir mois par mois (sans cumul)
          lancementR := array_append(lancementR, r.nbR);
          lancementRCorrige := array_append(lancementRCorrige, r.nbR);
          lancementT := array_append(lancementT, r.nbT);
          lancementA := array_append(lancementA, r.nbA);
          -- mais aussi avec les cumuls
          lancementRCumul := array_append(lancementRCumul, cumulLancementR);
          lancementRCorrigeCumul := array_append(lancementRCorrigeCumul, cumulLancementRCorrige);
          lancementTCumul := array_append(lancementTCumul, cumulLancementT);
          lancementACumul := array_append(lancementACumul, cumulLancementA);
        END IF;

        -- années précédantes
        SELECT INTO r ROUND(SUM(CASE
              WHEN libelle_remorque = 'Demi-remorqué - 250m' THEN 0.5
              WHEN libelle_remorque = 'Remorqué standard - 500m' THEN 1
              WHEN libelle_remorque = '750m' THEN 1.5
              WHEN libelle_remorque = '1000m' THEN 2
              WHEN libelle_remorque = '1300m' THEN 2.6
              ELSE 0 END)/moyenne_sur_nb_annee) AS nbR_corrige,
            ROUND(SUM(CASE WHEN mode_decollage = 'R' THEN 1 ELSE 0 END)/moyenne_sur_nb_annee) AS nbR,
            ROUND(SUM(CASE WHEN mode_decollage = 'T' THEN 1 ELSE 0 END)/moyenne_sur_nb_annee) AS nbT,
            ROUND(SUM(CASE WHEN mode_decollage = 'M' THEN 1 ELSE 0 END)/moyenne_sur_nb_annee) AS nbA
          FROM vfr_vol
          WHERE EXTRACT(YEAR FROM vfr_vol.date_vol) >= cette_annee - moyenne_sur_nb_annee AND EXTRACT(YEAR FROM vfr_vol.date_vol) < cette_annee
          AND EXTRACT(MONTH FROM date_vol) = EXTRACT(MONTH FROM rDate.start)
          AND categorie != 'U'; -- 'U' = remorqueur
        cumulLancementR_n_anneesPrecedantes := cumulLancementR_n_anneesPrecedantes + r.nbR;
        cumulLancementRCorrige_n_anneesPrecedantes := cumulLancementRCorrige_n_anneesPrecedantes + r.nbR_corrige;
        cumulLancementT_n_anneesPrecedantes := cumulLancementT_n_anneesPrecedantes + r.nbT;
        cumulLancementA_n_anneesPrecedantes := cumulLancementA_n_anneesPrecedantes + r.nbA;
        -- on veut sans cumul
        lancementR_n_anneesPrecedantes := array_append(lancementR_n_anneesPrecedantes, r.nbR);
        lancementRCorrige_n_anneesPrecedantes := array_append(lancementRCorrige_n_anneesPrecedantes, r.nbR_corrige);
        lancementT_n_anneesPrecedantes := array_append(lancementT_n_anneesPrecedantes, r.nbT);
        lancementA_n_anneesPrecedantes := array_append(lancementA_n_anneesPrecedantes, r.nbA);
        -- et avec cumul
        lancementRCumul_n_anneesPrecedantes := array_append(lancementRCumul_n_anneesPrecedantes, cumulLancementR_n_anneesPrecedantes);
        lancementRCorrigeCumul_n_anneesPrecedantes := array_append(lancementRCorrigeCumul_n_anneesPrecedantes, cumulLancementRCorrige_n_anneesPrecedantes);
        lancementTCumul_n_anneesPrecedantes := array_append(lancementTCumul_n_anneesPrecedantes, cumulLancementT_n_anneesPrecedantes);
        lancementACumul_n_anneesPrecedantes := array_append(lancementACumul_n_anneesPrecedantes, cumulLancementA_n_anneesPrecedantes);

      -- ============ DETAILS DES MOYENS DE LANCEMENT ============
        -- liste des remorqueurs et nombre de remorqués
      FOR rRemorqueurs IN SELECT immatriculation_remorqueur, COUNT(*) AS nb FROM vfr_vol
        WHERE date_vol BETWEEN rDate.start AND rDate.stop
          AND immatriculation_remorqueur IS NOT NULL AND immatriculation_remorqueur NOT IN ('RMEXT', 'REMEXT')
        GROUP BY immatriculation_remorqueur LOOP
          INSERT INTO remorqueursCount(immatriculation, m, nb) VALUES (rRemorqueurs.immatriculation_remorqueur, EXTRACT(MONTH FROM rDate.start), rRemorqueurs.nb);
      END LOOP;

    -- ============ VALORISATION ============
      -- ============ COUT CELLULE ============
        -- REVENU CELLULE des machines club
          SELECT INTO r ROUND(SUM(COALESCE(prix_vol_cdb, 0) + COALESCE(prix_vol_co, 0) + COALESCE(prix_vol_elv, 0))) AS prix FROM vfr_vol
            WHERE date_vol BETWEEN rDate.start AND rDate.stop AND situation = 'C' AND nom_type_vol IN ('1 Vol en solo', '2 Vol d''instruction', '3 Vol partagé', '41 VI perso')
            AND categorie != 'U'; -- 'U' = remorqueur 'B' = banalisé (pas les privés)
          valo_cumulHDV := valo_cumulHDV + r.prix;
          IF EXTRACT(MONTH FROM rDate.stop) <= EXTRACT(MONTH FROM last_computation_date) AND rDate.stop::date <= last_computation_date THEN
            valo_hdv := array_append(valo_hdv, valo_cumulHDV);
          END IF;

        -- REVENU CELLULE sur les 5 dernières années
          SELECT INTO r ROUND(SUM(COALESCE(prix_vol_cdb, 0) + COALESCE(prix_vol_co, 0) + COALESCE(prix_vol_elv, 0))/moyenne_sur_nb_annee) AS prix FROM vfr_vol
            WHERE EXTRACT(YEAR FROM date_vol) >= cette_annee - moyenne_sur_nb_annee AND EXTRACT(YEAR FROM date_vol) < cette_annee
            AND EXTRACT(MONTH FROM date_vol) = EXTRACT(MONTH FROM rDate.start)
            AND situation = 'C' AND nom_type_vol IN ('1 Vol en solo', '2 Vol d''instruction', '3 Vol partagé')
            AND categorie != 'U'; -- 'U' = remorqueur 'B' = banalisé (pas les privés)
          valo_cumulHDV_n_anneesPrecedantes := valo_cumulHDV_n_anneesPrecedantes + r.prix;
          valo_hdv_n_anneesPrecedantes := array_append(valo_hdv_n_anneesPrecedantes, valo_cumulHDV_n_anneesPrecedantes);

        -- REVENU MOTEUR des machines club
          SELECT INTO r ROUND(SUM(COALESCE(prix_moteur_cdb, 0) + COALESCE(prix_moteur_co, 0) + COALESCE(prix_moteur_elv, 0))) AS prix FROM vfr_vol
            WHERE date_vol BETWEEN rDate.start AND rDate.stop AND situation = 'C' AND nom_type_vol IN ('1 Vol en solo', '2 Vol d''instruction', '3 Vol partagé', '41 VI perso')
            AND categorie != 'U'; -- 'U' = remorqueur 'B' = banalisé (pas les privés)
          valo_cumulMoteur := valo_cumulMoteur + r.prix;
          IF EXTRACT(MONTH FROM rDate.stop) <= EXTRACT(MONTH FROM last_computation_date) AND rDate.stop::date <= last_computation_date THEN
            valo_moteur := array_append(valo_moteur, valo_cumulMoteur);
          END IF;

        -- REVENU MOTEUR sur les 5 dernières années
          SELECT INTO r ROUND(SUM(COALESCE(prix_moteur_cdb, 0) + COALESCE(prix_moteur_co, 0) + COALESCE(prix_moteur_elv, 0))/moyenne_sur_nb_annee) AS prix FROM vfr_vol
            WHERE EXTRACT(YEAR FROM date_vol) >= cette_annee - moyenne_sur_nb_annee AND EXTRACT(YEAR FROM date_vol) < cette_annee
            AND EXTRACT(MONTH FROM date_vol) = EXTRACT(MONTH FROM rDate.start)
            AND situation = 'C' AND nom_type_vol IN ('1 Vol en solo', '2 Vol d''instruction', '3 Vol partagé')
            AND categorie != 'U'; -- 'U' = remorqueur 'B' = banalisé (pas les privés)
          valo_cumulMoteur_n_anneesPrecedantes := valo_cumulMoteur_n_anneesPrecedantes + r.prix;
          valo_moteur_n_anneesPrecedantes := array_append(valo_moteur_n_anneesPrecedantes, valo_cumulMoteur_n_anneesPrecedantes);

        -- REVENU FORFAIT
          SELECT INTO r COALESCE(SUM(montant), 0) AS prix FROM pilote
            JOIN cp_piece_ligne li ON li.id_compte = pilote.id_compte
            JOIN cp_piece pi ON pi.id_piece = li.id_piece
            WHERE type = 'FORFAIT' AND LOWER(libelle) NOT LIKE '%treuil%' AND LOWER(libelle) NOT LIKE '%stage%' AND LOWER(libelle) NOT LIKE '%découverte%'
            AND pi.date_piece BETWEEN (CASE WHEN EXTRACT(MONTH FROM rDate.start) = 1 THEN rDate.start - interval '3 months' ELSE rDate.start END) AND rDate.stop;
          valo_cumulForfait := valo_cumulForfait + r.prix;
          IF EXTRACT(MONTH FROM rDate.stop) <= EXTRACT(MONTH FROM last_computation_date) AND rDate.stop::date <= last_computation_date THEN
            valo_forfait := array_append(valo_forfait, valo_cumulForfait);
          END IF;

        -- REVENU FORFAIT sur les 5 dernières années
          SELECT INTO r ROUND(COALESCE(SUM(montant), 0)/moyenne_sur_nb_annee) AS prix FROM pilote
            JOIN cp_piece_ligne li ON li.id_compte = pilote.id_compte
            JOIN cp_piece pi ON pi.id_piece = li.id_piece
            WHERE type = 'FORFAIT' AND LOWER(libelle) NOT LIKE '%treuil%' AND LOWER(libelle) NOT LIKE '%stage%' AND LOWER(libelle) NOT LIKE '%découverte%'
            AND EXTRACT(YEAR FROM pi.date_piece) >= cette_annee - moyenne_sur_nb_annee AND EXTRACT(YEAR FROM pi.date_piece) < cette_annee
            AND EXTRACT(MONTH FROM pi.date_piece) = EXTRACT(MONTH FROM rDate.start);
          valo_cumulForfait_n_anneesPrecedantes := valo_cumulForfait_n_anneesPrecedantes + r.prix;
          valo_forfait_n_anneesPrecedantes := array_append(valo_forfait_n_anneesPrecedantes, valo_cumulForfait_n_anneesPrecedantes);

        -- REVENU CELLULE PILOTES (vols solo, partagés et vi perso)
          SELECT INTO r ROUND(SUM(COALESCE(prix_vol_cdb, 0) + COALESCE(prix_vol_co, 0))) AS prix FROM vfr_vol
            WHERE date_vol BETWEEN rDate.start AND rDate.stop AND situation = 'C' AND nom_type_vol IN ('1 Vol en solo', '3 Vol partagé', '41 VI perso')
            AND categorie != 'U'; -- 'U' = remorqueur 'B' = banalisé (pas les privés)
          valo_cumulCellulePilotes := valo_cumulCellulePilotes + r.prix;
          IF EXTRACT(MONTH FROM rDate.stop) <= EXTRACT(MONTH FROM last_computation_date) AND rDate.stop::date <= last_computation_date THEN
            valo_cellulePilotes := array_append(valo_cellulePilotes, valo_cumulCellulePilotes);
          END IF;

        -- REVENU CELLULE PILOTES (vols solo, partagés et vi perso) sur les 5 dernières années
          SELECT INTO r ROUND(SUM(COALESCE(prix_vol_cdb, 0) + COALESCE(prix_vol_co, 0))/moyenne_sur_nb_annee) AS prix FROM vfr_vol
            WHERE EXTRACT(YEAR FROM date_vol) >= cette_annee - moyenne_sur_nb_annee AND EXTRACT(YEAR FROM date_vol) < cette_annee
            AND EXTRACT(MONTH FROM date_vol) = EXTRACT(MONTH FROM rDate.start)
            AND situation = 'C' AND nom_type_vol IN ('1 Vol en solo', '3 Vol partagé', '41 VI perso')
            AND categorie != 'U'; -- 'U' = remorqueur 'B' = banalisé (pas les privés)
          valo_cumulCellulePilotes_n_anneesPrecedantes := valo_cumulCellulePilotes_n_anneesPrecedantes + r.prix;
          valo_cellulePilotes_n_anneesPrecedantes := array_append(valo_cellulePilotes_n_anneesPrecedantes, valo_cumulCellulePilotes_n_anneesPrecedantes);

        -- REVENU CELLULE INSTRUCTION (vols en instruction)
          SELECT INTO r ROUND(SUM(COALESCE(prix_vol_elv, 0))) AS prix FROM vfr_vol
            WHERE date_vol BETWEEN rDate.start AND rDate.stop AND situation = 'C' AND nom_type_vol = '2 Vol d''instruction'
            AND categorie != 'U'; -- 'U' = remorqueur 'B' = banalisé (pas les privés)
          valo_cumulCelluleInstruction := valo_cumulCelluleInstruction + r.prix;
          IF EXTRACT(MONTH FROM rDate.stop) <= EXTRACT(MONTH FROM last_computation_date) AND rDate.stop::date <= last_computation_date THEN
            valo_celluleInstruction := array_append(valo_celluleInstruction, valo_cumulCelluleInstruction);
          END IF;

        -- REVENU CELLULE INSTRUCTION (vols en instruction)
          SELECT INTO r ROUND(SUM(COALESCE(prix_vol_elv, 0))/moyenne_sur_nb_annee) AS prix FROM vfr_vol
            WHERE EXTRACT(YEAR FROM date_vol) >= cette_annee - moyenne_sur_nb_annee AND EXTRACT(YEAR FROM date_vol) < cette_annee
            AND EXTRACT(MONTH FROM date_vol) = EXTRACT(MONTH FROM rDate.start)
            AND situation = 'C' AND nom_type_vol = '2 Vol d''instruction'
            AND categorie != 'U'; -- 'U' = remorqueur 'B' = banalisé (pas les privés)
          valo_cumulCelluleInstruction_n_anneesPrecedantes := valo_cumulCelluleInstruction_n_anneesPrecedantes + r.prix;
          valo_celluleInstruction_n_anneesPrecedantes := array_append(valo_celluleInstruction_n_anneesPrecedantes, valo_cumulCelluleInstruction_n_anneesPrecedantes);

        -- REVENU VI et JD 1 jour
          SELECT INTO r COALESCE(ROUND(SUM(li.montant)), 0) AS prix FROM cp_piece_ligne li
            JOIN cp_piece pi ON pi.id_piece = li.id_piece
            JOIN cp_compte ON cp_compte.id_compte = li.id_compte AND cp_compte.code LIKE '4%' AND LOWER(cp_compte.libelle) LIKE 'vi club'
              WHERE sens = 'C' AND pi.date_piece BETWEEN rDate.start AND rDate.stop;
          valo_cumulVI := valo_cumulVI + r.prix;
          -- JD 1 jour
          SELECT INTO r COALESCE(SUM(montant), 0) AS prix FROM pilote
            JOIN cp_piece_ligne li ON li.id_compte = pilote.id_compte
            JOIN cp_piece pi ON pi.id_piece = li.id_piece
            WHERE type = 'FORFAIT' AND LOWER(libelle) LIKE '%découverte 1 jour%'
            AND pi.date_piece BETWEEN rDate.start AND rDate.stop;
          valo_cumulVI := valo_cumulVI + r.prix;
          IF EXTRACT(MONTH FROM rDate.stop) <= EXTRACT(MONTH FROM last_computation_date) AND rDate.stop::date <= last_computation_date THEN
            valo_VI := array_append(valo_VI, valo_cumulVI);
          END IF;

        -- REVENU VI et JD 1 jour sur les 5 dernières années
          SELECT INTO r ROUND(COALESCE(SUM(li.montant), 0)) AS prix FROM cp_piece_ligne li
            JOIN cp_piece pi ON pi.id_piece = li.id_piece
            JOIN cp_compte ON cp_compte.id_compte = li.id_compte AND cp_compte.code LIKE '4%' AND LOWER(cp_compte.libelle) LIKE 'vi club'
            WHERE sens = 'C' AND EXTRACT(YEAR FROM li.date_piece) >= cette_annee - moyenne_sur_nb_annee AND EXTRACT(YEAR FROM li.date_piece) < cette_annee
            AND EXTRACT(MONTH FROM li.date_piece) = EXTRACT(MONTH FROM rDate.start);
          valo_cumulVI_n_anneesPrecedantes := valo_cumulVI_n_anneesPrecedantes + r.prix;
          -- JD 1 jour
          SELECT INTO r COALESCE(SUM(montant), 0) AS prix FROM pilote
            JOIN cp_piece_ligne li ON li.id_compte = pilote.id_compte
            JOIN cp_piece pi ON pi.id_piece = li.id_piece
            WHERE type = 'FORFAIT' AND LOWER(libelle) LIKE '%découverte 1 jour%'
            AND EXTRACT(YEAR FROM li.date_piece) >= cette_annee - moyenne_sur_nb_annee AND EXTRACT(YEAR FROM li.date_piece) < cette_annee
            AND EXTRACT(MONTH FROM li.date_piece) = EXTRACT(MONTH FROM rDate.start);
          valo_cumulVI_n_anneesPrecedantes := valo_cumulVI_n_anneesPrecedantes + r.prix;
          valo_VI_n_anneesPrecedantes := array_append(valo_VI_n_anneesPrecedantes, valo_cumulVI_n_anneesPrecedantes);

        -- REVENU JOURNEES DECOUVERTES (2 jours et plus) ET STAGES
          SELECT INTO r COALESCE(SUM(montant), 0) AS prix FROM pilote
            JOIN cp_piece_ligne li ON li.id_compte = pilote.id_compte
            JOIN cp_piece pi ON pi.id_piece = li.id_piece
            WHERE type = 'FORFAIT' AND (LOWER(libelle) LIKE '%stage%' OR LOWER(libelle) LIKE '%découverte%') AND LOWER(libelle) NOT LIKE '%découverte 1 jour'
            AND pi.date_piece BETWEEN rDate.start AND rDate.stop;
          valo_cumulJdStages := valo_cumulJdStages + r.prix;
          IF EXTRACT(MONTH FROM rDate.stop) <= EXTRACT(MONTH FROM last_computation_date) AND rDate.stop::date <= last_computation_date THEN
            valo_jdStages := array_append(valo_jdStages, valo_cumulJdStages);
          END IF;

        -- REVENU JOURNEES DECOUVERTES (2 jours et plus) ET STAGES sur les 5 dernières années
          SELECT INTO r ROUND(COALESCE(SUM(montant), 0)/moyenne_sur_nb_annee) AS prix FROM pilote
            JOIN cp_piece_ligne li ON li.id_compte = pilote.id_compte
            JOIN cp_piece pi ON pi.id_piece = li.id_piece
            WHERE type = 'FORFAIT' AND (LOWER(libelle) LIKE '%stage%' OR LOWER(libelle) LIKE '%découverte%') AND LOWER(libelle) NOT LIKE '%découverte 1 jour'
            AND EXTRACT(YEAR FROM pi.date_piece) >= cette_annee - moyenne_sur_nb_annee AND EXTRACT(YEAR FROM pi.date_piece) < cette_annee
            AND EXTRACT(MONTH FROM pi.date_piece) = EXTRACT(MONTH FROM rDate.start);
          valo_cumulJdStages_n_anneesPrecedantes := valo_cumulJdStages_n_anneesPrecedantes + r.prix;
          valo_jdStages_n_anneesPrecedantes := array_append(valo_jdStages_n_anneesPrecedantes, valo_cumulJdStages_n_anneesPrecedantes);

        -- REVENU LANCEMENT
          SELECT INTO r ROUND(SUM(COALESCE(prix_remorque_cdb, 0) + COALESCE(prix_remorque_co, 0) + COALESCE(prix_remorque_elv, 0) +
            COALESCE(prix_treuil_cdb, 0) + COALESCE(prix_treuil_co, 0) + COALESCE(prix_treuil_elv, 0))) AS prix FROM vfr_vol
            WHERE date_vol BETWEEN rDate.start AND rDate.stop;
          valo_cumulLancement := valo_cumulLancement + r.prix;
          IF EXTRACT(MONTH FROM rDate.stop) <= EXTRACT(MONTH FROM last_computation_date) AND rDate.stop::date <= last_computation_date THEN
            valo_lancement := array_append(valo_lancement, valo_cumulLancement);
          END IF;

        -- REVENU LANCEMENT sur les 5 dernières années
          SELECT INTO r ROUND((SUM(COALESCE(prix_remorque_cdb, 0) + COALESCE(prix_remorque_co, 0) + COALESCE(prix_remorque_elv, 0) +
            COALESCE(prix_treuil_cdb, 0) + COALESCE(prix_treuil_co, 0) + COALESCE(prix_treuil_elv, 0)))/moyenne_sur_nb_annee) AS prix FROM vfr_vol
            WHERE EXTRACT(YEAR FROM date_vol) >= cette_annee - moyenne_sur_nb_annee AND EXTRACT(YEAR FROM date_vol) < cette_annee
            AND EXTRACT(MONTH FROM date_vol) = EXTRACT(MONTH FROM rDate.start);
          valo_cumulLancement_n_anneesPrecedantes := valo_cumulLancement_n_anneesPrecedantes + r.prix;
          valo_lancement_n_anneesPrecedantes := array_append(valo_lancement_n_anneesPrecedantes, valo_cumulLancement_n_anneesPrecedantes);

    -- ============ DEPENSES ACTIVITES GENERALES ============
    SELECT INTO r COALESCE(SUM(CASE WHEN sens = 'C' THEN -montant ELSE montant END), 0) AS somme
      FROM cp_piece_ligne ligne
      JOIN cp_piece piece ON ligne.id_piece = piece.id_piece
      JOIN cp_compte ON cp_compte.id_compte = ligne.id_compte
      WHERE ligne.operation IS NOT NULL AND cp_compte.libelle IN ('Carburant Véhicule', 'Frais Fuel Chauffage',
      'Fournitures d''atelier', 'Fournitures de bureau', 'Achats de matériels, équipements et travaux',
      'Fournitures non stockables- Eau - Electricité', 'Fournitures administratives',
      'Fournitures & Produits netoyage des locaux', 'Achats de marchandises Diverses',
      'Locations immobilières', 'Simulateur', 'Entretien Radios', 'Maintenance - Ménage des Locaux',
      'Assurance - Matériel Roulant (MAIF)', 'Assurances Locaux & Aérodrome',
      'Assurance Véhicule piste', 'Frais de formation interne', 'Honoraires', 'Divers', 'Cadeaux, lots pour concours etc...',
      'Publicité, Publications', 'Foires et expositions', 'Voyages et Frais déplacements',
      'Réceptions', 'Téléphone  - Liaisons informatiques ou spécialisées', 'Abonnement Logiciel Informatique',
      'Abonnement informatique divers',
      'Affranchissements', 'Téléphone', 'Frais bancaires (abonnements, CB, frais virement, etc...)',
      'frais bancaires réglement CB', 'Frais bancaire site WEEZEVENT', 'Cotisations diverses (Comité extérieur, FFVP, foyer, GIVAV etc...)',
      'Taxe sur les salaires', 'Participation des employeurs à la formation professionnelle continue', 'Taxes foncières',
      'Autres impôts, taxes et versements assimilés (autres organismes)', 'Salaires, appointements',
      'Congés payés', 'Primes et gratifications', 'Indemnités et avantages divers', 'Cotisations à l''URSSAF',
      'Cotisations Mutuelle', 'Cotisations aux caisses de retraites et de prévoyance -', 'Cotisation aux ASSEDIC',
      'AMETIF - Médecine de travail, pharmacie', 'frais de formation personnel', 'Indemnité service civique',
      'Gestion des écarts', 'Coûts des V.I. Cadeau', 'Coût des Vols d''essai', 'Coût des Vols d''épreuve',
      'Coût des V.I. Promo Lycées & Collèges', 'Coût des VI  Armée', 'Coût des VI VIP', 'Coût des Vols BPP',
      'Instructeur - Treuillard', 'Total Autres Charges de gestion', 'Amortissement Matériel Roulant',
      'Amortissement radios & Equipements', 'Amortissement regelcoatage des planeurs', 'Amortissement Matériel de sécurité',
      'Dotations aux provisions charges d''exploitation', 'Dotations aux provisions pour risques et charges exceptionnels') AND ligne.date_piece BETWEEN rDate.start AND rDate.stop;

    depenses_generales_cumul := depenses_generales_cumul + r.somme;
    IF EXTRACT(MONTH FROM rDate.stop) <= EXTRACT(MONTH FROM last_computation_date) AND rDate.stop::date <= last_computation_date THEN
      depenses_generales := array_append(depenses_generales, depenses_generales_cumul);
    END IF;


    SELECT INTO r COALESCE(SUM(CASE WHEN sens = 'C' THEN -montant ELSE montant END), 0)/moyenne_sur_nb_annee AS somme
      FROM cp_piece_ligne ligne
      JOIN cp_piece piece ON ligne.id_piece = piece.id_piece
      JOIN cp_compte ON cp_compte.id_compte = ligne.id_compte
      WHERE ligne.operation IS NOT NULL AND cp_compte.libelle IN ('Carburant Véhicule', 'Frais Fuel Chauffage',
      'Fournitures d''atelier', 'Fournitures de bureau', 'Achats de matériels, équipements et travaux',
      'Fournitures non stockables- Eau - Electricité', 'Fournitures administratives',
      'Fournitures & Produits netoyage des locaux', 'Achats de marchandises Diverses',
      'Locations immobilières', 'Simulateur', 'Entretien Radios', 'Maintenance - Ménage des Locaux',
      'Assurance - Matériel Roulant (MAIF)', 'Assurances Locaux & Aérodrome',
      'Assurance Véhicule piste', 'Frais de formation interne', 'Honoraires', 'Divers', 'Cadeaux, lots pour concours etc...',
      'Publicité, Publications', 'Foires et expositions', 'Voyages et Frais déplacements',
      'Réceptions', 'Téléphone  - Liaisons informatiques ou spécialisées', 'Abonnement Logiciel Informatique',
      'Abonnement informatique divers',
      'Affranchissements', 'Téléphone', 'Frais bancaires (abonnements, CB, frais virement, etc...)',
      'frais bancaires réglement CB', 'Frais bancaire site WEEZEVENT', 'Cotisations diverses (Comité extérieur, FFVP, foyer, GIVAV etc...)',
      'Taxe sur les salaires', 'Participation des employeurs à la formation professionnelle continue', 'Taxes foncières',
      'Autres impôts, taxes et versements assimilés (autres organismes)', 'Salaires, appointements',
      'Congés payés', 'Primes et gratifications', 'Indemnités et avantages divers', 'Cotisations à l''URSSAF',
      'Cotisations Mutuelle', 'Cotisations aux caisses de retraites et de prévoyance -', 'Cotisation aux ASSEDIC',
      'AMETIF - Médecine de travail, pharmacie', 'frais de formation personnel', 'Indemnité service civique',
      'Gestion des écarts', 'Coûts des V.I. Cadeau', 'Coût des Vols d''essai', 'Coût des Vols d''épreuve',
      'Coût des V.I. Promo Lycées & Collèges', 'Coût des VI  Armée', 'Coût des VI VIP', 'Coût des Vols BPP',
      'Instructeur - Treuillard', 'Total Autres Charges de gestion',
      'Amortissement Matériel Roulant', 'Amortissement radios & Equipements', 'Amortissement regelcoatage des planeurs', 'Amortissement Matériel de sécurité',
      'Dotations aux provisions charges d''exploitation', 'Dotations aux provisions pour risques et charges exceptionnels')
        AND EXTRACT(YEAR FROM ligne.date_piece) >= cette_annee - moyenne_sur_nb_annee
        AND EXTRACT(YEAR FROM ligne.date_piece) < cette_annee
        AND EXTRACT(MONTH FROM ligne.date_piece) = EXTRACT(MONTH FROM rDate.start);
    depenses_generales_cumul_n_anneesPrecedantes := depenses_generales_cumul_n_anneesPrecedantes + r.somme;
    depenses_generales_n_anneesPrecedantes := array_append(depenses_generales_n_anneesPrecedantes, depenses_generales_cumul_n_anneesPrecedantes);

      -- ============ DEPENSES ENVOLS ============
    SELECT INTO r COALESCE(SUM(CASE WHEN sens = 'C' THEN -montant ELSE montant END), 0) AS somme
      FROM cp_piece_ligne ligne
      JOIN cp_piece piece ON ligne.id_piece = piece.id_piece
      JOIN cp_compte ON cp_compte.id_compte = ligne.id_compte
      WHERE ligne.operation IS NOT NULL AND cp_compte.libelle IN ('Carburant  avion', 'Huile moteur avion',
      'Location Matériel aéronautique', 'Entretien planeurs',
      'GNAV, OSAC & taxes diverses planeurs', 'Entretien Remorqueurs', 'Fournitures Pièces Remorqueurs',
      'Entretien Avion - WT9', 'Fournitures Pièces Avion - WT9', 'GNAV - Doc & Taxes -  Avion WT9',
      'Fournitures Piéces - Treuil', 'Assurance Accident ANEPVV - Remorqueurs',
      'ANEPVV Prévoyance Remorqueurs', 'Amortissement Moyens de lancement (Avions & Treuil)') AND ligne.date_piece BETWEEN rDate.start AND rDate.stop;
    depenses_moyens_lancement_cumul := depenses_moyens_lancement_cumul + r.somme;
    IF EXTRACT(MONTH FROM rDate.stop) <= EXTRACT(MONTH FROM last_computation_date) AND rDate.stop::date <= last_computation_date THEN
      depenses_moyens_lancement := array_append(depenses_moyens_lancement, depenses_moyens_lancement_cumul);
    END IF;


    SELECT INTO r COALESCE(SUM(CASE WHEN sens = 'C' THEN -montant ELSE montant END), 0) AS somme
      FROM cp_piece_ligne ligne
      JOIN cp_piece piece ON ligne.id_piece = piece.id_piece
      JOIN cp_compte ON cp_compte.id_compte = ligne.id_compte
      WHERE ligne.operation IS NOT NULL AND cp_compte.libelle IN ('Carburant  avion', 'Huile moteur avion',
      'Location Matériel aéronautique', 'Entretien planeurs',
      'GNAV, OSAC & taxes diverses planeurs', 'Entretien Remorqueurs', 'Fournitures Pièces Remorqueurs',
      'Entretien Avion - WT9', 'Fournitures Pièces Avion - WT9', 'GNAV - Doc & Taxes -  Avion WT9',
      'Fournitures Piéces - Treuil', 'Assurance Accident ANEPVV - Remorqueurs',
      'ANEPVV Prévoyance Remorqueurs', 'Amortissement Moyens de lancement (Avions & Treuil)')
        AND EXTRACT(YEAR FROM ligne.date_piece) >= cette_annee - moyenne_sur_nb_annee
        AND EXTRACT(YEAR FROM ligne.date_piece) < cette_annee
        AND EXTRACT(MONTH FROM ligne.date_piece) = EXTRACT(MONTH FROM rDate.start);
    depenses_moyens_lancement_cumul_n_anneesPrecedantes := depenses_moyens_lancement_cumul_n_anneesPrecedantes + r.somme;
    depenses_moyens_lancement_n_anneesPrecedantes := array_append(depenses_moyens_lancement_n_anneesPrecedantes, depenses_moyens_lancement_cumul_n_anneesPrecedantes);

      -- ============ DEPENSES HdV ============
    SELECT INTO r COALESCE(SUM(CASE WHEN sens = 'C' THEN -montant ELSE montant END), 0) AS somme
      FROM cp_piece_ligne ligne
      JOIN cp_piece piece ON ligne.id_piece = piece.id_piece
      JOIN cp_compte ON cp_compte.id_compte = ligne.id_compte
      WHERE ligne.operation IS NOT NULL AND cp_compte.libelle IN ('Fourniture Pièces - SF28', 'GNAV - Doc & Taxes - SF28',
      'Entretien des Remorques', 'Assurance Accident ANEPVV - Planeurs', 'Assurance Accident ANEPVV - SF28',
      'ANEPVV Prévoyance SF28', 'Amortissement Planeurs', 'Amortissement Parachutes', 'Amortissement  regelcoatage des planeurs')
      AND ligne.date_piece BETWEEN rDate.start AND rDate.stop;

    depenses_entretien_planeurs_cumul := depenses_entretien_planeurs_cumul + r.somme;
    IF EXTRACT(MONTH FROM rDate.stop) <= EXTRACT(MONTH FROM last_computation_date) AND rDate.stop::date <= last_computation_date THEN
      depenses_entretien_planeurs := array_append(depenses_entretien_planeurs, depenses_entretien_planeurs_cumul);
    END IF;


    SELECT INTO r COALESCE(SUM(CASE WHEN sens = 'C' THEN -montant ELSE montant END), 0) AS somme
      FROM cp_piece_ligne ligne
      JOIN cp_piece piece ON ligne.id_piece = piece.id_piece
      JOIN cp_compte ON cp_compte.id_compte = ligne.id_compte
      WHERE ligne.operation IS NOT NULL AND cp_compte.libelle IN ('Fourniture Pièces - SF28', 'GNAV - Doc & Taxes - SF28',
      'Entretien des Remorques', 'Assurance Accident ANEPVV - Planeurs', 'Assurance Accident ANEPVV - SF28',
      'ANEPVV Prévoyance SF28', 'Amortissement Planeurs', 'Amortissement Parachutes', 'Amortissement  regelcoatage des planeurs')
        AND EXTRACT(YEAR FROM ligne.date_piece) >= cette_annee - moyenne_sur_nb_annee
        AND EXTRACT(YEAR FROM ligne.date_piece) < cette_annee
        AND EXTRACT(MONTH FROM ligne.date_piece) = EXTRACT(MONTH FROM rDate.start);
    depenses_entretien_planeurs_cumul_n_anneesPrecedantes := depenses_entretien_planeurs_cumul_n_anneesPrecedantes + r.somme;
    depenses_entretien_planeurs_n_anneesPrecedantes := array_append(depenses_entretien_planeurs_n_anneesPrecedantes, depenses_entretien_planeurs_cumul_n_anneesPrecedantes);

      -- ============ DEPENSES MAIRIE ============
    SELECT INTO r COALESCE(SUM(CASE WHEN sens = 'C' THEN -montant ELSE montant END), 0) AS somme
      FROM cp_piece_ligne ligne
      JOIN cp_piece piece ON ligne.id_piece = piece.id_piece
      JOIN cp_compte ON cp_compte.id_compte = ligne.id_compte
      WHERE ligne.operation IS NOT NULL AND cp_compte.libelle IN ('Fournitures de piste', 'Entretien Véhicule de piste & Matériel divers',
      'Entretien des batiments', 'Maintenance - Entretien Pavillon', 'Entretien piste, pompes essence, Matériel hangar')
      AND ligne.date_piece BETWEEN rDate.start AND rDate.stop;

    depenses_mairie_cumul := depenses_mairie_cumul + r.somme;
    IF EXTRACT(MONTH FROM rDate.stop) <= EXTRACT(MONTH FROM last_computation_date) AND rDate.stop::date <= last_computation_date THEN
      depenses_mairie := array_append(depenses_mairie, depenses_mairie_cumul);
    END IF;


    SELECT INTO r COALESCE(SUM(CASE WHEN sens = 'C' THEN -montant ELSE montant END), 0)/moyenne_sur_nb_annee AS somme
      FROM cp_piece_ligne ligne
      JOIN cp_piece piece ON ligne.id_piece = piece.id_piece
      JOIN cp_compte ON cp_compte.id_compte = ligne.id_compte
      WHERE ligne.operation IS NOT NULL AND cp_compte.libelle IN ('Fournitures de piste', 'Entretien Véhicule de piste & Matériel divers',
      'Entretien des batiments', 'Maintenance - Entretien Pavillon', 'Entretien piste, pompes essence, Matériel hangar')
        AND EXTRACT(YEAR FROM ligne.date_piece) >= cette_annee - moyenne_sur_nb_annee
        AND EXTRACT(YEAR FROM ligne.date_piece) < cette_annee
        AND EXTRACT(MONTH FROM ligne.date_piece) = EXTRACT(MONTH FROM rDate.start);
    depenses_mairie_cumul_n_anneesPrecedantes := depenses_mairie_cumul_n_anneesPrecedantes + r.somme;
    depenses_mairie_n_anneesPrecedantes := array_append(depenses_mairie_n_anneesPrecedantes, depenses_mairie_cumul_n_anneesPrecedantes);

      -- ============ REVENUS ACTIVITE GENERALE ============
    SELECT INTO r COALESCE(SUM(CASE WHEN sens = 'D' THEN -montant ELSE montant END), 0) AS somme
      FROM cp_piece_ligne ligne
      JOIN cp_piece piece ON ligne.id_piece = piece.id_piece
      JOIN cp_compte ON cp_compte.id_compte = ligne.id_compte
      WHERE ligne.operation IS NOT NULL AND cp_compte.libelle IN ('Prestations de services', 'Heures de vols planeur hors AAVO',
      'Prestations diverses faites aux membres', 'Prestations diverses à des tiers (travaux effectués pour les clubs, etc...)',
      'VI Club', 'Vente essence Avion', 'Autres produits d''activités annexes', 'Subvention Départementales', 'Subvention - CD3VO',
      'Subvention - CFVP', 'Subvention - A.N.S.', 'Subvention diverses formation (ANS-CFVP-FFVV )',
      'Cotisations', 'Frais Tech', 'Frais Tech Junior 7/7', 'Frais Tech Senior semaine', 'Frais Tech  couple sénior',
      'Frais Tech  couple junior', 'Frais Tech  sénior 1ére inscription à/01 juin', 'Frais Tech  junior 1ére inscription à/01 juin',
      'Frais Tech  sénior  - stage 7 jours consécutifs', 'Frais Tech  junior  - stage 7 jours consécutifs',
      'Frais Tech - couple sénior - 1ére inscription à/01 juin', 'Frais exceptionnel 2 jours max, prix par jour',
      'Forfait découverte', 'Forfait découverte 2 jours', 'Forfait découverte 5 jours',
      'Forfait Loisir Campagne', 'Recette sur manifestation', 'CFE',
      'Refacturation aux clubs et aux membres de fournitures', 'Produits divers', 'Frais hangar & en remorque',
      'Intérêts des comptes financiers débiteurs') AND ligne.date_piece BETWEEN rDate.start AND rDate.stop;

    revenus_generales_cumul := revenus_generales_cumul + r.somme;
    IF EXTRACT(MONTH FROM rDate.stop) <= EXTRACT(MONTH FROM last_computation_date) AND rDate.stop::date <= last_computation_date THEN
      revenus_generales := array_append(revenus_generales, revenus_generales_cumul);
    END IF;


    SELECT INTO r COALESCE(SUM(CASE WHEN sens = 'D' THEN -montant ELSE montant END), 0)/moyenne_sur_nb_annee AS somme
      FROM cp_piece_ligne ligne
      JOIN cp_piece piece ON ligne.id_piece = piece.id_piece
      JOIN cp_compte ON cp_compte.id_compte = ligne.id_compte
      WHERE ligne.operation IS NOT NULL AND cp_compte.libelle IN ('Prestations de services', 'Heures de vols planeur hors AAVO',
      'Prestations diverses faites aux membres', 'Prestations diverses à des tiers (travaux effectués pour les clubs, etc...)',
      'VI Club', 'Vente essence Avion', 'Autres produits d''activités annexes', 'Subvention Départementales', 'Subvention - CD3VO',
      'Subvention - CFVP', 'Subvention - A.N.S.', 'Subvention diverses formation (ANS-CFVP-FFVV )',
      'Cotisations', 'Frais Tech', 'Frais Tech Junior 7/7', 'Frais Tech Senior semaine', 'Frais Tech  couple sénior',
      'Frais Tech  couple junior', 'Frais Tech  sénior 1ére inscription à/01 juin', 'Frais Tech  junior 1ére inscription à/01 juin',
      'Frais Tech  sénior  - stage 7 jours consécutifs', 'Frais Tech  junior  - stage 7 jours consécutifs',
      'Frais Tech - couple sénior - 1ére inscription à/01 juin', 'Frais exceptionnel 2 jours max, prix par jour',
      'Forfait découverte', 'Forfait découverte 2 jours', 'Forfait découverte 5 jours',
      'Forfait Loisir Campagne', 'Recette sur manifestation', 'CFE',
      'Refacturation aux clubs et aux membres de fournitures', 'Produits divers', 'Frais hangar & en remorque',
      'Intérêts des comptes financiers débiteurs')
        AND EXTRACT(YEAR FROM ligne.date_piece) >= cette_annee - moyenne_sur_nb_annee
        AND EXTRACT(YEAR FROM ligne.date_piece) < cette_annee
        AND EXTRACT(MONTH FROM ligne.date_piece) = EXTRACT(MONTH FROM rDate.start);
    revenus_generales_cumul_n_anneesPrecedantes := revenus_generales_cumul_n_anneesPrecedantes + r.somme;
    revenus_generales_n_anneesPrecedantes := array_append(revenus_generales_n_anneesPrecedantes, revenus_generales_cumul_n_anneesPrecedantes);

      -- ============ REVENUS ENVOLS ============
    SELECT INTO r COALESCE(SUM(CASE WHEN sens = 'D' THEN -montant ELSE montant END), 0) AS somme
      FROM cp_piece_ligne ligne
      JOIN cp_piece piece ON ligne.id_piece = piece.id_piece
      JOIN cp_compte ON cp_compte.id_compte = ligne.id_compte
      WHERE ligne.operation IS NOT NULL AND cp_compte.libelle IN ('Remorquage & Dépannage & heures vol d avions', 'Treuillage', 'Amortissement Subvention Treuil') AND ligne.date_piece BETWEEN rDate.start AND rDate.stop;

    revenus_moyens_lancement_cumul := revenus_moyens_lancement_cumul + r.somme;
    IF EXTRACT(MONTH FROM rDate.stop) <= EXTRACT(MONTH FROM last_computation_date) AND rDate.stop::date <= last_computation_date THEN
      revenus_moyens_lancement := array_append(revenus_moyens_lancement, revenus_moyens_lancement_cumul);
    END IF;


    SELECT INTO r COALESCE(SUM(CASE WHEN sens = 'D' THEN -montant ELSE montant END), 0)/moyenne_sur_nb_annee AS somme
      FROM cp_piece_ligne ligne
      JOIN cp_piece piece ON ligne.id_piece = piece.id_piece
      JOIN cp_compte ON cp_compte.id_compte = ligne.id_compte
      WHERE ligne.operation IS NOT NULL AND cp_compte.libelle IN ('Remorquage & Dépannage & heures vol d avions', 'Treuillage', 'Amortissement Subvention Treuil')
        AND EXTRACT(YEAR FROM ligne.date_piece) >= cette_annee - moyenne_sur_nb_annee
        AND EXTRACT(YEAR FROM ligne.date_piece) < cette_annee
        AND EXTRACT(MONTH FROM ligne.date_piece) = EXTRACT(MONTH FROM rDate.start);
    revenus_moyens_lancement_cumul_n_anneesPrecedantes := revenus_moyens_lancement_cumul_n_anneesPrecedantes + r.somme;
    revenus_moyens_lancement_n_anneesPrecedantes := array_append(revenus_moyens_lancement_n_anneesPrecedantes, revenus_moyens_lancement_cumul_n_anneesPrecedantes);

      -- ============ REVENUS HDV ============
    SELECT INTO r COALESCE(SUM(CASE WHEN sens = 'D' THEN -montant ELSE montant END), 0) AS somme
      FROM cp_piece_ligne ligne
      JOIN cp_piece piece ON ligne.id_piece = piece.id_piece
      JOIN cp_compte ON cp_compte.id_compte = ligne.id_compte
      WHERE ligne.operation IS NOT NULL AND cp_compte.libelle IN ('Heures de vol des planeurs', 'Dépannage & Convoyage par route', 'Heures SF28 & WT9', 'Forfait Perfo') AND ligne.date_piece BETWEEN rDate.start AND rDate.stop;
    -- on doit retrancher Achats d'études et prestations de services de ce montant
    SELECT INTO r2 COALESCE(SUM(CASE WHEN sens = 'C' THEN -montant ELSE montant END), 0) AS somme
      FROM cp_piece_ligne ligne
      JOIN cp_piece piece ON ligne.id_piece = piece.id_piece
      JOIN cp_compte ON cp_compte.id_compte = ligne.id_compte
      WHERE ligne.operation IS NOT NULL AND cp_compte.libelle = 'Achats d''études et prestations de services' AND ligne.date_piece BETWEEN rDate.start AND rDate.stop;

    revenus_entretien_planeurs_cumul := revenus_entretien_planeurs_cumul + r.somme - r2.somme;
    IF EXTRACT(MONTH FROM rDate.stop) <= EXTRACT(MONTH FROM last_computation_date) AND rDate.stop::date <= last_computation_date THEN
      revenus_entretien_planeurs := array_append(revenus_entretien_planeurs, revenus_entretien_planeurs_cumul);
    END IF;


    SELECT INTO r COALESCE(SUM(CASE WHEN sens = 'D' THEN -montant ELSE montant END), 0) AS somme
      FROM cp_piece_ligne ligne
      JOIN cp_piece piece ON ligne.id_piece = piece.id_piece
      JOIN cp_compte ON cp_compte.id_compte = ligne.id_compte
      WHERE ligne.operation IS NOT NULL AND cp_compte.libelle IN ('Heures de vol des planeurs', 'Dépannage & Convoyage par route', 'Heures SF28 & WT9', 'Forfait Perfo')
        AND EXTRACT(YEAR FROM ligne.date_piece) >= cette_annee - moyenne_sur_nb_annee
        AND EXTRACT(YEAR FROM ligne.date_piece) < cette_annee
        AND EXTRACT(MONTH FROM ligne.date_piece) = EXTRACT(MONTH FROM rDate.start);
    SELECT INTO r2 COALESCE(SUM(CASE WHEN sens = 'C' THEN -montant ELSE montant END), 0) AS somme
      FROM cp_piece_ligne ligne
      JOIN cp_piece piece ON ligne.id_piece = piece.id_piece
      JOIN cp_compte ON cp_compte.id_compte = ligne.id_compte
      WHERE ligne.operation IS NOT NULL AND cp_compte.libelle = 'Achats d''études et prestations de services'
        AND EXTRACT(YEAR FROM ligne.date_piece) >= cette_annee - moyenne_sur_nb_annee
        AND EXTRACT(YEAR FROM ligne.date_piece) < cette_annee
        AND EXTRACT(MONTH FROM ligne.date_piece) = EXTRACT(MONTH FROM rDate.start);
    revenus_entretien_planeurs_cumul_n_anneesPrecedantes := revenus_entretien_planeurs_cumul_n_anneesPrecedantes + (r.somme - r2.somme)/moyenne_sur_nb_annee;
    revenus_entretien_planeurs_n_anneesPrecedantes := array_append(revenus_entretien_planeurs_n_anneesPrecedantes, revenus_entretien_planeurs_cumul_n_anneesPrecedantes);

      -- ============ REVENUS MAIRIE ============
    SELECT INTO r COALESCE(SUM(CASE WHEN sens = 'D' THEN -montant ELSE montant END), 0) AS somme
      FROM cp_piece_ligne ligne
      JOIN cp_piece piece ON ligne.id_piece = piece.id_piece
      JOIN cp_compte ON cp_compte.id_compte = ligne.id_compte
      WHERE ligne.operation IS NOT NULL AND cp_compte.libelle IN ('Dotation Mairie - Subvention') AND ligne.date_piece BETWEEN rDate.start AND rDate.stop;

    revenus_mairie_cumul := revenus_mairie_cumul + r.somme;
    IF EXTRACT(MONTH FROM rDate.stop) <= EXTRACT(MONTH FROM last_computation_date) AND rDate.stop::date <= last_computation_date THEN
      revenus_mairie := array_append(revenus_mairie, revenus_mairie_cumul);
    END IF;


    SELECT INTO r COALESCE(SUM(CASE WHEN sens = 'D' THEN -montant ELSE montant END), 0)/moyenne_sur_nb_annee AS somme
      FROM cp_piece_ligne ligne
      JOIN cp_piece piece ON ligne.id_piece = piece.id_piece
      JOIN cp_compte ON cp_compte.id_compte = ligne.id_compte
      WHERE ligne.operation IS NOT NULL AND cp_compte.libelle IN ('Dotation Mairie - Subvention')
        AND EXTRACT(YEAR FROM ligne.date_piece) >= cette_annee - moyenne_sur_nb_annee
        AND EXTRACT(YEAR FROM ligne.date_piece) < cette_annee
        AND EXTRACT(MONTH FROM ligne.date_piece) = EXTRACT(MONTH FROM rDate.start);
    revenus_mairie_cumul_n_anneesPrecedantes := revenus_mairie_cumul_n_anneesPrecedantes + r.somme;
    revenus_mairie_n_anneesPrecedantes := array_append(revenus_mairie_n_anneesPrecedantes, revenus_mairie_cumul_n_anneesPrecedantes);

  END LOOP;
  -- ============ ACTIVITES ============
    stats := setVarInData(stats, 'licences', licences);
    stats := setVarInData(stats, 'licences_n_annees_precedantes', licences_n_anneesPrecedantes);
    -- MACHINES CLUB
    stats := setVarInData(stats, 'HDVClubCDB', HDVClubCDB);
    stats := setVarInData(stats, 'HDVClubCDB_n_anneesPrecedantes', HDVClubCDB_n_anneesPrecedantes);
    stats := setVarInData(stats, 'HDVClubInstruction', HDVClubInstruction);
    stats := setVarInData(stats, 'HDVClubInstruction_n_anneesPrecedantes', HDVClubInstruction_n_anneesPrecedantes);
    -- MACHINES BANALISEES
    stats := setVarInData(stats, 'HDVBanaliseCDB', HDVBanaliseCDB);
    stats := setVarInData(stats, 'HDVBanaliseCDB_n_anneesPrecedantes', HDVBanaliseCDB_n_anneesPrecedantes);
    stats := setVarInData(stats, 'HDVBanaliseInstruction', HDVBanaliseInstruction);
    stats := setVarInData(stats, 'HDVBanaliseInstruction_n_anneesPrecedantes', HDVBanaliseInstruction_n_anneesPrecedantes);
    stats := setVarInData(stats, 'HDVBanaliseNonProprietaire', HDVBanaliseNonProprietaire);
    stats := setVarInData(stats, 'HDVBanaliseNonProprietaire_n_anneesPrecedantes', HDVBanaliseNonProprietaire_n_anneesPrecedantes);

    -- HDV CDB PILOTES DANS FORFAIT
    stats := setVarInData(stats, 'HDVPilotesDansForfait', HDVPilotesDansForfait);
    stats := setVarInData(stats, 'HDVPilotesDansForfait_n_anneesPrecedantes', HDVPilotesDansForfait_n_anneesPrecedantes);

    -- HDV CDB PILOTES HORS FORFAIT
    stats := setVarInData(stats, 'HDVPilotesHorsForfait', HDVPilotesHorsForfait);
    stats := setVarInData(stats, 'HDVPilotesHorsForfait_n_anneesPrecedantes', HDVPilotesHorsForfait_n_anneesPrecedantes);

    -- MOYENS DE LANCEMENT
    stats := setVarInData(stats, 'lancementR', lancementR);
    stats := setVarInData(stats, 'lancementRCorrige', lancementRCorrige);
    stats := setVarInData(stats, 'lancementT', lancementT);
    stats := setVarInData(stats, 'lancementA', lancementA);
    stats := setVarInData(stats, 'lancementRCumul', lancementRCumul);
    stats := setVarInData(stats, 'lancementRCorrigeCumul', lancementRCorrigeCumul);
    stats := setVarInData(stats, 'lancementTCumul', lancementTCumul);
    stats := setVarInData(stats, 'lancementACumul', lancementACumul);
    stats := setVarInData(stats, 'lancementR_n_anneesPrecedantes', lancementR_n_anneesPrecedantes);
    stats := setVarInData(stats, 'lancementRCorrige_n_anneesPrecedantes', lancementRCorrige_n_anneesPrecedantes);
    stats := setVarInData(stats, 'lancementT_n_anneesPrecedantes', lancementT_n_anneesPrecedantes);
    stats := setVarInData(stats, 'lancementA_n_anneesPrecedantes', lancementA_n_anneesPrecedantes);
    stats := setVarInData(stats, 'lancementRCumul_n_anneesPrecedantes', lancementRCumul_n_anneesPrecedantes);
    stats := setVarInData(stats, 'lancementRCorrigeCumul_n_anneesPrecedantes', lancementRCorrigeCumul_n_anneesPrecedantes);
    stats := setVarInData(stats, 'lancementTCumul_n_anneesPrecedantes', lancementTCumul_n_anneesPrecedantes);
    stats := setVarInData(stats, 'lancementACumul_n_anneesPrecedantes', lancementACumul_n_anneesPrecedantes);

    -- stats avec clef dynamiques (immatriculation des remorqueurs)
    -- exemple:
   -- "nbRemorquesParRemorqueur": {"F-GEKY": [39, 124, 316, 528, 698, 967, 1283, 1553, 1706], "F-JDTX": [0, 0, 0, 84, 166, 211, 308, 449, 525]}, "valo_revenu_infra_membre": [28534.00, 43385.00, 63576.00, 69559.00, 75333.00, 79530.00, 82308.00, 83894.80]
    remorqueursCount := '{}';
    FOR rRemorqueurs IN SELECT immatriculation FROM remorqueursCount GROUP BY immatriculation ORDER BY immatriculation LOOP
      cumulRemorqueurCount := 0;
      remorqueursCount1 := '{}';
      -- crade :( On doit remplir les mois où il n'y a pas de remorqué et on doit cumuler
      -- compliqué de tout faire avec des CTE, on décompose pour plus de lisibilité
      FOR rMonth IN SELECT GENERATE_SERIES(1, 12) AS month LOOP
        SELECT nb INTO rRemorqueursStats FROM remorqueursCount WHERE immatriculation = rRemorqueurs.immatriculation AND m = rMonth.month;
        IF rMonth.month <= EXTRACT(MONTH FROM last_computation_date) THEN
          IF rRemorqueursStats.nb IS NOT NULL THEN
            cumulRemorqueurCount := cumulRemorqueurCount + rRemorqueursStats.nb;
          END IF;
          remorqueursCount1 := array_append(remorqueursCount1, cumulRemorqueurCount);
        END IF;
      END LOOP;
      remorqueursCount := setVarInData(remorqueursCount, rRemorqueurs.immatriculation, remorqueursCount1);
    END LOOP;
    stats := setVarInData(stats, 'nbRemorquesParRemorqueur', remorqueursCount);

  -- ============ ACTIVITES ============
    stats := setVarInData(stats, 'valo_hdv', valo_hdv);
    stats := setVarInData(stats, 'valo_hdv_n_anneesPrecedantes', valo_hdv_n_anneesPrecedantes);
    -- MOTEUR
    stats := setVarInData(stats, 'valo_moteur', valo_moteur);
    stats := setVarInData(stats, 'valo_moteur_n_anneesPrecedantes', valo_moteur_n_anneesPrecedantes);
    -- FORFAIT
    stats := setVarInData(stats, 'valo_forfait', valo_forfait);
    stats := setVarInData(stats, 'valo_forfait_n_anneesPrecedantes', valo_forfait_n_anneesPrecedantes);
    -- JD ET STAGES
    stats := setVarInData(stats, 'valo_jdStages', valo_jdStages);
    stats := setVarInData(stats, 'valo_jdStages_n_anneesPrecedantes', valo_jdStages_n_anneesPrecedantes);
    -- CELLULE PILOTES
    stats := setVarInData(stats, 'valo_cellulePilotes', valo_cellulePilotes);
    stats := setVarInData(stats, 'valo_cellulePilotes_n_anneesPrecedantes', valo_cellulePilotes_n_anneesPrecedantes);
    -- CELLULE INSTRUCTION
    stats := setVarInData(stats, 'valo_celluleInstruction', valo_celluleInstruction);
    stats := setVarInData(stats, 'valo_celluleInstruction_n_anneesPrecedantes', valo_celluleInstruction_n_anneesPrecedantes);
    -- VI
    stats := setVarInData(stats, 'valo_VI', valo_VI);
    stats := setVarInData(stats, 'valo_VI_n_anneesPrecedantes', valo_VI_n_anneesPrecedantes);
    -- LANCEMENT
    stats := setVarInData(stats, 'valo_lancement', valo_lancement);
    stats := setVarInData(stats, 'valo_lancement_n_anneesPrecedantes', valo_lancement_n_anneesPrecedantes);

    -- ============ DEPENSES GENERALES ============
    stats := setVarInData(stats, 'depenses_generales', depenses_generales);
    stats := setVarInData(stats, 'depenses_generales_n_anneesPrecedantes', depenses_generales_n_anneesPrecedantes);

    -- ============ DEPENSES MOYENS LANCEMENT ============
    stats := setVarInData(stats, 'depenses_moyens_lancement', depenses_moyens_lancement);
    stats := setVarInData(stats, 'depenses_moyens_lancement_n_anneesPrecedantes', depenses_moyens_lancement_n_anneesPrecedantes);

    -- ============ DEPENSES ENTRETIEN PLANEURS ============
    stats := setVarInData(stats, 'depenses_entretien_planeurs', depenses_entretien_planeurs);
    stats := setVarInData(stats, 'depenses_entretien_planeurs_n_anneesPrecedantes', depenses_entretien_planeurs_n_anneesPrecedantes);

    -- ============ DEPENSES MAIRIE ============
    stats := setVarInData(stats, 'depenses_mairie', depenses_mairie);
    stats := setVarInData(stats, 'depenses_mairie_n_anneesPrecedantes', depenses_mairie_n_anneesPrecedantes);

    -- ============ REVENUS GENERALES ============
    stats := setVarInData(stats, 'revenus_generales', revenus_generales);
    stats := setVarInData(stats, 'revenus_generales_n_anneesPrecedantes', revenus_generales_n_anneesPrecedantes);

    -- ============ REVENUS MOYENS LANCEMENT ============
    stats := setVarInData(stats, 'revenus_moyens_lancement', revenus_moyens_lancement);
    stats := setVarInData(stats, 'revenus_moyens_lancement_n_anneesPrecedantes', revenus_moyens_lancement_n_anneesPrecedantes);

    -- ============ REVENUS ENTRETIEN PLANEURS ============
    stats := setVarInData(stats, 'revenus_entretien_planeurs', revenus_entretien_planeurs);
    stats := setVarInData(stats, 'revenus_entretien_planeurs_n_anneesPrecedantes', revenus_entretien_planeurs_n_anneesPrecedantes);

    -- ============ REVENUS MAIRIE ============
    stats := setVarInData(stats, 'revenus_mairie', revenus_mairie);
    stats := setVarInData(stats, 'revenus_mairie_n_anneesPrecedantes', revenus_mairie_n_anneesPrecedantes);

  RETURN stats;
END;
$$ LANGUAGE plpgsql VOLATILE;


CREATE OR REPLACE FUNCTION statsAuCoursAnnee(annee int) RETURNS TABLE (
  d DATE,
  stats JSONB
  ) AS $$
DECLARE
  r RECORD;
  r2 RECORD;
  r_vol RECORD;
  sub_json JSONB;
  mise_en_l_air TEXT;
  mises_en_l_air TEXT[] := '{"R", "T", "M"}';
BEGIN
  FOR r IN SELECT t AS start, t + interval '1 month' AS stop FROM generate_series(DATE_TRUNC('day', make_date(annee, 1, 1)), make_date(annee, 12, 31), interval '1 month') AS t(day)
  LOOP
    d := r.start;
    stats := '{}';

    IF EXTRACT(YEAR FROM d) = EXTRACT(YEAR FROM now()) THEN
      SELECT INTO r2 COUNT(*) AS nb FROM pilote WHERE licence_debut >= r.start AND licence_debut < r.stop AND licence_nom = 'Passion -25 ans (Annuelle)';
      sub_json := '{}';
      sub_json := setVarInData(sub_json, 'nb_licence_moins25', r2.nb);
      SELECT INTO r2 COUNT(*) AS nb FROM pilote WHERE licence_debut >= r.start AND licence_debut < r.stop AND licence_nom = 'Passion +25 ans (Annuelle)';
      sub_json := setVarInData(sub_json, 'nb_licence_plus25', r2.nb);
      sub_json := setVarInData(sub_json, 'nb_licence', (sub_json->>'nb_licence_moins25')::numeric + (sub_json->>'nb_licence_plus25')::numeric);
      stats := setVarInData(stats, 'licence', sub_json);
    END IF;


    SELECT INTO r_vol COUNT(*) AS nb_vol, SUM(temps_vol) AS temps_vol,
      SUM(COALESCE(prix_vol_elv, 0)) + SUM(COALESCE(prix_treuil_elv, 0)) + SUM(COALESCE(prix_remorque_elv, 0)) + SUM(COALESCE(prix_moteur_elv, 0)) +
      SUM(COALESCE(prix_vol_cdb, 0)) + SUM(COALESCE(prix_treuil_cdb, 0)) + SUM(COALESCE(prix_remorque_cdb, 0)) + SUM(COALESCE(prix_moteur_cdb, 0)) +
      SUM(COALESCE(prix_vol_co, 0)) + SUM(COALESCE(prix_treuil_co, 0)) + SUM(COALESCE(prix_remorque_co, 0)) + SUM(COALESCE(prix_moteur_co, 0)) AS ca FROM vfr_vol
      WHERE date_vol >= r.start AND date_vol < r.stop;
    sub_json := '{}';
    sub_json := setVarInData(sub_json, 'nb_vol', r_vol.nb_vol);
    sub_json := setVarInData(sub_json, 'temps_vol', r_vol.temps_vol);
    sub_json := setVarInData(sub_json, 'ca', r_vol.ca);
    stats := setVarInData(stats, 'global', sub_json);


    FOR r2 IN SELECT vfr_vol.nom_type_vol FROM vfr_vol WHERE date_vol >= r.start AND date_vol < r.stop GROUP BY vfr_vol.nom_type_vol
    LOOP
      SELECT INTO r_vol COUNT(*) AS nb_vol, SUM(temps_vol) AS temps_vol,
        SUM(COALESCE(prix_vol_elv, 0)) + SUM(COALESCE(prix_treuil_elv, 0)) + SUM(COALESCE(prix_remorque_elv, 0)) + SUM(COALESCE(prix_moteur_elv, 0)) +
        SUM(COALESCE(prix_vol_cdb, 0)) + SUM(COALESCE(prix_treuil_cdb, 0)) + SUM(COALESCE(prix_remorque_cdb, 0)) + SUM(COALESCE(prix_moteur_cdb, 0)) +
        SUM(COALESCE(prix_vol_co, 0)) + SUM(COALESCE(prix_treuil_co, 0)) + SUM(COALESCE(prix_remorque_co, 0)) + SUM(COALESCE(prix_moteur_co, 0)) AS ca FROM vfr_vol
        WHERE date_vol >= r.start AND date_vol < r.stop AND vfr_vol.nom_type_vol = r2.nom_type_vol;
      sub_json := '{}';
      sub_json := setVarInData(sub_json, 'nb_vol', r_vol.nb_vol);
      sub_json := setVarInData(sub_json, 'temps_vol', r_vol.temps_vol);
      sub_json := setVarInData(sub_json, 'ca', r_vol.ca);
      stats := setVarInData(stats, r2.nom_type_vol, sub_json);
    END LOOP;

    FOREACH mise_en_l_air IN ARRAY mises_en_l_air
    LOOP
      SELECT INTO r_vol COUNT(*) AS nb_vol, SUM(temps_vol) AS temps_vol,
        SUM(COALESCE(prix_vol_elv, 0)) + SUM(COALESCE(prix_treuil_elv, 0)) + SUM(COALESCE(prix_remorque_elv, 0)) + SUM(COALESCE(prix_moteur_elv, 0)) +
        SUM(COALESCE(prix_vol_cdb, 0)) + SUM(COALESCE(prix_treuil_cdb, 0)) + SUM(COALESCE(prix_remorque_cdb, 0)) + SUM(COALESCE(prix_moteur_cdb, 0)) +
        SUM(COALESCE(prix_vol_co, 0)) + SUM(COALESCE(prix_treuil_co, 0)) + SUM(COALESCE(prix_remorque_co, 0)) + SUM(COALESCE(prix_moteur_co, 0)) AS ca FROM vfr_vol
        WHERE date_vol >= r.start AND date_vol < r.stop AND vfr_vol.mode_decollage = mise_en_l_air;
      sub_json := '{}';
      sub_json := setVarInData(sub_json, 'nb_vol', r_vol.nb_vol);
      sub_json := setVarInData(sub_json, 'temps_vol', r_vol.temps_vol);
      sub_json := setVarInData(sub_json, 'ca', r_vol.ca);
      stats := setVarInData(stats, mise_en_l_air, sub_json);
    END LOOP;

    return NEXT;
  END LOOP;
END;
$$ LANGUAGE plpgsql VOLATILE;

-- anonymisation de vfr_vol
CREATE OR REPLACE FUNCTION anonymisationVol(annee INT, avec_anonymisation BOOLEAN) RETURNS TABLE (
  id BIGINT,
  aeronef VARCHAR,
  date_vol DATE,
  prix_vol NUMERIC,
  prix_remorque NUMERIC,
  prix_treuil NUMERIC,
  prix_moteur NUMERIC,
  mode_decollage CHARACTER,
  libelle_remorque VARCHAR,
  pilote_remorqueur VARCHAR,
  cat_age_pilote_remorqueur VARCHAR,
  treuilleur VARCHAR,
  cat_age_treuilleur VARCHAR,
  instructeur VARCHAR,
  eleve VARCHAR,
  cat_age_eleve VARCHAR,
  frais_technique_eleve VARCHAR,
  prix_frais_technique_eleve NUMERIC,
  prix_vol_elv NUMERIC,
  prix_treuil_elv NUMERIC,
  prix_remorque_elv NUMERIC,
  prix_moteur_elv NUMERIC,
  cdt_de_bord VARCHAR,
  cat_age_cdb VARCHAR,
  frais_technique_cdb VARCHAR,
  prix_frais_technique_cdb NUMERIC,
  prix_vol_cdb NUMERIC,
  prix_treuil_cdb NUMERIC,
  prix_remorque_cdb NUMERIC,
  prix_moteur_cdb NUMERIC,
  co_pilote VARCHAR,
  cat_age_co VARCHAR,
  frais_technique_co VARCHAR,
  prix_frais_technique_co NUMERIC,
  prix_vol_co NUMERIC,
  prix_treuil_co NUMERIC,
  prix_remorque_co NUMERIC,
  prix_moteur_co NUMERIC,
  nom_type_vol VARCHAR,
  temps_vol INTERVAL,
  immatriculation_remorqueur VARCHAR,
  vol_est_dans_forfait BOOLEAN,
  prix_vol_sans_forfait NUMERIC,
  proprietaire_vole_sur_sa_machine BOOLEAN,
  machine_privee BOOLEAN,
  prix_vol_si_la_machine_etait_club NUMERIC -- il s'agit du prix de la cellule (équivalent à prix_vol_cdb)
  ) AS $$
DECLARE
  r RECORD;
  r2 RECORD;
  i INT;
  chars TEXT[] := '{0,1,2,3,4,5,6,7,8,9,a,b,c,d,e,f}';
  salt TEXT;
BEGIN
  salt := '';
  FOR i IN 1..16 LOOP
    salt := salt || chars[1+random()*(array_length(chars, 1)-1)];
  END LOOP;

  i := 1;
  FOR r IN SELECT vfr_vol.*,
    instructeur_age.date_naissance AS instructeur_date_naissance,
    eleve_age.date_naissance AS eleve_date_naissance,
    cdb_age.date_naissance AS cdb_date_naissance,
    co_age.date_naissance AS co_date_naissance,
    remorqueur_age.date_naissance AS remorqueur_date_naissance,
    treuilleur_age.date_naissance AS treuilleur_date_naissance
    FROM vfr_vol
    LEFT JOIN gv_personne instructeur_age ON instructeur_age.id_personne = vfr_vol.id_instructeur
    LEFT JOIN gv_personne eleve_age ON eleve_age.id_personne = vfr_vol.id_eleve
    LEFT JOIN gv_personne cdb_age ON cdb_age.id_personne = vfr_vol.id_cdt_de_bord
    LEFT JOIN gv_personne co_age ON co_age.id_personne = vfr_vol.id_co_pilote
    LEFT JOIN gv_personne remorqueur_age ON remorqueur_age.id_personne = vfr_vol.id_pilote_remorqueur
    LEFT JOIN gv_personne treuilleur_age ON treuilleur_age.id_personne = vfr_vol.id_treuilleur
    WHERE saison = annee AND situation IN ('B', 'C') ORDER BY date_vol ASC, decollage ASC
  LOOP
    IF avec_anonymisation IS false THEN
      id := r.id_vol;
    ELSE
      id := i;
    END IF;
    i := i + 1;
    aeronef := r.immatriculation;
    IF avec_anonymisation IS false THEN
      date_vol := r.date_vol;
    ELSE
      date_vol := DATE_TRUNC('month', r.date_vol);
    END IF;
    prix_vol := r.prix_vol;
    prix_remorque := r.prix_remorque;
    prix_treuil := r.prix_treuil;
    prix_moteur := r.prix_moteur;
    mode_decollage := r.mode_decollage;
    libelle_remorque := r.libelle_remorque;
    IF r.pilote_remorqueur IS NOT NULL AND avec_anonymisation IS true THEN
      pilote_remorqueur := SUBSTRING(MD5(salt || r.pilote_remorqueur), 1, 6);
    ELSE
      pilote_remorqueur := r.pilote_remorqueur;
    END IF;
    IF r.remorqueur_date_naissance IS NOT NULL THEN
      IF date_part('year', now()) - date_part('year', r.remorqueur_date_naissance) > 25 THEN
        cat_age_pilote_remorqueur := '+25 ans';
      ELSE
        cat_age_pilote_remorqueur := '-25 ans';
      END IF;
    END IF;

    IF r.treuilleur IS NOT NULL AND avec_anonymisation IS true THEN
      treuilleur := SUBSTRING(MD5(salt || r.treuilleur), 1, 6);
    ELSE
      treuilleur := r.treuilleur;
    END IF;
    IF r.treuilleur_date_naissance IS NOT NULL THEN
      IF date_part('year', now()) - date_part('year', r.treuilleur_date_naissance) > 25 THEN
        cat_age_treuilleur := '+25 ans';
      ELSE
        cat_age_treuilleur := '-25 ans';
      END IF;
    END IF;
    instructeur := r.instructeur;

    IF r.eleve IS NOT NULL AND avec_anonymisation IS true THEN
      eleve := SUBSTRING(MD5(salt || r.eleve), 1, 6);
    ELSE
      eleve := r.eleve;
    END IF;
    IF r.eleve_date_naissance IS NOT NULL THEN
      IF date_part('year', now()) - date_part('year', r.eleve_date_naissance) > 25 THEN
        cat_age_eleve := '+25 ans';
      ELSE
        cat_age_eleve := '-25 ans';
      END IF;
    END IF;
    prix_vol_elv := r.prix_vol_elv;
    prix_treuil_elv := r.prix_treuil_elv;
    prix_remorque_elv := r.prix_remorque_elv;
    prix_moteur_elv := r.prix_moteur_elv;
    IF r.cdt_de_bord IS NOT NULL AND avec_anonymisation IS true THEN
      cdt_de_bord := SUBSTRING(MD5(salt || r.cdt_de_bord), 1, 6);
    ELSE
      cdt_de_bord := r.cdt_de_bord;
    END IF;
    IF r.cdb_date_naissance IS NOT NULL THEN
      IF date_part('year', now()) - date_part('year', r.cdb_date_naissance) > 25 THEN
        cat_age_cdb := '+25 ans';
      ELSE
        cat_age_cdb := '-25 ans';
      END IF;
    END IF;
    prix_vol_cdb := r.prix_vol_cdb;
    prix_remorque_cdb := r.prix_remorque_cdb;
    prix_treuil_cdb := r.prix_treuil_cdb;
    prix_moteur_cdb := r.prix_moteur_cdb;
    IF r.co_pilote IS NOT NULL AND avec_anonymisation IS true THEN
      co_pilote := SUBSTRING(MD5(salt || r.co_pilote), 1, 6);
    ELSE
      co_pilote := r.co_pilote;
    END IF;
    IF r.co_date_naissance IS NOT NULL THEN
      IF date_part('year', now()) - date_part('year', r.co_date_naissance) > 25 THEN
        cat_age_co := '+25 ans';
      ELSE
        cat_age_co := '-25 ans';
      END IF;
    END IF;
    prix_vol_co := r.prix_vol_co;
    prix_remorque_co := r.prix_remorque_co;
    prix_treuil_co := r.prix_treuil_co;
    prix_moteur_co := r.prix_moteur_co;

    -- frais technique
    IF r.id_eleve IS NOT NULL THEN
      SELECT INTO r2 cp_compte.libelle, li.montant FROM pilote
        JOIN cp_piece_ligne li ON li.id_compte = pilote.id_compte
        JOIN cp_piece pi ON pi.id_piece = li.id_piece
        JOIN cp_piece_ligne li2 ON li2.id_piece = pi.id_piece
        JOIN cp_compte ON cp_compte.id_compte = li2.id_compte AND cp_compte.code LIKE '7561%'
        WHERE pilote.id_personne = r.id_eleve AND
        pi.date_echeance BETWEEN make_date(annee-1, 10, 01) AND make_date(annee, 12, 31) LIMIT 1;
      frais_technique_eleve = r2.libelle;
      prix_frais_technique_eleve = r2.montant;
    END IF;
    IF r.id_cdt_de_bord IS NOT NULL THEN
      SELECT INTO r2 cp_compte.libelle, li.montant FROM pilote
        JOIN cp_piece_ligne li ON li.id_compte = pilote.id_compte
        JOIN cp_piece pi ON pi.id_piece = li.id_piece
        JOIN cp_piece_ligne li2 ON li2.id_piece = pi.id_piece
        JOIN cp_compte ON cp_compte.id_compte = li2.id_compte AND cp_compte.code LIKE '7561%'
        WHERE pilote.id_personne = r.id_cdt_de_bord AND
        pi.date_echeance BETWEEN make_date(annee-1, 10, 01) AND make_date(annee, 12, 31) LIMIT 1;
      frais_technique_cdb = r2.libelle;
      prix_frais_technique_cdb = r2.montant;
    END IF;
    IF r.id_co_pilote IS NOT NULL THEN
      SELECT INTO r2 cp_compte.libelle, li.montant FROM pilote
        JOIN cp_piece_ligne li ON li.id_compte = pilote.id_compte
        JOIN cp_piece pi ON pi.id_piece = li.id_piece
        JOIN cp_piece_ligne li2 ON li2.id_piece = pi.id_piece
        JOIN cp_compte ON cp_compte.id_compte = li2.id_compte AND cp_compte.code LIKE '7561%'
        WHERE pilote.id_personne = r.id_co_pilote AND
        pi.date_echeance BETWEEN make_date(annee-1, 10, 01) AND make_date(annee, 12, 31) LIMIT 1;
      frais_technique_co = r2.libelle;
      prix_frais_technique_co = r2.montant;
    END IF;
    nom_type_vol := r.nom_type_vol;
    temps_vol := r.temps_vol;
    immatriculation_remorqueur := r.immatriculation_remorqueur;

    -- est-ce que le pilote a volé sur une machine compris dans son forfait ?
    vol_est_dans_forfait := false;
    prix_vol_sans_forfait := 0;
    IF r.id_cdt_de_bord IS NOT NULL THEN
      SELECT INTO r2 * FROM vfr_forfait_pilote
        JOIN vfr_gv_personne ON vfr_forfait_pilote.id_personne = r.id_cdt_de_bord
        WHERE EXTRACT(YEAR FROM vfr_forfait_pilote.date_debut) = EXTRACT(YEAR FROM r.date_vol)
          AND vfr_forfait_pilote.hrs_cellule = '999:00:00'
          AND NOT EXISTS (SELECT 1 FROM forfait_modele_aeronef_exclu WHERE forfait_modele_aeronef_exclu.id_aeronef = r.id_aeronef AND forfait_modele_aeronef_exclu.id_forfait_modele = vfr_forfait_pilote.id_forfait_modele) LIMIT 1;
      IF FOUND THEN
        --DEBUG RAISE NOTICE '% % % %', r.date_vol, r.cdt_de_bord, r.nom_type_vol, r2;
        --DEBUG RAISE NOTICE 'id_cdt_de_bord: % id_aeronef: %', r.id_cdt_de_bord, r.id_aeronef;
        vol_est_dans_forfait := true;
        -- si c'est le cas quel serait le prix du vol si le pilote n'avait
        -- pas de forfait ?
        prix_vol_sans_forfait := calculPrixVol(r.id_cdt_de_bord, r.id_aeronef, r.date_vol, r.id_tarif_type_vol, r.temps_vol);
      END IF;
    END IF;

    -- prix du vol pour les propriétaires (certains proprietaires n'ont pas de retrocession, donc pas de prix de cellule), on le calcule pour faire des stats
    IF r.id_cdt_de_bord IS NOT NULL THEN
      SELECT INTO r2 piloteEstProprietaireDeMachine(r.id_cdt_de_bord, r.id_aeronef, r.date_vol) AS estProprietaire;
    ELSIF r.id_eleve IS NOT NULL THEN
      SELECT INTO r2 piloteEstProprietaireDeMachine(r.id_eleve, r.id_aeronef, r.date_vol) AS estProprietaire;
    END IF;
    --DEBUG RAISE NOTICE '% % % %', aeronef, r.date_vol, r.cdt_de_bord, r2;
    prix_vol_si_la_machine_etait_club := 0; -- par defaut le prix est a 0
    proprietaire_vole_sur_sa_machine := false;
    IF r2.estProprietaire is true THEN
      proprietaire_vole_sur_sa_machine := true;
      prix_vol_si_la_machine_etait_club := calculPrixVol(r.id_cdt_de_bord, r.id_aeronef, r.date_vol, r.id_tarif_type_vol, r.temps_vol);
    END IF;
    IF r.situation = 'C' THEN
      machine_privee = false;
    ELSE
      machine_privee = true;
    END IF;
    return NEXT;
  END LOOP;
END;
$$ LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION simulationParcVolant() RETURNS TABLE (
  membre VARCHAR,
  categorie VARCHAR,
  fraisTechnique DECIMAL,
  typeFraisTechnique VARCHAR,
  forfait DECIMAL,
  nomForfait VARCHAR,
  HDVSurMachineHorsClub DECIMAL, -- heures de vol payées en 2024 sur machine hors club (les propriétaires qui volent sur leurs machines ont 0)
  HDVSurMachineClub DECIMAL, -- heures de vol école à payer avec parc volant
  HDVEnEcole DECIMAL, -- heures de vol école à payer avec parc volant
  remorques DECIMAL,
  treuillees DECIMAL,
  moteur DECIMAL
  ) AS $$
DECLARE
   rMembre RECORD;
   rVol RECORD;
   rTest RECORD;
   estInstructeur BOOLEAN;
   estProprietaireOuSection BOOLEAN;
   estEleve BOOLEAN;
   estStagiaire BOOLEAN;
   aSPLPlus25 BOOLEAN;
   aSPLMoins25 BOOLEAN;
   annee INT;
BEGIN
  annee := 2024;
  FOR rMembre IN SELECT * FROM vfr_pilote WHERE pilote_actif IS true AND LOWER(licence_nom) NOT LIKE '%découverte%' AND club_nom LIKE '%AAVO%' ORDER BY nom LOOP
    -- reset
    estInstructeur := false;
    estProprietaireOuSection := false;
    estEleve := false;
    estStagiaire := false;
    aSPLMoins25 := false;
    aSPLPlus25 := false;
    categorie := NULL;
    fraisTechnique := NULL;
    typeFraisTechnique := NULL;
    forfait := NULL;
    nomForfait := NULL;
    nomForfait := NULL;
    HDVSurMachineHorsClub := NULL;
    HDVSurMachineClub := NULL;
    HDVEnEcole := NULL;
    remorques := NULL;
    treuillees := NULL;
    moteur := NULL;
    membre := CONCAT(rMembre.nom, ' ', rMembre.prenom);
    -- est-il instructeur ?
    IF rMembre.instructeur_actif IS true THEN
      RAISE NOTICE '% est instructeur (vfr_pilote(id_pilote=%).instructeur_actif=true)', membre, rMembre.id_pilote;
      estInstructeur := true;
    END IF;
    -- est-il élève ?
    IF rMembre.lp_date IS NOT NULL THEN
      RAISE NOTICE '% a une SPL (vfr_pilote(id_pilote=%).lp_date=%)', membre, rMembre.id_pilote, rMembre.lp_date;
      IF rMembre.cat_age = '-25 ans' THEN
        aSPLMoins25 := true;
      ELSE
        aSPLPlus25 := true;
      END IF;
    ELSE
      RAISE NOTICE '% est élève (vfr_pilote(id_pilote=%).lp_date=NULL)', membre, rMembre.id_pilote;
      estEleve := true;
    END IF;
    -- est-il stagiaire ?
    SELECT nom_forfait, montant INTO rTest FROM vfr_forfait_pilote
      JOIN vfr_gv_personne ON vfr_forfait_pilote.id_personne = rMembre.id_personne
      WHERE EXTRACT(YEAR FROM vfr_forfait_pilote.date_debut) = EXTRACT(YEAR FROM NOW())
        AND LOWER(vfr_forfait_pilote.nom_forfait) LIKE '%stage%';
    IF FOUND THEN
      RAISE NOTICE '% est stagiaire (vfr_forfait_pilote(id_personne=%)%)', membre, rMembre.id_personne, rTest.nom_forfait;
      estStagiaire := true;
      forfait := rTest.montant;
      nomForfait := rTest.nom_forfait;
    END IF;
    -- fait-il partie de l'ANEG ou de CORMEILLES ?
    IF rMembre.tarif LIKE '%EGF%' OR rMembre.tarif LIKE '%CORMEILLES%' THEN
      RAISE NOTICE '% est section (vfr_pilote(id_pilote=%).tarif=%)', membre, rMembre.id_pilote, rMembre.tarif;
      estProprietaireOuSection := true;
    END IF;
    -- est-il propriétaire ? (est-il bénéficiaire d'une machine ?)
    SELECT 1 INTO rTest FROM aeronef
      JOIN aeronef_situation ON aeronef_situation.id_aeronef = aeronef.id_aeronef
      JOIN aeronef_situation_benef ON aeronef_situation_benef.id_aeronef_situation = aeronef_situation.id_aeronef_situation
      WHERE id_personne = rMembre.id_personne;
    IF FOUND THEN
      RAISE NOTICE '% est propriétaire (vfr_pilote(id_pilote=%))', membre, rMembre.id_pilote;
      estProprietaireOuSection := true;
    END IF;
    -- a-t'il un forfait ?
    SELECT nom_forfait, montant INTO rTest FROM vfr_forfait_pilote
      JOIN vfr_gv_personne ON vfr_forfait_pilote.id_personne = rMembre.id_personne
      WHERE EXTRACT(YEAR FROM vfr_forfait_pilote.date_debut) = EXTRACT(YEAR FROM NOW())
      AND LOWER(vfr_forfait_pilote.nom_forfait) NOT LIKE '%récompense%';
    IF FOUND THEN
      forfait := rTest.montant;
      nomForfait := rTest.nom_forfait;
    END IF;

    -- frais technique
    SELECT INTO rTest cp_compte.libelle, li.montant FROM pilote
      JOIN cp_piece_ligne li ON li.id_compte = pilote.id_compte
      JOIN cp_piece pi ON pi.id_piece = li.id_piece
      JOIN cp_piece_ligne li2 ON li2.id_piece = pi.id_piece
      JOIN cp_compte ON cp_compte.id_compte = li2.id_compte AND cp_compte.code LIKE '7561%'
      WHERE pilote.id_personne = rMembre.id_personne AND
      pi.date_echeance BETWEEN '2023-10-01' AND '2024-09-30' LIMIT 1;
    typeFraisTechnique = rTest.libelle;
    fraisTechnique = rTest.montant;



    -- calculer le coût heure de vol sur machine hors club
    HDVSurMachineHorsClub := 0;
    FOR rVol IN SELECT *
      FROM vfr_vol
      WHERE saison = annee AND situation = 'B' AND (id_cdt_de_bord = rMembre.id_personne OR id_co_pilote = rMembre.id_personne)
      ORDER BY date_vol ASC, decollage ASC
    LOOP
      IF rVol.id_cdt_de_bord = rMembre.id_personne THEN
        SELECT INTO rTest piloteEstProprietaireDeMachine(rVol.id_cdt_de_bord, rVol.id_aeronef, rVol.date_vol) AS estProprietaire;
        IF rTest.estProprietaire IS false THEN
          HDVSurMachineHorsClub := HDVSurMachineHorsClub + rVol.prix_vol_cdb;
        END IF;
      ELSE
        SELECT INTO rTest piloteEstProprietaireDeMachine(rVol.id_co_pilote, rVol.id_aeronef, rVol.date_vol) AS estProprietaire;
        IF rTest.estProprietaire IS false THEN
          HDVSurMachineHorsClub := HDVSurMachineHorsClub + rVol.prix_vol_co;
        END IF;
      END IF;
    END LOOP;

    -- calculer le coût heures de vol sur machine club
    SELECT INTO rTest
      SUM(COALESCE(prix_vol_cdb, 0)) AS hdv
    FROM vfr_vol
    WHERE saison = annee AND id_cdt_de_bord = rMembre.id_personne AND situation = 'C';
    HDVSurMachineClub := rTest.hdv;
    SELECT INTO rTest
      COALESCE(SUM(COALESCE(prix_vol_co, 0)), 0) AS hdv
    FROM vfr_vol
    WHERE saison = annee AND id_co_pilote = rMembre.id_personne AND situation = 'C';
    HDVSurMachineClub := HDVSurMachineClub + rTest.hdv;

    -- calculer le coût heures de vol en instruction
    SELECT INTO rTest
      SUM(COALESCE(prix_vol_elv, 0)) AS hdv
    FROM vfr_vol
    WHERE saison = annee AND
      id_eleve = rMembre.id_personne;
    HDVEnEcole := rTest.hdv;

    -- calculer le coût remorques, treuillées et moteur
    SELECT INTO rTest
      SUM(COALESCE(prix_remorque_elv, 0) + COALESCE(prix_remorque_cdb, 0) + COALESCE(prix_remorque_co, 0)) AS prix_remorque,
      SUM(COALESCE(prix_treuil_elv, 0) + COALESCE(prix_treuil_cdb, 0) + COALESCE(prix_treuil_co, 0)) AS prix_treuil,
      SUM(COALESCE(prix_moteur_elv, 0) + COALESCE(prix_moteur_cdb, 0) + COALESCE(prix_moteur_co, 0)) AS prix_moteur
    FROM vfr_vol
    WHERE saison = annee AND
      (id_cdt_de_bord = rMembre.id_personne OR id_co_pilote = rMembre.id_personne OR id_eleve = rMembre.id_personne);
    remorques := rTest.prix_remorque;
    treuillees := rTest.prix_treuil;
    moteur := rTest.prix_moteur;

    IF estProprietaireOuSection IS true THEN
      categorie := 'section/propriétaire';
    ELSIF estInstructeur IS true THEN
      categorie := 'instructeur';
    ELSIF estStagiaire IS true THEN
      categorie := 'stagiaire';
    ELSIF estEleve IS true THEN
      categorie := 'élève';
    ELSIF aSPLMoins25 IS true THEN
      categorie := 'SPL -25 ans';
    ELSIF aSPLPlus25 IS true THEN
      categorie := 'SPL +25 ans';
    ELSE
      RAISE EXCEPTION 'impossible de categoriser ce membre: %', membre;
    END IF;
    return NEXT;
  END LOOP;
END;
$$ LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION exportRemorque(dateVolDebut DATE, dateVolFin DATE, immat VARCHAR) RETURNS TABLE (
  nb_remorque INT,
  nb_remorque_monoplace INT,
  nb_remorque_biplace INT,
  remorque_ponderation NUMERIC, -- 0.5 pour un 250, 1 pour un 500 ...
  remorque_ponderation_monoplace NUMERIC,
  remorque_ponderation_biplace NUMERIC,
  details VARCHAR
  ) AS $$
DECLARE
  rVol RECORD;
  ponderation NUMERIC;
  currentNbRemorque INT;
  currentNbRemorqueMonoplace INT;
  currentNbRemorqueBiplace INT;
  currentRemorquePonderation NUMERIC;
  currentRemorquePonderationMonoplace NUMERIC;
  currentRemorquePonderationBiplace NUMERIC;
  currentDetails VARCHAR[];
BEGIN
  currentNbRemorque := 0;
  currentNbRemorqueMonoplace := 0;
  currentNbRemorqueBiplace := 0;
  currentRemorquePonderation := 0;
  currentRemorquePonderationMonoplace := 0;
  currentRemorquePonderationBiplace := 0;
  currentDetails := '{}';
  FOR rVol IN SELECT * FROM vfr_vol WHERE date_vol BETWEEN dateVolDebut AND dateVolFin AND mode_decollage = 'R' AND immatriculation_remorqueur = immat ORDER BY date_vol LOOP
    currentNbRemorque := currentNbRemorque + 1;
    ponderation := 0;
    IF rVol.libelle_remorque = 'Demi-remorqué - 250m' THEN ponderation := 0.5;
      ELSIF rVol.libelle_remorque = 'Remorqué standard - 500m' THEN ponderation := 1;
      ELSIF rVol.libelle_remorque = '750m' THEN ponderation := 1.5;
      ELSIF rVol.libelle_remorque = '1000m' THEN ponderation := 2;
      ELSIF rVol.libelle_remorque = 'voltige - 1300m' THEN ponderation := 2.5;
      ELSE currentDetails := array_append(currentDetails, CONCAT(rVol.date_vol, ': ', rVol.libelle_remorque));
    END IF;
    currentRemorquePonderation := currentRemorquePonderation + ponderation;
    IF rVol.nb_places = 1 THEN
      currentNbRemorqueMonoplace := currentNbRemorqueMonoplace + 1;
      currentRemorquePonderationMonoplace := currentRemorquePonderationMonoplace + ponderation;
    ELSE
      currentNbRemorqueBiplace := currentNbRemorqueBiplace + 1;
      currentRemorquePonderationBiplace := currentRemorquePonderationBiplace + ponderation;
    END IF;
  END LOOP;
  nb_remorque := currentNbRemorque;
  nb_remorque_monoplace := currentNbRemorqueMonoplace;
  nb_remorque_biplace := currentNbRemorqueBiplace;
  remorque_ponderation := currentRemorquePonderation;
  remorque_ponderation_monoplace := currentRemorquePonderationMonoplace;
  remorque_ponderation_biplace := currentRemorquePonderationBiplace;
  details := array_to_string(currentDetails, ', ');
  return NEXT;
END;
$$ LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION calculPiloteSaisons(first_season INT, last_season INT) RETURNS TABLE (
  pilote VARCHAR,
  nb_saison_actif INT,
  derniere_saison INT,
  derniere_saison_nb_vol INT,
  derniere_saison_temps_vol INTERVAL,
  temps_vol_eleve INTERVAL,
  temps_vol_saison_actuelle INTERVAL,
  stats JSONB
  ) AS $$
DECLARE
  i INT;
  r RECORD;
  r2 RECORD;
  r3 RECORD;
  registerPilote BOOLEAN;
  a_vole_la_derniere_annee BOOLEAN;
BEGIN
  DROP TABLE IF EXISTS pilotesSaisons;
  CREATE TEMPORARY TABLE pilotesSaisons(id SERIAL PRIMARY KEY, membre VARCHAR, saison INT, temps_vol INTERVAL, nb_vol INT, CONSTRAINT pilotessaisons_unique UNIQUE (membre, saison));
  FOR i IN first_season..last_season LOOP
    FOR r IN SELECT cdt_de_bord, SUM(temps_vol) AS temps_vol, COUNT(*) AS nb_vol FROM vfr_vol
    JOIN vfr_pilote ON vfr_pilote.id_personne = vfr_vol.id_cdt_de_bord AND vfr_pilote.club_nom LIKE '%AAVO%'
    WHERE saison = i AND nom_type_vol IN ('1 Vol en solo', '3 Vol partagé') GROUP BY cdt_de_bord LOOP
      INSERT INTO pilotesSaisons(membre, saison, temps_vol, nb_vol) VALUES (r.cdt_de_bord, i, r.temps_vol, r.nb_vol) ON CONFLICT(membre, saison) DO UPDATE SET temps_vol = pilotesSaisons.temps_vol + r.temps_vol, nb_vol = pilotesSaisons.nb_vol + r.nb_vol;
    END LOOP;

    FOR r IN SELECT co_pilote, SUM(temps_vol) AS temps_vol, COUNT(*) AS nb_vol FROM vfr_vol
    JOIN vfr_pilote ON vfr_pilote.id_personne = vfr_vol.id_co_pilote AND vfr_pilote.club_nom LIKE '%AAVO%'
    WHERE saison = i AND nom_type_vol = '3 Vol partagé' GROUP BY co_pilote LOOP
      INSERT INTO pilotesSaisons(membre, saison, temps_vol, nb_vol) VALUES (r.co_pilote, i, r.temps_vol, r.nb_vol) ON CONFLICT(membre, saison) DO UPDATE SET temps_vol = pilotesSaisons.temps_vol + r.temps_vol, nb_vol = pilotesSaisons.nb_vol + r.nb_vol;
    END LOOP;

  END LOOP;
  FOR r IN SELECT DISTINCT(membre) FROM pilotesSaisons ORDER BY membre ASC LOOP
    pilote := r.membre;
    derniere_saison := NULL;
    derniere_saison_nb_vol := NULL;
    derniere_saison_temps_vol := NULL;
    temps_vol_eleve := NULL;
    nb_saison_actif := 0;
    stats := '{}';
    registerPilote := false;
    a_vole_la_derniere_annee := false;
    FOR i IN REVERSE last_season..first_season LOOP
      SELECT * INTO r2 FROM pilotesSaisons WHERE membre = r.membre AND saison = i;
      IF i = last_season THEN
        temps_vol_saison_actuelle := r2.temps_vol;
      END IF;
      IF r2.nb_vol > 0 THEN
        nb_saison_actif := nb_saison_actif + 1;
        registerPilote := true;
        IF derniere_saison IS NULL THEN
          IF i = EXTRACT(YEAR FROM NOW()) THEN
            a_vole_la_derniere_annee := true;
          ELSE
            IF a_vole_la_derniere_annee IS false THEN
              derniere_saison := i;
              derniere_saison_nb_vol := r2.nb_vol;
              derniere_saison_temps_vol := r2.temps_vol;
              IF temps_vol_eleve IS NULL THEN
                SELECT SUM(temps_vol) AS temps_vol INTO r3 FROM vfr_vol WHERE eleve = pilote;
                temps_vol_eleve := r3.temps_vol;
              END IF;
            END IF;
          END IF;
        END IF;
      END IF;
      stats := setVarInData(stats, CONCAT('saison_', r2.saison, '_temps_vol'), r2.temps_vol);
      stats := setVarInData(stats, CONCAT('saison_', r2.saison, '_nb_vol'), r2.nb_vol);
    END LOOP;
    IF registerPilote THEN
      return NEXT;
    END IF;
  END LOOP;
END;
$$ LANGUAGE plpgsql VOLATILE;

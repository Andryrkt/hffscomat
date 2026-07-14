
CREATE TABLE ir_scomat:regix.bc_soumis_magasin (
    id                     SERIAL8 NOT NULL,
    numero_cde             VARCHAR(8),
    date_heure_soumission  DATETIME YEAR TO SECOND,
    statut                 VARCHAR(50),
    date_heure_validation  DATETIME YEAR TO SECOND,
    operateur              VARCHAR(50),
    deposer_dw             BOOLEAN
);

-- Exemple d'insertion
INSERT INTO informix.bc_soumis_magasin
    (numero_cde, date_heure_soumission, statut, date_heure_validation, operateur, deposer_dw)
VALUES
    ('CDE00123', '2026-07-10 09:15:32', 'SOUMIS', NULL, 'jdupont', 'f');

INSERT INTO informix.bc_soumis_magasin
    (numero_cde, date_heure_soumission, statut, date_heure_validation, operateur, deposer_dw)
VALUES
    ('CDE00124', '2026-07-10 09:20:05', 'VALIDE', '2026-07-10 09:45:12', 'mleroy', 't');

INSERT INTO informix.bc_soumis_magasin
    (numero_cde, date_heure_soumission, statut, date_heure_validation, operateur, deposer_dw)
VALUES
    ('CDE00125', '2026-07-11 14:03:47', 'REJETE', '2026-07-11 14:10:00', 'jdupont', 'f');
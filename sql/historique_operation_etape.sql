-- ============================================================
-- Table : historique_operation_etape (SQL Server) - repris de Hffintranet
-- Suivi étape par étape des soumissions et enregistrements (DIT, devis, OR, RI,
-- facture, AC/BC, commande fournisseur, devis négoce...). Une ligne par étape,
-- toutes les étapes d'une même opération partagent le même id_suivi.
-- (alimentée par SuiviOperationService, lue par la page admin "Suivi des opérations")
--
-- statut : EN_COURS   -> étape commencée mais jamais terminée (arrêt brutal du script)
--          SUCCES     -> étape terminée sans erreur
--          ECHEC      -> exception, copie DocuWare échouée ou message de blocage
--          ALERTE     -> opération réussie mais un contrôle signale une anomalie
--          INTERROMPU -> le script s'est arrêté (redirection / exit) pendant l'étape
-- ============================================================

-- supprime uniquement l'ancienne version de cette table (sans id_suivi), créée par un premier script scomat
IF OBJECT_ID('dbo.historique_operation_etape', 'U') IS NOT NULL
    AND COL_LENGTH('dbo.historique_operation_etape', 'id_suivi') IS NULL
    DROP TABLE dbo.historique_operation_etape;

CREATE TABLE
    historique_operation_etape (
        id INT IDENTITY (1, 1) NOT NULL,
        id_suivi VARCHAR(36) NOT NULL,
        id_historique_operation INT NULL,
        type_operation VARCHAR(50) NOT NULL,
        numero_document VARCHAR(50) NULL,
        ordre INT NOT NULL,
        etape VARCHAR(100) NOT NULL,
        statut VARCHAR(20) NOT NULL,
        message NVARCHAR(1000) NULL,
        detail_erreur NVARCHAR(MAX) NULL,
        date_debut DATETIME2 NOT NULL,
        date_fin DATETIME2 NULL,
        duree_ms INT NULL,
        utilisateur VARCHAR(100) NULL,
        url VARCHAR(500) NULL,
        -- marquage "lu / non lu" des opérations en problème depuis la page admin
        est_lu BIT NOT NULL CONSTRAINT DF_historique_operation_etape_est_lu DEFAULT 0,
        lu_par VARCHAR(100) NULL,
        date_lecture DATETIME2 NULL,
        CONSTRAINT PK_historique_operation_etape PRIMARY KEY (id)
    );

CREATE INDEX IDX_historique_operation_etape_suivi ON historique_operation_etape (id_suivi, ordre);

CREATE INDEX IDX_historique_operation_etape_date ON historique_operation_etape (date_debut);

CREATE INDEX IDX_historique_operation_etape_numero ON historique_operation_etape (numero_document);

CREATE INDEX IDX_historique_operation_etape_historique ON historique_operation_etape (id_historique_operation);

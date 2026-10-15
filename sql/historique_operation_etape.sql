-- Détail des étapes (upload, fusion, BD, DocuWare, mail...) d'une opération historisée
-- Entité : App\Entity\admin\historisation\documentOperation\HistoriqueOperationEtape
-- Base : SQL Server (même base que historique_operation_document)

CREATE TABLE historique_operation_etape (
    id                    INT IDENTITY(1,1) NOT NULL,
    idHistoriqueOperation INT NOT NULL,
    ordre                 INT NOT NULL,
    etape                 NVARCHAR(100) NOT NULL,
    statut                NVARCHAR(20) NOT NULL,
    duree_ms              INT NULL,
    message               NVARCHAR(1000) NULL,
    date_etape            DATETIME2(6) NOT NULL,
    CONSTRAINT PK_historique_operation_etape PRIMARY KEY (id),
    CONSTRAINT FK_historique_operation_etape_historique FOREIGN KEY (idHistoriqueOperation)
        REFERENCES historique_operation_document (id)
);

CREATE INDEX IDX_historique_operation_etape_historique ON historique_operation_etape (idHistoriqueOperation);

-- Exemple : dernières soumissions en échec avec l'étape fautive
-- SELECT h.numeroDocument, h.dateOperation, h.utilisateur, e.etape, e.message, e.duree_ms
-- FROM historique_operation_document h
-- JOIN historique_operation_etape e ON e.idHistoriqueOperation = h.id
-- WHERE e.statut = 'Echec'
-- ORDER BY h.id DESC;

-- Création de la nouvelle page
-- INSERT INTO Hff_pages (nom, nom_route, lien, application_id, date_creation, date_modification)
select page.nom, page.nom_route, page.lien, a.id, page.date_creation, page.date_modification FROM (
VALUES
(N'Générer commandes fournisseur', N'generer_commande_fournisseur', N'/magasin/commande/generer-commande-fournisseur', 'MAG', GETDATE(), GETDATE())
) AS page(nom, nom_route, lien, code_app, date_creation, date_modification)
join applications a on a.code_app=page.code_app; 

-- insertion dans application_profil_page
WITH 
page_inserted AS (
    SELECT hp.id as page_id, hp.application_id 
    FROM Hff_pages hp 
    WHERE hp.nom_route='generer_commande_fournisseur'
),
profil_maj AS (
	select p.id 
	from profil p
)
-- INSERT INTO application_profil_page (application_profil_id, page_id, peut_voir, peut_multi_succursale, peut_voir_liste_avec_debiteur, peut_supprimer, peut_exporter)
SELECT 
    ap.id,
    p.page_id,
    1, 0, 0, 0, 0
FROM application_profil ap
join profil_maj pr on ap.profil_id=pr.id
CROSS JOIN page_inserted p
WHERE ap.application_id = p.application_id
AND NOT EXISTS (
    SELECT 1 
    FROM application_profil_page existing
    WHERE existing.application_profil_id = ap.id
        AND existing.page_id = p.page_id
);

# Plan : aligner la soumission de commande fournisseur de scomat sur Hffintranet

Suivi : `[ ]` à faire · `[x]` fait. Après approbation, ce fichier est copié dans le projet : `c:\wamp64\www\scomat\PLAN_SOUMISSION_CDE_FRN.md`.

## Contexte
Scomat doit reproduire à l'identique le comportement de la route `generer_commande_fournisseur`
de Hffintranet (branche `feature/pdfSoumissionCdeFrn`, diff `main...HEAD` uniquement).
Seule exclusion : la persistance. On garde `CdeSoumissionModel::enregistrerBcSoumisMagasin` (table `bc_soumis_magasin`) et `copyToDOCUWARE` façon scomat.
Source : `C:\wamp64\www\Hffintranet` · Cible : `C:\wamp64\www\scomat` (branche `feature/magasin`).

## Règles de travail
- Conventions scomat (namespaces/casse), chemins réels listés ci-dessous.
- Fichier existant dans les deux projets : je prépare le diff, **vous décidez** avant écriture.
- Injection de dépendances / `services_*.yaml` : **faits par vous** (je ne touche pas la config du conteneur).
- Ne pas porter : renommage `basePathFile` des ~20 sous-classes `GeneratePdf*`, Doctrine bootstrap, `DemandePaiement*`, entités/repositories/mapper Doctrine.
- Écarts DTO/persistance : signalés, jamais adaptés en silence.
- Pas de tests ; validation manuelle par vous. 1 commit par étape, branche `feature/magasin`.

## Étapes

### Étape 1 — Assets, CSS, partials twig (nouveaux fichiers)
- [x] `Views/css/magasin/soumissionCommande.css`
- [x] `Views/assets/info_HFF_1.png`, `info_HFF_2.png`
- [x] `Views/templates/partials/_dropZonePdfMultiple.html.twig`
- [x] `Views/js/utils/multiPdfDropzone.js`
- [x] (optionnel) `_dropZoneFileMultiple` : non appliqué (décision utilisateur)

### Étape 2 — DTO (fusion, existent déjà)
Dossier : `src/Dto/Magasin/Commande/Soumission/`
- [x] `CommandeSoumissionDTO` : + `devise`, `poidsTotal`, `montantTotal`, `allValidatedOR/PO` + getters ; retrait `getFournisseur()` ; `getDelaiExpedition()` sans garde
- [x] `CommandeSoumissionLigneDTO` : prix sans suffixe " Ar"
- [x] `CommandeSoumissionDetailDTO` : gardes `"-"` et null `numClient/nomClient`
- [x] Nouveau DTO équivalent à `CdeFrnSoumisAValidationDTO` (numCde, codeFrn, libelleFrn, numVersion, statut, urlPDFCourt/Long, token, pdfDeposerDw, dateDepotDw) — mis en cache. `BcSoumisMagasinDTO` inchangé.
- [x] `CdeFrnSoumisAValidationLigneDTO` : non créé (pas de table lignes dans scomat), `lignes` retiré du DTO principal

### Étape 3 — Model Informix (fusion de logique uniquement, sans écraser)
`src/Model/magasin/Commande/Soumission/CdeSoumissionModel.php` + `src/Model/Informix/SelectWhereCondition.php`
- [x] `in()/ni()` : rien à faire, la version scomat est déjà plus complète
- [x] Devise : `fbse_devise AS devise_code` + sous-select `adev_lib AS devise_libelle`
- [x] Nouvelles méthodes : `findLignesSavEtVenteNegoceParPieces`, `filterOrdresReparationValides`, `findAllValidatedPO`
- [x] Signature `findInfoCommande` : garder style scomat (succursale `SUC_NEG`), exposer `allValidatedOR/PO`
- [x] Vérifier que `bc_client_soumis_neg` existe en base (absente de `sql/`)
- [x] Corriger le `use ...CommANDe...` (typo de casse) dans controller/API
- [x] **Ne pas modifier** `enregistrerBcSoumisMagasin`

### Étape 4 — Factories
`src/Factory/magasin/Commande/Soumission/`
- [x] `CommandeSoumissionFactory` : `hydrate($data,$details,$allValidatedOR,$allValidatedPO,$email)`, `devise`, `hydrateLignes($dto,…)` avec poids/montant totaux
- [x] Nouvelle `CdeFrnSoumisAValidationFactory` : sans EntityManager ni `findNumeroVersionMax` (version non gérée en base scomat) ; chemins PDF (`BASE_PATH_FICHIER/magasin/commandes fournisseurs/$num/$num.pdf`), suppression de l'ancien fichier

### Étape 5 — Génération PDF
- [x] `src/Service/genererPdf/GeneratePdf.php` : ajouter `basePathAssets` + équivalent de `copyToDWCdeFnrSoumis` (sans renommer les props existantes)
- [x] `src/Service/genererPdf/magasin/GeneratePdfCdeMagasin.php` : DTO au constructeur, `generate($filePath)`, en-tête images, devise, pied de page (totaux, signature, OR/PO rattachés). `copyToDOCUWARE` retiré de la classe : remplacé par `GeneratePdf::copyToDWCdeFnrSoumis` (même chemin DocuWare et même `copyFile` scomat).
- [x] `TraitementDeFichier::fusionFichers(array, string)` : identique dans les deux projets, rien à faire

### Étape 6 — Store (cache) + Service
`src/Service/magasin/Commande/Soumission/` (emplacement à confirmer)
- [x] `CdeFrnSoumissionStore` (cache PSR-6, TTL 7200 s, 1 jeton actif/utilisateur)
- [x] `CdeFrnSoumissionService` : `generatePdfForSubmission`, `soumettre($mail,$numCmde,$token,$pj)`, fusion des pièces jointes, jeton, contrôles fichier/numéro, notifications `HistoriqueOperationCdeFrnService`
  - Doctrine remplacé par : mapping DTO → `BcSoumisMagasinDTO` (`numCde→numeroCommande`, `pdfDeposerDw→deposerDw`, `operateur=getUserName`) puis `CdeSoumissionModel::enregistrerBcSoumisMagasin`
  - Contrôle de doublon par version : **retiré** (pas de colonne) — signalé
  - Copie DocuWare : `GeneratePdf::copyToDWCdeFnrSoumis` (logique scomat). Opérateur : paramètre optionnel `$operateur` de `soumettre()` (le contrôleur passera `getUserName()`)
- [x] Écarts signalés (non portés) : `codeFrn/libelleFrn/numVersion/dateDepotDw/lignes OR-NEG` sans colonne dans `bc_soumis_magasin`

### Étape 7 — Form, Controller, API
- [x] `src/Form/magasin/Commande/SoumissionCommande/SoumissionCommandeType.php` : champs `numCmde`, `piecesJointesPdf` (multiple, All(File 5M, pdf)), `generationToken` (hidden)
- [x] `SoumissionCommandeController` : constructeur avec `CdeFrnSoumissionService`, appel `soumettre($this->getUserMail(), …)`
- [x] `src/Api/magasin/GenerateCommandeFournisseurApi.php` : `POST /api/cde-frn/generate-pdf` (corps JSON `numCde`), réponse `{data:{urlPDFCourt, generationToken}, message}`

### Étape 8 — Twig + JS (fusion)
- [x] `soumissionCommandeFournisseur.html.twig` : CSS, `multipart/form-data`, `#pj-section`, viewer à onglets, `#validationBtn`
- [x] `Views/js/magasin/commande/soumissionCommandeFournisseur.js` : dropzone, `generationToken`, POST, `urlPDFCourt`
- [x] `Views/js/api/apiEndpoints.js` : ajouter `generatePdfCdeFrnMag: "api/cde-frn/generate-pdf"` **sans écraser** les autres clés

### Étape 9 — À faire par vous (hors de mon périmètre)
- [x] Déclarer service/store/factory/API/controller avec leurs `arguments` (autowire: false)
- [x] Pool de cache `cache.cde_frn_soumission` (FilesystemAdapter, TTL 7200) dans `services_framework.yaml`
- [x] Éventuel import `services_magasin.yaml`

## Vérification (manuelle, par vous)
1. Page `/magasin/commande/generer-commande-fournisseur` se charge.
2. Saisie d'un numéro → PDF généré et affiché (en-tête, devise, totaux, OR/PO).
3. Ajout de pièces jointes PDF (≤10 fichiers, 5 Mo/fichier, 7 Mo total).
4. Soumission → ligne dans `bc_soumis_magasin`, PDF fusionné déposé dans DocuWare, notification visible.
5. Erreurs (jeton expiré, fichier absent, copie DW en échec) : mêmes messages que Hffintranet.

## Étape 10 — Nettoyage config (commits Hffintranet e33bf17, f51a9be, f5812e8)
- [x] Supprimés : `config/{config.yaml,console.php,doctrine-migrations.php,doctrine.yaml,dotenv.php,form.yaml,migrations-config.php,migrations.php,session_config.php,twig.yaml}`
- [x] Références corrigées : `doctrineBootstrap.php` (chargement `.env` inline), `src/Doctrine/EntityManagerFactory.php` (require_once retiré), `bin/console` (require dotenv retiré)
- [x] `bootstrap_build.php` : retrait de request_stack, cache.security, cache.menu, userData.service, security.service, menu.service
- [x] `services_framework.yaml` : ajout de ces services/caches ; retrait des définitions `session` / `session.storage.native` (la session reste définie dans bootstrap_build.php)
- [ ] Non fait (index.php non touché, demandé) : suppression de `config/environment.yaml` + retrait de sa lecture dans `index.php`

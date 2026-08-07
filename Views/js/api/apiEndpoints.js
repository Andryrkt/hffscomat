export const API_ENDPOINTS = {
  /** Demande Intervention (DIT) */
  getDossierDit: (numDit) =>
    `atelier/demande-intervention/dw-intervention-atelier-avec-dit/${numDit}`,

  /** Commande Fournissuer Magasin */
  generatePdfCdeFrnMag: (numCde) =>
    `api/cmde-fournisseur/${numCde}/generate-pdf`,

  /** Planning Atelier + Magasin */
  getTechnicienIntervenant: (numOr, numItv) =>
    `api/technicien-intervenant/${numOr}/${numItv}`,
  getDetailModal: (numOrItv) => `api/detail-modal/${numOrItv}`,
  getDetailModalMagasin: (numOrItv) =>
    `api/detail-plannigMagasin-modal/${numOrItv}`,

  /** Planning Magasin */
  LIST_FOURNISSEURS: "api/magasin-planning-liste-fournisseur",
};

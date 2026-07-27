export const API_ENDPOINTS = {
  generatePdfCdeFrnMag: (numCde) =>
    `api/cmde-fournisseur/${numCde}/generate-pdf`,
  getDossierDit: (numDit) =>
    `atelier/demande-intervention/dw-intervention-atelier-avec-dit/${numDit}`,
};

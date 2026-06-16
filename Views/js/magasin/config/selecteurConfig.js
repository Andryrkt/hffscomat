import {
  cellIndicesOrATraiter,
  cellIndicesOrALivrer,
  cellIndicesCisATraiter,
  cellIndicesCisALivrer,
  cellIndicesListePlanning,
  cellIndicesLcfng,
} from "./cellIndicesConfig.js";
export const config = {
  or_a_traiter: {
    tableBody: "#tableBody",
    agenceInput: "#or_a_traiter_search_agence",
    serviceInput: "#or_a_traiter_search_service",
    spinnerService: "#spinner-service",
    serviceContainer: "#service-container",
    numDitInput: "#or_a_traiter_search_numDit",
    refPieceInput: "#or_a_traiter_search_referencePiece",
    numOrInput: "#or_a_traiter_search_numOr",
    cellIndices: cellIndicesOrATraiter, // Utilise la config avec `user: 16`
  },
  or_a_livrer: {
    tableBody: "#tableBody",
    agenceInput: "#or_livrer_search_agence",
    serviceInput: "#or_livrer_search_service",
    spinnerService: "#spinner-service",
    serviceContainer: "#service-container",
    numDitInput: "or_livrer_search_numDit",
    refPieceInput: "#or_livrer_search_referencePiece",
    numOrInput: "#or_livrer_search_numOr",
    cellIndices: cellIndicesOrALivrer, // Utilise la config avec `user: 18`
  },

  liste_planning: {
    tableBody: "#tableBody",
    cellIndices: cellIndicesListePlanning,
  },
  liste_cde_fnr_non_genere: {
    tableBody: "#tableBody",
    agenceEmetteurInput: "#liste_cde_frn_non_generer_search_agenceEmetteur",
    serviceEmetteurInput: "#liste_cde_frn_non_generer_search_serviceEmetteur",
    agenceInput: "#liste_cde_frn_non_generer_search_agence",
    serviceInput: "#liste_cde_frn_non_generer_search_service",
    spinnerServiceEmetteur: "#spinner-service-emetteur",
    serviceContainerEmetteur: "#service-container-emetteur",
    spinnerService: "#spinner-service",
    serviceContainer: "#service-container",
    numDitInput: "#liste_cde_frn_non_generer_search_numDit",
    refPieceInput: "#liste_cde_frn_non_generer_search_referencePiece",
    numDocInput: "#liste_cde_frn_non_generer_search_numDoc",
    cellIndices: cellIndicesLcfng,
  },
  liste_cde_fnr_non_place: {
    tableBody: "#tableBody",
    // agenceEmetteurInput: "#liste_cde_frn_non_place_search_agenceEmetteur",
    // serviceEmetteurInput: "#liste_cde_frn_non_place_search_serviceEmetteur",
    agenceInput: "#liste_cde_frn_non_place_search_agence",
    serviceInput: "#liste_cde_frn_non_place_search_service",
    // spinnerServiceEmetteur: "#spinner-service-emetteur",
    // serviceContainerEmetteur: "#service-container-emetteur",
    spinnerService: "#spinner-service",
    serviceContainer: "#service-container",

    cellIndices: cellIndicesLcfng,
  },
};

import { baseUrl } from "../../utils/config.js";
import {
  configDocSoumisDwModal,
  configCloturDit,
} from "./config/listDitConfig.js";
import {
  docSoumisModalHidden,
  docSoumisModalShow,
} from "./fonctionUtils/fonctionListDit.js";
import {
  toUppercase,
  allowOnlyNumbers,
  limitInputLength,
} from "../../utils/inputUtils.js";
import {
  toggleSpinner,
  affichageOverlay,
  affichageSpinner,
} from "../../utils/ui/uiSpinnerUtils.js";
import { FetchManager } from "../../api/FetchManager.js";
import { filterServiceByAgence } from "../../utils/agenceService/filterServiceByAgence.js";

// Instanciation de FetchManager avec la base URL
const fetchManager = new FetchManager();

document.addEventListener("DOMContentLoaded", (event) => {
  /**===========================================================================
   * Configuration des agences et services
   *===========================================================================*/

  filterServiceByAgence({
    agenceSelector: "#dit_search_agenceEmetteur",
    serviceSelector: "#dit_search_serviceEmetteur",
  });

  filterServiceByAgence({
    agenceSelector: "#dit_search_agenceDebiteur",
    serviceSelector: "#dit_search_serviceDebiteur",
  });

  /**=======================================
   * Docs à intégrer dans DW MODAL
   * ======================================*/

  configDocSoumisDwModal.docDansDwModal.addEventListener(
    "show.bs.modal",
    docSoumisModalShow,
  );

  // Gestionnaire pour la fermeture du modal
  configDocSoumisDwModal.docDansDwModal.addEventListener(
    "hidden.bs.modal",
    docSoumisModalHidden,
  );

  /**====================================================
   * MISE EN MAJUSCULE
   *=================================================*/
  const numDitSearchInput = document.querySelector("#dit_search_numDit");
  numDitSearchInput.addEventListener("input", () => {
    toUppercase(numDitSearchInput);
    limitInputLength(numDitSearchInput, 11);
  });

  /**===========================================
   * SEULMENT DES CHIFFRES
   *============================================*/
  const numOrSearchInput = document.querySelector("#dit_search_numOr");
  const numDevisSearchInput = document.querySelector("#dit_search_numDevis");
  numOrSearchInput.addEventListener("input", () => {
    allowOnlyNumbers(numOrSearchInput);
    limitInputLength(numOrSearchInput, 8);
  });
  numDevisSearchInput.addEventListener("input", () => {
    allowOnlyNumbers(numDevisSearchInput);
    limitInputLength(numDevisSearchInput, 8);
  });

  allowOnlyNumbers(numDevisSearchInput);

  /**==================================================
   * sweetalert pour le bouton cloturer dit
   *==================================================*/

  configCloturDit.clotureDit.forEach((el) => {
    el.addEventListener("click", (e) => {
      e.preventDefault();
      let id = el.getAttribute("data-id");

      Swal.fire(configCloturDit.text).then((result) => {
        if (result.isConfirmed) {
          // Afficher un overlay de chargement
          affichageOverlay();

          // Ajouter un spinner CSS
          affichageSpinner();

          // Redirection après confirmation
          window.location.href = `${baseUrl}/atelier/demande-intervention/cloturer-annuler/${id}`;
        }
      });
    });
  });

});

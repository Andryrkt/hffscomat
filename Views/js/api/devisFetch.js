import { FetchManager } from "./FetchManager.js";
import { updateServiceOptions } from "../utils/ui/uiAgenceServiceUtils.js";
import { toggleSpinner } from "../utils/ui/uiSpinnerUtils.js";

// Instanciation de FetchManager avec la base URL
const fetchManager = new FetchManager();

/**
 * Fonction pour mettre à jour les donner dans le select de docSoumis à validation DW
 * @param {string} numDit
 * @param {HTMLElement} spinnerSelect
 * @param {HTMLElement} selectContainer
 * @param {HTMLElement} selecteInput
 */
export function fetchDevis(
  numDit,
  spinnerSelect,
  selectContainer,
  selecteInput
) {
  const url = `api/constraint-soumission/${numDit}`;
  toggleSpinner(spinnerSelect, selectContainer, true);
  fetchManager
    .get(url)
    .then((docDansDw) => {
      console.log(docDansDw);
      let docASoumettre = valeurDocASoumettre(docDansDw);
      updateServiceOptions(docASoumettre, selecteInput);
    })
    .catch((error) => console.error("Error:", error))
    .finally(() => toggleSpinner(spinnerSelect, selectContainer, false));
}

/**
 * Détermine les documents à soumettre en fonction des conditions.
 * @param {Object} docDansDw - L'objet contenant les informations nécessaires.
 * @returns {Array} - Un tableau d'objets avec `value` et `text`.
 */
function valeurDocASoumettre(docDansDw) {
  let docASoumettre = [];
  
  // DEVIS-VP ne s'affiche pas si le backend indique qu'aucun article
  // de vérification de prix n'est présent sur l'OR (afficherVerifPrix === false)
  if (docDansDw.afficherVerifPrix !== false) docASoumettre.push({ value: "DEVIS-VP", text: "DEVIS - Vérification de prix" });

  docASoumettre.push(
    { value: "DEVIS-VA", text: "DEVIS - Validation atelier" },
    { value: "BC", text: "BC - BON COMMANDE" },
    { value: "OR", text: "OR - ORDRE DE REPARATION" },
    { value: "RI", text: "RI - RAPPORT D'INTERVENTION" },
    { value: "FACTURE", text: "FACTURE" }
  );

  return docASoumettre; // Retourne le tableau
}

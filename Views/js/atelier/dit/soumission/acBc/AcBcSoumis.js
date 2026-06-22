import { initializeFileHandlers } from "../../../../utils/file_upload_Utils.js";
import { FetchManager } from "../../../../api/FetchManager.js";
import { AutoComplete } from "../../../../utils/AutoComplete.js";
import { formatNumberSpecial } from "../../../../utils/formatNumberUtils.js";

const fetchManager = new FetchManager();
/** ============================================
 * FICHIER BC AC
 *==================================================*/
const fileInput = document.querySelector("#ac_soumis_pieceJoint01");
initializeFileHandlers(1, fileInput);

const montantDevisDisplay = document.querySelector("#montant-devis-display");
const montantDevis = document.querySelector("#ac_soumis_montantDevis");
montantDevisDisplay.textContent = formatNumberSpecial(montantDevis.value);

/**=======================================
 * Methode pour l'autocomplet nom client
 *=======================================*/
const nomClientInput = document.querySelector("#ac_soumis_nomClient");
const suggestionContainer = document.querySelector("#suggestion");

async function fetchClient() {
  return await fetchManager.get("api/autocomplete/all-client");
}

function displayClient(item) {
  return `${item.num_client} - ${item.nom_client}`;
}

function onSelectClient(item) {
  nomClientInput.value = item.nom_client.trim();
}

// Activation sur le champ "Numéro Fournisseur"
new AutoComplete({
  inputElement: nomClientInput,
  suggestionContainer: suggestionContainer,
  loaderElement: document.querySelector("#loader-num-fournisseur"), // Ajout du loader
  debounceDelay: 300, // Délai en ms
  fetchDataCallback: fetchClient,
  displayItemCallback: displayClient,
  onSelectCallback: onSelectClient,
  itemToStringCallback: (item) => `${item.num_client} - ${item.value}`,
});

import { AutoComplete } from "../../utils/AutoComplete.js?v=2026.03.23.01";
import { FetchManager } from "../../api/FetchManager.js?v=2026.03.23.01";
import { mergeCellsRecursiveTable } from "../../utils/tableHandler.js";

document.addEventListener("DOMContentLoaded", () => {
  // Merge colone cellule commande [0]
  mergeCellsRecursiveTable([
    {
      pivotIndex: 0, // colonne commande
      columns: [0],
    },
  ]);

  const fetchManager = new FetchManager();
  /**===================================================
   * Autocomplete champ code client
   *====================================================*/
  async function fetchCodeClient() {
    return await fetchManager.get("api/code-client-fetch");
  }
  function displayCodeClient(item) {
    return `${item.code_client} - ${item.nom_client}`;
  }
  const codeClientInput =
    document.getElementById("commande_livrer_search_codeClient") ||
    document.getElementById("commande_traiter_search_codeClient");

  if (codeClientInput) {
    function onSelectCodeClient(item) {
      codeClientInput.value = `${item.code_client}`;
    }

    new AutoComplete({
      inputElement: codeClientInput,
      suggestionContainer: document.getElementById("suggestion-code-client"),
      loaderElement: document.getElementById("loader-code-client"),
      fetchDataCallback: fetchCodeClient,
      displayItemCallback: displayCodeClient,
      onSelectCallback: onSelectCodeClient,
      lazyLoad: true,
    });
  }
});

// Desactivation sur [1]
const agenceUserSelect = document.getElementById(
  "commande_traiter_search_agence",
);

if (agenceUserSelect) {
  agenceUserSelect.selectedIndex = 1;
  agenceUserSelect.disabled = true;
  agenceUserSelect.style.pointerEvents = "none";
  agenceUserSelect.style.backgroundColor = "#e9ecef"; // Gris standard Bootstrap
  agenceUserSelect.style.color = "#6c757d"; // Texte grisé
  agenceUserSelect.style.borderColor = "#ced4da"; // Bordure adoucie
  agenceUserSelect.style.cursor = "not-allowed"; // Curseur d'interdiction
}

/** *======================
 * LIST DETAIL MODAL
 *  =======================*/
import { API_ENDPOINTS } from "../../api/apiEndpoints";
import { baseUrl } from "../../utils/config";
import { hideCells, applyRowspanAndClass } from "../utils/uiUtils.js";

const CELL_INDICES_LIGNE = {
  constp: 0,
  refp: 1,
  desi: 2,
  qteDem: 3,
  qteRest: 4,
  statut: 5,
};

document.addEventListener("DOMContentLoaded", function () {
  let abortController; // AbortController pour annuler les requêtes fetch précédentes

  const listeCommandeModal = document.getElementById(
    "listeLigneCommandeMagasin"
  );

  // Gestionnaire pour l'ouverture du modal
  listeCommandeModal.addEventListener("show.bs.modal", function (event) {
    // Annuler les requêtes fetch en cours s'il y en a
    if (abortController) {
      abortController.abort();
    }

    abortController = new AbortController(); // Créer un nouveau contrôleur

    const button = event.relatedTarget; // Bouton qui a déclenché le modal
    const commandeId = button.getAttribute("data-id");
    const numCdeFrn = document.getElementById("numCdeFrn");
    const emetteur = document.getElementById("emetteurCdeFrn");

    numCdeFrn.textContent = commandeId;
    emetteur.textContent = button.dataset.emetteur;

    // Afficher le spinner
    document.getElementById("loading").style.display = "block";
    document.getElementById("dataContent").style.display = "none";

    // Utiliser AbortController pour fetchDetailModal
    fetchDetailModal(commandeId, abortController.signal);
  });

  // Gestionnaire pour la fermeture du modal
  listeCommandeModal.addEventListener("hidden.bs.modal", function () {
    const tableBody = document.getElementById("commandesTableBody");
    const numCdeFrn = document.getElementById("numCdeFrn");
    const emetteur = document.getElementById("emetteurCdeFrn");

    tableBody.innerHTML = ""; // Vider le tableau
    numCdeFrn.textContent = "";
    emetteur.textContent = "";
  });

  function getPivotKey(row) {
    return ["constp", "refp", "desi"]
      .map((key) => row.cells[CELL_INDICES_LIGNE[key]]?.textContent.trim())
      .join("|");
  }

  function fusionnerLignesParPivot(tableBody) {
    const cellIndices = Object.values(CELL_INDICES_LIGNE);
    let firstRowInGroup = null;
    let rowSpanCount = 0;
    let previousKey = null;

    Array.from(tableBody.rows).forEach((row) => {
      const key = getPivotKey(row);

      if (key === previousKey) {
        rowSpanCount++;
        hideCells(row, cellIndices);
      } else {
        if (firstRowInGroup) {
          applyRowspanAndClass(firstRowInGroup, rowSpanCount, CELL_INDICES_LIGNE);
        }
        firstRowInGroup = row;
        rowSpanCount = 1;
        previousKey = key;
      }
    });

    if (firstRowInGroup) {
      applyRowspanAndClass(firstRowInGroup, rowSpanCount, CELL_INDICES_LIGNE);
    }
  }

  function masquerSpinner() {
    // Masquer le spinner et afficher les données
    document.getElementById("loading").style.display = "none";
    document.getElementById("dataContent").style.display = "block";
  }

  function fetchDetailModal(id, signal) {
    fetch(`${baseUrl}/${API_ENDPOINTS.getLigneCommandeMagasin(id)}`, {
      signal,
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error("Network response was not ok");
        }
        return response.json();
      })
      .then((data) => {
        const tableBody = document.getElementById("commandesTableBody");
        tableBody.innerHTML = ""; // Clear previous data

        if (data.data.length > 0) {
          const qte = (val) => {
            const n = parseInt(val);
            return n === 0 ? "" : n;
          };
          tableBody.innerHTML = data.data
            .map(
              (detail) => `
                <tr>
                  <td>${detail.constp || ""}</td>
                  <td>${detail.refp || ""}</td>
                  <td>${detail.desi || ""}</td>
                  <td>${qte(detail.qte_dem)}</td>
                  <td>${qte(detail.qte_rest)}</td>
                  <td>${detail.statut || ""}</td>
                  <td class="col-separator">${detail.type_doc || ""}</td>
                  <td>${detail.numero || ""}</td>
                  <td>${detail.numcli || ""}</td>
                  <td>${detail.nomcli || ""}</td>
                  <td>${qte(detail.qte_dem_ligne)}</td>
                </tr>`
            )
            .join("");

          fusionnerLignesParPivot(tableBody);

          masquerSpinner();
        } else {
          // Si les données sont vides, afficher un message vide
          tableBody.innerHTML =
            '<tr><td colspan="11">Aucune donnée disponible.</td></tr>';
          masquerSpinner();
        }
      })
      .catch((error) => {
        if (error.name === "AbortError") {
          console.log("Requête annulée !");
        } else {
          const tableBody = document.getElementById("commandesTableBody");
          tableBody.innerHTML =
            '<tr><td colspan="11">Could not retrieve data.</td></tr>';
          console.error("There was a problem with the fetch operation:", error);
          masquerSpinner();
        }
      });
  }
});

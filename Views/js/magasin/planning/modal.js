/** *======================
 * LIST DETAIL MODAL
 *  =======================*/
import { API_ENDPOINTS } from "../../api/apiEndpoints";
import { baseUrl } from "../../utils/config";

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
                  <td>${detail.num_ligne || ""}</td>
                  <td>${detail.type || ""}</td>
                  <td>${detail.num_bc_negoce || ""}</td>
                  <td>${detail.client || ""}</td>
                  <td>${detail.code_cst || ""}</td>
                  <td>${detail.ref || ""}</td>
                  <td>${detail.designation || ""}</td>
                  <td>${qte(detail.qte_dem)}</td>
                  <td>${qte(detail.qte_rest)}</td>
                  <td>${qte(detail.qte_a_livrer)}</td>
                  <td>${qte(detail.qte_livree)}</td>
                  <td>${qte(detail.qte_facturee)}</td>
                  <td>${detail.statut || ""}</td>
                  <td>${formaterDate(detail.eta_magasin)}</td>
                </tr>`
            )
            .join("");

          masquerSpinner();
        } else {
          // Si les données sont vides, afficher un message vide
          tableBody.innerHTML =
            '<tr><td colspan="5">Aucune donnée disponible.</td></tr>';
          masquerSpinner();
        }
      })
      .catch((error) => {
        if (error.name === "AbortError") {
          console.log("Requête annulée !");
        } else {
          const tableBody = document.getElementById("commandesTableBody");
          tableBody.innerHTML =
            '<tr><td colspan="5">Could not retrieve data.</td></tr>';
          console.error("There was a problem with the fetch operation:", error);
          masquerSpinner();
        }
      });
  }

  function formaterDate(daty) {
    if (!daty || daty === "0000-00-00" || daty === "0000-00-00 00:00:00")
      return "";
    const date = new Date(daty);
    if (isNaN(date.getTime())) return "";

    const formatted = date.toLocaleDateString("fr-FR");
    if (formatted === "01/01/1970" || formatted === "01/01/1900") return "";

    return formatted;
  }
});

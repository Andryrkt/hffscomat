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
    const orIntv = button.getAttribute("data-id");
    const numCdeFrn = document.getElementById("numCdeFrn");
    const emetteur = document.getElementById("emetteurCdeFrn");

    numCdeFrn.textContent = orIntv;
    emetteur.textContent = button.dataset.emetteur;

    // Afficher le spinner
    document.getElementById("loading").style.display = "block";
    document.getElementById("dataContent").style.display = "none";

    // Utiliser AbortController pour fetchDetailModal
    fetchDetailModal(orIntv, abortController.signal);
  });

  // Gestionnaire pour la fermeture du modal
  listeCommandeModal.addEventListener("hidden.bs.modal", function () {
    const tableBody = document.getElementById("commandesTableBody");
    const numCdeFrn = document.getElementById("numCdeFrn");
    const emetteur = document.getElementById("emetteurCdeFrn");
    const planningTableHead = document.getElementById("planningTableHead");

    tableBody.innerHTML = ""; // Vider le tableau
    numCdeFrn.textContent = "";
    emetteur.textContent = "";
    planningTableHead.innerHTML = "";
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
        const planningTableHead = document.getElementById("planningTableHead");
        tableBody.innerHTML = ""; // Clear previous data
        planningTableHead.innerHTML = "";

        if (data.data.length > 0) {
          let rowHeader = `<th>N° Ligne</th>
                          <th>Type</th>
                          <th>N° BC Négoce</th>
                          <th>Client</th>
                          <th>Const.</th>
                          <th>Réf</th>
                          <th>Désignation</th>
                          <th>Qté DEM.</th>
                          <th>Qté REST.</th>
                          <th>A Livrer</th>
                          <th>Qté Livrée</th>
                          <th>Qté FAC.</th>
                          <th>Statut</th>
                          <th>Eta Magasin</th>
                        `;
          planningTableHead.innerHTML += rowHeader;
          data.data.forEach((detail) => {
            const qte = (val) => (parseInt(val) === 0 ? "" : parseInt(val));
            let row = `<tr>
                      <td>${detail.num_ligne || ""}</td>
                      <td>${detail.type || ""}</td>
                      <td>${detail.num_bc_negoce || ""}</td>
                      <td>${detail.client || ""}</td>
                      <td>${detail.const || ""}</td>
                      <td>${detail.ref || ""}</td>
                      <td>${detail.desi || ""}</td>
                      <td>${qte(detail.qte_dem)}</td>
                      <td>${qte(detail.qte_rest)}</td>
                      <td>${qte(detail.alivrer)}</td>
                      <td>${qte(detail.qteLivree)}</td>
                      <td>${qte(detail.qtefac)}</td>
                      <td>${detail.statut || ""}</td>
                      <td>${formaterDate(detail.Eta_magasin)}</td>
                  </tr>`;
            tableBody.innerHTML += row;
          });

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
    if (
      !daty ||
      daty === "" ||
      daty === "0000-00-00" ||
      daty === "0000-00-00 00:00:00"
    )
      return "";
    const date = new Date(daty);
    if (isNaN(date.getTime())) return "";

    const d = date.getDate().toString().padStart(2, "0");
    const m = (date.getMonth() + 1).toString().padStart(2, "0");
    const y = date.getFullYear();
    const formatted = `${d}/${m}/${y}`;

    if (formatted === "01/01/1970" || formatted === "01/01/1900") return "";

    return formatted;
  }

  /**
   * pour le separateur et fusion des numOR
   *
   * */
  const tableBody = document.querySelector("#tableBody");
  const rows = document.querySelectorAll("#tableBody tr");

  let previousOrNumber = null;
  let rowSpanCount = 0;
  let firstRowInGroup = null;

  for (var i = 0; i < rows.length; i++) {
    let currentRow = rows[i];
    let orNumberCell = currentRow.getElementsByTagName("td")[2]; // Modifier l'indice selon la position du numéro OR
    let currentOrNumber = orNumberCell ? orNumberCell.textContent.trim() : null;

    if (previousOrNumber === null) {
      // Initialisation pour la première ligne
      firstRowInGroup = currentRow;
      rowSpanCount = 1;
    } else if (previousOrNumber && previousOrNumber !== currentOrNumber) {
      if (firstRowInGroup) {
        let cellToRowspanNumDit = firstRowInGroup.getElementsByTagName("td")[1]; // Modifier l'indice selon la position du numéro OR
        let cellToRowspanNumOr = firstRowInGroup.getElementsByTagName("td")[2];
        let cellToRowspanInter = firstRowInGroup.getElementsByTagName("td")[7];
        let cellToRowspanAgence = firstRowInGroup.getElementsByTagName("td")[5];
        let cellToRowspanService =
          firstRowInGroup.getElementsByTagName("td")[6];
        cellToRowspanNumDit.rowSpan = rowSpanCount;
        cellToRowspanNumOr.rowSpan = rowSpanCount;
        cellToRowspanInter.rowSpan = rowSpanCount;
        cellToRowspanAgence.rowSpan = rowSpanCount;
        cellToRowspanService.rowSpan = rowSpanCount;
        cellToRowspanNumDit.classList.add("rowspan-cell");
        cellToRowspanNumOr.classList.add("rowspan-cell");
        cellToRowspanInter.classList.add("rowspan-cell");
        cellToRowspanAgence.classList.add("rowspan-cell");
        cellToRowspanService.classList.add("rowspan-cell");
      }

      // Début pour le séparateur
      let separatorRow = document.createElement("tr");
      separatorRow.classList.add("separator-row");
      let td = document.createElement("td");
      td.colSpan = currentRow.cells.length;
      td.classList.add("p-0");
      separatorRow.appendChild(td);
      tableBody.insertBefore(separatorRow, currentRow);
      // Fin pour le séparateur

      rowSpanCount = 1;
      firstRowInGroup = currentRow;
    } else {
      rowSpanCount++;
      if (firstRowInGroup !== currentRow) {
        currentRow.getElementsByTagName("td")[2].style.display = "none";
        currentRow.getElementsByTagName("td")[1].style.display = "none";
        currentRow.getElementsByTagName("td")[7].style.display = "none";
        currentRow.getElementsByTagName("td")[5].style.display = "none";
        currentRow.getElementsByTagName("td")[6].style.display = "none";
      }
    }

    previousOrNumber = currentOrNumber;
  }

  // Appliquer le rowspan à la dernière série de lignes
  if (firstRowInGroup) {
    let cellToRowspanNumDit = firstRowInGroup.getElementsByTagName("td")[1]; // Modifier l'indice selon la position du numéro OR
    let cellToRowspanNumOr = firstRowInGroup.getElementsByTagName("td")[2];
    let cellToRowspanInter = firstRowInGroup.getElementsByTagName("td")[7];
    let cellToRowspanAgence = firstRowInGroup.getElementsByTagName("td")[5];
    let cellToRowspanService = firstRowInGroup.getElementsByTagName("td")[6];
    cellToRowspanNumDit.rowSpan = rowSpanCount;
    cellToRowspanNumOr.rowSpan = rowSpanCount;
    cellToRowspanInter.rowSpan = rowSpanCount;
    cellToRowspanAgence.rowSpan = rowSpanCount;
    cellToRowspanService.rowSpan = rowSpanCount;
    cellToRowspanNumDit.classList.add("rowspan-cell");
    cellToRowspanNumOr.classList.add("rowspan-cell");
    cellToRowspanInter.classList.add("rowspan-cell");
    cellToRowspanAgence.classList.add("rowspan-cell");
    cellToRowspanService.classList.add("rowspan-cell");
  }
});

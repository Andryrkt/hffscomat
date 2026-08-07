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
    const numDit = button.getAttribute("data-numDit");
    const migration = button.getAttribute("data-migration");
    const dossierDitLink = document.getElementById("dossierDitLink");
    if (migration == "1") {
      dossierDitLink.style.display = "none";
    }

    dossierDitLink.onclick = (event) => {
      event.preventDefault();
      window.open(
        `${baseUrl}/${API_ENDPOINTS.getDossierDit(numDit)}`,
        "_blank"
      );
    };

    // Afficher le spinner
    document.getElementById("loading").style.display = "block";
    document.getElementById("dataContent").style.display = "none";

    // Utiliser AbortController pour fetchDetailModal
    fetchDetailModal(orIntv, abortController.signal);
  });

  // Gestionnaire pour la fermeture du modal
  listeCommandeModal.addEventListener("hidden.bs.modal", function () {
    const tableBody = document.getElementById("commandesTableBody");
    const Ornum = document.getElementById("orIntv");
    const planningTableHead = document.getElementById("planningTableHead");

    tableBody.innerHTML = ""; // Vider le tableau
    Ornum.innerHTML = "";
    planningTableHead.innerHTML = "";
  });

  function masquerSpinner() {
    // Masquer le spinner et afficher les données
    document.getElementById("loading").style.display = "none";
    document.getElementById("dataContent").style.display = "block";
  }

  function fetchDetailModal(id, signal) {
    // TODO: définir le moyen d'obtenir les données du modal (endpoint API)
    fetch(``, { signal })
      .then((response) => {
        if (!response.ok) {
          throw new Error("Network response was not ok");
        }
        return response.json();
      })
      .then((data) => {
        const Ornum = document.getElementById("orIntv");
        const tableBody = document.getElementById("commandesTableBody");
        const planningTableHead = document.getElementById("planningTableHead");
        tableBody.innerHTML = ""; // Clear previous data
        Ornum.innerHTML = "";
        planningTableHead.innerHTML = "";

        if (data.data.length > 0) {
          let rowHeader = `<th>N°BC Irium </th>
                          <th>Ligne</th>
                          <th>N° Commande</th>
                          <th>Statut ctrmrq</th>
                          <th>CST</th>
                          <th>Ref</th>
                          <th>Désignation</th>
                          <th>Qté DEM</th>
                          <th>Qté ALL</th>
                          <th>QTé RLQ</th>
                          <th>QTé LIV</th>
                          <th>Statut</th>
                          <th>Date Statut</th>
                          <th>Eta Maurice</th>
                          <th>Eta Magasin</th>
                        `;
          planningTableHead.innerHTML += rowHeader;
          data.data.forEach((detail) => {
            Ornum.innerHTML = `${detail.numor} | intitulé : ${detail.commentaire} | `;
            if (detail.plan == "PLANIFIE") {
              Ornum.innerHTML += `délai client  : ${formaterDate(
                detail.dateplanning
              )}`;
            } else {
              Ornum.innerHTML += `date début : ${formaterDate(
                detail.dateplanning
              )}`;
            }
            // Formater la date
            let dateStatut = formaterDate(detail.datestatut);
            if (detail.cst && detail.cst.startsWith("Z")) {
              dateStatut = "";
            }

            let dateEtatPays = formaterDate(detail.Etat_pays);
            let dateEtaMagasin = formaterDate(detail.Eta_magasin);

            let numCde = detail.numerocmd || "";
            let statrmq = detail.statut_ctrmq || "";
            let statut =
              detail.statut == null || detail.cst.startsWith("Z")
                ? ""
                : detail.statut;
            let cmdColorRmq = "";
            let numRef = detail.ref || "";

            //reception partiel
            let qteSolde = parseInt(detail.qteSlode);
            let qteQte = parseInt(detail.qte);

            if (qteSolde > 0 && qteSolde != qteQte) {
              cmdColorRmq = 'style="background-color: yellow;"';
            }
            let cmdColor;
            let Ord = detail.Ord;
            if (statut == "DISPO STOCK") {
              cmdColor = 'style="background-color: #c8ad7f; color: white;"';
            } else if (statut == "Error" || statut == "Back Order") {
              cmdColor = 'style="background-color: red; color: white;"';
            } else if (Ord == "ORD") {
              cmdColor = 'style="background-color:#9ACD32  ; color: white;"';
            } else if (detail.estDansCesMagasin) {
              cmdColor = 'style="background-color:#9ACD32  ; color: white;"';
            }
            let row = `<tr>
                      <td>${detail.numor}</td>
                      <td>${detail.intv}</td>
                      <td ${cmdColor}>${numCde}</td>
                      <td ${cmdColorRmq}>${statrmq}</td>
                      <td>${detail.cst}</td>
                      <td>${numRef}</td>
                      <td>${detail.desi}</td>
                      <td>${parseInt(detail.qteres_or) === 0 ? "" : parseInt(detail.qteres_or)}</td>
                      <td>${parseInt(detail.qteall) === 0 ? "" : parseInt(detail.qteall)}</td>
                      <td>${parseInt(detail.qtereliquat) === 0 ? "" : parseInt(detail.qtereliquat)}</td>
                      <td>${parseInt(detail.qteliv) === 0 ? "" : parseInt(detail.qteliv)}</td>
                      <td >${statut}</td>
                      <td>${dateStatut}</td>
                      <td>${dateEtatPays}</td>
                      <td>${dateEtaMagasin}</td>
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

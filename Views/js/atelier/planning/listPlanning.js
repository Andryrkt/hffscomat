/** *======================
 * LIST DETAIL MODAL
 *  =======================*/

import { baseUrl } from "../../utils/config";

document.addEventListener("DOMContentLoaded", (event) => {
  let abortController; // AbortController pour annuler les requêtes fetch précédentes

  const listeCommandeModal = document.getElementById("listeCommande");

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
        `${baseUrl}/atelier/demande-intervention/dw-intervention-atelier-avec-dit/${numDit}`,
        "_blank"
      );
    };

    // Afficher le spinner
    document.getElementById("loading").style.display = "block";
    document.getElementById("dataContent").style.display = "none";

    const numOr = orIntv.split("-")[0];
    const numItv = orIntv.split("-")[1];

    // Utiliser AbortController pour fetchDetailModal
    fetchDetailModal(orIntv, abortController.signal);
    fetchTechnicienInterv(numOr, numItv, abortController.signal);
  });

  // Gestionnaire pour la fermeture du modal
  listeCommandeModal.addEventListener("hidden.bs.modal", function () {
    const tableBody = document.getElementById("commandesTableBody");
    const tableBodyOR = document.getElementById("commandesTableBodyOR");
    const tableBodyLign = document.getElementById("commandesTableBodyLign");
    const Ornum = document.getElementById("orIntv");
    const planningTableHead = document.getElementById("planningTableHead");

    tableBody.innerHTML = ""; // Vider le tableau
    tableBodyLign.innerHTML = "";
    tableBodyOR.innerHTML = "";
    Ornum.innerHTML = "";
    planningTableHead.innerHTML = "";
  });

  function masquerSpinner() {
    // Masquer le spinner et afficher les données
    document.getElementById("loading").style.display = "none";
    document.getElementById("dataContent").style.display = "block";
  }

  function fetchTechnicienInterv(numOr, numItv, signal) {
    fetch(`${baseUrl}/api/technicien-intervenant/${numOr}/${numItv}`, {
      signal,
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error("Network response was not ok");
        }
        return response.json();
      })
      .then((data) => {
        const tableBody = document.getElementById("technicienTableBody");

        tableBody.innerHTML = ""; // Clear previous data

        if (data.length > 0) {
          data.forEach((technicien) => {
            let nomPrenom = technicien.matriculenomprenom.split("-")[1];
            // Affichage
            let row = `<tr>
              <td>${technicien.matricule}</td> 
              <td>${nomPrenom}</td> 
          </tr>`;
            tableBody.innerHTML += row;
          });
        } else {
          // Si les données sont vides, afficher un message vide
          tableBody.innerHTML =
            '<tr><td colspan="5">Aucune donnée disponible.</td></tr>';
        }
      })
      .catch((error) => {
        if (error.name === "AbortError") {
          console.log("Requête annulée !");
        } else {
          const tableBody = document.getElementById("technicienTableBody");
          tableBody.innerHTML =
            '<tr><td colspan="5">Could not retrieve data.</td></tr>';
          console.error("There was a problem with the fetch operation:", error);
        }
      });
  }

  function fetchDetailModal(id, signal) {
    // Fetch request to get the data
    console.log(id, signal);
    fetch(`${baseUrl}/api/detail-modal/${id}`, { signal })
      .then((response) => {
        if (!response.ok) {
          console.log(response);
          throw new Error("Network response was not ok");
        }
        return response.json();
      })
      .then((data) => {
        console.log(data.avecOnglet);

        displayOnglet(data.avecOnglet);
        const Ornum = document.getElementById("orIntv");
        const tableBody = document.getElementById("commandesTableBody");
        const planningTableHead = document.getElementById("planningTableHead");
        const tableBodyOR = document.getElementById("commandesTableBodyORAte");
        const planningTableHeadOR = document.getElementById(
          "planningTableHeadOR"
        );
        const tableBodyLign = document.getElementById("commandesTableBodyLign");
        const planningTableHeadLign = document.getElementById(
          "planningTableHeadLign"
        );

        tableBody.innerHTML = ""; // Clear previous data
        Ornum.innerHTML = "";
        planningTableHead.innerHTML = "";
        planningTableHeadOR.innerHTML = "";
        planningTableHeadLign.innerHTML = "";

        console.log(data.data, data.data.length)
        let rowHeader = `<th>N° OR</th>
                            <th>Intv</th>
                            <th>N° CIS</th>
                            <th>N° Commande</th>
                            <th>Statut ctrmrq</th>
                            <th>CST</th>
                            <th>Ref</th>
                            <th>Désignation</th>
                            <th>Qté OR</th>
                            <th>Qté ALL</th>
                            <th>QTé RLQ</th>
                            <th>QTé LIV</th>
                            <th>Statut</th>
                            <th>Date Statut</th>`
        if (data.data.length > 0) {
          
            planningTableHead.innerHTML += rowHeader;
          
          data.data.forEach((detail) => {
            console.log(detail);

            Ornum.innerHTML = `${detail.num_or} - ${detail.num_itv} | intitulé : ${detail.commentaire} | `;
            if (detail.planning == "PLANIFIE") {
              Ornum.innerHTML += `planifié le : ${formaterDate(
                detail.date_planning
              )}`;
            } else {
              Ornum.innerHTML += `date début : ${formaterDate(
                detail.date_planning
              )}`;
            }
            // Formater la date
            let dateStatut;
            let numCis;
            let numCde;
            let numeroCdeCis;
            let statrmq;
            let StatutCtrmqCis;
            let statut;
            let message;
            let cmdColorRmq = "";
            let numRef;
            if (
              formaterDate(detail.datestatut) == "01/01/1970" ||
              formaterDate(detail.datestatut) == "01/01/1900"
            ) {
              dateStatut = "";
            } else {
              dateStatut = formaterDate(detail.datestatut);
            }
           
            if (detail.num_cmd == null) {
              numCde = "";
            } else {
              numCde = detail.num_cmd;
            }
            if (detail.ref == null) {
              numRef = "";
            } else {
              numRef = detail.ref;
            }
            if (detail.statut_ctrmq == null) {
              statrmq = "";
            } else {
              statrmq = detail.statut_ctrmq;
            }
            if (detail.statut == null) {
              statut = "";
            } else {
              statut = detail.statut;
            }

            if (detail.message == null) {
              message = "";
            } else {
              message = detail.message;
            }

            if (detail.num_cis == "0") {
              numCis = "";
            } else {
              numCis = detail.num_cis;
            }
            if (detail.num_cmd_cis == null) {
              numeroCdeCis = "";
            } else {
              numeroCdeCis = detail.num_cmd_cis;
            }
            if (detail.statut_ctrmq_cis == null) {
              StatutCtrmqCis = "";
            } else {
              StatutCtrmqCis = detail.statut_ctrmq_cis;
            }

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
            }
            //onglet CIS
            let statutCIS;
            let dateStatutCIS;

            if (
              parseInt(detail.qtelivlig) > 0 &&
              parseInt(detail.qtealllig) === 0 &&
              parseInt(detail.qterlqlig) === 0
            ) {
              statutCIS = "LIVRE";
              dateStatutCIS = formaterDate(detail.dateLivLIg);
            } else if (parseInt(detail.qtealllig) > 0) {
              statutCIS = "A LIVRER";
              dateStatutCIS = formaterDate(detail.dateAllLIg);
            } else {
              statutCIS = detail.statut;
              dateStatutCIS = "";
            }

            let row = `<tr>
                        <td>${detail.num_or}</td> 
                        <td>${detail.num_itv}</td> 
                        <td ${cmdColor}>${numCis}</td> 
                        <td ></td> 
                        <td></td> 
                        <td>${detail.cst}</td> 
                        <td>${numRef}</td> 
                        <td>${detail.desi}</td> 
                        <td>${parseInt(detail.qte_res_or)}</td> 
                        <td>${parseInt(detail.qte_all)}</td> 
                        <td>${parseInt(detail.qte_reliquat)}</td> 
                        <td>${parseInt(detail.qte_liv)}</td> 
                        <td>${statut} </td> 
                        <td>${dateStatut}</td>
                    </tr>`;
              // tableBody.innerHTML += row;
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

  function displayOnglet(show) {
    const avecOnglet = document.getElementById("avec_onglet");
    const sansOnglet = document.getElementById("sans_onglet");
    if (show) {
      avecOnglet.classList.remove("d-none");
      sansOnglet.classList.add("d-none");
    } else {
      avecOnglet.classList.add("d-none");
      sansOnglet.classList.remove("d-none");
    }
  }

  function formaterDate(daty) {
    const date = new Date(daty);
    return `${date.getDate().toString().padStart(2, "0")}/${(
      date.getMonth() + 1
    )
      .toString()
      .padStart(2, "0")}/${date.getFullYear()}`;
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

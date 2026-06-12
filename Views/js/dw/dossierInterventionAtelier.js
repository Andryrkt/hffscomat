import { afficherFichier, couleurDefondClick } from "./utils.js";
import { FetchManager } from "../api/FetchManager.js";

// Instanciation de FetchManager avec la base URL
const fetchManager = new FetchManager();

document.addEventListener("DOMContentLoaded", function () {
  // Sélectionne toutes les lignes du premier tableau
  const rows = document.querySelectorAll(".clickable-row");
  const spinners = document.getElementById("spinners");
  const spinner = document.getElementById("spinner");

  rows.forEach(function (row) {
    row.addEventListener("click", function () {
      couleurDefondClick(row);
      // Récupère le numéro DIT de la ligne cliquée
      const numeroDit = this.dataset.dit;

      // Affiche le spinner
      spinner.style.display = "block";

      // Met à jour le titre avec le numéro DIT
      document.getElementById("numero-dit").textContent = numeroDit;
      console.log(numeroDit);

      fetchManager
        .get(`api/dw-fetch/${numeroDit}`)
        .then((response) => {
          console.log(response);
          // Masque le spinner une fois les données chargées
          spinner.style.display = "none";

          // Remplace le tbody du tableau à chaque clic
          const newTbody = document.createElement("tbody");
          newTbody.id = "documents-tbody";
          const oldTbody = document.getElementById("documents-tbody");
          oldTbody.parentNode.replaceChild(newTbody, oldTbody);

          // Parcourt les données reçues et insère chaque document dans le tableau
          response.data.forEach((doc) => {
            const row = document.createElement("tr");

            row.innerHTML = `
                      <td>${doc.iconRaw}</td>
                      <td>${doc.nomDoc}</td>
                      <td>${doc.numeroDoc}</td>
                      <td class="text-center">${doc.dateCreation}</td>
                      <td class="text-center">${doc.dateModification}</td>
                      <td class="text-center">${doc.numeroVersion}</td>
                      <td class="text-center">${doc.totalPage}</td>
                      <td class="text-end">${doc.tailleFichier}</td>
                  `;
            row.classList.add("clickable-row");

            // Ajoute un événement de clic pour afficher le fichier dans la page
            row.addEventListener("click", function () {
              couleurDefondClick(row);

              // Affiche le spinner avant d'afficher le fichier
              spinners.style.display = "block";

              afficherFichier(doc.chemin); // Appelle la fonction pour afficher le fichier

              // Masque le spinner après l'affichage du fichier
              spinners.style.display = "none";
            });

            newTbody.appendChild(row);
          });
        })
        .catch((error) => {
          console.error("Erreur lors de la récupération des données:", error);
          // Masque le spinner en cas d'erreur
          spinner.style.display = "none";
        });
    });
  });
});

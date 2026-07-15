import { FetchManager } from "../../api/FetchManager";

const fetchManager = new FetchManager();

async function generatePdf(numCde) {
  return await fetchManager.get(
    `api/generer-pdf-cmde-fournisseur?numCde=${numCde}`,
  );
}

document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("myForm");
  const pdfBtn = document.getElementById("genererPdf");
  const validationBtn = document.getElementById("validationBtn");
  const iframe = document.getElementById("pdf-iframe");
  const loader = document.getElementById("spinners");
  const viewerContainer = document.getElementById("viewer-container");
  const numCdeInput = document.getElementById("soumission_commande_numCmde");

  numCdeInput.addEventListener("keydown", function (e) {
    if (e.key === "Enter") {
      e.preventDefault();
      pdfBtn.click();
    }
  });

  pdfBtn.addEventListener("click", async function (e) {
    const numCde = numCdeInput.value.trim();
    if (!numCde) {
      Swal.fire({
        icon: "warning",
        title: "Numéro de commande requis",
        text: "Veuillez saisir un numéro de commande.",
        timer: 2500,
        showConfirmButton: false,
      });
      return;
    }
 

    try {
      loader.classList.remove("d-none");
      viewerContainer.style.display = "block";
      iframe.src = "";

      const response = await generatePdf(numCde);
      const pdfUrl = response.url || response;

      if (!pdfUrl) {
        throw new Error("Aucune URL de PDF reçue");
      }

      iframe.src = pdfUrl + "#toolbar=0";
      validationBtn.style.display = "block";
    } catch (error) {
      console.error(error);
      Swal.fire({
        icon: "error",
        title: "Erreur",
        text: "Impossible de générer le PDF : " + error.message,
        timer: 3000,
        showConfirmButton: false,
      });
      loader.classList.add("d-none");
      viewerContainer.style.display = "none";
    }
  });

  // ─── Hide loader when iframe loads ───
  iframe.addEventListener("load", function () {
    viewerContainer.style.display = "block";
    loader.classList.add("d-none");
  });

  // ─── Validation (submission) ───

  form.addEventListener("submit", function (e) {
    const submitter = e.submitter;

    if (submitter && submitter.name === "soumissionValidationCommande") {
      e.preventDefault();

      Swal.fire({
        title: "Confirmer la soumission",
        html: `Êtes-vous sûr de vouloir <strong style="color: #f8bb86;">soumettre</strong> cette demande ?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#198754",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Oui, Soumettre",
        cancelButtonText: "Non, Annuler",
      }).then((result) => {
        if (result.isConfirmed) {
          const hidden = document.createElement("input");
          hidden.type = "hidden";
          hidden.name = "action";
          hidden.value = "validate";
          form.appendChild(hidden);
          form.submit();
        } else {
          Swal.fire({
            icon: "info",
            title: "Annulé",
            text: "La soumission de la demande a été annulée.",
            timer: 2000,
            showConfirmButton: false,
          });
        }
      });
    }
  });
});

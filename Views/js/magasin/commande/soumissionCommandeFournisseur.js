import { API_ENDPOINTS } from "../../api/apiEndpoints";
import { ApiRequestManager } from "../../api/ApiRequestManager";

const apiManager = new ApiRequestManager();

document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("myForm");
  const pdfBtn = document.getElementById("genererPdf");
  const validationBtn = document.getElementById("validationBtn");
  const iframe = document.getElementById("pdf-iframe");
  const loader = document.getElementById("spinners");
  const viewerContainer = document.getElementById("viewer-container");
  const numCdeInput = document.getElementById("soumission_commande_numCmde");
  const hiddenNumCdeAValiderInput = document.getElementById(
    "soumission_commande_numCmdeAValider"
  );
  const hiddenGeneratedFilePath = document.getElementById(
    "soumission_commande_generatedFilePath"
  );

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

      const response = await apiManager.get(
        API_ENDPOINTS.generatePdfCdeFrnMag(numCde)
      );
      const pdfUrl = response.url || response;

      if (!pdfUrl) {
        throw new Error("Aucune URL de PDF reçue");
      }

      hiddenNumCdeAValiderInput.value = numCde;
      hiddenGeneratedFilePath.value = pdfUrl;

      console.log(hiddenGeneratedFilePath, hiddenNumCdeAValiderInput);

      iframe.src = `${pdfUrl}#zoom=150`;
      loader.classList.add("d-none");
      validationBtn.style.display = "block";
    } catch (error) {
      console.error(error);
      Swal.fire({
        icon: "error",
        title: "Erreur",
        html: "Impossible de générer le PDF : <br>" + error.message,
      });
      loader.classList.add("d-none");
      validationBtn.style.display = "none";
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

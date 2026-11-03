import { ApiRequestManager } from "../../api/ApiRequestManager.js";

const fetchManager = new ApiRequestManager();

document.addEventListener("DOMContentLoaded", () => {
  const checkAll = document.getElementById("or-check-all");
  const rowCheckboxes = document.querySelectorAll(".or-row-selector");
  const selectedCountLabel = document.getElementById("or-selected-count");
  const sendCountLabel = document.getElementById("or-send-count");
  const btnSend = document.getElementById("btn-send-for-processing");

  const confirmModalTbody = document.getElementById("confirm-send-tbody");
  const btnConfirmSend = document.getElementById("btn-confirm-send");
  const confirmModal = document.getElementById("confirm-send-modal");

  // Fonction pour mettre à jour l'interface (compteurs, boutons, surlignage)
  function updateSelectionUI() {
    const checkedRows = document.querySelectorAll(".or-row-selector:checked");
    const count = checkedRows.length;

    if (selectedCountLabel) selectedCountLabel.textContent = count;
    if (sendCountLabel) sendCountLabel.textContent = count;

    if (btnSend) {
      if (count > 0) {
        btnSend.disabled = false;
        btnSend.classList.replace("btn-secondary", "btn-primary");
      } else {
        btnSend.disabled = true;
        btnSend.classList.replace("btn-primary", "btn-secondary");
      }
    }

    rowCheckboxes.forEach((cb) => {
      const tr = cb.closest("tr");
      if (cb.checked) {
        tr.classList.add("table-active");
      } else {
        tr.classList.remove("table-active");
      }
    });

    if (checkAll) {
      if (count === 0) {
        checkAll.checked = false;
        checkAll.indeterminate = false;
      } else if (count === rowCheckboxes.length) {
        checkAll.checked = true;
        checkAll.indeterminate = false;
      } else {
        checkAll.checked = false;
        checkAll.indeterminate = true;
      }
    }
  }

  if (checkAll) {
    checkAll.addEventListener("change", (e) => {
      rowCheckboxes.forEach((cb) => (cb.checked = e.target.checked));
      updateSelectionUI();
    });
  }

  rowCheckboxes.forEach((cb) => {
    cb.addEventListener("change", updateSelectionUI);
  });

  if (btnSend) {
    btnSend.addEventListener("click", () => {
      confirmModalTbody.innerHTML = "";

      document.querySelectorAll(".or-row-selector:checked").forEach((cb) => {
        const tr = cb.closest("tr");
        const numOr = tr.getAttribute("data-numor");
        const client = tr.getAttribute("data-client");
        const leadtime = tr.getAttribute("data-leadtime");

        const row = document.createElement("tr");
        row.innerHTML = `
                    <td class="fw-bold">${numOr}</td>
                    <td>${client}</td>
                    <td>${leadtime}</td>
                `;
        confirmModalTbody.appendChild(row);
      });
    });
  }

  if (btnConfirmSend) {
    btnConfirmSend.addEventListener("click", async () => {
      const selectedIds = Array.from(
        document.querySelectorAll(".or-row-selector:checked"),
      ).map((cb) => cb.value);

      if (selectedIds.length === 0) return;

      btnConfirmSend.disabled = true;
      btnConfirmSend.innerHTML =
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Envoi...';

      try {
        const url = `api/atelier/cva/or-csa/envoyer`;

        // ✅ Utilisation de FetchManager (si la méthode post retourne du JSON)
        const result = await fetchManager.post(url, { ors: selectedIds });

        // Vérification de la réponse
        if (result && result.success) {
          const modalInstance = bootstrap.Modal.getInstance(confirmModal);
          if (modalInstance) modalInstance.hide();

          let nombreLignesSupprimees = 0;
          document
            .querySelectorAll(".or-row-selector:checked")
            .forEach((cb) => {
              cb.closest("tr").remove();
              nombreLignesSupprimees++;
            });

          const nombreResultatLabel = document.getElementById("nombreResultat");
          if (nombreResultatLabel) {
            let texteActuel = nombreResultatLabel.textContent;
            let nombreActuel =
              parseInt(texteActuel.replace(/\D/g, ""), 10) || 0;
            let nouveauTotal = Math.max(
              0,
              nombreActuel - nombreLignesSupprimees,
            );
            nombreResultatLabel.textContent = nouveauTotal + " Résultats";
          }

          Swal.fire({
            icon: "success",
            title: "Transmission réussie !",
            html: `Les OR suivants ont été envoyés pour traitement :<br><br>
                   <span class="fw-bold text-success fs-5">${result.ors.join(" - ")}</span>`,
            confirmButtonText: "OK",
            confirmButtonColor: "#198754",
          }).then(() => {
            if (document.querySelectorAll(".or-row-selector").length === 0) {
              window.location.reload();
            } else {
              updateSelectionUI();
            }
          });
        } else {
          // Si l'API renvoie success: false
          throw new Error(
            result?.message || "Erreur lors du traitement serveur.",
          );
        }
      } catch (error) {
        console.error(error);
        Swal.fire({
          icon: "error",
          title: "Erreur",
          text: error.message || "Une erreur est survenue lors de l'appel API.",
        });
      } finally {
        btnConfirmSend.disabled = false;
        btnConfirmSend.innerHTML = "Confirmer l'envoi";
      }
    });
  }
});

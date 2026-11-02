document.addEventListener("DOMContentLoaded", () => {
  const checkAll = document.getElementById("or-check-all");
  const rowCheckboxes = document.querySelectorAll(".or-row-selector");
  const selectedCountLabel = document.getElementById("or-selected-count");
  const sendCountLabel = document.getElementById("or-send-count");
  const btnSend = document.getElementById("btn-send-for-processing");

  const confirmModalTbody = document.getElementById("confirm-send-tbody");
  const btnConfirmSend = document.getElementById("btn-confirm-send");
  const confirmModal = document.getElementById("confirm-send-modal");

  // 1. Mettre à jour l'état de la sélection
  function updateSelection() {
    const checkedRows = document.querySelectorAll(".or-row-selector:checked");
    const count = checkedRows.length;

    // Mettre à jour les textes
    selectedCountLabel.textContent = count;
    sendCountLabel.textContent = count;

    // Activer/Désactiver le bouton
    if (count > 0) {
      btnSend.disabled = false;
      btnSend.classList.replace("btn-secondary", "btn-primary"); // Devient bleu
    } else {
      btnSend.disabled = true;
      btnSend.classList.replace("btn-primary", "btn-secondary"); // Devient gris
    }

    // Surligner les lignes
    rowCheckboxes.forEach((cb) => {
      if (cb.checked) {
        cb.closest("tr").classList.add("selected");
      } else {
        cb.closest("tr").classList.remove("selected");
      }
    });

    // Mettre à jour la case "Tout cocher"
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

  // 2. Event: Case "Tout cocher"
  if (checkAll) {
    checkAll.addEventListener("change", (e) => {
      rowCheckboxes.forEach((cb) => (cb.checked = e.target.checked));
      updateSelection();
    });
  }

  // 3. Event: Cases individuelles
  rowCheckboxes.forEach((cb) => {
    cb.addEventListener("change", updateSelection);
  });

  // 4. Préparer la modale au clic sur "Envoyer"
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

  // 5. Appel API au clic sur "Confirmer l'envoi"
  btnConfirmSend.addEventListener("click", async () => {
    const selectedIds = Array.from(
      document.querySelectorAll(".or-row-selector:checked"),
    ).map((cb) => cb.value);

    if (selectedIds.length === 0) return;

    // Désactiver le bouton pour éviter le double-clic
    btnConfirmSend.disabled = true;
    btnConfirmSend.innerHTML = "Envoi en cours...";

    try {
      // ⚠️ Remplace cette URL par la route de ton API Symfony
      const response = await fetch("/atelier/cva/api/or-csa/envoyer", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify({ ors: selectedIds }),
      });

      if (!response.ok) throw new Error("Erreur réseau");

      const result = await response.json();

      if (result.success) {
        // Cacher la modale (via Bootstrap)
        const modalInstance = bootstrap.Modal.getInstance(confirmModal);
        modalInstance.hide();

        // Retirer les lignes traitées du tableau
        document.querySelectorAll(".or-row-selector:checked").forEach((cb) => {
          cb.closest("tr").remove();
        });

        // Réinitialiser la sélection
        updateSelection();

        // Afficher le message de succès
        alert(`Succès : Les OR ${result.ors.join(", ")} ont été envoyés.`);

        // Optionnel : recharger la page
        // window.location.reload();
      } else {
        alert("Erreur lors du traitement.");
      }
    } catch (error) {
      console.error(error);
      alert("Une erreur est survenue lors de l'appel API.");
    } finally {
      // Restaurer le bouton
      btnConfirmSend.disabled = false;
      btnConfirmSend.innerHTML = "Confirmer l'envoi";
    }
  });
});

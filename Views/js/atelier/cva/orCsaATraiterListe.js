import { ApiRequestManager } from "../../api/ApiRequestManager.js";

const fetchManager = new ApiRequestManager();

document.addEventListener("DOMContentLoaded", () => {
  // ==========================================================
  // HELPERS
  // ==========================================================
  const isRowVisible = (tr) => tr && tr.style.display !== "none";

  function getDataRows() {
    return Array.from(document.querySelectorAll("tbody tr[data-numor]"));
  }

  function getVisibleCheckboxes() {
    return Array.from(document.querySelectorAll(".or-row-selector")).filter(
      (cb) => isRowVisible(cb.closest("tr")),
    );
  }

  // ==========================================================
  // 1. FILTRE PAR STATUT (client-side)
  // ==========================================================
  const statutsBar = document.getElementById("cva-statuts-filter");
  const filterEmptyMsg = document.getElementById("or-filter-empty");
  let currentFilter = "tous";

  function applyStatutFilter(filter) {
    let visibleCount = 0;
    const allRows = getDataRows();

    allRows.forEach((tr) => {
      const match = filter === "tous" || tr.dataset.statut === filter;
      tr.style.display = match ? "" : "none";
      if (match) visibleCount++;

      // Décoche les lignes masquées (évite "coché invisible")
      if (!match) {
        const cb = tr.querySelector(".or-row-selector");
        if (cb) cb.checked = false;
      }
    });

    if (filterEmptyMsg) {
      const shouldShow = allRows.length > 0 && visibleCount === 0;
      filterEmptyMsg.classList.toggle("d-none", !shouldShow);
    }

    // Rafraîchit les compteurs de sélection après décoche
    updateSelectionUI();
  }

  function setActiveStatutFilter(filter) {
    currentFilter = filter;
    if (statutsBar) {
      statutsBar.querySelectorAll(".cva-legend-btn").forEach((btn) => {
        btn.classList.toggle("active", btn.dataset.filter === filter);
      });
    }
    applyStatutFilter(filter);
  }

  window.resetOrStatutFilter = () => setActiveStatutFilter("tous");
  window.reapplyOrStatutFilter = () => applyStatutFilter(currentFilter);

  if (statutsBar) {
    statutsBar.querySelectorAll(".cva-legend-btn").forEach((btn) => {
      btn.addEventListener("click", () =>
        setActiveStatutFilter(btn.dataset.filter),
      );
    });
  }

  // ==========================================================
  // 2. SÉLECTION (corrigée : visible uniquement)
  // ==========================================================
  const checkAll = document.getElementById("or-check-all");
  const selectedCountLabel = document.getElementById("or-selected-count");
  const sendCountLabel = document.getElementById("or-send-count");
  const btnSend = document.getElementById("btn-send-for-processing");

  function updateSelectionUI() {
    const visibleCheckboxes = getVisibleCheckboxes();
    const checkedVisible = visibleCheckboxes.filter((cb) => cb.checked);
    const count = checkedVisible.length;

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

    // Surlignage : seulement lignes cochées ET visibles
    document.querySelectorAll(".or-row-selector").forEach((cb) => {
      const tr = cb.closest("tr");
      if (!tr) return;
      if (cb.checked && isRowVisible(tr)) tr.classList.add("table-active");
      else tr.classList.remove("table-active");
    });

    // État de la case "Tout sélectionner" (basé sur les visibles)
    if (checkAll) {
      const visibleCount = visibleCheckboxes.length;
      if (visibleCount === 0 || count === 0) {
        checkAll.checked = false;
        checkAll.indeterminate = false;
      } else if (count === visibleCount) {
        checkAll.checked = true;
        checkAll.indeterminate = false;
      } else {
        checkAll.checked = false;
        checkAll.indeterminate = true;
      }
    }
  }

  // "Tout sélectionner" : agit sur les lignes VISIBLES
  if (checkAll) {
    checkAll.addEventListener("change", (e) => {
      getVisibleCheckboxes().forEach((cb) => (cb.checked = e.target.checked));
      updateSelectionUI();
    });
  }

  document.addEventListener("change", (e) => {
    if (e.target.matches(".or-row-selector")) updateSelectionUI();
  });

  // ==========================================================
  // 3. MODALE D'ENVOI (ne liste que les OR visibles sélectionnés)
  // ==========================================================
  const confirmModalTbody = document.getElementById("confirm-send-tbody");
  const btnConfirmSend = document.getElementById("btn-confirm-send");
  const confirmModal = document.getElementById("confirm-send-modal");

  if (btnSend) {
    btnSend.addEventListener("click", () => {
      confirmModalTbody.innerHTML = "";
      getVisibleCheckboxes()
        .filter((cb) => cb.checked)
        .forEach((cb) => {
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

  // ==========================================================
  // 4. ENVOI API
  // ==========================================================
  if (btnConfirmSend) {
    btnConfirmSend.addEventListener("click", async () => {
      const selectedIds = getVisibleCheckboxes()
        .filter((cb) => cb.checked)
        .map((cb) => cb.value);

      if (selectedIds.length === 0) return;

      btnConfirmSend.disabled = true;
      btnConfirmSend.innerHTML =
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Envoi...';

      try {
        const result = await fetchManager.post(
          `api/atelier/cva/or-csa/envoyer`,
          { ors: selectedIds },
        );

        if (!result || !result.success) {
          throw new Error(
            result?.message || "Erreur lors du traitement serveur.",
          );
        }

        bootstrap.Modal.getInstance(confirmModal)?.hide();

        // Supprime + met à jour les compteurs de statut dans la légende
        let removed = 0;
        getVisibleCheckboxes()
          .filter((cb) => cb.checked)
          .forEach((cb) => {
            const tr = cb.closest("tr");
            const statut = tr.dataset.statut;

            // Décrémente le badge du statut concerné
            const btnStatut = statutsBar?.querySelector(
              `.cva-legend-btn[data-filter="${statut}"] .cva-legend-count`,
            );
            if (btnStatut) {
              const current = parseInt(btnStatut.textContent, 10) || 0;
              btnStatut.textContent = Math.max(0, current - 1);
            }
            // Décrémente le badge "Tous"
            const btnTous = statutsBar?.querySelector(
              `.cva-legend-btn[data-filter="tous"] .cva-legend-count`,
            );
            if (btnTous) {
              const current = parseInt(btnTous.textContent, 10) || 0;
              btnTous.textContent = Math.max(0, current - 1);
            }

            tr.remove();
            removed++;
          });

        // Total résultats (label serveur existant)
        const totalLabel = document.getElementById("nombreResultat");
        if (totalLabel) {
          const cur =
            parseInt(totalLabel.textContent.replace(/\D/g, ""), 10) || 0;
          totalLabel.textContent = Math.max(0, cur - removed) + " Résultats";
        }

        // ✅ Construire la liste à partir de result.details
        const succes = (result.details || []).filter((d) => d.success);
        const echecs = (result.details || []).filter((d) => !d.success);

        const lignesSucces = succes
          .map(
            (d) =>
              `<li><span class="fw-bold text-success">${d.numeroDit ?? d.numeroOr}</span>
       <small class="text-muted">(OR ${d.numeroOr})</small></li>`,
          )
          .join("");

        const lignesEchecs = echecs
          .map(
            (d) =>
              `<li><span class="fw-bold text-danger">${d.numeroOr}</span>
       <small class="text-muted">— ${d.message ?? "échec"}</small></li>`,
          )
          .join("");

        let htmlMsg = "";
        if (succes.length) {
          htmlMsg += `<p class="mb-1">DIT créée(s) :</p><ul class="text-start">${lignesSucces}</ul>`;
        }
        if (echecs.length) {
          htmlMsg += `<p class="mb-1 mt-2">OR en échec :</p><ul class="text-start">${lignesEchecs}</ul>`;
        }

        Swal.fire({
          icon:
            echecs.length === 0
              ? "success"
              : succes.length
                ? "warning"
                : "error",
          title:
            echecs.length === 0
              ? "Transmission réussie !"
              : "Transmission partielle",
          html: htmlMsg || result.message,
          confirmButtonText: "OK",
          confirmButtonColor: echecs.length === 0 ? "#198754" : "#f0ad4e",
        }).then(() => {
          if (document.querySelectorAll(".or-row-selector").length === 0) {
            window.location.reload();
          } else {
            updateSelectionUI();
            window.reapplyOrStatutFilter();
          }
        });
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

  // ==========================================================
  // 5. INIT + RESET SUR CHANGEMENT DE PAGE
  // ==========================================================
  window.resetOrStatutFilter();

  document.addEventListener("click", (e) => {
    const link = e.target.closest(
      ".pagination a, .pagination button, .page-link, [data-page-link]",
    );
    if (link && !link.closest(".disabled, .active")) {
      window.resetOrStatutFilter();
    }
  });

  document.addEventListener("or:pageChanged", () =>
    window.resetOrStatutFilter(),
  );
});

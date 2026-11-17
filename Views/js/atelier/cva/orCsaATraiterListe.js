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

  // Helper d'échappement — à placer en haut du fichier si absent
  const escapeHtml = (str) =>
    String(str ?? "").replace(
      /[&<>"']/g,
      (c) =>
        ({
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          '"': "&quot;",
          "'": "&#39;",
        })[c],
    );

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

        // ⚠️ Accepte aussi les réponses 207 (partiel) — on ne throw que si tout échoue
        if (!result) {
          throw new Error("Aucune réponse du serveur.");
        }
        const details = Array.isArray(result.details) ? result.details : [];
        if (details.length === 0) {
          throw new Error(result.message || "Réponse serveur inexploitable.");
        }

        bootstrap.Modal.getInstance(confirmModal)?.hide();

        // ---------------------------------------------------------
        // Classification : succès complet / partiel (PDF KO) / échec
        // ---------------------------------------------------------
        const complets = [];
        const partiels = [];
        const echecs = [];

        details.forEach((d) => {
          if (!d.success) {
            echecs.push(d);
            return;
          }
          if (!d.numeroDit) {
            // Cas anormal : le backend dit "succès" mais pas de numéro DIT
            console.error(
              "[CVA] Réponse incohérente : succès sans numeroDit",
              d,
            );
            echecs.push({
              ...d,
              success: false,
              message: "Réponse serveur incohérente (DIT manquante).",
            });
            return;
          }
          const pdfKo = d.partial === true || d.pdf?.success === false;
          (pdfKo ? partiels : complets).push(d);
        });

        // Index pour retrouver le détail par OR
        const detailsByOr = new Map(
          details.map((d) => [String(d.numeroOr), d]),
        );

        // ---------------------------------------------------------
        // Mise à jour du DOM : on retire uniquement les OR complets
        // Les OR partiels sont conservés et marqués "PDF à regénérer"
        // ---------------------------------------------------------
        let removed = 0;
        let keptPartiels = 0;

        getVisibleCheckboxes()
          .filter((cb) => cb.checked)
          .forEach((cb) => {
            const tr = cb.closest("tr");
            if (!tr) return;

            const numOr = tr.getAttribute("data-numor");
            const detail = detailsByOr.get(String(numOr));
            const isPartial =
              detail &&
              detail.success &&
              (detail.partial === true || detail.pdf?.success === false);

            if (isPartial) {
              // On garde la ligne : l'utilisateur doit pouvoir relancer le PDF
              cb.checked = false;
              tr.classList.add("table-warning");
              tr.dataset.pdfFailed = "1";

              // Badge visible dans la cellule PDF si elle existe
              const tdPdf = tr.querySelector("td[data-field='pdf']");
              if (tdPdf) {
                tdPdf.innerHTML =
                  '<span class="badge bg-warning text-dark">PDF à regénérer</span>';
              } else {
                // Fallback : ajoute un marqueur dans la première cellule
                const first = tr.querySelector("td");
                if (first && !first.querySelector(".pdf-ko-badge")) {
                  const badge = document.createElement("span");
                  badge.className =
                    "pdf-ko-badge badge bg-warning text-dark ms-1";
                  badge.textContent = "PDF KO";
                  first.appendChild(badge);
                }
              }
              keptPartiels++;
              return;
            }

            // Cas normal : suppression + décrément des compteurs
            const statut = tr.dataset.statut;

            const btnStatut = statutsBar?.querySelector(
              `.cva-legend-btn[data-filter="${statut}"] .cva-legend-count`,
            );
            if (btnStatut) {
              const current = parseInt(btnStatut.textContent, 10) || 0;
              btnStatut.textContent = Math.max(0, current - 1);
            }
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

        // Total résultats — on ne déduit que les lignes effectivement retirées
        const totalLabel = document.getElementById("nombreResultat");
        if (totalLabel) {
          const cur =
            parseInt(totalLabel.textContent.replace(/\D/g, ""), 10) || 0;
          totalLabel.textContent = Math.max(0, cur - removed) + " Résultats";
        }

        // ---------------------------------------------------------
        // Construction du message Swal
        // ---------------------------------------------------------
        const lignesComplets = complets
          .map(
            (d) =>
              `<li><span class="fw-bold text-success">${escapeHtml(d.numeroDit)}</span>
             <small class="text-muted">(OR ${escapeHtml(d.numeroOr)})</small></li>`,
          )
          .join("");

        const lignesPartiels = partiels
          .map(
            (d) =>
              `<li><span class="fw-bold text-warning">${escapeHtml(d.numeroDit)}</span>
             <small class="text-muted">(OR ${escapeHtml(d.numeroOr)})</small>
             <br><small class="text-danger">${escapeHtml(d.pdf?.message || d.message || "PDF non généré")}</small></li>`,
          )
          .join("");

        const lignesEchecs = echecs
          .map(
            (d) =>
              `<li><span class="fw-bold text-danger">${escapeHtml(d.numeroOr)}</span>
             <small class="text-muted">— ${escapeHtml(d.message || "échec")}</small></li>`,
          )
          .join("");

        let htmlMsg = "";

        // Résumé chiffré — toujours en premier
        if (result.message) {
          htmlMsg += `<p class="mb-2 fw-semibold">${escapeHtml(result.message)}</p>`;
        }

        if (complets.length) {
          htmlMsg += `<p class="mb-1">DIT créée(s) avec PDF :</p>
                    <ul class="text-start">${lignesComplets}</ul>`;
        }
        if (partiels.length) {
          htmlMsg += `<p class="mb-1 mt-2 text-warning"><strong>DIT créée(s) mais PDF non déposé :</strong></p>
                    <ul class="text-start">${lignesPartiels}</ul>
                    <small class="text-muted d-block mb-2">
                      Les lignes concernées ont été conservées dans le tableau.
                      Relancez la génération du PDF.
                    </small>`;
        }
        if (echecs.length) {
          htmlMsg += `<p class="mb-1 mt-2">OR en échec :</p>
                    <ul class="text-start">${lignesEchecs}</ul>`;
        }

        // Icône / titre / couleur : les 3 cas possibles
        let icon, title, confirmColor;
        if (echecs.length > 0) {
          icon = partiels.length || complets.length ? "warning" : "error";
          title = "Transmission partielle";
          confirmColor = "#f0ad4e";
        } else if (partiels.length > 0) {
          icon = "warning";
          title = "DIT créées, PDF en échec";
          confirmColor = "#f0ad4e";
        } else {
          icon = "success";
          title = "Transmission réussie !";
          confirmColor = "#198754";
        }

        Swal.fire({
          icon,
          title,
          html:
            htmlMsg || escapeHtml(result.message || "Aucun détail retourné."),
          confirmButtonText: "OK",
          confirmButtonColor: confirmColor,
        }).then(() => {
          if (document.querySelectorAll(".or-row-selector").length === 0) {
            window.location.reload();
          } else {
            updateSelectionUI();
            window.reapplyOrStatutFilter();
          }
        });

        // Console : trace les partiels pour debug / support
        if (partiels.length) {
          console.warn(
            `[CVA] ${partiels.length} DIT créée(s) sans PDF :`,
            partiels.map((d) => ({
              numeroOr: d.numeroOr,
              numeroDit: d.numeroDit,
              message: d.pdf?.message || d.message,
            })),
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

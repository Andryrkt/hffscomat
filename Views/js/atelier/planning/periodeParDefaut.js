/**
 * Renseigne automatiquement les dates début/fin du formulaire de recherche planning
 * en fonction de la période sélectionnée (champ "months"), selon la même logique
 * que RollingMonthsService / PlanningTraits côté back-end.
 */
document.addEventListener("DOMContentLoaded", function () {
  const moisSelect = document.getElementById("planning_search_months");
  const dateDebutInput = document.getElementById("planning_search_dateDebut");
  const dateFinInput = document.getElementById("planning_search_dateFin");

  if (!moisSelect || !dateDebutInput || !dateFinInput) return;

  function formatDate(date) {
    const pad = (n) => n.toString().padStart(2, "0");
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
  }

  // Reproduit generateMonthData() de PlanningTraits.php / RollingMonthsService.php
  function generateMonthData(currentMonthIndex, currentYear, offset) {
    const totalMonths = currentMonthIndex + offset;
    const monthIndex = ((totalMonths % 12) + 12) % 12;
    let year = currentYear + Math.trunc(totalMonths / 12);

    if (totalMonths < 0 && monthIndex > currentMonthIndex) {
      year--;
    }

    return { monthIndex, year };
  }

  // Détermine le premier et le dernier mois de la période de 12 mois (mêmes codes que PlanningSearchType)
  function getPeriodBounds(moisCode, referenceDate) {
    const currentMonthIndex = referenceDate.getMonth();
    const currentYear = referenceDate.getFullYear();
    const code = parseInt(moisCode, 10);

    switch (code) {
      case 3: // 3 mois suivant
      case 6: { // 6 mois suivant
        const monthsCount = code === 3 ? 4 : 7;
        return {
          first: generateMonthData(currentMonthIndex, currentYear, -(12 - monthsCount)),
          last: generateMonthData(currentMonthIndex, currentYear, monthsCount - 1),
        };
      }
      case 12: // 12 mois suivant
        return {
          first: generateMonthData(currentMonthIndex, currentYear, 0),
          last: generateMonthData(currentMonthIndex, currentYear, 11),
        };
      case 13: // 12 mois précédent
        return {
          first: generateMonthData(currentMonthIndex, currentYear, -11),
          last: generateMonthData(currentMonthIndex, currentYear, 0),
        };
      case 9: // Année en cours
        return { first: { monthIndex: 0, year: currentYear }, last: { monthIndex: 11, year: currentYear } };
      case 11: // Année suivante
        return { first: { monthIndex: 0, year: currentYear + 1 }, last: { monthIndex: 11, year: currentYear + 1 } };
      case 14: // Année précédente
        return { first: { monthIndex: 0, year: currentYear - 1 }, last: { monthIndex: 11, year: currentYear - 1 } };
      default:
        return null;
    }
  }

  function appliquerPeriodeParDefaut() {
    const bounds = getPeriodBounds(moisSelect.value, new Date());
    if (!bounds) return;

    const dateDebut = new Date(bounds.first.year, bounds.first.monthIndex, 1);
    const dateFin = new Date(bounds.last.year, bounds.last.monthIndex + 1, 0);

    dateDebutInput.value = formatDate(dateDebut);
    dateFinInput.value = formatDate(dateFin);
  }

  moisSelect.addEventListener("change", appliquerPeriodeParDefaut);

  if (!dateDebutInput.value && !dateFinInput.value) {
    appliquerPeriodeParDefaut();
  }
});

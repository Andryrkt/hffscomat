import { initializeFileHandlers } from "../../../../utils/file_upload_Utils.js";
import { setupConfirmationButtons } from "../../../../utils/ui/boutonConfirmUtils.js";

/**=======================================
 * traitement de telechargement du fichier
 *======================================*/
const fileInput = document.querySelector(
  `#dit_devis_soumis_a_validation_pieceJoint01`,
);
initializeFileHandlers(1, fileInput);

/**==================================================
 * sweetalert pour le bouton Enregistrer
 *==================================================*/

setupConfirmationButtons();

/**==================================================
 * Traitement de l'observation
 *==================================================*/
const observationInput = document.querySelector('#dit_devis_soumis_a_validation_observation');
const charCount = document.getElementById('observation-char-count');
const maxChars = 5000;

if (observationInput) {
  // Initialiser le compteur
  if (observationInput.value.length > 0) {
    charCount.textContent = observationInput.value.length + ' / ' + maxChars + ' caractères';
  } else {
    charCount.textContent = '0 / ' + maxChars + ' caractères';
  }

  // Ajouter un event listener pour compter les caractères en temps réel
  observationInput.addEventListener('input', function () {
    const length = this.value.length;
    charCount.textContent = length + ' / ' + maxChars + ' caractères';

    // Ajouter une classe CSS si la limite est atteinte
    if (length > maxChars) {
      charCount.style.color = 'red';
    } else {
      charCount.style.color = '';
    }
  });
}

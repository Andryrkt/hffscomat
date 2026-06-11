import { baseUpload } from "../utils/config";

export function conversionEnKo(nombre) {
  return (nombre / 1024).toFixed(2).replace(".", ",");
}

export function iconSelonTypeFile(extension) {
  const icons = {
    ".pdf": "-pdf",
    ".doc": "-word",
    ".docx": "-word",
    ".xls": "-excel",
    ".xlsx": "-excel",
    ".jpg": "-image",
    ".jpeg": "-image",
    ".png": "-image",
    ".zip": "-archive",
    ".rar": "-archive",
    ".txt": "-alt",
  };
  const icon = icons[extension.toLowerCase()] || "";

  return `<i class="fas fa-file${icon} fs-4"></i>`;
}

// Fonction pour afficher le fichier dans le conteneur
export function afficherFichier(cheminFichier) {
  const fileUrl = `${baseUpload}/${cheminFichier}`;

  const fileViewer = document.getElementById("file-viewer");
  fileViewer.innerHTML = `<iframe src="${fileUrl}#toolbar=0" width="100%" height="800px" frameborder="0"></iframe>`;
}

export function couleurDefondClick(row) {
  document.querySelectorAll("tr").forEach(function (r) {
    r.classList.remove("selected");
  });
  row.classList.add("selected");
}

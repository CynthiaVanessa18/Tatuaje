document.addEventListener("DOMContentLoaded", () => {
    const textarea = document.querySelector("[data-character-count]");
    const characterOutput = document.querySelector("[data-character-output]");

    const updateCharacterCount = () => {
        if (textarea && characterOutput) {
            characterOutput.textContent = String(textarea.value.length);
        }
    };

    textarea?.addEventListener("input", updateCharacterCount);
    updateCharacterCount();

    const input = document.querySelector("[data-file-input]");
    const previews = document.querySelector("[data-file-previews]");
    const zone = document.querySelector("[data-upload-zone]");

    const renderFiles = () => {
        if (!(input instanceof HTMLInputElement) || !previews) return;

        previews.replaceChildren();
        const files = Array.from(input.files || []).slice(0, 3);

        files.forEach((file, index) => {
            const item = document.createElement("figure");
            const image = document.createElement("img");
            const caption = document.createElement("figcaption");
            const reader = new FileReader();

            item.className = "quote-preview";
            caption.textContent = `${String(index + 1).padStart(2, "0")} · ${file.name}`;
            image.alt = `Vista previa de ${file.name}`;
            reader.addEventListener("load", () => {
                image.src = typeof reader.result === "string" ? reader.result : "";
            });
            reader.readAsDataURL(file);
            item.append(image, caption);
            previews.append(item);
        });

        if ((input.files?.length || 0) > 3) {
            const warning = document.createElement("p");
            warning.className = "quote-preview__warning";
            warning.textContent = "Selecciona un máximo de 3 imágenes antes de enviar.";
            previews.append(warning);
        }
    };

    input?.addEventListener("change", renderFiles);

    ["dragenter", "dragover"].forEach((eventName) => {
        zone?.addEventListener(eventName, () => zone.classList.add("is-dragging"));
    });

    ["dragleave", "drop"].forEach((eventName) => {
        zone?.addEventListener(eventName, () => zone.classList.remove("is-dragging"));
    });
});

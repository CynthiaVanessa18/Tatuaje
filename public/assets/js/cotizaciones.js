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
            const removeButton = document.createElement("button");
            const reader = new FileReader();

            item.className = "quote-preview";
            caption.textContent = `${String(index + 1).padStart(2, "0")} · ${file.name}`;
            removeButton.className = "quote-preview__remove";
            removeButton.type = "button";
            removeButton.setAttribute("aria-label", `Quitar ${file.name}`);
            removeButton.title = "Quitar imagen";
            removeButton.textContent = "×";
            image.alt = `Vista previa de ${file.name}`;
            reader.addEventListener("load", () => {
                image.src = typeof reader.result === "string" ? reader.result : "";
            });
            reader.readAsDataURL(file);
            removeButton.addEventListener("click", () => {
                const transfer = new DataTransfer();
                Array.from(input.files || []).forEach((selectedFile, selectedIndex) => {
                    if (selectedIndex !== index) transfer.items.add(selectedFile);
                });
                input.files = transfer.files;
                renderFiles();
            });
            item.append(image, removeButton, caption);
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

    const dateInput = document.querySelector("[data-quote-date]");
    const artistSelect = document.querySelector("[data-quote-artist]");
    const availabilityMessage = document.querySelector("[data-availability-message]");

    const loadAvailableArtists = async () => {
        if (!(dateInput instanceof HTMLInputElement) || !(artistSelect instanceof HTMLSelectElement)) return;

        if (!dateInput.value) {
            artistSelect.disabled = true;
            artistSelect.innerHTML = '<option value="">Primero elige una fecha</option>';
            return;
        }

        const selectedArtist = artistSelect.dataset.selectedArtist || artistSelect.value;
        artistSelect.disabled = true;
        artistSelect.innerHTML = '<option value="">Consultando la agenda…</option>';
        if (availabilityMessage) availabilityMessage.textContent = "Comprobando horarios, bloqueos y citas existentes…";

        try {
            const response = await fetch(`disponibilidad.php?fecha=${encodeURIComponent(dateInput.value)}`, {
                headers: { Accept: "application/json" },
            });
            const payload = await response.json();

            if (!response.ok || payload.ok !== true) {
                throw new Error(payload.message || "No fue posible consultar la agenda.");
            }

            artistSelect.replaceChildren(new Option("Que el estudio elija por mí", ""));
            payload.artists.forEach((artist) => {
                const suffix = artist.especialidades ? ` · ${artist.especialidades}` : "";
                const option = new Option(`${artist.nombre_publico}${suffix}`, String(artist.id_artista));
                option.selected = String(artist.id_artista) === String(selectedArtist);
                artistSelect.add(option);
            });
            artistSelect.disabled = payload.artists.length === 0;
            dateInput.setCustomValidity(payload.artists.length === 0 ? "No hay artistas disponibles ese día." : "");
            if (availabilityMessage) {
                availabilityMessage.textContent = payload.artists.length > 0
                    ? `${payload.artists.length} artista${payload.artists.length === 1 ? "" : "s"} con espacio disponible ese día.`
                    : "Ese día no es laboral o la agenda está completa. Elige otra fecha.";
            }
        } catch (error) {
            artistSelect.innerHTML = '<option value="">Agenda no disponible</option>';
            dateInput.setCustomValidity(error instanceof Error ? error.message : "No fue posible consultar la agenda.");
            if (availabilityMessage) availabilityMessage.textContent = dateInput.validationMessage;
        }
    };

    dateInput?.addEventListener("change", loadAvailableArtists);
    if (dateInput instanceof HTMLInputElement && dateInput.value) loadAvailableArtists();
});

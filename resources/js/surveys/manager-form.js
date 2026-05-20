document.addEventListener('DOMContentLoaded', () => {
    const wrapper = document.getElementById('questions-wrapper');
    const addButton = document.getElementById('add-question');
    const questionTemplate = document.getElementById('question-template');
    const optionTemplate = document.getElementById('option-template');
    const surveyForm = document.querySelector('form[enctype="multipart/form-data"]');
    const accessibilityStatus = document.getElementById('survey-manager-a11y-status');
    const blobUploadUrl = surveyForm?.dataset.blobUploadUrl ?? '';
    const pendingUploads = new Set();

    if (!wrapper || !questionTemplate || !optionTemplate || !surveyForm) {
        return;
    }

    const MAX_IMAGE_SIZE = 2 * 1024 * 1024;
    const MAX_TOTAL_IMAGE_SIZE = 5 * 1024 * 1024;

    function createOptionRow() {
        return optionTemplate.content.firstElementChild.cloneNode(true);
    }

    function announce(message) {
        if (!accessibilityStatus) {
            return;
        }

        accessibilityStatus.textContent = '';

        window.requestAnimationFrame(() => {
            accessibilityStatus.textContent = message;
        });
    }

    function getSelectedFileSize(input) {
        return input?.files?.[0]?.size ?? 0;
    }

    function calculateTotalSelectedImageSize() {
        let total = 0;

        document.querySelectorAll('[data-option-image]').forEach((input) => {
            total += getSelectedFileSize(input);
        });

        return total;
    }

    function getPreviewAlt(row) {
        return row?.querySelector('[data-option-image-alt]')?.value.trim()
            || row?.querySelector('[data-option-label]')?.value.trim()
            || 'Voorbeeld van antwoordoptie';
    }

    function updatePreviewAlt(row) {
        const previewImage = row?.querySelector('[data-image-preview]');

        if (previewImage) {
            previewImage.alt = getPreviewAlt(row);
        }
    }

    function setLocalPreview(row, file) {
        const previewWrapper = row?.querySelector('[data-image-preview-wrapper]');
        const previewImage = row?.querySelector('[data-image-preview]');

        if (!previewWrapper || !previewImage || !file) {
            return;
        }

        const previousUrl = row.dataset.previewObjectUrl;

        if (previousUrl) {
            URL.revokeObjectURL(previousUrl);
        }

        const objectUrl = URL.createObjectURL(file);
        row.dataset.previewObjectUrl = objectUrl;
        previewImage.src = objectUrl;
        previewWrapper.classList.remove('hidden');
        updatePreviewAlt(row);
    }

    function validateImageInput(input) {
        const file = input.files?.[0];

        if (!file) {
            return true;
        }

        if (file.size > MAX_IMAGE_SIZE) {
            alert('Een afbeelding mag maximaal 2 MB groot zijn.');
            input.value = '';
            return false;
        }

        return true;
    }

    function validateTotalImageSize(changedInput = null) {
        const totalSize = calculateTotalSelectedImageSize();

        if (totalSize > MAX_TOTAL_IMAGE_SIZE) {
            if (changedInput) {
                changedInput.value = '';
            }

            alert('De totale grootte van alle afbeeldingen mag maximaal 5 MB zijn.');
            return false;
        }

        return true;
    }

    function bindPreviewFields(scope = document) {
        scope.querySelectorAll('.option-row').forEach((row) => {
            const labelInput = row.querySelector('[data-option-label]');
            const altInput = row.querySelector('[data-option-image-alt]');

            [labelInput, altInput].forEach((field) => {
                if (!field || field.dataset.previewBound === 'true') {
                    return;
                }

                field.dataset.previewBound = 'true';
                field.addEventListener('input', () => updatePreviewAlt(row));
            });
        });
    }

    function bindImageValidation(scope = document) {
        scope.querySelectorAll('[data-option-image]').forEach((input) => {
            if (input.dataset.validationBound === 'true') {
                return;
            }

            input.dataset.validationBound = 'true';

            input.addEventListener('change', async () => {
                const file = input.files?.[0];
                const row = input.closest('.option-row');

                if (!file) {
                    updatePreviewAlt(row);
                    return;
                }

                setLocalPreview(row, file);

                if (!validateImageInput(input)) {
                    return;
                }

                if (!validateTotalImageSize(input)) {
                    return;
                }

                if (!blobUploadUrl || !row) {
                    updatePreviewAlt(row);
                    return;
                }

                const existingImageInput = row.querySelector('[data-option-existing-image]');
                const previewWrapper = row.querySelector('[data-image-preview-wrapper]');
                const previewImage = row.querySelector('[data-image-preview]');
                const submitButton = surveyForm.querySelector('button[type="submit"]');

                const uploadPromise = (async () => {
                    input.disabled = true;

                    if (submitButton) {
                        submitButton.disabled = true;
                    }

                    try {
                        const response = await fetch(blobUploadUrl, {
                            method: 'PUT',
                            headers: {
                                'x-file-name': file.name,
                                'x-content-type': file.type || 'application/octet-stream',
                            },
                            body: file,
                        });

                        if (!response.ok) {
                            throw new Error('Upload failed');
                        }

                        const payload = await response.json();

                        if (!payload?.url || !existingImageInput) {
                            throw new Error('Upload response missing url');
                        }

                        existingImageInput.value = payload.url;

                        if (previewWrapper && previewImage) {
                            previewImage.src = payload.url;
                            previewWrapper.classList.remove('hidden');
                        }

                        updatePreviewAlt(row);
                        input.value = '';
                    } catch (error) {
                        console.error(error);
                        alert('De afbeelding kon niet naar Vercel Blob worden geüpload. Probeer het opnieuw.');
                    } finally {
                        input.disabled = false;

                        if (submitButton && pendingUploads.size <= 1) {
                            submitButton.disabled = false;
                        }
                    }
                })();

                pendingUploads.add(uploadPromise);
                uploadPromise.finally(() => pendingUploads.delete(uploadPromise));
                await uploadPromise;
            });
        });
    }

    function updateOptionLayout(card) {
        const typeField = card.querySelector('.question-type');
        const addOptionButton = card.querySelector('.add-option');
        const optionsWrapper = card.querySelector('.options-wrapper');
        const isSwipe = typeField?.value === 'swipe';
        const optionCount = optionsWrapper?.querySelectorAll('.option-row').length ?? 0;

        card.querySelectorAll('.option-row').forEach((row) => {
            const labelCol = row.querySelector('.option-label-col');
            const imageField = row.querySelector('.swipe-image-field');
            const removeCol = row.querySelector('.option-remove-col');

            if (isSwipe) {
                labelCol?.classList.remove('md:col-span-11');
                labelCol?.classList.add('md:col-span-6');

                imageField?.classList.remove('hidden');
                imageField?.classList.add('md:col-span-4');

                removeCol?.classList.remove('md:col-span-1');
                removeCol?.classList.add('md:col-span-2');
            } else {
                labelCol?.classList.remove('md:col-span-6');
                labelCol?.classList.add('md:col-span-11');

                imageField?.classList.add('hidden');

                const imageInput = row.querySelector('[data-option-image]');
                if (imageInput) {
                    imageInput.value = '';
                }

                removeCol?.classList.remove('md:col-span-2');
                removeCol?.classList.add('md:col-span-1');
            }

            updatePreviewAlt(row);
        });

        if (addOptionButton) {
            addOptionButton.classList.toggle('hidden', isSwipe && optionCount >= 2);
        }
    }

    function ensureMinimumOptions(card) {
        const typeField = card.querySelector('.question-type');
        const optionsWrapper = card.querySelector('.options-wrapper');

        if (!typeField || !optionsWrapper) {
            return;
        }

        if (typeField.value === 'textarea') {
            updateOptionLayout(card);
            return;
        }

        while (optionsWrapper.querySelectorAll('.option-row').length < 2) {
            optionsWrapper.appendChild(createOptionRow());
        }

        if (typeField.value === 'swipe') {
            while (optionsWrapper.querySelectorAll('.option-row').length > 2) {
                optionsWrapper.querySelector('.option-row:last-child')?.remove();
            }
        }

        updateOptionLayout(card);
        bindImageValidation(card);
        bindPreviewFields(card);
    }

    function renameOptionFields(card, questionIndex) {
        const optionRows = card.querySelectorAll('.option-row');

        optionRows.forEach((row, optionIndex) => {
            const labelInput = row.querySelector('[data-option-label]');
            const existingImageInput = row.querySelector('[data-option-existing-image]');
            const imageInput = row.querySelector('[data-option-image]');
            const imageAltInput = row.querySelector('[data-option-image-alt]');

            if (labelInput) {
                labelInput.setAttribute('name', `questions[${questionIndex}][options][${optionIndex}][label]`);
            }

            if (existingImageInput) {
                existingImageInput.setAttribute('name', `questions[${questionIndex}][options][${optionIndex}][existing_image]`);
            }

            if (imageInput) {
                imageInput.setAttribute('name', `questions[${questionIndex}][options][${optionIndex}][image]`);
            }

            if (imageAltInput) {
                imageAltInput.setAttribute('name', `questions[${questionIndex}][options][${optionIndex}][image_alt]`);
            }

            updatePreviewAlt(row);
        });
    }

    function renameQuestionFields() {
        const cards = wrapper.querySelectorAll('.question-card');

        cards.forEach((card, index) => {
            const number = card.querySelector('.question-number');

            if (number) {
                number.textContent = index + 1;
            }

            const fieldMap = {
                id: `questions[${index}][id]`,
                question: `questions[${index}][question]`,
                type: `questions[${index}][type]`,
                required_hidden: `questions[${index}][required]`,
                required: `questions[${index}][required]`,
            };

            Object.entries(fieldMap).forEach(([key, name]) => {
                const field = card.querySelector(`[data-field="${key}"]`) || card.querySelector(`[name$="[${key}]"]`);

                if (field) {
                    field.setAttribute('name', name);
                }
            });

            renameOptionFields(card, index);
            updateOptionLayout(card);
        });
    }

    function toggleOptionsVisibility(card) {
        const typeField = card.querySelector('.question-type');
        const optionsField = card.querySelector('.options-field');

        if (!typeField || !optionsField) {
            return;
        }

        const shouldHide = typeField.value === 'textarea';
        optionsField.classList.toggle('hidden', shouldHide);

        if (!shouldHide) {
            ensureMinimumOptions(card);
            renameQuestionFields();
            updateOptionLayout(card);
        } else {
            updateOptionLayout(card);
        }
    }

    function attachOptionRowEvents(card) {
        card.querySelectorAll('.remove-option').forEach((button) => {
            if (button.dataset.bound === 'true') {
                return;
            }

            button.dataset.bound = 'true';

            button.addEventListener('click', () => {
                const optionsWrapper = card.querySelector('.options-wrapper');
                const typeField = card.querySelector('.question-type');
                const row = button.closest('.option-row');

                if (!optionsWrapper || !row) {
                    return;
                }

                const currentCount = optionsWrapper.querySelectorAll('.option-row').length;
                const isSwipe = typeField?.value === 'swipe';
                const minimum = typeField?.value === 'textarea' ? 0 : 2;

                if (currentCount <= minimum) {
                    alert(isSwipe
                        ? 'Een swipe-vraag moet precies 2 opties hebben.'
                        : 'Een radio-vraag moet minimaal 2 opties hebben.');
                    return;
                }

                row.remove();
                renameQuestionFields();
                announce('Antwoordoptie verwijderd.');
                card.querySelector('.add-option')?.focus();
            });
        });
    }

    function attachOptionEvents(card) {
        const addOptionButton = card.querySelector('.add-option');

        if (addOptionButton && addOptionButton.dataset.bound !== 'true') {
            addOptionButton.dataset.bound = 'true';

            addOptionButton.addEventListener('click', () => {
                const optionsWrapper = card.querySelector('.options-wrapper');
                const typeField = card.querySelector('.question-type');

                if (!optionsWrapper || !typeField) {
                    return;
                }

                const optionCount = optionsWrapper.querySelectorAll('.option-row').length;

                if (typeField.value === 'swipe' && optionCount >= 2) {
                    alert('Een swipe-vraag mag precies 2 opties hebben.');
                    updateOptionLayout(card);
                    return;
                }

                const newRow = createOptionRow();
                optionsWrapper.appendChild(newRow);
                renameQuestionFields();
                attachOptionRowEvents(card);
                bindImageValidation(card);
                bindPreviewFields(card);
                updateOptionLayout(card);
                announce('Antwoordoptie toegevoegd.');
                newRow.querySelector('[data-option-label]')?.focus();
            });
        }

        attachOptionRowEvents(card);
        bindImageValidation(card);
        bindPreviewFields(card);
    }

    function attachCardEvents(card) {
        const removeButton = card.querySelector('.remove-question');
        const typeField = card.querySelector('.question-type');

        if (removeButton && removeButton.dataset.bound !== 'true') {
            removeButton.dataset.bound = 'true';

            removeButton.addEventListener('click', () => {
                if (wrapper.querySelectorAll('.question-card').length === 1) {
                    alert('Een enquête moet minimaal 1 vraag hebben.');
                    return;
                }

                const nextCard = card.nextElementSibling ?? card.previousElementSibling;
                card.remove();
                renameQuestionFields();
                announce('Vraag verwijderd.');
                nextCard?.querySelector('[data-field="question"]')?.focus();
            });
        }

        if (typeField && typeField.dataset.bound !== 'true') {
            typeField.dataset.bound = 'true';
            typeField.addEventListener('change', () => toggleOptionsVisibility(card));
        }

        attachOptionEvents(card);
        toggleOptionsVisibility(card);
    }

    addButton?.addEventListener('click', () => {
        const clone = questionTemplate.content.firstElementChild.cloneNode(true);
        const optionsWrapper = clone.querySelector('.options-wrapper');

        optionsWrapper.appendChild(createOptionRow());
        optionsWrapper.appendChild(createOptionRow());

        wrapper.appendChild(clone);
        renameQuestionFields();
        attachCardEvents(clone);
        bindImageValidation(clone);
        bindPreviewFields(clone);
        announce('Nieuwe vraag toegevoegd.');
        clone.querySelector('[data-field="question"]')?.focus();
    });

    surveyForm.addEventListener('submit', (event) => {
        if (pendingUploads.size > 0) {
            event.preventDefault();
            alert('Wacht tot de afbeelding is geüpload voordat je de enquête opslaat.');
            return;
        }

        const hasTooLargeFile = [...document.querySelectorAll('[data-option-image]')]
            .some((input) => getSelectedFileSize(input) > MAX_IMAGE_SIZE);

        if (hasTooLargeFile) {
            event.preventDefault();
            alert('Een afbeelding mag maximaal 2 MB groot zijn.');
            return;
        }

        if (!validateTotalImageSize()) {
            event.preventDefault();
        }
    });

    wrapper.querySelectorAll('.question-card').forEach((card) => attachCardEvents(card));
    renameQuestionFields();
    bindImageValidation(document);
    bindPreviewFields(document);
});

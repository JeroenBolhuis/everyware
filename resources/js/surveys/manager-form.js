document.addEventListener('DOMContentLoaded', () => {
    const wrapper = document.getElementById('questions-wrapper');
    const addButton = document.getElementById('add-question');
    const questionTemplate = document.getElementById('question-template');
    const optionTemplate = document.getElementById('option-template');
    const surveyForm = document.querySelector('form[enctype="multipart/form-data"]');

    if (!wrapper || !questionTemplate || !optionTemplate || !surveyForm) {
        return;
    }

    const MAX_IMAGE_SIZE = 2 * 1024 * 1024;
    const MAX_TOTAL_IMAGE_SIZE = 5 * 1024 * 1024;

    function createOptionRow() {
        return optionTemplate.content.firstElementChild.cloneNode(true);
    }

    function getSelectedFileSize(input) {
        return input?.files?.[0]?.size ?? 0;
    }

    function calculateTotalSelectedImageSize() {
        let total = 0;

        // Sum currently selected files across all option image inputs.
        document.querySelectorAll('[data-option-image]').forEach((input) => {
            total += getSelectedFileSize(input);
        });

        return total;
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

    function bindImageValidation(scope = document) {
        scope.querySelectorAll('[data-option-image]').forEach((input) => {
            // Guard to avoid binding duplicate listeners when rows are re-rendered/reused.
            if (input.dataset.validationBound === 'true') {
                return;
            }

            input.dataset.validationBound = 'true';

            input.addEventListener('change', () => {
                if (!validateImageInput(input)) {
                    return;
                }

                validateTotalImageSize(input);
            });
        });
    }

    function updateOptionLayout(card) {
        const typeField = card.querySelector('.question-type');
        const addOptionButton = card.querySelector('.add-option');
        const optionsWrapper = card.querySelector('.options-wrapper');
        const isSwipe = typeField?.value === 'swipe';
        const optionCount = optionsWrapper?.querySelectorAll('.option-row').length ?? 0;

        // Switch between radio and swipe layouts by changing grid spans and image visibility.
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
        });

        if (addOptionButton) {
            if (isSwipe && optionCount >= 2) {
                addOptionButton.classList.add('hidden');
            } else {
                addOptionButton.classList.remove('hidden');
            }
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

        // Non-text questions need at least 2 options; swipe questions are locked to exactly 2.
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
    }

    function renameOptionFields(card, questionIndex) {
        const optionRows = card.querySelectorAll('.option-row');

        // Keep indexed names aligned with DOM order so Laravel parses nested arrays correctly.
        optionRows.forEach((row, optionIndex) => {
            const labelInput = row.querySelector('[data-option-label]');
            const existingImageInput = row.querySelector('[data-option-existing-image]');
            const imageInput = row.querySelector('[data-option-image]');

            if (labelInput) {
                labelInput.setAttribute('name', `questions[${questionIndex}][options][${optionIndex}][label]`);
            }

            if (existingImageInput) {
                existingImageInput.setAttribute('name', `questions[${questionIndex}][options][${optionIndex}][existing_image]`);
            }

            if (imageInput) {
                imageInput.setAttribute('name', `questions[${questionIndex}][options][${optionIndex}][image]`);
            }
        });
    }

    function renameQuestionFields() {
        const cards = wrapper.querySelectorAll('.question-card');

        // Re-number and re-index every question after add/remove operations.
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
                    if (isSwipe) {
                        alert('Een swipe-vraag moet precies 2 opties hebben.');
                    } else {
                        alert('Een radio-vraag moet minimaal 2 opties hebben.');
                    }
                    return;
                }

                row.remove();
                renameQuestionFields();
            });
        });
    }

    function attachOptionEvents(card) {
        card.querySelector('.add-option')?.addEventListener('click', () => {
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
            updateOptionLayout(card);
        });

        attachOptionRowEvents(card);
        bindImageValidation(card);
    }

    function attachCardEvents(card) {
        card.querySelector('.remove-question')?.addEventListener('click', () => {
            if (wrapper.querySelectorAll('.question-card').length === 1) {
                alert('Een enquête moet minimaal 1 vraag hebben.');
                return;
            }

            card.remove();
            renameQuestionFields();
        });

        card.querySelector('.question-type')?.addEventListener('change', () => {
            toggleOptionsVisibility(card);
        });

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
    });

    surveyForm.addEventListener('submit', (event) => {
        const imageInputs = document.querySelectorAll('[data-option-image]');
        let hasTooLargeSingleFile = false;

        // Final client-side safety check before the form is posted.
        imageInputs.forEach((input) => {
            if (getSelectedFileSize(input) > MAX_IMAGE_SIZE) {
                hasTooLargeSingleFile = true;
            }
        });

        if (hasTooLargeSingleFile) {
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
});
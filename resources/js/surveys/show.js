document.addEventListener('DOMContentLoaded', () => {
    const steps = Array.from(document.querySelectorAll('.question-step'));
    const form = document.getElementById('surveyForm');
    const validationMessage = document.getElementById('surveyValidationMessage');

    if (!steps.length || !form || !validationMessage) {
        return;
    }

    const requestedInitialStep = Number.parseInt(form.dataset.initialStep ?? '0', 10);
    const initialStep = Number.isNaN(requestedInitialStep)
        ? 0
        : Math.min(Math.max(requestedInitialStep, 0), steps.length - 1);

    let currentStep = 0;
    let isSubmitting = false;

    function showValidationMessage(message) {
        validationMessage.textContent = message;
        validationMessage.classList.remove('hidden');
    }

    function hideValidationMessage() {
        validationMessage.textContent = '';
        validationMessage.classList.add('hidden');
    }

    function showStep(index) {
        steps.forEach((step, i) => {
            const isActive = i === index;
            step.classList.toggle('hidden', !isActive);
            step.setAttribute('aria-hidden', isActive ? 'false' : 'true');
        });

        currentStep = index;
        hideValidationMessage();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function getStepAnswer(step) {
        const type = step.dataset.type;

        if (type === 'email') {
            const input = step.querySelector('input[type="email"]');
            return input ? input.value.trim() : '';
        }

        if (type === 'radio') {
            const checked = step.querySelector('input[type="radio"]:checked');
            return checked ? checked.value.trim() : '';
        }

        if (type === 'swipe') {
            const hiddenInput = step.querySelector('input[type="hidden"]');
            return hiddenInput ? hiddenInput.value.trim() : '';
        }

        if (type === 'textarea') {
            const textarea = step.querySelector('textarea');
            return textarea ? textarea.value.trim() : '';
        }

        if (type === 'contact') {
            const email = step.querySelector('input[name="contact_email"]');
            const name = step.querySelector('input[name="contact_name"]');

            return [email?.value.trim() ?? '', name?.value.trim() ?? ''].join('');
        }

        return '';
    }

    function validateStep(step) {
        const required = step.dataset.required === '1';
        const type = step.dataset.type;

        if (!required) {
            return true;
        }

        if (type === 'email') {
            const value = getStepAnswer(step);
            return value !== '' && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
        }

        return getStepAnswer(step) !== '';
    }

    function getValidationMessage(step) {
        if (step.dataset.type === 'email') {
            return 'Vul een geldig e-mailadres in om verder te gaan.';
        }

        return 'Geef eerst een antwoord op deze verplichte vraag.';
    }

    function goToNextStep() {
        const current = steps[currentStep];

        if (!validateStep(current)) {
            showValidationMessage(getValidationMessage(current));
            return;
        }

        if (currentStep < steps.length - 1) {
            showStep(currentStep + 1);
            return;
        }

        if (!isSubmitting) {
            isSubmitting = true;
            form.submit();
        }
    }

    function goToPreviousStep() {
        if (currentStep > 0) {
            showStep(currentStep - 1);
        }
    }

    function chooseSwipeAnswer(questionId, value, goNext = true) {
        const input = document.getElementById(`answer-${questionId}`);

        if (!input) {
            return;
        }

        input.value = value;
        hideValidationMessage();

        if (goNext) {
            setTimeout(() => {
                goToNextStep();
            }, 180);
        }
    }

    document.querySelectorAll('.prev-btn').forEach((button) => {
        button.addEventListener('click', goToPreviousStep);
    });

    document.querySelectorAll('.next-btn').forEach((button) => {
        button.addEventListener('click', goToNextStep);
    });

    document.querySelectorAll('input[type="radio"]').forEach((radio) => {
        radio.addEventListener('change', () => {
            hideValidationMessage();

            setTimeout(() => {
                goToNextStep();
            }, 150);
        });
    });

    document.querySelectorAll('textarea, input[type="text"], input[type="email"]').forEach((field) => {
        field.addEventListener('input', hideValidationMessage);
    });

    document.querySelectorAll('.swipe-choice').forEach((button) => {
        button.addEventListener('click', () => {
            chooseSwipeAnswer(button.dataset.questionId, button.dataset.value, true);
        });
    });

    document.querySelectorAll('.swipe-card').forEach((card) => {
        const questionId = card.dataset.questionId;
        const leftValue = card.dataset.left;
        const rightValue = card.dataset.right;
        const badgeLeft = card.querySelector('.swipe-badge-left');
        const badgeRight = card.querySelector('.swipe-badge-right');

        let startX = 0;
        let currentX = 0;
        let dragging = false;

        function resetCard() {
            card.style.transform = 'translateX(0px) rotate(0deg)';
            badgeLeft.style.opacity = '0';
            badgeRight.style.opacity = '0';
        }

        function updateCard(deltaX) {
            const rotate = deltaX * 0.05;
            card.style.transform = `translateX(${deltaX}px) rotate(${rotate}deg)`;

            if (deltaX < -20) {
                badgeLeft.style.opacity = '1';
                badgeRight.style.opacity = '0';
            } else if (deltaX > 20) {
                badgeRight.style.opacity = '1';
                badgeLeft.style.opacity = '0';
            } else {
                badgeLeft.style.opacity = '0';
                badgeRight.style.opacity = '0';
            }
        }

        function finishSwipe(deltaX) {
            if (deltaX > 120) {
                card.style.transform = 'translateX(500px) rotate(20deg)';
                chooseSwipeAnswer(questionId, rightValue, true);
                return;
            }

            if (deltaX < -120) {
                card.style.transform = 'translateX(-500px) rotate(-20deg)';
                chooseSwipeAnswer(questionId, leftValue, true);
                return;
            }

            resetCard();
        }

        card.addEventListener('mousedown', (event) => {
            dragging = true;
            startX = event.clientX;
            currentX = event.clientX;
            card.style.transition = 'none';
        });

        window.addEventListener('mousemove', (event) => {
            if (!dragging) {
                return;
            }

            currentX = event.clientX;
            updateCard(currentX - startX);
        });

        window.addEventListener('mouseup', (event) => {
            if (!dragging) {
                return;
            }

            dragging = false;
            card.style.transition = 'transform 0.2s ease';
            currentX = event.clientX;
            finishSwipe(currentX - startX);
        });

        card.addEventListener('touchstart', (event) => {
            dragging = true;
            startX = event.touches[0].clientX;
            currentX = event.touches[0].clientX;
            card.style.transition = 'none';
        });

        card.addEventListener('touchmove', (event) => {
            if (!dragging) {
                return;
            }

            currentX = event.touches[0].clientX;
            updateCard(currentX - startX);
        });

        card.addEventListener('touchend', () => {
            if (!dragging) {
                return;
            }

            dragging = false;
            card.style.transition = 'transform 0.2s ease';
            finishSwipe(currentX - startX);
        });

        card.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowLeft') {
                chooseSwipeAnswer(questionId, leftValue, true);
            }

            if (event.key === 'ArrowRight') {
                chooseSwipeAnswer(questionId, rightValue, true);
            }
        });

        resetCard();
    });

    showStep(initialStep);
});

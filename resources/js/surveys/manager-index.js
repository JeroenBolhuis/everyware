const searchInput = document.getElementById('search');
const statusSelect = document.getElementById('status');
const surveyCards = document.querySelectorAll('.survey-card');
const emptyState = document.getElementById('survey-live-empty-state');

function filterSurveys() {
    const searchValue = searchInput?.value.toLowerCase().trim() ?? '';
    const statusValue = statusSelect?.value ?? '';

    let visibleCount = 0;

    surveyCards.forEach((card) => {
        const title = card.dataset.title ?? '';
        const creator = card.dataset.creator ?? '';
        const status = card.dataset.status ?? '';

        const matchesSearch =
            title.includes(searchValue) ||
            creator.includes(searchValue);

        const matchesStatus =
            statusValue === '' ||
            status === statusValue;

        const shouldShow = matchesSearch && matchesStatus;

        card.classList.toggle('hidden', !shouldShow);

        if (shouldShow) {
            visibleCount++;
        }
    });

    emptyState?.classList.toggle('hidden', visibleCount > 0);
}

searchInput?.addEventListener('input', filterSurveys);
statusSelect?.addEventListener('change', filterSurveys);

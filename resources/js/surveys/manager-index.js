const filterForm = document.getElementById('survey-filter-form');
const statusSelect = document.getElementById('status');

statusSelect?.addEventListener('change', () => {
    filterForm?.requestSubmit();
});

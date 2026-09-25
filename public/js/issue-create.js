(() => {
    'use strict';

    const initialize = () => {
        document.querySelectorAll('select[data-issue-category]').forEach((category) => {
            const service = category.form?.querySelector('select[data-issue-service]');
            if (!service || category.dataset.initialized === 'true') return;

            category.dataset.initialized = 'true';
            const options = Array.from(category.options);
            const update = () => {
                const selected = category.value;
                const matching = options.filter((option) =>
                    option.value === '' || option.dataset.serviceId === service.value
                );
                category.replaceChildren(...matching);
                category.value = matching.some((option) => option.value === selected) ? selected : '';
            };

            service.addEventListener('change', update);
            update();
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }
})();

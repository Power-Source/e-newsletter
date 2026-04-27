(function () {
    'use strict';

    function isPluginPage() {
        var params = new URLSearchParams(window.location.search || '');
        var page = params.get('page') || '';

        if (page.indexOf('newsletters') === 0) {
            return true;
        }

        return !!document.querySelector('.wrap');
    }

    function enhanceToolbars() {
        var wraps = document.querySelectorAll('.wrap');
        wraps.forEach(function (wrap) {
            var paragraph = wrap.querySelector(':scope > p');
            if (!paragraph) {
                return;
            }

            var buttons = paragraph.querySelectorAll('.button');
            if (!buttons.length) {
                return;
            }

            paragraph.classList.add('enews-toolbar');
        });
    }

    function buildCampaignStatsCards() {
        var statsPage = document.querySelector('.enews-campaign-stats-page');
        if (!statsPage) {
            return;
        }

        var summaryTable = statsPage.querySelector('table.widefat');
        if (!summaryTable) {
            return;
        }

        var thead = summaryTable.querySelectorAll('thead th');
        var values = summaryTable.querySelectorAll('tbody td');
        if (!thead.length || !values.length || thead.length !== values.length) {
            return;
        }

        if (summaryTable.previousElementSibling && summaryTable.previousElementSibling.classList && summaryTable.previousElementSibling.classList.contains('enews-summary-grid')) {
            return;
        }

        var grid = document.createElement('div');
        grid.className = 'enews-summary-grid';

        for (var i = 0; i < thead.length; i += 1) {
            var card = document.createElement('div');
            card.className = 'enews-metric';

            var label = document.createElement('div');
            label.className = 'enews-metric-label';
            label.textContent = (thead[i].textContent || '').trim();

            var value = document.createElement('div');
            value.className = 'enews-metric-value';
            value.textContent = (values[i].textContent || '').trim();

            card.appendChild(label);
            card.appendChild(value);
            grid.appendChild(card);
        }

        summaryTable.parentNode.insertBefore(grid, summaryTable);
    }

    function init() {
        if (!isPluginPage()) {
            return;
        }

        document.body.classList.add('enews-ui');
        enhanceToolbars();
        buildCampaignStatsCards();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

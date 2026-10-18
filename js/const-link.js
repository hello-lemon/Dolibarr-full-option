(function () {
    'use strict';

    if (!/\/admin\/const\.php$/.test(window.location.pathname)) return;

    function addLink() {
        var title = document.querySelector('table.table-fiche-title');
        if (!title || document.getElementById('dolibarrfulloption-const-link')) return;

        var url = window.location.pathname.replace(/\/admin\/const\.php$/, '/custom/dolibarrfulloption/admin/setup.php');
        var wrapper = document.createElement('div');
        wrapper.className = 'tabsAction';
        var link = document.createElement('a');
        link.id = 'dolibarrfulloption-const-link';
        link.className = 'butAction';
        link.href = url;
        link.textContent = 'Options expliquées';
        wrapper.appendChild(link);
        title.insertAdjacentElement('afterend', wrapper);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', addLink);
    } else {
        addLink();
    }
}());

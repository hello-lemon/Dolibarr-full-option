(function () {
    'use strict';

    var root = document.getElementById('dolibarrfulloption-admin');
    if (!root) return;

    function numbersFromValue(value) {
        var numbers = [];
        var parts = value.split(',');
        if (!value || parts.length > 20) return null;
        for (var i = 0; i < parts.length; i++) {
            var match = parts[i].match(/^(\d{1,4}):\1$/);
            if (!match) return null;
            var number = Number(match[1]);
            if (!Number.isInteger(number) || number < 1 || number > 5000 || numbers.indexOf(number) !== -1) return null;
            numbers.push(number);
        }
        return numbers;
    }

    function setupChoices(editor) {
        var hidden = editor.querySelector('[data-choice-value]');
        var list = editor.querySelector('[data-choice-list]');
        var input = editor.querySelector('[data-choice-input]');
        var add = editor.querySelector('[data-choice-add]');
        var technical = editor.querySelector('[data-choice-technical]');
        var warning = editor.querySelector('[data-choice-warning]');
        if (!hidden || !list || !input || !add || !technical) return;

        var numbers = numbersFromValue(hidden.value);
        if (numbers === null) {
            technical.textContent = hidden.value;
            if (warning) warning.hidden = false;
            input.disabled = true;
            add.disabled = true;
            var save = editor.closest('form').querySelector('button[type="submit"]');
            if (save) save.disabled = true;
            return;
        }

        function render(persist) {
            numbers.sort(function (a, b) { return a - b; });
            if (persist) hidden.value = numbers.map(function (number) { return number + ':' + number; }).join(',');
            technical.textContent = hidden.value;
            list.replaceChildren();
            numbers.forEach(function (number) {
                var chip = document.createElement('span');
                chip.className = 'dolibarrfulloption-choice';
                chip.appendChild(document.createTextNode(number + ' lignes'));
                var remove = document.createElement('button');
                remove.type = 'button';
                remove.textContent = '×';
                remove.setAttribute('aria-label', 'Retirer ' + number + ' lignes');
                remove.addEventListener('click', function () {
                    if (numbers.length === 1) return;
                    numbers = numbers.filter(function (current) { return current !== number; });
                    render(true);
                });
                chip.appendChild(remove);
                list.appendChild(chip);
            });
        }

        function addNumber() {
            var number = Number(input.value);
            if (!Number.isInteger(number) || number < 1 || number > 5000) {
                input.setCustomValidity('Saisissez un nombre entre 1 et 5 000.');
                input.reportValidity();
                return;
            }
            input.setCustomValidity('');
            if (numbers.indexOf(number) !== -1) {
                input.setCustomValidity('Cette taille est déjà proposée.');
                input.reportValidity();
                return;
            }
            if (numbers.length >= 20) {
                input.setCustomValidity('La limite est de 20 tailles.');
                input.reportValidity();
                return;
            }
            numbers.push(number);
            input.value = '';
            render(true);
        }

        add.addEventListener('click', addNumber);
        input.addEventListener('input', function () { input.setCustomValidity(''); });
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                addNumber();
            }
        });
        render(false);
    }

    root.querySelectorAll('[data-choice-editor]').forEach(setupChoices);
}());

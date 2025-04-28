jQuery(document).ready(function($) {

    // -------------------------------------------------------------------------
    // Funkcjonalność powtarzalnych pól dla partii produkcyjnych (jeśli potrzebna)
    // -------------------------------------------------------------------------
    var wrapper = $('#partie-produkcyjne-wrapper');

    // Sprawdź, czy element istnieje, zanim dodasz event listenery
    if (wrapper.length) {
        var tbody = $('#partie-tbody');
        // Upewnij się, że szablon istnieje przed próbą odczytu .html()
        var templateRow = $('#partia-template tr.partia-row');
        var templateRowHtml = templateRow.length ? templateRow.html() : null;

        // Dodawanie nowego wiersza tylko jeśli szablon istnieje
        if (templateRowHtml) {
            wrapper.on('click', '#add-partia-row', function(e) {
                e.preventDefault();
                var newIndex = tbody.find('tr.partia-row').length;
                // Utwórz nowy wiersz i zamień indeksy w atrybutach name
                var newRow = '<tr class="partia-row">' + templateRowHtml.replace(/__INDEX__/g, newIndex) + '</tr>';
                tbody.append(newRow);
            });
        } else {
            console.warn('Physis ostrzeżenie JS: Nie znaleziono szablonu #partia-template.');
        }


        // Usuwanie wiersza
        wrapper.on('click', '.remove-row-button', function(e) {
            e.preventDefault();
            // Użyj `physis_admin_vars.confirm_delete` jeśli zdefiniowano
            // Domyślnie użyj tekstu po polsku
            var confirmMsg = (typeof physis_admin_vars !== 'undefined' && physis_admin_vars.confirm_delete)
                             ? physis_admin_vars.confirm_delete
                             : 'Czy na pewno chcesz usunąć ten wiersz?'; // Domyślny tekst

            if (confirm(confirmMsg)) {
                $(this).closest('tr.partia-row').remove();
                // Opcjonalne przeindeksowanie - zazwyczaj nie jest konieczne, jeśli logika serwera radzi sobie z lukami w indeksach
            }
        });
    } // koniec if (wrapper.length)


    // -------------------------------------------------------------------------
    // Funkcjonalność dla strony wyszukiwania produktów Physis
    // -------------------------------------------------------------------------

    // Sprawdź, czy jesteśmy na stronie wyszukiwania (identyfikując unikalny element tej strony)
    if ($('#physis-export-options').length && $('#physis-export-button').length) {

        // Pokaż/ukryj checkboxy do wyboru kolumn eksportu
        $('#physis-toggle-export-cols').on('click', function(e) {
            e.preventDefault();
            $('#physis-export-cols-checkboxes').slideToggle(); // Animowane przełączanie widoczności
        });

        // Obsługa przycisku "Zaznacz/Odznacz wszystkie" dla kolumn eksportu
        $('#physis-select-all-cols').on('click', function() {
            // Znajdź wszystkie checkboxy w kontenerze, które NIE są zablokowane (:not(:disabled))
            var checkboxes = $('#physis-export-cols-checkboxes input[type="checkbox"]:not(:disabled)');
            // Sprawdź, czy WSZYSTKIE niezablokowane są aktualnie zaznaczone
            var allChecked = checkboxes.length === checkboxes.filter(':checked').length;
            // Ustaw stan zaznaczenia na przeciwny do obecnego stanu "wszystkie zaznaczone"
            checkboxes.prop('checked', !allChecked);
            // Zaktualizuj link eksportu po zmianie zaznaczeń
            updateExportLink();
        });

        /**
         * Aktualizuje atrybut 'href' przycisku eksportu CSV (#physis-export-button)
         * na podstawie aktualnie zaznaczonych checkboxów kolumn.
         */
        function updateExportLink() {
            var exportButton = $('#physis-export-button');
            // Jeśli przycisk nie istnieje na stronie, zakończ funkcję
            if (!exportButton.length) {
                return;
            }

            // Pobierz bazowy URL zapisany w atrybucie 'data-baseurl' przycisku
            var baseUrl = exportButton.data('baseurl');

            // Sprawdź, czy udało się pobrać poprawny bazowy URL
            if (typeof baseUrl !== 'string' || baseUrl === '') {
                 console.error("Physis Błąd JS: Brak lub niepoprawny atrybut data-baseurl dla przycisku eksportu.");
                 // Zablokuj przycisk, aby zapobiec błędnemu działaniu
                 exportButton.addClass('disabled').attr('href', '#');
                 return;
            }

            var selectedColsParams = [];
            // Zbierz wartości wszystkich ZAZNACZONYCH checkboxów kolumn
            $('#physis-export-cols-checkboxes input[type="checkbox"]:checked').each(function() {
                // Dodaj parametr w formacie 'export_cols[]=wartość' do tablicy
                selectedColsParams.push('export_cols[]=' + encodeURIComponent($(this).val()));
            });

            var finalUrl = baseUrl; // Domyślnie użyj bazowego URL

            // Jeśli są zaznaczone jakieś kolumny
            if (selectedColsParams.length > 0) {
                // Sprawdź, czy bazowy URL zawiera już znak zapytania ('?')
                var separator = baseUrl.indexOf('?') !== -1 ? '&' : '?';
                // Dołącz parametry kolumn do bazowego URL
                finalUrl = baseUrl + separator + selectedColsParams.join('&');
            }
            // Ustaw zaktualizowany URL w atrybucie 'href' przycisku
            exportButton.attr('href', finalUrl);

            // Opcjonalnie: Odblokuj przycisk, jeśli był zablokowany
            // (np. jeśli blokujemy go, gdy żadna kolumna nie jest zaznaczona)
            exportButton.removeClass('disabled');

        } // <-- Koniec funkcji updateExportLink

        // Nasłuchuj zdarzenia 'change' na checkboxach kolumn
        // Za każdym razem, gdy stan checkboxa się zmieni, wywołaj funkcję aktualizującą link
        $('#physis-export-cols-checkboxes input[type="checkbox"]').on('change', function() {
            updateExportLink();
        });

        // Uruchom funkcję raz przy pierwszym załadowaniu strony,
        // aby ustawić poprawny początkowy URL przycisku eksportu
        updateExportLink();

    } // --- Koniec if (jesteśmy na stronie wyszukiwania) ---

}); // <-- Koniec jQuery(document).ready
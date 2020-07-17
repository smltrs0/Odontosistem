@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://unpkg.com/fullcalendar@5.1.0/main.min.css">
    <script src="https://unpkg.com/fullcalendar@5.1.0/main.min.js"></script>
    <script src="https://unpkg.com/fullcalendar@5.1.0/locales-all.js"></script>
<div class="mb-3">
        <div class="card">
            <div class="card-header">Citas</div>
                <div class="card-body">
                    <div id="calendario" ></div>

                </div>
        </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var initialLocaleCode = 'es';
        var localeSelectorEl = document.getElementById('locale-selector');
        var calendarEl = document.getElementById('calendario');

        var calendar = new FullCalendar.Calendar(calendarEl, {
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
            },
            locale: initialLocaleCode,
            buttonIcons: true, // show the prev/next text
            weekNumbers: true,
            navLinks: true, // can click day/week names to navigate views
            editable: true,
            dayMaxEvents: true, // allow "more" link when too many events
            events: [
                {
                    id: 'a',
                    title: 'No puedo dormir',
                    start: '2020-07-14',
                    editable:true,

                }
            ]
        });

        calendar.render();

        // build the locale selector's options
        calendar.getAvailableLocaleCodes().forEach(function(localeCode) {
            var optionEl = document.createElement('option');
            optionEl.value = localeCode;
            optionEl.selected = localeCode == initialLocaleCode;
            optionEl.innerText = localeCode;
            localeSelectorEl.appendChild(optionEl);
        });

        // when the selected option changes, dynamically change the calendar option
        localeSelectorEl.addEventListener('change', function() {
            if (this.value) {
                calendar.setOption('locale', this.value);
            }
        });

    });
</script>

@endsection



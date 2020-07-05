@extends('layouts.app')

@section('content')
<div class="mb-3">
        <div class="card">
            <div class="card-header">Citas</div>
                <div class="card-body">
                    <div id="calendar" ></div>
                        <script>
                            document.addEventListener("DOMContentLoaded", function () {
                                var calendar = new FullCalendar.Calendar(
                                    document.getElementById("calendar"),
                                    {
                                        locale: esLocale,
                                        initialView: "dayGridMonthCustom",
                                        initialDate: "2020-03-01",
                                        duration: { weeks: 8 }, //Works when duration is under views does not work here
                                        views: {
                                            dayGridMonthCustom: {
                                                type: "dayGridMonth",
                                                fixedWeekCount: false
                                            }
                                        }
                                    }
                                );
                                calendar.render();
                            });


                        </script>
                </div>
        </div>
</div>

@endsection



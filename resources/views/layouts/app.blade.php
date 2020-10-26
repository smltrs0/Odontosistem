<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Odontosistem') }}</title>
    <!-- Styles -->
    <link href="{{ asset('css/base.css') }}" rel="stylesheet">

    <!--Odontograma-->
<link href="{{ asset('css/odontogram.css') }}" rel="stylesheet"/>
    <script src="https://kit.fontawesome.com/eba26df4c2.js" crossorigin="anonymous"></script>
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/pixeden-stroke-7-icon@1.2.3/pe-icon-7-stroke/dist/pe-icon-7-stroke.min.css">

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"
          integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/simple-line-icons/2.5.4/css/simple-line-icons.css">
    <link rel="stylesheet" href="{{asset('css/bootstrap-mod.css')}}">
    @yield('odontograma')
    @yield('finanza')
</head>
<body>
<div>
    <!--Menu fixed top-->
    <div class="app-container app-theme-white body-tabs-shadow fixed-sidebar fixed-header">
        @include('layouts.menu-header')
        @auth
            @include('layouts.boton-config')
            <div class="app-main">
                @include('layouts.menu')
                @endauth
                <div class="app-main__outer">
                    <div class="app-main__inner">
                        @include('partials.alerts')
                        @yield('content')

                    </div>
                </div>
            </div>
    </div>
</div>
</body>
<script type="text/javascript" src="{{ asset('./assets/scripts/main.js') }}"></script>
{{--<script src="https://cdn.jsdelivr.net/npm/jquery"></script>--}}
<script src="https://cdn.jsdelivr.net/npm/metismenu"></script>

<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"
        integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN"
        crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"
        integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV"
        crossorigin="anonymous"></script>
<script>
    // Script del menu principal
    jQuery('#main-menu').metisMenu({
        toggle: true
    }).show();

    // Script del boton de buscar
    setTimeout(function () {
        $(".vertical-nav-menu").metisMenu()}, 100);
        $(".search-icon").click(function () {
            $(this).parent().parent().addClass("active");
        });
        $(".search-wrapper .close").click(function () {
            $(this).parent().removeClass("active")
        });
    $(".dropdown-menu").on("click", function (e) {
        var t = $.a._data(document, "events") || {};
        t = t.click || [];
        for (var n = 0; n < t.length; n++) t[n].selector && ($(e.target).is(t[n].selector) && t[n].handler.call(e
            .target, e), $(e.target).parents(t[n].selector).each(function () {
            t[n].handler.call(this, e)
        }));
        e.stopPropagation()
    });


    $(".mobile-toggle-nav").click(function () {
            $(this).toggleClass("is-active");
            $(".app-container").toggleClass("sidebar-mobile-open")
    });
        $(".mobile-toggle-header-nav").click(function () {
            $(this).toggleClass("active");
            $(".app-header__content").toggleClass("header-mobile-open");
    });

    $(window).on("resize", function () {
        $(this).width() < 1250 ? $(".app-container").addClass("closed-sidebar-mobile closed-sidebar") : $("" +
            ".app-container").removeClass("closed-sidebar-mobile closed-sidebar")
    })
    // Menu de configuracion

    $(document).ready(function () {

        $(".btn-open-options, #config").click(function () {
            if ($('.ui-theme-settings').hasClass('settings-open')){
                $('#TooltipDemo').addClass('d-none');
            }else{
                $('#TooltipDemo').removeClass('d-none');
            }
            $(".ui-theme-settings").toggleClass("settings-open");
        });

            $(".close-sidebar-btn").click(function () {
                var t = $(this).attr("data-class");
                $(".app-container").toggleClass(t);
                var n = $(this);
                n.hasClass("is-active") ? n.removeClass("is-active") : n.addClass("is-active")
            });
            $(".switch-container-class").on("click", function () {
                var t = $(this).attr("data-class");
                $(".app-container").toggleClass(t)
                    $(this).parent().find(".switch-container-class").removeClass
                ("active")
                    $(this).addClass("active")
            });
            $(".switch-theme-class").on("click", function () {
            var t = $(this).attr("data-class");
            "body-tabs-line" == t && ($(".app-container").removeClass("body-tabs-shadow"),
                $(".app-container").addClass(t)), "body-tabs-shadow" == t && ($(".app-container").removeClass("body-tabs-line"),
                $("" + ".app-container").addClass(t)), $(this).parent().find(".switch-theme-class").removeClass("active"), $
            (this).addClass("active")
        });
            $(".switch-header-cs-class").on("click", function () {
            var t = $(this).attr("data-class");
            $(".switch-header-cs-class").removeClass("active"), $(this).addClass("active"),
                $(".app-header").attr
                ("class", "app-header"), $(".app-header").addClass("header-shadow " + t)
        });
            $(".switch-sidebar-cs-class").on("click", function () {
                var t = $(this).attr("data-class");
                $(".switch-sidebar-cs-class").removeClass("active"), $(this).addClass("active"), $(".app-sidebar").attr
                ("class", "app-sidebar"), $(".app-sidebar").addClass("sidebar-shadow " + t)
            })
    })


</script>

</html>

<style>
.contenedor-moneda {
    background-color: #fff;
	list-style: none;
	/* contain the list items */
	overflow: hidden;
	/*margin: 0 auto;
	display: table;*/ /* ul will behave like a table now */
	text-align: center;
    padding-right: 2rem;
    border-radius: 0px 0px 20px 20px;
}

.elemento-moneda{
    font-size: 12px;
    display: inline-block;
    /* padding:0.5em; */
    border: 5px hidden #1C6EA4;
}

.shadow-moneda {
    -webkit-box-shadow: 0px 0px 30px 0px rgba(50, 50, 50, 0.20);
    -moz-box-shadow:    0px 0px 30px 0px rgba(50, 50, 50, 0.20);
    box-shadow:         0px 0px 30px 0px rgba(50, 50, 50, 0.20);
}

</style>
<div class="app-header header-shadow fixed-top">
    <div class="app-header__logo">
        <div class="logo-src"></div>
        <div class="header__pane ml-auto">
            <div>
                <button type="button" class="hamburger close-sidebar-btn hamburger--elastic" data-class="closed-sidebar">
                    <span class="hamburger-box">
                        <span class="hamburger-inner"></span>
                    </span>
                </button>
            </div>
        </div>
    </div>
    <!--Header mobile-->
    <div class="app-header__mobile-menu">
        <div>
            <button type="button" class="hamburger hamburger--elastic mobile-toggle-nav">
                <span class="hamburger-box">
                    <span class="hamburger-inner"></span>
                </span>
            </button>
        </div>
    </div>
    <!--/ Final header mobile-->

    <div class="app-header__menu">
        <span>
            <button type="button" class="btn-icon btn-icon-only btn btn-primary btn-sm mobile-toggle-header-nav">
                <span class="btn-icon-wrapper">
                    <i class="fa fa-ellipsis-v fa-w-6"></i>
                </span>
            </button>
        </span>
    </div>
    <div class="app-header__content">
        <!--Header búsqueda-->
        @auth
        <div class="app-header-left">
            <div class="search-wrapper">
                <div class="input-holder">
                    <input type="text" id="search-box" class="search-input" placeholder="Que quieres buscar?">
                    <button class="search-icon"><span></span></button>
                </div>
                <button class="close"></button>
            </div>
        </div>
        @endauth
        <!--Final Header busqueda-->
        <ul style="margin-left:50vh" class="text-muted contenedor-moneda shadow-moneda">
            <li class="elemento-moneda">
                <p class="content-center" id="dolar"></p>
            </li>
        </ul>
        <div class="app-header-right">
            <div class="header-btn-lg pr-0">
                <div class="widget-content p-0">
                    <div class="widget-content-wrapper">
                        <div class="widget-content-left">
                            <div class="btn-group">
                                @guest()
                                <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                @if (Route::has('register'))
                                <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                @endif
                                @endguest

                                @auth
                                <a data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="p-0 btn">

{{--                                    <img width="42" class="rounded-circle" src="{{asset('assets/images/avatars/1.jpg')}}" alt="">--}}
                                    <i class="fa fa-cog"></i>
                                    <i class="fa fa-angle-down ml-2 opacity-8"></i>
                                </a>
                                @endauth

                                <div tabindex="-1" role="menu" aria-hidden="true" class="dropdown-menu dropdown-menu-right">

                                    <ul class="navbar-nav ml-auto">
                                        <!-- Authentication Links -->
                                        @auth()
                                        <li>
                                            <a class="dropdown-item" href="{{route('mi-cuenta.index')}}">
                                                Mi cuenta
                                            </a>
                                            <a href="#config" class="dropdown-item" id="config">Configuración</a>
                                            <div class="dropdown-divider"></div>
                                            <div aria-labelledby="navbarDropdown">
                                                <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                                    {{ __('Logout') }}
                                                </a>
                                                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                                    @csrf
                                                </form>
                                            </div>
                                        </li>
                                        @endauth
                                    </ul>
                                </div>
                            </div>
                        </div>
                        @auth()

                        <div class="widget-content-left  ml-3 header-user-info">
                            <div class="widget-heading">
                                {{ Auth::user()->name }}

                            </div>
                            <div class="widget-subheading">
                                Online
                            </div>
                        </div>

                        <div class="widget-content-right header-user-info ml-3">
                        </div>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

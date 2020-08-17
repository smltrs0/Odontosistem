<!--Menu lateral izquierdo-->
<div class="app-sidebar sidebar-shadow" id="main-menu">
    <div class="app-header__logo">
        <div class="logo-src"></div>
        <div class="header__pane ml-auto">
            <div>
                <button type="button" class="hamburger close-sidebar-btn hamburger--elastic"
                        data-class="closed-sidebar">
                    <span class="hamburger-box">
                        <span class="hamburger-inner"></span>
                    </span>
                </button>
            </div>
        </div>
    </div>
    <div class="app-header__mobile-menu">
        <div>
            <button type="button" class="hamburger hamburger--elastic mobile-toggle-nav">
                <span class="hamburger-box">
                    <span class="hamburger-inner"></span>
                </span>
            </button>
        </div>
    </div>
    <div class="app-header__menu">
        <span>
            <button type="button" class="btn-icon btn-icon-only btn btn-primary btn-sm mobile-toggle-header-nav">
                <span class="btn-icon-wrapper">
                    <i class="fa fa-ellipsis-v fa-w-6"></i>
                </span>
            </button>
        </span>
    </div>

    <div class="scrollbar-sidebar">
        <div class="app-sidebar__inner">
            <ul class="vertical-nav-menu">
                <li class="app-sidebar__heading">Inicio</li>
                <li>
                    <a href="{{route('home')}}" class="mm-active">
                        <i class="metismenu-icon pe-7s-home"></i>
                        Pagina principal
                    </a>
                </li>
                <li class="app-sidebar__heading">Agenda</li>
                <li>
                    <a href="#">
                        <i class="metismenu-icon pe-7s-date"></i>
                        Citas
                        <i class="metismenu-state-icon pe-7s-angle-down caret-left"></i>
                    </a>
                    <ul>
                        <li>
                            <a href="{{route('citas-hoy.index')}}">
                                <i class="metismenu-icon"></i>
                                Citas (Admin)
                            </a>
                        </li>
                        <li>
                            <a href="{{route('citas.index')}}">
                                <i class="metismenu-icon">
                                </i>Ver mis citas
                            </a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="#">
                        <i class="metismenu-icon fa fa-user-injured"></i>
                        Pacientes
                        <i class="metismenu-state-icon pe-7s-angle-down caret-left"></i>
                    </a>
                    <ul>
                        <li>
                            <a href="{{route('pacientes.create')}}">
                                <i class="metismenu-icon"></i>
                                Agregar nuevo paciente
                            </a>
                        </li>
                        <li>
                            <a href="{{route('pacientes.index')}}">
                                <i class="metismenu-icon">
                                </i>Ver todos los pacientes
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="app-sidebar__heading">Administrador</li>
                <li>
                    <a href="#">
                        <i class="metismenu-icon fa fa-users"></i>
                        Administrar usuarios
                        <i class="metismenu-state-icon pe-7s-angle-down caret-left"></i>
                    </a>
                    <ul>
                        <li>
                            <a href="">
                                <i class="metismenu-icon">
                                </i>Agregar nuevo
                            </a>
                        </li>
                        <li>
                            <a href="{{route('user.index')}}">
                                <i class="metismenu-icon">
                                </i>Ver todos los usuarios
                            </a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="{{route('role.index')}}">
                        <i class="metismenu-icon pe-7s-key"></i>
                        Administrar roles
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="metismenu-icon pe-7s-server"></i>
                        Base de datos
                        <i class="metismenu-state-icon pe-7s-angle-down caret-left"></i>
                    </a>
                    <ul>
                        <li>
                            <a href="{{route('respaldo')}}">
                                <i class="metismenu-icon">
                                </i>Crear respaldo
                            </a>
                        </li>
                        <li>
                            <a href="#">
                                <i class="metismenu-icon">
                                </i>Restaurar
                            </a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="{{route('finanzas')}}">
                        <i class="metismenu-icon pe-7s-cash"></i>
                        Control financiero
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
<!--/Final menu lateral izquierdo-->

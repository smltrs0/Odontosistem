<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css" integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2" crossorigin="anonymous">
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" integrity="sha384-AYmEC3Yw5cVb3ZcuHtOA93w35dYTsvhLPVnYs9eStHfGJvOvKxVfELGroGkvsg+p" crossorigin="anonymous"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/2.1.1/animate.min.css" integrity="sha512-15bOOCiHMyrYK2OnHQxrNuk8U7JWunXiSbGH2tNGUcEboODCWTbpQTpdoMBr4tuQzZ3S7DZhwWgdW7FoKqX89Q==" crossorigin="anonymous" />
        
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Odontosistem</title>
</head>
<style>
      /*---------------------------------------
     TYPOGRAPHY              
  -----------------------------------------*/

  h1,h2,h3,h4,h5,h6 {
    font-weight: 600;
    line-height: inherit;
  }

  h1,h2,h3,h4 {
    letter-spacing: -1px;
  }

  h5 {
    font-weight: 500;
  }

  h1 {
    color: #252525;
    font-size: 5em;
  }

  h2 {
    color: #272727;
    font-size: 3em;
    padding-bottom: 10px;
  }

  h3 {
    font-size: 1.8em;
    line-height: 1.2em;
    margin-bottom: 0;
  }

  h4 {
    color: #454545;
    font-size: 1.8em;
    padding-bottom: 2px;
  }

  h6 {
    letter-spacing: 0;
    font-weight: normal;
  }

  p {
    color: #757575;
    font-size: 14px;
    font-weight: normal;
    line-height: 24px;
  }
  .nav-item a{
    color: #393939;
  }


  html{
    -webkit-font-smoothing: antialiased;
  }
  .carousel-item{
      background: rgba(20,20,20,0.2);
  }


</style>
<body>
    <header>
        <div class="container">
            <div class="row">
                   <div class="col-md-4 col-sm-5">
                      <p><h3>Bienvenidos al Servicio de Odontologia del Dispensario San Francisco de Asís</h3></p>
                 </div> 
                      
              <div class="col-md-8 col-sm-7 text-right " style="font-size: calc(0.09em + 0.9vw)">
                      <span class="phone-icon"><i class="fa fa-phone text-success"></i> 0285-6335114</span> |
                      <span class="date-icon"><i class="fa fa-calendar text-success"></i> 8:00 AM - 11:00 AM y 2:00 PM - 6:00 Pm  (Lunes a Viernes)</span>
                 </div> 

            </div>
       </div>
        <nav class="navbar navbar-expand-md" id="navbar" style="box-shadow: 0 2px 8px rgb(0 0 0 / 24%); background: #ffffff94;">
           
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse"
                aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarCollapse">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item"><a href="#top" class="nav-link">Inicio</a></li>
                    <li class="nav-item"><a href="#about" class="nav-link">Misión y Visión</a></li>
                    <li class="nav-item"><a href="#team" class="nav-link">Odontólogo</a></li>
                    <li class="nav-item"><a href="#news" class="nav-link">Servicios</a></li>
                    <li class="nav-item"><a href="#google-map" class="nav-link">Ubíquenos</a></li>
                    <li class="nav-item"><a href="#appointment" class="nav-link">Pedir Cita</a></li>
                </ul>

                <ul class="navbar-nav">
                    @if (Route::has('login'))
                    @auth
                    <li class="nav-item active">
                        <a class="nav-link" href="{{ url('/home') }}">Panel principal <span
                                class="sr-only">(current)</span></a>

                    </li>

                    @else
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">{{__('Entrar')}}</a>
                    </li>

                    @if (Route::has('register'))
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('register') }}">{{__('Registrate')}}</a>
                    </li>
                    @endif
                    @endauth

                    @endif
                </ul>

            </div>
        </nav>
    </header>

    <main role="main">
        <div id="slider-principal" class="carousel slide" data-ride="carousel">
            <div class="carousel-inner">
              <div class="carousel-item active" data-interval="10000">
                <img src="{{ asset('img/slider1.jpg') }}" class="d-block w-100" height="625" alt="...">
                <div class="carousel-caption d-none d-md-block">
                    <h3>Hagamos tu vida más feliz</h3>
                           <h1>El tiempo lo cura todo, menos las caries, visitanos</h1>
                           <a href="#team" class="section-btn btn btn-default smoothScroll">Conoce a nuestro odontólogo</a>
                      </div>
              </div>
              <div class="carousel-item" data-interval="2000">
                <img src="{{ asset('img/slider2.jpg') }}" class="d-block w-100" height="625" alt="...">
                 <div class="carousel-caption d-none d-md-block">
                <h3>El Mejor Servicio a Nuestra Comunidad</h3>
                <h1>Algunos buscan sonrisas bonitas, otros las crean</h1>
                <a href="#about" class="section-btn btn btn-default btn-gray smoothScroll">Nuestra Vocación de Servicio</a>
                 </div>
              </div>
              <div class="carousel-item">
                <img src="{{ asset('img/slider3.jpg') }}" class="d-block w-100" height="625" alt="...">
                 <div class="carousel-caption d-none d-md-block">
                <h3>Solicite Presupuesto</h3>
                <h1>Beneficios Para La Salud Dental</h1>
                <a href="#news" class="section-btn btn btn-default btn-primary smoothScroll">Ver Servicios</a>
                 </div>
              </div>
            </div>
            <a class="carousel-control-prev" href="#slider-principal" role="button" data-slide="prev">
              <span class="carousel-control-prev-icon" aria-hidden="true"></span>
              <span class="sr-only">Anterior</span>
            </a>
            <a class="carousel-control-next" href="#slider-principal" role="button" data-slide="next">
              <span class="carousel-control-next-icon" aria-hidden="true"></span>
              <span class="sr-only">Siguiente</span>
            </a>
          </div>
        <!-- Nosotros -->
        <section id="about">
            <div class="container mt-5">
                <div class="row">
                    <div class="col-md-6 col-sm-6">
                        <div class="about-info">
                            <h2 class="wow fadeInUp" data-wow-delay="0.6s"> Misión </h2>
                            <div class="wow fadeInUp" data-wow-delay="0.8s">
                                <p class="text-justify">Es una institución parte de San Francisco De Asís, que con ciencia y amor provee servicios de salud humanos, de calidad, a bajo costo y sin distinción, para mejorar las condiciones sanitarias de la comunidad, como expresión de solidaridad cristiana.
                                </p>
                            </div>
                            <h2 class="wow fadeInUp" data-wow-delay="0.6s"> Visión </h2>
                            <div class="wow fadeInUp" data-wow-delay="0.8s">
                                <p class="text-justify">Ser una institución de sólida estructura organizacional, participativa, referente de
                                    salud para la comunidad, que responde a sus necesidades y expectativas, mejorando
                                    sus condiciones de vida a través de servicios de calidad: profesionales
                                    cualificados, equipos con la tecnología requerida y espacios adecuados para la
                                    comodidad.</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>


        <!-- TEAM -->
        <section id="team" data-stellar-background-ratio="1" class="mt-5 mb-5">
            <div class="container">
                <div class="row">
                    <div class="col-md-6 col-sm-6">
                        <div class="about-info">
                            <h2 class="wow fadeInUp" data-wow-delay="0.1s"> Nuestro equipo: </h2>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="clearfix"></div>
                    <div class="col-md-6 col-sm-6">
                        <div class="team-thumb wow fadeInUp" data-wow-delay="0.4s">
                            <img src="https://via.placeholder.com/300" class="img-responsive" alt="">

                            <div class="team-info">
                                <h3>Emir Lopez</h3>
                                <p>Odonotólogo</p>
                            </div>

                        </div>
                    </div>

                    <div class="col-md-6 col-sm-6">
                        <div class="team-thumb wow fadeInUp" data-wow-delay="0.4s">
                            <img src="https://via.placeholder.com/300" class="img-responsive" alt="">

                            <div class="team-info">
                                <h3>Edwar Brito</h3>
                                <p>Asistente de Odontólogo</p>
                            </div>

                        </div>
                    </div>




                </div>
            </div>
        </section>


        <!-- NEWS -->
        <section id="news" data-stellar-background-ratio="2.5">
            <div class="container">
                <div class="row">

                    <div class="col-md-12 col-sm-12">
                        <!-- SECTION TITLE -->
                        <div class="section-title wow fadeInUp" data-wow-delay="0.1s">
                            <h2>Observe algunos de nuestros servicios</h2>
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-6">
                        <div class="news-thumb wow fadeInUp" data-wow-delay="0.4s">
                            <a href="news-detail.html">
                                <img src="{{ asset('img/news-image1.jpg') }}" class="img-responsive" alt="" width="320">
                            </a>
                            <div class="news-info">

                                <h3><a href="news-detail.html">Prótesis dentales</a></h3>
                                <p>Restaurar la anatomía de una o varias piezas dentarias.</p>

                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-6">
                        <div class="news-thumb wow fadeInUp" data-wow-delay="0.6s">
                            <a href="news-detail.html">
                                <img src="{{ asset('img/news-image2.jpg') }}" class="img-responsive" alt="" width="320">
                            </a>
                            <div class="news-info">
                                <h3><a href="news-detail.html">Amalgamado</a></h3>
                                <p>Se utiliza para restaurar dientes afectados por caries y resulta de la aleación del
                                    mercurio con otros metales.</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-6">
                        <div class="news-thumb wow fadeInUp" data-wow-delay="0.8s">
                            <a href="news-detail.html">
                                <img src="{{  asset('img/Root_Canal_Illustration_Molar.png') }}" class="img-responsive" alt="" width="320">
                            </a>
                            <div class="news-info">
                                <h3><a href="news-detail.html">Tratamiento de conducto</a></h3>
                                <p>Para salvar un diente que de otra manera debería extraerse.</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>


        <section id="appointment" data-stellar-background-ratio="3">
            <div class="container mb-4 mt-4">
                <div class="row">
                    <div class="col-md-6 col-sm-6">
                        <img src="{{ asset('img/appointment-image.jpg') }}" class="" alt="" style="width: 100%">
                    </div>
                    <div class="col-md-6 col-sm-6">
                        <!-- CONTACT FORM HERE -->
                        <form id="appointment-form" role="form" method="post" action="#">
                            <!-- SECTION TITLE -->
                            <div class="section-title wow fadeInUp" data-wow-delay="0.4s">
                                <h2>Solicite su Consulta</h2>
                            </div>
                            <div data-wow-delay="0.8s">
                                <div class="row">
                                    <div class="col-md-6 col-sm-6">
                                        <div class="form-group">
                                            <label for="name">Nombre</label>
                                            <input type="text" class="form-control" id="name" name="name"
                                            placeholder="Nombre y Apellido">
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-sm-6">
                                       <div class="form-group">
                                        <label for="email">Correo</label>
                                        <input type="email" class="form-control" id="email" name="email"
                                            placeholder="Su Correo">
                                       </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 col-sm-6">
                                       <div class="form-group">
                                            <label for="date">Seleccione el día</label>
                                            <input type="date" name="date" value="" class="form-control">
                                       </div>
                                    </div>
                                    <div class="col-md-6 col-sm-6">
                                        <div class="form-group">
                                            <label for="select">Tipo de consulta</label>
                                            <select class="form-control">
                                                <option>Rutina</option>
                                                <option>Control</option>
                                                <option>Urgente</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 col-sm-12">
                                        <div class="form-group">
                                            <label for="phone">Número telefónico</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" placeholder="Nº">
                                        </div>
                                        <div class="form-group">
                                            <label for="Message">Observación (opcional)</label>
                                            <textarea class="form-control" rows="5" id="message" name="message"
                                                placeholder="Mensaje"></textarea>
                                            <button type="submit" class="form-control mt-1" id="cf-submit" name="submit">Solicitar consulta</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </section>


        <!-- GOOGLE MAP -->
        <section id="google-map" class="mb-5">
            <!-- How to change your own map point
          1. Go to Google Maps
          2. Click on your location point
          3. Click "Share" and choose "Embed map" tab
          4. Copy only URL and paste it within the src="" field below -->
            <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1083.922381361521!2d-63.53650182023768!3d8.106367847244364!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8dce87b3826164e7%3A0xbe4f3dd6fb5fdd78!2sIglesia%20San%20Francisco%20De%20As%C3%ADs!5e1!3m2!1ses-419!2sve!4v1602349775065!5m2!1ses-419!2sve"
                width="100%" height="500" frameborder="12" style="border:25;" allowfullscreen="" aria-hidden="false"
                tabindex="0"></iframe>
        </section>

            <!-- START THE FEATURETTES -->
            <hr class="featurette-divider">
        </div><!-- /.container -->
       

        <div class="container mt-5">
            <div class="row">
                 <div class="col-md-6 col-sm-6">
                      <div class="footer-thumb"> 
                           <h4 class="wow fadeInUp" data-wow-delay="0.4s">Contacto</h4>
                           <p>Este Sistema web fue diseñado y creado por:</p>
                           <p><i class="fa fa-envelope-o"></i> <a href="#">
                            Samuel Trias: smltrs0@gmail.com
                            <br>
                            Ivan Ascanio: ivan@iva.com
                            </a></p>
                           
                      </div>
                 </div>
                 <div class="col-md-6 col-sm-6"> 
                      <div class="footer-thumb">
                           <div class="opening-hours">
                                <h4 class="wow fadeInUp" data-wow-delay="0.4s">Horarios de Atención</h4>
                                <p>Lunes - Viernes <span> 07:00 AM - 12:00 M y 02:00 - 06:00 PM</span></p>
                                <p>Sabado <span> 09:00 AM - 12:00 PM</span></p>
                                <p>Domingo <span>Cerrado</span></p>
                           </div> 

                           
                      </div>
                 </div>
            </div>
       </div>
        <!-- FOOTER -->
        <footer class="container">
            <p class="float-right"><a href="#">Subir</a></p>
            <p>© Copy 2017-2020 Todos los derechos reservados· <a href="#">Privacidad</a> · <a href="#">Terminos de uso</a></p>
        </footer>
    </main>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.min.js" integrity="sha384-w1Q4orYjBQndcko6MimVbzY0tgp4pWB4lZ7lr30WKz0vr/aWKhXdBNmNb5D92v7s" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/wow/1.1.2/wow.js" integrity="sha512-Rd5Gf5A6chsunOJte+gKWyECMqkG8MgBYD1u80LOOJBfl6ka9CtatRrD4P0P5Q5V/z/ecvOCSYC8tLoWNrCpPg==" crossorigin="anonymous"></script>
    <script>
        window.onscroll = function() {myFunction()};
        
        var navbar = document.getElementById("navbar");
        var sticky = navbar.offsetTop;
        
        function myFunction() {
          if (window.pageYOffset >= sticky) {
            navbar.classList.add("fixed-top")
          } else {
            navbar.classList.remove("fixed-top");
          }
        }

        new WOW({ mobile: false }).init();

        </script>


</body>

</html>
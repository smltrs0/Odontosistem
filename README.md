#  Odontosistem

Este es un proyecto en laravel 7. 

Para poder utilizar este proyecto, debes tener los siguientes requisitos:

1) debes tener la versión de PHP mayor o igual a la 7.2.5. 
para más información visita la documentación oficial de Laravel: https://laravel.com/docs/7.x

2) debes tener instalado composer en tu equipo: https://getcomposer.org/

3) si utilizas windows, puedes descargar el programa git desde la página oficial: https://gitforwindows.org/

Si cumples con estos pre-requisitos, entonces podrás instalar este proyecto.

Pasos para instalar este proyecto correctamente:

1) descarga este proyecto o clónalo con el comando: 

git clone y la url####

2) ejecutar el comando: 

composer install

3) copiar el archivo ".env.example" y pegarlo con el nombre: ".env". Si estas con el sistema gitforwindows, o en linux o mac, puedes ejecutar el siguiente comando: 

cp .env.example .env

4) debemos generar una nueva llave de laravel con el siguiente comando:

php artisan key:generate

5) Configura la nueva base de datos modificando el archivo ".env":

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_jhonatan_permisos
DB_USERNAME=root
DB_PASSWORD=

6) ejecuta php artisan migrate

7) ejecuta npm install && npm run dev


8) php artisan storage:link

9) ejecuta php artisan serve y prueba el proyecto que debe funcionar.

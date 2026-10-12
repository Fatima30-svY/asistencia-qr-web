# Sistema web de asistencia con código QR

Aplicación en Laravel que registra asistencia con código QR, aplica un sistema de
reglas con excepciones y estima el riesgo de ausentismo con un clasificador bayesiano.

## Instalación

1. `composer install`
2. `npm install`
3. Copiar `.env.example` a `.env` y configurar la base de datos (MySQL)
4. `php artisan key:generate`
5. Crear la base de datos y correr `php artisan migrate --seed`
6. `php artisan clasificador:entrenar`
7. En dos terminales: `php artisan serve` y `npm run dev`

## Usuarios de prueba (contraseña: `password`)

- `admin@test.com`
- `docente@test.com`
- `alumno1@test.com` a `alumno5@test.com`

## Evaluar el clasificador

`php artisan clasificador:evaluar`
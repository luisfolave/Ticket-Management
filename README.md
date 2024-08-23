## Prueba de Servicio de gestion de tickets para eventos artisticos

## Introducción
Para cumplir con lo solicitado en la evaluación técnica, se desarrolla una API REST a través de HTTP utilizando JSON para el traspaso de mensajes, que tiene como finalidad simular la gestión de tickets para eventos artísticos.

## Requerimientos

## Requisitos
Para el correcto funcionamiento de la API REST se debe:

- Tener instalada una versión vigente de PHP con Laravel.
- Clonar repositorio: https://github.com/Luisfolaveg/Ticket-Management.git.
- Ejecutar las migraciones ‘php artisan migrate’ en la terminal.
- Ejecutar comando ‘php artisan serve’ en la terminal para levantar el server.

## Base de datos
Se utiliza SQLite como motor de base de datos. Para configurar correctamente SQLite con Laravel debemos cerciorarnos de que el archivo .env contenga la conexión “DB_CONNECTION=sqlite”.
Creamos las tablas:

- event, con los campos: “event_id”, “event_name”, “organizer_name”, “description”, “description_details”, “event_date”, “location”, “ticket_price”.
- purchase, con los campos: “purchase_id”, “client_name”, “client_mail”, “client_phone”, “purchase_date”.
- ticket, con los campos: “ticket_id”, “purchase_id”, “event_id”, “seat_number”, “price”, “ticket_type”. Esta tabla, tiene como claves foráneas a “purchase_id”, referenciando a la tabla purchase, y “event_id”, referenciando a la tabla event.

## Socilitudes
Para probar el funcionamiento del API se deben hacer solicitudes mediante POSTMAN a los endpoints generados.

### */events*

### Metodo: GET

### Modelo

Event: Este modelo representa la tabla “event” en la base de datos. El modelo también incluye la lógica para la generación automática de un UUID al crear un nuevo evento.

### Controlador

EventController: Este controlador maneja las operaciones que tiene relación a los eventos. En particular, se hace uso del método “listEvents()”, el cual realiza una consulta para obtener la lista de eventos disponibles en la base de datos, y retorna los datos más relevantes de cada uno, en formato JSON.

### Ruta

La ruta definida para el endpoints es:

```php
Route::get('events', [EventController::class, 'listEvents']);
```


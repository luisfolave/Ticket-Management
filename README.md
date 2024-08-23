# Prueba de Servicio de gestion de tickets para eventos artisticos

# Introducción
Para cumplir con lo solicitado en la evaluación técnica, se desarrolla una API REST a través de HTTP utilizando JSON para el traspaso de mensajes, que tiene como finalidad simular la gestión de tickets para eventos artísticos.

# Requerimientos

# Requisitos
Para el correcto funcionamiento de la API REST se debe:

- Tener instalada una versión vigente de PHP con Laravel.
- Clonar repositorio: https://github.com/Luisfolaveg/Ticket-Management.git.
- Ejecutar las migraciones ‘php artisan migrate’ en la terminal.
- Ejecutar comando ‘php artisan serve’ en la terminal para levantar el server.

# Base de datos
Se utiliza SQLite como motor de base de datos. Para configurar correctamente SQLite con Laravel debemos cerciorarnos de que el archivo .env contenga la conexión “DB_CONNECTION=sqlite”.
Creamos las tablas:

- event, con los campos: “event_id”, “event_name”, “organizer_name”, “description”, “description_details”, “event_date”, “location”, “ticket_price”.
- purchase, con los campos: “purchase_id”, “client_name”, “client_mail”, “client_phone”, “purchase_date”.
- ticket, con los campos: “ticket_id”, “purchase_id”, “event_id”, “seat_number”, “price”, “ticket_type”. Esta tabla, tiene como claves foráneas a “purchase_id”, referenciando a la tabla purchase, y “event_id”, referenciando a la tabla event.

# Socilitudes
Para probar el funcionamiento del API se deben hacer solicitudes mediante POSTMAN a los endpoints generados.

## */events*

### Metodo: GET

### Modelo

Event: Este modelo representa la tabla “event” en la base de datos. El modelo también incluye la lógica para la generación automática de un UUID al crear un nuevo evento.

### Controlador

EventController: Este controlador maneja las operaciones que tiene relación a los eventos. En particular, se hace uso del método “listEvents()”, el cual realiza una consulta para obtener la lista de eventos disponibles en la base de datos, y retorna los datos más relevantes de cada uno, en formato JSON.

```php
public function listEvents()
    {
        try {
            $events = Event::select('event_name', 'description', 'location', 'event_date')->get();

            // Comprobar que se listaron los eventos correctamente
            $data = $events;
            return response()->json($data, 200);
        } catch (Exception $e) {
            $data = ['error' => 'Ocurrió un error al obtener los eventos: ' . $e->getMessage()];
            return response()->json($data, 500);
        }
    }
```

### Ruta

La ruta definida para el endpoints es:

```php
Route::get('events', [EventController::class, 'listEvents']);
```
## */event*

### Metodo: GET

### Modelo

Event: Se utiliza el mismo modelo que en el endpoint anterior.

### Controlador

EventController: En este caso, se hace uso del método “eventDetails()”, el cual realiza una consulta en la base de datos, para obtener los detalles de un evento especifico basado en su “event_id”, el cual se debe adjuntar al método. También retorna la información completa del evento en formato JSON.

```php
public function eventDetails($eventID)
    {
        try {
            $event = Event::where('event_id', $eventID)
                ->select('event_name', 'organizer_name', 'description', 'description_details', 'event_date', 'location', 'ticket_price')
                ->first();

            // Comprobar que se obtuvo informacion del evento correctamente
            if (!$event) {
                $data = ['message' => 'Evento no encontrado'];
                return response()->json($data, 404);
            }

            $data = $event;
            return response()->json($data, 200);
        } catch (Exception $e) {
            $data = ['error' => 'Ocurrió un error al obtener la informacion del evento: ' . $e->getMessage()];
            return response()->json($data, 500);
        }
    }
```

### Ruta

La ruta definida para el endpoints es:

```php
Route::get('event/{eventID}', [EventController::class, 'eventDetails']);
```

Este endpoint requiere de incluir el “event_id” del evento específico del que se quiere obtener la información.

## */purchase*

### Metodo: POST

### Modelo

Event: Se utiliza para verificar la existencia del evento y obtener el precio del ticket.

Purchase: Este modelo representa la tabla “purchase” en la base de datos. Se utiliza para hacer registro de la compra.

Ticket: Este modelo representa la tabla “ticket” en la base de datos. Se utiliza para registrar los tickets comprados.

### Controlador

PurchaseController: Este controlador maneja las operaciones de compra de tickets. En este caso, se utiliza el metodo “buyTickets()”, el cual crear un registro de la compra en la tabla “purchase”  y asocia los tickets con la compra realizada en la tabla “ticket”. Este método, además, verifica que los datos ingresados sean válidos, tales como que el correo electrónico cumpla con el formato de electrónico, el número telefónico debe estar compuesto por exactamente 9 dígitos, el evento al que se referencia debe existir, el asiento no debe de haber sido comprado antes en el evento, además debe componerse de 3 caracteres, de los cuales el primero es una letra y los que le siguen sean números (e., “A01”, “B23”), agregar también que se puede hacer compras de varios tickets en un solo proceso tan solo dejando una “,” sin espacio entre los asientos (e., “A01,A02,A03”),  el tipo del ticket solo pueden ser “Regular” o  “Premium”, y que el precio ingresado no puede ser un monto menor al costo establecido para el evento.

```php
public function buyTickets(Request $request)
    {
        try {
            $event = Event::find($request->event_id);
    
            if (!$event) {
                return response()->json(['error' => 'El evento no existe.'], 404);
            }
    
            $ticketPrice = $event->ticket_price;
    
            $request->validate([
                'client_name' => 'required|string',
                'client_mail' => 'required|email',
                'client_phone' => 'nullable|string|size:9', // Validar nuúmero telefonico con 9 caracteres
                'event_id' => 'required|string|exists:event,event_id', // Validar evento existente
                'seat_numbers' => [
                    'required',
                    'string',
                    //Validar formato de asiento con 1 digito seguido de 2 letras
                    function ($attribute, $value, $fail) {
                        $seats = array_map('trim', explode(',', $value));
                        foreach ($seats as $seat) {
                            if (!preg_match('/^[A-Z]\d{1,}$/', $seat)) {
                                $fail("El asiento $seat no tiene un formato válido.");
                            }
                        }
                    },
                    'size:3' // Validar formato de asiento con 3 caracteres
                ],
                'ticket_type' => ['required', Rule::in(['Regular', 'Premium'])], // Validar tipo de ticket entre dos tipos
                'price' => [
                    'required',
                    'numeric',
                    'min:' . $ticketPrice, // Validar que precio pagado sea minimo el precio del ticket
                ]
            ]);
    
            $purchaseID = (string) Str::uuid();
            $seatNumbers = array_map('trim', explode(',', $request->seat_numbers)); // Tratar numeros de asiento como array y eliminar los espacios en blanco
    
            // Verificar si algunos asientos ya han sido registrados en el evento
            $existingSeats = Ticket::where('event_id', $request->event_id)
                ->whereIn('seat_number', $seatNumbers)
                ->pluck('seat_number')
                ->toArray();
    
            if (!empty($existingSeats)) {
                $data = ['error' => 'Los siguientes asientos ya han sido registrados para este evento: ' . implode(', ', $existingSeats)];
                return response()->json($data, 400);
            }
    
            DB::beginTransaction();
    
            $purchase = Purchase::create([
                'purchase_id' => $purchaseID,
                'client_name' => $request->client_name,
                'client_mail' => $request->client_mail,
                'client_phone' => $request->client_phone,
            ]);
    
            foreach ($seatNumbers as $seatNumber) {
                Ticket::create([
                    'ticket_id' => (string) Str::uuid(),
                    'purchase_id' => $purchaseID,
                    'event_id' => $request->event_id,
                    'seat_number' => $seatNumber,
                    'price' => $request->price,
                    'ticket_type' => $request->ticket_type
                ]);
            }
    
            DB::commit();
    
            // Comprobar que se realizo la compra correctamente
            $data = ['purchase_id' => $purchaseID];
            return response()->json($data, 201);
        } catch (Exception $e) {
            DB::rollBack();
            $data = ['error' => 'Error al procesar la compra: ' . $e->getMessage()];
            return response()->json($data, 500);
        }
    }
```

### Ruta

La ruta definida para el endpoints es:

```php
Route::post('purchase', [PurchaseController::class, 'buyTickets']);
```

Para hacer una solicitud en método POST y realizar la compra se solicita rellenar un JSON con los campos: "client_name", "client_mail", "client_phone", "event_id", "seat_numbers", "ticket_type".

## */orders*

### Metodo: GET

### Modelo

Event: Se utiliza para obtener el nombre del evento.

Purchase: Se utiliza para identificar al cliente en cuestion junto a sus datos.

Ticket:  Se utiliza para obtener los tickets comprados.

### Controlador

PurchaseController: En este caso se hace uso del método “clientOrders()”, el cual obtiene todas las compras realizadas por un cliente especifico, basado en su correo electrónico, el cual debe cumplir con el formato de un correo electrónico.

```php
public function clientOrders($clientMail)
    {
        try {
            // Validar que el correo electrónico sea válido
            if (!filter_var($clientMail, FILTER_VALIDATE_EMAIL)) {
                $data = ['error' => 'El correo electrónico proporcionado no es válido'];
                return response()->json($data, 400);
            }
    
            $orders = DB::table('purchase as p')
                ->join('ticket as t', 'p.purchase_id', '=', 't.purchase_id')
                ->join('event as e', 't.event_id', '=', 'e.event_id')
                ->where('p.client_mail', $clientMail)
                ->select(
                    'p.purchase_id',
                    'p.client_name',
                    'p.client_mail',
                    'p.client_phone',
                    'p.purchase_date',
                    't.event_id',
                    'e.event_name',
                    't.seat_number',
                    't.price',
                    't.ticket_type'
                )
                ->get();
    
            // Comprobar que se obtuvo la información correctamente
            $data = $orders;
            return response()->json($data, 200);
        } catch (Exception $e) {
            $data = ['error' => 'Ocurrió un error al obtener las órdenes: ' . $e->getMessage()];
            return response()->json($data, 500);
        }
    }
```

### Ruta

La ruta definida para el endpoints es:

```php
Route::get('orders/{clientMail}', [PurchaseController::class, 'clientOrders']);
```

Este endpoint requiere de incluir el correo electrónico del cliente del cual se espera recibir los datos de compra.

## */postEvents*

### Metodo: POST

### Modelo

Event: Se utiliza para hacer registro de eventos en la tabla de la base de datos.

### Controlador

EventController: En este caso se hace uso del método “postEvents()”, el cual inserta eventos en la tabla event. 

```php
public function postEvents(Request $request)
    {
        try {
            $request->validate([
                'event_name' => 'required|string',
                'organizer_name' => 'nullable|string',
                'description' => 'nullable|string',
                'description_details' => 'nullable|string',
                'event_date' => 'required|date',
                'location' => 'required|string',
                'ticket_price' => 'required|integer'
            ]);

            $event = Event::create([
                'event_name' => $request->event_name,
                'organizer_name' => $request->organizer_name,
                'description' => $request->description,
                'description_details' => $request->description_details,
                'event_date' => $request->event_date,
                'location' => $request->location,
                'ticket_price' => $request->ticket_price
            ]);

            // Comprobar que el evento se haya creado correctamente
            if (!$event) {
                $data = ['message' => 'Error al crear evento'];
                return response()->json($data, 500);
            }

            $data = ['message' => 'Evento creado correctamente'];
            return response()->json($data, 201);
        } catch (Exception $e) {
            $data = ['error' => 'Ocurrió un error: ' . $e->getMessage()];
            return response()->json($data, 500);
        }
    }
```

### Ruta

La ruta definida para el endpoints es:

```php
Route::post('postEvents', [EventController::class, 'postEvents']);
```

Se da a entender, que este endpoint no es uno de los solicitados, pero es de gran importancia a la hora de registrar eventos, para luego hacer pruebas de los endpoints solicitados de manera más optima.

# Evidencia
Se adjuntan una serie de imagenes que denuncian el correcto funcionamiento de los endpoints:
### /events
[![Captura-de-pantalla-2024-08-23-050602.png](https://i.postimg.cc/8Cjzz42h/Captura-de-pantalla-2024-08-23-050602.png)](https://postimg.cc/p9MH0fXT)
### /event
[![Captura-de-pantalla-2024-08-23-050742.png](https://i.postimg.cc/j2QsFFfr/Captura-de-pantalla-2024-08-23-050742.png)](https://postimg.cc/SYRFXVVZ)
### /purchase
[![Captura-de-pantalla-2024-08-23-050803.png](https://i.postimg.cc/TPFYLmxB/Captura-de-pantalla-2024-08-23-050803.png)](https://postimg.cc/kVNdL2By)
### /orders
[![Captura-de-pantalla-2024-08-23-050819.png](https://i.postimg.cc/T1XdQ6p9/Captura-de-pantalla-2024-08-23-050819.png)](https://postimg.cc/5YnWND5Y)
### /postEvents
[![Captura-de-pantalla-2024-08-23-050842.png](https://i.postimg.cc/85vkh5Pc/Captura-de-pantalla-2024-08-23-050842.png)](https://postimg.cc/gXYPpGhb)

# Supuestos

- Colección POSTMAN: Se dispone de la siguiente colección POSTMAN para hacer las solicitudes a los endpoints: https://events-management.postman.co/workspace/Events-Management-Workspace~ed6518fe-5294-4ac1-9310-96f1d02f08a8/collection/30376809-84db652c-7229-446a-9007-3cb5cdf2f1b3?action=share&creator=30376809.
- Validación de datos: Se asume que los datos ingresados por el usuario son correctos, sin embargo, se incluye validación básica en los controladores.
- Creación de eventos: No es obligatoria la creación de eventos según el enunciado, sin embargo, para una mejor calidad de pruebas, se incorpora un método para ingresar eventos.
- Uso de SQLite: Se utiliza SQLite como motor de base de datos, dado a su compatibilidad con Laravel, y dado que no se especifica sobre qué base de datos utilizar, se utiliza esta.
- Errores de Concurrencia: Dado que la API REST solo cumple con simular la gestión, por lo que no se implementa un manejo especial de concurrencias, por lo que en casos particulares podría generarse un error al realizarse dos compras simultaneas del mismo asiento.
- Existencia de Eventos: Se asume que debe haber eventos cargados en la tabla “event” para realizar las pruebas de solicitudes.
- Id como UUID: Los elementos en las tablas poseen sus id en forma de UUID, que se generan de manera automática, razón por la que no se solicita en ningún momento el ingreso de id manual. La decisión de este formato es para que cada elemento tenga una id única.
- Campos obligatorios: Existen campos que se deben de rellenar para los endpoints del metodo POST. En caso de /postEvents: “event_name”, “event_date”, “location” y “ticket_price”; En caso de /purchase: “client_name”, “client_mail”, “event_id”, “seat_numbers”, “ticket_type” y “price”.
- Tipos de dato obligatorio: Para el ingreso de campos en los endpoints del metodo POST, se debe respetar el tipo de dato. Para /postEvents: “event_name”-String, “event_date”-String, “organizer_name”-String, “description”-String, “desciption_details”-String, “event_date”-date, “location”-String y “ticket_price”-Integer; En caso de /purchase: “client_name”-String, “client_mail”-Email, “client_phone”-String, “event_id”-String, “seat_numbers”-String, “price”-Numeric.
- Teléfono: Se solicita que los números telefónicos deben constituirse de 9 dígitos específicamente.
- Evento Existente: Para querer ver datos relacionados a un evento, el evento en cuestión debe existir.
- Comprar asientos: Para comprar un asiento en un evento, este asiento no puede haber sido ya comprado con anterioridad para el mismo evento. También se exige que los asientos estén constituidos exactamente de 3 caracteres, donde el primero sea una letra los dos siguientes sean números.
- Tipos de Ticket: Para poder comprar un ticket se debe elegir el tipo de este, el cual solo ofrece dos opciones: “Regular” o “Premium”.
- Precio del ticket: Para comprar un ticket se debe ingresar un monto en “price” que represente un valor mayor al que expone el precio del evento.
- Listar Compras: Para poder ver las compras relacionadas a un usuario, se solicita el correo electrónico con el cual este realizo la compra. Se asume que el usuario ha realizado compras que se puedan mostrar. También se exige que el correo cumpla con el formato de un email.

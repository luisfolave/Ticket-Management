<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\EventController;
use App\Http\Controllers\PurchaseController;

Route::post('postEvents', [EventController::class, 'postEvents']); // Ruta para cargar eventos
Route::get('events', [EventController::class, 'listEvents']); // Ruta para consultar eventos disponibles
Route::get('event/{eventID}', [EventController::class, 'eventDetails']); // Ruta para consultar informacion del evento
Route::post('purchase', [PurchaseController::class, 'buyTickets']); // Ruta para cargar la compra de ticket
Route::get('orders/{clientMail}', [PurchaseController::class, 'clientOrders']); // Ruta para consultar compras de cliente
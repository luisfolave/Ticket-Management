<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\EventController;
use App\Http\Controllers\PurchaseController;

Route::post('postEvents', [EventController::class, 'postEvents']);
Route::get('events', [EventController::class, 'listEvents']);
Route::get('event/{eventID}', [EventController::class, 'eventDetails']);
Route::post('purchase', [PurchaseController::class, 'buyTickets']);
Route::get('orders/{clientMail}', [PurchaseController::class, 'clientOrders']);
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\Purchase;
use App\Models\Ticket;
use App\Models\Event;
use Illuminate\Support\Str;
use Exception;

class PurchaseController extends Controller
{
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
                            } elseif (strlen($seat) !== 3){
                                $fail("El asiento $seat debe tener exactamente 3 caracteres"); // Validar que el asiento este compuesto de 3 caracteres
                            }
                        }
                    }
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
}

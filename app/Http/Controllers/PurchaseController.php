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
        $request->validate([
            'client_name' => 'required|string',
            'client_mail' => 'required|email',
            'client_phone' => 'nullable|string|size:9',
            'event_id' => 'required|string|exists:event,event_id',
            'seat_numbers' => 'required|string',
            'ticket_type' => ['required', Rule::in(['Regular', 'Premium'])]
        ]);

        $event = Event::find($request->event_id);
        $ticketPrice = $event->ticket_price;
        $purchaseID = (string) Str::uuid();

        // Convertimos los números de asientos en un array y eliminamos espacios en blanco
        $seatNumbers = array_map('trim', explode(',', $request->seat_numbers));

        // Verificamos si alguno de los asientos ya ha sido registrado en el evento
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
                'price' => $ticketPrice,
                'ticket_type' => $request->ticket_type
            ]);
        }

        DB::commit();

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

            $data = $orders;
            return response()->json($data, 200);
        } catch (Exception $e) {
            $data = ['error' => 'Ocurrió un error al obtener las órdenes: ' . $e->getMessage()];
            return response()->json($data, 500);
        }
    }
}

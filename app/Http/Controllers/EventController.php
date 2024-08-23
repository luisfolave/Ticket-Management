<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Event;
use Exception;

class EventController extends Controller
{
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

    public function listEvents()
    {
        try {
            $events = Event::select('event_name', 'description', 'location', 'event_date')->get();
            $data = $events;
            return response()->json($data, 200);
        } catch (Exception $e) {
            $data = ['error' => 'Ocurrió un error al obtener los eventos: ' . $e->getMessage()];
            return response()->json($data, 500);
        }
    }

    public function eventDetails($eventID)
    {
        try {
            $event = Event::where('event_id', $eventID)
                ->select('event_name', 'organizer_name', 'description', 'description_details', 'event_date', 'location', 'ticket_price')
                ->first();

            if (!$event) {
                $data = ['message' => 'Evento no encontrado'];
                return response()->json($data, 404);
            }

            $data = $event;
            return response()->json($data, 200);
        } catch (Exception $e) {
            $data = ['error' => 'Ocurrió un error al obtener los detalles del evento: ' . $e->getMessage()];
            return response()->json($data, 500);
        }
    }
}

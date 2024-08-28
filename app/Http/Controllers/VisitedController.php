<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;

class VisitedController extends Controller
{
    public function updateStatusToVisited($reservation_id)
    {
       
        $reservation = Reservation::find($reservation_id); 
        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }

        $reservation->status = 'visited';
        $reservation->save();

        return response()->json([
            'status' => 200,
            'message' => 'Reservation status updated to visited',
            'data' => $reservation
        ]);
    }

}
<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Appointment;
use Illuminate\Http\Request;
use App\Models\admin\Assistant;

class StatisticsController extends Controller
{

    public function count()
    {

        $todayAppointmentsCount = Appointment::whereDate('time_start', today())->count();

        $assistantCount = Assistant::count();
        $patientCount = Patient::count();
        $doctorCount = User::count();

        // Check if any count is zero and prepare the message
        $message = 'Data is available.';
        if ($todayAppointmentsCount == 0 && $assistantCount == 0 && $patientCount == 0 && $doctorCount == 0) {
            $message = 'No data available.';
        }
        // Return the counts and message as a JSON response
        return response()->json([
            'data' => [
                'doctor_count' => $doctorCount,
                'today_appointments_count' => $todayAppointmentsCount,
                'patient_count' => $patientCount,
                'assistant_count' => $assistantCount,
            ],
            'message' => $message,
            'status' => 200
        ]);
    }

    public function getMonthlyTotalsByYear(Request $request)
    {
        // Check if the request has the 'year' parameter
        if (!$request->has('year')) {
            $year = \Carbon\Carbon::now()->year; // Use 400 status code for bad requests
        } else {
            // Validate the 'year' parameter
            $validated = $request->validate([
                'year' => 'required|integer|digits:4'
            ]);
            // Get the year from the validated request
            $year = $validated['year'];

            // Validate that the year is within a reasonable range, e.g., not in the future
            if ($year > \Carbon\Carbon::now()->year) {
                return response()->json([
                    'status' => 400,
                    'message' => 'The year cannot be in the future.'
                ], 400);
            }
        }
        // Calculate the start and end of the given year
        $startOfYear = \Carbon\Carbon::createFromDate($year, 1, 1)->startOfYear();
        $endOfYear = \Carbon\Carbon::createFromDate($year, 12, 31)->endOfDay();

        // Query to get total payments grouped by month for the given year
        $monthlyTotals = Payment::selectRaw('
            MONTH(created_at) as month,
            SUM(total) as total
        ')
            ->whereBetween('created_at', [$startOfYear, $endOfYear])
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();
        // Map month numbers to month names
        $monthNames = [
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'May',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Aug',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Dec',
        ];
        // Transform the result to include month names and total amounts
        $monthlyTotals = $monthlyTotals->map(function ($item) use ($monthNames) {
            return [
                'month' => $monthNames[$item->month] ?? 'Unknown',
                'amount' => $item->total,
            ];
        });

        // Check if there are results
        if ($monthlyTotals->isEmpty()) {
            return response()->json([
                'status' => 404,
                'message' => 'No payments found for the specified year.'
            ], 404);
        }

        return response()->json([
            $year => $monthlyTotals,
            'status' => 200
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
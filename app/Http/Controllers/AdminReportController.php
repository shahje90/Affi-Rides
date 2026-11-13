<?php

namespace App\Http\Controllers;

use App\Enums\RideStatus;
use App\Http\Requests\AdminReportsFilterRequest;
use App\Models\Ride;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminReportController extends Controller
{
    public function index(AdminReportsFilterRequest $request): View
    {
        [$from, $to] = $this->validatedRange($request->validated());
        $rides = $this->completedRides($from, $to);

        return view('admin.reports', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'rideCount' => (clone $rides)->count(),
            'commission' => (string) (clone $rides)->sum('company_commission'),
            'riderEarnings' => (string) (clone $rides)->sum('rider_earning'),
        ]);
    }

    public function export(AdminReportsFilterRequest $request): StreamedResponse
    {
        [$from, $to] = $this->validatedRange($request->validated());
        $rides = $this->completedRides($from, $to)
            ->with(['rider:id,name,phone', 'passenger:id,name,phone'])
            ->orderBy('completed_at')
            ->get();

        return Response::streamDownload(function () use ($rides): void {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['Ride ID', 'Completed At', 'Passenger', 'Rider', 'Final Fare PKR', 'Commission PKR', 'Rider Earning PKR']);

            foreach ($rides as $ride) {
                fputcsv($stream, [
                    $ride->id,
                    $ride->completed_at?->toIso8601String(),
                    $ride->passenger?->name,
                    $ride->rider?->name,
                    $ride->final_fare,
                    $ride->company_commission,
                    $ride->rider_earning,
                ]);
            }

            fclose($stream);
        }, "acr-commission-{$from->toDateString()}-{$to->toDateString()}.csv", [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{Carbon, Carbon}
     */
    private function validatedRange(array $validated): array
    {
        $from = Carbon::parse($validated['from'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $to = Carbon::parse($validated['to'] ?? today()->toDateString())->endOfDay();

        return [$from, $to];
    }

    private function completedRides(Carbon $from, Carbon $to)
    {
        return Ride::query()
            ->where('status', RideStatus::Completed->value)
            ->whereBetween('completed_at', [$from, $to]);
    }
}

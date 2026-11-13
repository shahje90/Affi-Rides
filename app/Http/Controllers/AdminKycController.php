<?php

namespace App\Http\Controllers;

use App\Enums\KycStatus;
use App\Http\Requests\ReviewKycRequest;
use App\Models\RiderProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminKycController extends Controller
{
    public function index(): View
    {
        return view('admin.kyc.index', [
            'profiles' => RiderProfile::query()
                ->whereIn('kyc_status', [KycStatus::Pending, KycStatus::Rejected])
                ->with('user:id,name,phone,status')
                ->latest()
                ->paginate(20),
        ]);
    }

    public function show(RiderProfile $profile): View
    {
        return view('admin.kyc.show', ['profile' => $profile->load('user:id,name,phone,status')]);
    }

    public function document(RiderProfile $profile, string $document)
    {
        $this->authorize('viewDocuments', $profile);

        $path = match ($document) {
            'cnic-front' => $profile->cnic_front_image,
            'cnic-back' => $profile->cnic_back_image,
            'bike-document' => $profile->bike_document_image,
            default => null,
        };

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path));
    }

    public function review(ReviewKycRequest $request, RiderProfile $profile): RedirectResponse
    {
        $this->authorize('review', $profile);
        $data = $request->validated();

        DB::transaction(function () use ($profile, $data): void {
            $lockedProfile = RiderProfile::query()->lockForUpdate()->findOrFail($profile->id);

            if ($lockedProfile->kyc_status !== KycStatus::Pending) {
                throw ValidationException::withMessages([
                    'kyc_status' => __('This KYC application has already been reviewed.'),
                ]);
            }

            $status = KycStatus::from($data['kyc_status']);
            $lockedProfile->update([
                'kyc_status' => $status,
                'kyc_note' => $data['kyc_note'],
                'is_online' => $status === KycStatus::Approved && $lockedProfile->is_online,
            ]);

            Log::info('Rider KYC application reviewed.', [
                'rider_id' => $lockedProfile->user_id,
                'status' => $status->value,
                'reviewed_by' => auth()->id(),
            ]);
        });

        return redirect()->route('admin.kyc.show', $profile)->with('status', __('KYC application updated.'));
    }
}

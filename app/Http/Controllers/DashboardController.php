<?php

namespace App\Http\Controllers;

use App\Http\Resources\SubmissionResource;
use App\Models\Submission;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        return response()->json([
            'awaiting_signature' => Submission::query()->where('status', 'sent')->count(),
            'pending' => Submission::query()->where('status', 'pending')->count(),
            'signed_this_week' => Submission::query()
                ->where('status', 'signed')
                ->whereBetween('signed_at', [now()->startOfWeek(), now()])
                ->count(),
            'expired' => Submission::query()
                ->where('expires_at', '<', now())
                ->whereNotIn('status', ['signed'])
                ->count(),
            'recent_submissions' => SubmissionResource::collection(
                Submission::query()->with(['document', 'media'])->latest()->limit(10)->get()
            ),
        ]);
    }
}

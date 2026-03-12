<?php

namespace App\Http\Controllers;

use App\Http\Resources\SubmissionResource;
use App\Models\Submission;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerSubmissionController extends Controller
{
    public function index(string $email): AnonymousResourceCollection
    {
        $submissions = Submission::query()
            ->where('recipient_email', $email)
            ->with('document')
            ->latest()
            ->paginate(15);

        return SubmissionResource::collection($submissions);
    }
}

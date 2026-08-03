<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\AppBusinessEnquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BusinessEnquiryController extends Controller
{
    public function store(Request $request, string $appSlug)
    {
        $app = App::query()->where('slug', $appSlug)->where('is_active', true)->firstOrFail();
        $data = $request->validate([
            'name' => ['required','string','max:160'],
            'organization' => ['nullable','string','max:190'],
            'email' => ['required','email','max:190'],
            'country' => ['nullable','string','max:100'],
            'project_type' => ['nullable','string','max:120'],
            'message' => ['required','string','min:10','max:5000'],
            'preferred_response' => ['nullable','in:email'],
        ]);
        $reference = 'DXM-'.strtoupper(Str::random(8));
        $row = AppBusinessEnquiry::create([
            'app_id' => $app->id,
            'reference' => $reference,
            'status' => 'new',
            'name' => $data['name'],
            'organization' => $data['organization'] ?? null,
            'email' => $data['email'],
            'country' => $data['country'] ?? null,
            'project_type' => $data['project_type'] ?? null,
            'message' => $data['message'],
            'preferred_response' => 'email',
            'phone' => null,
            'meta_json' => ['source' => 'flutter_more_build_with_dxm', 'app_version' => $request->input('app_version'), 'platform' => $request->input('platform')],
        ]);
        return response()->json(['ok' => true, 'reference' => $row->reference], 201);
    }
}

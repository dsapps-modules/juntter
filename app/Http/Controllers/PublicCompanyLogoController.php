<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicCompanyLogoController extends Controller
{
    public function show(Request $request): BinaryFileResponse|Response
    {
        $path = (string) $request->query('path', '');

        abort_unless($path !== '', 404);
        abort_unless(Storage::disk('public')->exists($path), 404);

        return response()->file(Storage::disk('public')->path($path));
    }
}

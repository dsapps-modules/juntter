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
        $defaultLogoPath = public_path('img/logo/juntter_webp_640_174.webp');

        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            abort_unless(is_file($defaultLogoPath), 404);

            return response()->file($defaultLogoPath);
        }

        return response()->file(Storage::disk('public')->path($path));
    }
}

<?php

namespace App\Http\Controllers\Installer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function index()
    {
        return view('installer.step2');
    }

    public function verify(Request $request)
    {

        $request->validate([
            'access_key' => 'required|string|min:8'
        ]);

        // Example license key
        $validKey = "1234-5678";

        if ($request->access_key != $validKey) {

            return response()->json([
                'status' => false,
                'message' => 'Invalid access key.'
            ]);
        }

        $data = [
            'domain' => $request->getHost(),
            'key' => $request->access_key,
            'expiry' => '2030-01-01'
        ];

        file_put_contents(
            storage_path('license.dat'),
            encrypt(json_encode($data))
        );

        return response()->json([
            'status' => true,
            'message' => 'License verified successfully.'
        ]);

    }
}

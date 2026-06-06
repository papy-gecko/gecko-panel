<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class PhpMyAdminSsoController extends Controller
{
    public function redirect(Request $request)
    {
        $token = $request->query('token');
        if (!$token) abort(403);

        try {
            $data = json_decode(base64_decode(urldecode($token)), true);
        } catch (\Exception $e) {
            abort(403);
        }

        if (!$data || $data['expires'] < now()->timestamp) {
            abort(403, 'Token expiré');
        }

        $user = $data['user'];
        $password = $data['password'];
        $db = $data['db'];

        $pmaUrl = '/phpmyadmin/index.php';
        $query = http_build_query([
            'pma_username' => $user,
            'pma_password' => $password,
            'db'           => $db,
        ]);

        return redirect($pmaUrl . '?' . $query);
    }
}

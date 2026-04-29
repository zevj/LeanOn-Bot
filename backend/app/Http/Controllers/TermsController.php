<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TermsController extends Controller
{
    public function current()
    {
        return response()->json([
            'content' => 'Terms and conditions placeholder'
        ]);
    }

    public function accept(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->terms_accepted_at = now();
            $user->save();
            return response()->json(['message' => 'Terms accepted successfully']);
        }
        return response()->json(['message' => 'Unauthorized'], 401);
    }
}

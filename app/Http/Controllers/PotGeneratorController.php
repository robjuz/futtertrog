<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PotGeneratorController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'lidWidth' => 'numeric|min:50',
            'lidHandleRadius' => 'numeric|min:10',
            'lidColor' => ['regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],

            'potWidth' => 'numeric|min:50',
            'potHeight' => 'numeric|min:50',
            'potRadius' => 'numeric|min:10',
            'potColor' => ['regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
        ]);

        if ($request->has('download')) {
            return response(view('tools/pot')->with($data), 200, [
                'Content-Type' => 'image/svg+xml',
                'Content-Disposition' => 'attachment; filename="pot.svg"',
            ]);
        }

        return view('tools/pot')->with($data);
    }
}

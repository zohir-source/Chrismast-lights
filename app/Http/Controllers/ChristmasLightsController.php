<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ChristmasLightsController extends Controller
{
    public function index()
    {
        $defaults = [
            'rows' => 1,
            'cols' => 7,
            'interval' => 500,
            'intensity' => 1.0,
            'normalOpacity' => 0.3,
            'sizes' => array_fill(0, 7, 40),
            'colors' => ['#ff0000','#ff7f00','#ffff00','#00ff00','#00ffff','#0000ff','#4b0082','#8f00ff']
        ];

        return view('christmas-lights', compact('defaults'));
    }
}

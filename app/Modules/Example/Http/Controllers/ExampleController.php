<?php

namespace App\Modules\Example\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ExampleController extends Controller
{
    /**
     * Display the example module main page
     */
    public function index(): View
    {
        return view('example::index');
    }
}

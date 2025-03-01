<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
  public function welcome(): Response
  {
    return Inertia::render('Welcome');
  }
}

<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Package;

class LandingController extends Controller
{
    public function index(): void
    {
        $this->render('landing/index', [
            'packages' => Package::sellable(),
        ], 'layouts/public');
    }
}

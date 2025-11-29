<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(protected DashboardService $dashboardService)
    {
    }

    /**
     * Display the user's dashboard with their balance and transactions.
     */
    public function index(Request $request): Response
    {
        $dashboardData = $this->dashboardService->getDashboardData();

        return Inertia::render('Dashboard', $dashboardData);
    }
}
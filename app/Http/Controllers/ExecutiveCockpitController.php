<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ExecutiveDashboardService;
use Illuminate\View\View;

class ExecutiveCockpitController extends Controller
{
    protected ExecutiveDashboardService $dashboardService;

    // Dependency Injection ile Service sınıfını alıyoruz.
    public function __construct(ExecutiveDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index(): View
    {
        $stats = $this->dashboardService->getCockpitStats();

        return view('executive.cockpit', compact('stats'));
    }
}

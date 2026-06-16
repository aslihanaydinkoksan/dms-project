<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UniversalAnalyticsService;

class AnalyticsController extends Controller
{
    protected UniversalAnalyticsService $universalService;

    public function __construct(UniversalAnalyticsService $universalService)
    {
        $this->universalService = $universalService;
    }

    public function index()
    {
        $modulesConfig = $this->universalService->getAvailableModules();
        return view('analytics.index', compact('modulesConfig'));
    }

    public function getChartData(Request $request)
    {
        $validated = $request->validate([
            'module' => 'required|string',
            'group' => 'required|string',
            'date_start' => 'nullable|date',
            'date_end' => 'nullable|date',
        ]);

        try {
            $chartData = $this->universalService->generateChartData(
                $validated['module'],
                $validated['group'],
                $validated['date_start'],
                $validated['date_end']
            );

            return response()->json($chartData);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

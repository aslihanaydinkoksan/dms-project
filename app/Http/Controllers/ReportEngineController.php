<?php

namespace App\Http\Controllers;

use App\Models\ScheduledReport;
use Illuminate\Http\Request;

class ReportEngineController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'report_name' => 'required|string|max:255',
            'module' => 'required|string',
            'frequency' => 'required|string',
            'date_range' => 'required|string',
            'format' => 'required|string',
            'recipients' => 'required|string',
        ]);

        ScheduledReport::create($request->all());

        return back()->with('success', 'Rapor görevi başarıyla zamanlandı! Belirttiğiniz periyotlarda otomatik gönderilecektir.');
    }
}

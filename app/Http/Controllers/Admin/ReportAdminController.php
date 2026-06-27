<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Report;
use Illuminate\Http\Request;

class ReportAdminController extends Controller
{
    public function index(Request $request)
    {
        $q = Report::with('reporter:id,name')->latest();
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        return response()->json($q->paginate(20));
    }

    public function update(Request $request, int $id)
    {
        $request->validate(['status' => 'required|in:pending,reviewed,dismissed']);

        $report = Report::findOrFail($id);
        $report->update(['status' => $request->status]);

        AuditLog::record('report_' . $request->status, 'report', $report->id);

        return response()->json($report);
    }
}

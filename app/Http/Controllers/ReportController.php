<?php
namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'reportable_type' => 'required|in:demande,offre,avis,message,user',
            'reportable_id'   => 'required|integer',
            'reason'          => 'required|string|max:80',
            'details'         => 'nullable|string|max:1000',
        ]);

        // don't allow stacking duplicate pending reports on the same target
        $already = Report::where('reporter_id', $this->currentUser()->id)
            ->where('reportable_type', $request->reportable_type)
            ->where('reportable_id', $request->reportable_id)
            ->where('status', 'pending')
            ->exists();

        if ($already) {
            return response()->json(['message' => 'Vous avez déjà signalé cet élément.'], 422);
        }

        Report::create([
            'reporter_id'     => $this->currentUser()->id,
            'reportable_type' => $request->reportable_type,
            'reportable_id'   => $request->reportable_id,
            'reason'          => $request->reason,
            'details'         => $request->details,
            'status'          => 'pending',
        ]);

        return response()->json(['message' => 'Signalement envoyé. Merci de votre vigilance.'], 201);
    }
}

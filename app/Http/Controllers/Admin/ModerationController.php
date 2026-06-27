<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Avis;
use App\Models\Demande;
use App\Models\Offre;
use Illuminate\Http\Request;

class ModerationController extends Controller
{
    public function demandes(Request $request)
    {
        $q = Demande::with(['client:id,name', 'category:id,nom'])->withCount('offres')->latest();
        if ($request->filled('statut')) $q->where('statut', $request->statut);
        if ($request->filled('search')) $q->where('title', 'like', "%{$request->search}%");
        return response()->json($q->paginate(20));
    }

    public function setDemandeStatut(Request $request, int $id)
    {
        $request->validate(['statut' => 'required|in:ouverte,en_cours,terminee,annulee']);
        $d = Demande::findOrFail($id);
        $d->update(['statut' => $request->statut]);
        AuditLog::record('demande_statut', 'demande', $id, $request->statut);
        return response()->json($d);
    }

    public function destroyDemande(int $id)
    {
        Demande::findOrFail($id)->delete();
        AuditLog::record('demande_delete', 'demande', $id);
        return response()->json(['message' => 'Demande supprimée']);
    }

    public function offres(Request $request)
    {
        $q = Offre::with(['prestataire:id,name', 'demande:id,title'])->latest();
        if ($request->filled('statut')) $q->where('statut', $request->statut);
        return response()->json($q->paginate(20));
    }

    public function destroyOffre(int $id)
    {
        Offre::findOrFail($id)->delete();
        AuditLog::record('offre_delete', 'offre', $id);
        return response()->json(['message' => 'Offre supprimée']);
    }

    public function avis(Request $request)
    {
        $q = Avis::with(['client:id,name', 'prestataire:id,name'])->latest();
        if ($request->filled('search')) $q->where('commentaire', 'like', "%{$request->search}%");
        return response()->json($q->paginate(20));
    }

    public function destroyAvis(int $id)
    {
        Avis::findOrFail($id)->delete();
        AuditLog::record('avis_delete', 'avis', $id);
        return response()->json(['message' => 'Avis supprimé']);
    }
}

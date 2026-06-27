<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Avis;
use App\Models\Demande;
use App\Models\Offre;
use App\Models\PrestataireProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserAdminController extends Controller
{
    public function index(Request $request)
    {
        $q = User::query()
            ->withCount(['demandes', 'offres'])
            ->with('prestataireProfile:id,user_id,is_verified,rating_avg,category_id');

        if ($request->filled('role'))   $q->where('role', $request->role);
        if ($request->filled('status')) $q->where('is_active', $request->status === 'active');
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%"));
        }

        return response()->json($q->latest()->paginate(20));
    }

    public function show(int $id)
    {
        $user = User::with('prestataireProfile.category')->findOrFail($id);

        return response()->json([
            'user'     => $user,
            'demandes' => Demande::where('client_id', $id)->with('category:id,nom')->latest()->limit(10)->get(),
            'offres'   => Offre::where('prestataire_id', $id)->with('demande:id,title')->latest()->limit(10)->get(),
            'avis'     => Avis::where('prestataire_id', $id)->orWhere('client_id', $id)->latest()->limit(10)->get(),
            'stats'    => [
                'demandes'   => Demande::where('client_id', $id)->count(),
                'offres'     => Offre::where('prestataire_id', $id)->count(),
                'avis_recus' => Avis::where('prestataire_id', $id)->count(),
            ],
        ]);
    }

    public function update(Request $request, int $id)
    {
        $user = User::findOrFail($id);
        $request->validate([
            'name'  => 'sometimes|string|max:100',
            'email' => "sometimes|email|unique:users,email,$id",
            'phone' => 'sometimes|nullable|string|max:20',
        ]);
        $user->update($request->only(['name', 'email', 'phone']));
        return response()->json($user);
    }

    public function setRole(Request $request, int $id)
    {
        $request->validate(['role' => 'required|in:client,prestataire,admin']);
        $user = User::findOrFail($id);
        $user->update(['role' => $request->role]);
        AuditLog::record('user_role_change', 'user', $user->id, $request->role);
        return response()->json($user);
    }

    public function suspend(Request $request, int $id)
    {
        $request->validate(['reason' => 'nullable|string|max:255']);
        $user = User::findOrFail($id);

        if ($user->id === $this->currentUser()->id) {
            return response()->json(['message' => 'Vous ne pouvez pas suspendre votre propre compte'], 422);
        }

        $user->update(['is_active' => false, 'suspended_reason' => $request->reason]);
        $user->tokens()->delete(); // force logout everywhere

        AuditLog::record('user_suspend', 'user', $user->id, $request->reason);

        return response()->json($user);
    }

    public function reactivate(int $id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => true, 'suspended_reason' => null]);
        AuditLog::record('user_reactivate', 'user', $user->id);
        return response()->json($user);
    }

    public function verifyPrestataire(Request $request, int $id)
    {
        $request->validate(['verified' => 'required|boolean']);
        $profile = PrestataireProfile::where('user_id', $id)->firstOrFail();
        $profile->update(['is_verified' => $request->boolean('verified')]);
        AuditLog::record('prestataire_verify', 'user', $id, $request->boolean('verified') ? 'vérifié' : 'non vérifié');
        return response()->json($profile);
    }

    public function destroy(int $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === $this->currentUser()->id) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer votre propre compte ici'], 422);
        }

        // ordered cleanup mirrors UserController::destroy (messaging FKs cascade)
        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            Avis::where('client_id', $user->id)->orWhere('prestataire_id', $user->id)->delete();
            $demandeIds = Demande::where('client_id', $user->id)->pluck('id');
            Offre::whereIn('demande_id', $demandeIds)->delete();
            Offre::where('prestataire_id', $user->id)->delete();
            Demande::whereIn('id', $demandeIds)->delete();
            PrestataireProfile::where('user_id', $user->id)->delete();
            $user->delete();
        });

        AuditLog::record('user_delete', 'user', $user->id, $user->name);

        return response()->json(['message' => 'Utilisateur supprimé']);
    }
}

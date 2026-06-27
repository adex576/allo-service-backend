<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Avis;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\Demande;
use App\Models\Message;
use App\Models\Offre;
use App\Models\PrestataireProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats()
    {
        $usersByRole      = User::select('role', DB::raw('count(*) as c'))->groupBy('role')->pluck('c', 'role');
        $demandesByStatut = Demande::select('statut', DB::raw('count(*) as c'))->groupBy('statut')->pluck('c', 'statut');
        $offresByStatut   = Offre::select('statut', DB::raw('count(*) as c'))->groupBy('statut')->pluck('c', 'statut');

        $offresTotal     = Offre::count();
        $offresAcceptees = (int) ($offresByStatut['acceptee'] ?? 0);
        $now             = now();

        $months = collect(range(5, 0))->map(fn ($i) => $now->copy()->subMonths($i)->format('Y-m'));

        return response()->json([
            'totals' => [
                'users'                 => (int) User::count(),
                'clients'               => (int) ($usersByRole['client'] ?? 0),
                'prestataires'          => (int) ($usersByRole['prestataire'] ?? 0),
                'admins'                => (int) ($usersByRole['admin'] ?? 0),
                'demandes'              => (int) Demande::count(),
                'offres'                => $offresTotal,
                'avis'                  => (int) Avis::count(),
                'categories'            => (int) Category::count(),
                'conversations'         => (int) Conversation::count(),
                'messages'              => (int) Message::count(),
                'announcements'         => (int) Announcement::count(),
                'suspended'             => (int) User::where('is_active', false)->count(),
                'verified_prestataires' => (int) PrestataireProfile::where('is_verified', true)->count(),
            ],
            'revenue'            => (float) Offre::where('statut', 'acceptee')->sum('devis'),
            'acceptance_rate'    => $offresTotal ? (int) round($offresAcceptees / $offresTotal * 100) : 0,
            'signups_7d'         => (int) User::where('created_at', '>=', $now->copy()->subDays(7))->count(),
            'signups_30d'        => (int) User::where('created_at', '>=', $now->copy()->subDays(30))->count(),
            'demandes_by_statut' => $demandesByStatut,
            'offres_by_statut'   => $offresByStatut,
            'users_monthly'      => $this->monthlySeries(User::query(), $months),
            'demandes_monthly'   => $this->monthlySeries(Demande::query(), $months),
            'top_prestataires'   => PrestataireProfile::with('user:id,name,avatar')
                ->orderByDesc('rating_avg')->limit(5)->get()
                ->map(fn ($p) => [
                    'id'       => $p->user_id,
                    'name'     => $p->user->name ?? '—',
                    'avatar'   => $p->user->avatar,
                    'rating'   => $p->rating_avg,
                    'verified' => $p->is_verified,
                ]),
            'recent_activity'    => $this->recentActivity(),
        ]);
    }

    private function monthlySeries($query, $months)
    {
        $rows = (clone $query)
            ->where('created_at', '>=', now()->subMonths(6)->startOfMonth())
            ->get(['created_at'])
            ->groupBy(fn ($r) => $r->created_at->format('Y-m'))
            ->map->count();

        return $months->map(fn ($m) => ['month' => $m, 'count' => (int) ($rows[$m] ?? 0)]);
    }

    private function recentActivity()
    {
        $users = User::latest()->limit(6)->get(['id', 'name', 'role', 'created_at'])
            ->map(fn ($u) => ['type' => 'user', 'label' => "{$u->name} a rejoint ({$u->role})", 'date' => $u->created_at]);

        $demandes = Demande::with('client:id,name')->latest()->limit(6)->get()
            ->map(fn ($d) => ['type' => 'demande', 'label' => (($d->client->name ?? 'Un client') . " a publié : \"{$d->title}\""), 'date' => $d->created_at]);

        $offres = Offre::with('prestataire:id,name')->latest()->limit(6)->get()
            ->map(fn ($o) => ['type' => 'offre', 'label' => (($o->prestataire->name ?? 'Un prestataire') . ' a soumis une offre'), 'date' => $o->created_at]);

        return $users->concat($demandes)->concat($offres)->sortByDesc('date')->values()->take(12);
    }
}

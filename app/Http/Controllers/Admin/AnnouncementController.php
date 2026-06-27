<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index()
    {
        return response()->json(Announcement::with('admin:id,name')->latest()->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'    => 'required|string|max:150',
            'body'     => 'required|string|max:2000',
            'audience' => 'required|in:all,client,prestataire',
        ]);

        $a = Announcement::create([
            'admin_id' => $this->currentUser()->id,
            'title'    => $request->title,
            'body'     => $request->body,
            'audience' => $request->audience,
        ]);

        // fan out as a notification to the target audience
        $q = User::query();
        $request->audience === 'all'
            ? $q->whereIn('role', ['client', 'prestataire'])
            : $q->where('role', $request->audience);
        foreach ($q->pluck('id') as $uid) {
            UserNotification::pushTo($uid, 'announcement', "📢 {$request->title}", null);
        }

        return response()->json($a->load('admin:id,name'), 201);
    }

    public function destroy(int $id)
    {
        Announcement::findOrFail($id)->delete();
        return response()->json(['message' => 'Annonce supprimée']);
    }
}

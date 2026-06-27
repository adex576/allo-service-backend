<?php
namespace App\Http\Controllers;

use App\Models\UserNotification;

class NotificationController extends Controller
{
    public function index()
    {
        $notifs = UserNotification::where('user_id', $this->currentUser()->id)
            ->latest()
            ->limit(40)
            ->get(['id', 'type', 'message', 'link_id', 'read_at', 'created_at'])
            ->map(fn ($n) => [
                'id'      => $n->id,
                'type'    => $n->type,
                'message' => $n->message,
                'date'    => $n->created_at,
                'link_id' => $n->link_id,
                'read'    => $n->read_at !== null,
            ]);

        return response()->json($notifs);
    }

    public function unreadCount()
    {
        return response()->json([
            'count' => UserNotification::where('user_id', $this->currentUser()->id)
                ->whereNull('read_at')->count(),
        ]);
    }

    public function markRead()
    {
        UserNotification::where('user_id', $this->currentUser()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'ok']);
    }
}

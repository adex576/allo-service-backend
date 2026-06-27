<?php
namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    // my conversations: other participant, last message, unread count — newest first
    public function index()
    {
        $me = $this->currentUser();

        $conversations = $me->conversations()
            ->with(['participants', 'latestMessage.attachments'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('conversations.updated_at')
            ->get()
            ->map(fn ($c) => $this->present($c, $me));

        return response()->json($conversations);
    }

    // start (or reuse) a conversation with any user
    public function store(Request $request)
    {
        $request->validate(['recipient_id' => 'required|exists:users,id']);

        $me = $this->currentUser();

        if ((int) $request->recipient_id === $me->id) {
            return response()->json(['message' => 'Vous ne pouvez pas démarrer une conversation avec vous-même'], 422);
        }

        $conversation = Conversation::between($me->id, (int) $request->recipient_id);
        $conversation->load(['participants', 'latestMessage.attachments']);

        return response()->json($this->present($conversation, $me), 201);
    }

    public function show(int $id)
    {
        $me = $this->currentUser();
        $conversation = $this->authorizeParticipant($id, $me);
        $conversation->load(['participants', 'latestMessage.attachments']);

        return response()->json($this->present($conversation, $me));
    }

    // paginated messages (oldest first) + mark the conversation read for me
    public function messages(int $id)
    {
        $me = $this->currentUser();
        $conversation = $this->authorizeParticipant($id, $me);

        $conversation->participants()->updateExistingPivot($me->id, ['last_read_at' => now()]);

        // latest 200 messages, returned oldest-first for the chat thread
        $messages = $conversation->messages()
            ->with(['attachments', 'sender:id,name,avatar,role'])
            ->latest('id')
            ->limit(200)
            ->get()
            ->reverse()
            ->values();

        return response()->json($messages);
    }

    // total unread messages across all my conversations (for the navbar badge)
    public function unreadCount()
    {
        $me = $this->currentUser();
        $total = 0;

        foreach ($me->conversations()->pluck('conversations.id') as $cid) {
            $total += $this->unreadFor($cid, $me);
        }

        return response()->json(['count' => $total]);
    }

    /* ── helpers ─────────────────────────────────────────────── */

    private function authorizeParticipant(int $id, User $me): Conversation
    {
        $conversation = Conversation::findOrFail($id);

        if (!$conversation->participants()->where('users.id', $me->id)->exists()) {
            abort(403, 'Accès interdit à cette conversation');
        }

        return $conversation;
    }

    private function unreadFor(int $conversationId, User $me): int
    {
        $lastRead = DB::table('conversation_user')
            ->where('conversation_id', $conversationId)
            ->where('user_id', $me->id)
            ->value('last_read_at');

        return Message::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $me->id)
            ->when($lastRead, fn ($q) => $q->where('created_at', '>', $lastRead))
            ->count();
    }

    private function present(Conversation $c, User $me): array
    {
        $other = $c->participants->firstWhere('id', '!=', $me->id);
        $last  = $c->latestMessage;

        return [
            'id'              => $c->id,
            'last_message_at' => $c->last_message_at,
            'other'           => $other ? [
                'id'     => $other->id,
                'name'   => $other->name,
                'avatar' => $other->avatar,
                'role'   => $other->role,
            ] : null,
            'last_message'    => $last ? [
                'body'           => $last->body,
                'sender_id'      => $last->sender_id,
                'created_at'     => $last->created_at,
                'has_attachment' => $last->attachments->isNotEmpty(),
            ] : null,
            'unread'          => $this->unreadFor($c->id, $me),
        ];
    }
}

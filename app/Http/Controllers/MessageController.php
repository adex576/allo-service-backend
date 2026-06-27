<?php
namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\UserNotification;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    // send a message (text and/or attachments) to a conversation
    public function store(Request $request, int $id)
    {
        $me = $this->currentUser();
        $conversation = Conversation::findOrFail($id);

        if (!$conversation->participants()->where('users.id', $me->id)->exists()) {
            return response()->json(['message' => 'Accès interdit à cette conversation'], 403);
        }

        $request->validate([
            'body'          => 'nullable|string|max:5000',
            'attachments'   => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip,txt',
        ]);

        if (!$request->filled('body') && !$request->hasFile('attachments')) {
            return response()->json(['message' => 'Le message est vide'], 422);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $me->id,
            'body'            => $request->input('body'),
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store("messages/{$conversation->id}", 'public');
                MessageAttachment::create([
                    'message_id'    => $message->id,
                    'path'          => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type'     => $file->getClientMimeType(),
                    'size'          => $file->getSize(),
                ]);
            }
        }

        $conversation->update(['last_message_at' => now()]);
        // the sender has implicitly read up to their own message
        $conversation->participants()->updateExistingPivot($me->id, ['last_read_at' => now()]);

        // notify the other participant(s)
        $others = $conversation->participants()->where('users.id', '!=', $me->id)->pluck('users.id');
        foreach ($others as $uid) {
            UserNotification::pushTo($uid, 'new_message', "Nouveau message de {$me->name}", $conversation->id);
        }

        $message->load(['attachments', 'sender:id,name,avatar,role']);

        return response()->json($message, 201);
    }
}

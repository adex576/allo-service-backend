<?php
namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;

class ConversationSeeder extends Seeder
{
    public function run(): void
    {
        $client = User::where('email', 'alice@example.com')->first();
        $presta = User::where('email', 'hassan@example.com')->first();

        if (!$client || !$presta) {
            return;
        }

        $c = Conversation::between($client->id, $presta->id);

        $lines = [
            [$client->id, 'Bonjour, êtes-vous disponible cette semaine ?'],
            [$presta->id, 'Bonjour ! Oui, je peux passer jeudi après-midi.'],
            [$client->id, 'Parfait, jeudi 15h ça me convient.'],
            [$presta->id, 'Très bien, à jeudi alors 👍'],
        ];

        foreach ($lines as [$senderId, $body]) {
            Message::create([
                'conversation_id' => $c->id,
                'sender_id'       => $senderId,
                'body'            => $body,
            ]);
        }

        $c->update(['last_message_at' => now()]);

        // prestataire has read; client keeps an unread badge for the demo
        $c->participants()->updateExistingPivot($presta->id, ['last_read_at' => now()]);
    }
}

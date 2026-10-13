<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// The user <-> admin messaging feature. Its own in-memory SQLite tables, like
// userAccountTest, so it does not depend on the migration chain. Nothing here
// touches the real database.
class MessagingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::connection('sqlite')->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('google_id')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone')->nullable()->unique();
            $table->text('address')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('sqlite')->create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('reference')->unique();
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->integer('unread_count')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id')->index();
            $table->string('sender_type')->default('client');
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->text('body')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->unsignedBigInteger('reply_to_id')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    // ------------------------------------------------------------- helpers

    private function makeUser(array $overrides = []): User
    {
        static $count = 0;
        $count++;
        $user = User::create(array_merge([
            'first_name' => 'Test',
            'last_name' => 'Client',
            'email' => "client{$count}@example.com",
            'phone' => '0917'.str_pad((string) $count, 7, '0', STR_PAD_LEFT),
            'password' => Hash::make('Password1!'),
        ], $overrides));
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }

    private function makeConversation(User $user, array $messages = []): Conversation
    {
        $conversation = Conversation::create([
            'user_id' => $user->id,
            'last_message_at' => now(),
        ]);

        foreach ($messages as $message) {
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_type' => $message['from'],
                'sender_id' => $message['from'] === 'client' ? $user->id : null,
                'body' => $message['body'],
                'read_at' => $message['read_at'] ?? null,
            ]);
        }

        return $conversation->fresh();
    }

    // --------------------------------------------------------------- user

    public function test_the_client_thread_shows_the_whole_history(): void
    {
        $this->withoutVite();
        $user = $this->makeUser();
        $conversation = $this->makeConversation($user, [
            ['from' => 'client', 'body' => 'First question'],
            ['from' => 'admin', 'body' => 'First answer'],
            ['from' => 'client', 'body' => 'Follow up question'],
            ['from' => 'admin', 'body' => 'Second answer'],
            ['from' => 'client', 'body' => 'Latest question'],
        ]);

        $this->actingAs($user)->get(route('user.account.messages'))
            ->assertOk()
            ->assertSee('data-account-chat', false)
            ->assertSee(route('user.messages.send'), false)
            ->assertSee('First question')
            ->assertSee('First answer')
            ->assertSee('Second answer')
            ->assertSee('Latest question');

        // Opening the page reads only the admin replies.
        $this->assertSame(0, $conversation->messages()->where('sender_type', 'admin')->whereNull('read_at')->count());
        $this->assertSame(3, $conversation->messages()->where('sender_type', 'client')->whereNull('read_at')->count());
    }

    public function test_a_client_message_creates_and_reuses_a_single_conversation(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->postJson(route('user.messages.send'), ['body' => 'Hello admin'])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->actingAs($user)->postJson(route('user.messages.send'), ['body' => 'Second message'])
            ->assertOk();

        $this->assertSame(1, Conversation::where('user_id', $user->id)->count());

        $conversation = Conversation::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(2, $conversation->messages()->count());
        $this->assertSame(2, $conversation->unread_count);

        // Both client messages are unread for the admin.
        $this->assertSame(2, $conversation->unreadCountFor('admin'));
        $this->assertSame(0, $conversation->unreadCountFor('client'));
    }

    public function test_updates_returns_only_admin_messages_after_the_given_id(): void
    {
        $user = $this->makeUser();
        $conversation = $this->makeConversation($user, [
            ['from' => 'admin', 'body' => 'Old reply'],
            ['from' => 'client', 'body' => 'Client note'],
        ]);

        $oldAdminId = $conversation->messages()->where('sender_type', 'admin')->value('id');
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'admin',
            'body' => 'Brand new reply',
        ]);

        $this->actingAs($user)->getJson(route('user.messages.updates', ['after' => $oldAdminId]))
            ->assertOk()
            ->assertJsonPath('unread', 2)
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.body', 'Brand new reply')
            ->assertJsonPath('messages.0.from', 'admin');
    }

    public function test_marking_read_clears_the_client_badge_but_not_the_admin_one(): void
    {
        $user = $this->makeUser();
        $conversation = $this->makeConversation($user, [
            ['from' => 'admin', 'body' => 'Reply to read'],
            ['from' => 'client', 'body' => 'Still unread for admin'],
        ]);

        $this->actingAs($user)->postJson(route('user.messages.read'))
            ->assertOk()
            ->assertJsonPath('unread', 0);

        $this->assertSame(0, $conversation->unreadCountFor('client'));
        $this->assertSame(1, $conversation->unreadCountFor('admin'));
    }

    public function test_guests_are_rejected_with_401_from_the_client_endpoints(): void
    {
        $this->postJson(route('user.messages.send'), ['body' => 'Hello'])->assertUnauthorized();
        $this->postJson(route('user.messages.read'))->assertUnauthorized();
        $this->getJson(route('user.messages.updates'))->assertUnauthorized();
    }

    // -------------------------------------------------------------- admin

    public function test_the_admin_page_shows_every_message_not_just_the_last(): void
    {
        $this->withoutVite();
        $user = $this->makeUser(['first_name' => 'Maria', 'last_name' => 'Santos']);
        $this->makeConversation($user, [
            ['from' => 'client', 'body' => 'Earliest message'],
            ['from' => 'admin', 'body' => 'Middle reply'],
            ['from' => 'client', 'body' => 'Newest message'],
        ]);

        $this->get(route('admin.messages'))
            ->assertOk()
            ->assertSee('Earliest message')
            ->assertSee('Middle reply')
            ->assertSee('Newest message');
    }

    public function test_opening_a_chat_on_the_admin_side_reads_only_the_client_messages(): void
    {
        $user = $this->makeUser();
        $conversation = $this->makeConversation($user, [
            ['from' => 'client', 'body' => 'Question one'],
            ['from' => 'admin', 'body' => 'Answer one'],
            ['from' => 'client', 'body' => 'Question two'],
        ]);

        $this->getJson(route('admin.messages.show', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertJsonPath('conversation.id', $conversation->id);

        // Client messages are now read; the admin's own reply is untouched, so the
        // client still sees it as unread until they open the chat.
        $this->assertSame(0, $conversation->unreadCountFor('admin'));
        $this->assertSame(1, $conversation->unreadCountFor('client'));
        $this->assertSame(0, $conversation->fresh()->unread_count);
    }

    public function test_admin_page_renders_after_the_client_account_is_soft_deleted(): void
    {
        $this->withoutVite();
        $user = $this->makeUser(['first_name' => 'Gone', 'last_name' => 'Client']);
        $conversation = $this->makeConversation($user, [
            ['from' => 'client', 'body' => 'Message from a deleted account'],
        ]);

        $user->delete(); // soft delete: the row stays, only deleted_at is set

        $this->assertSoftDeleted('users', ['id' => $user->id]);

        $this->get(route('admin.messages'))
            ->assertOk()
            ->assertSee('Message from a deleted account')
            ->assertSee('Gone Client');

        $this->getJson(route('admin.messages.show', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertJsonPath('conversation.client.name', 'Gone Client');
    }

    public function test_admin_page_renders_when_the_client_row_is_missing(): void
    {
        $this->withoutVite();
        $conversation = Conversation::create([
            'user_id' => 999999,
            'last_message_at' => now(),
        ]);
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'client',
            'body' => 'Orphaned message',
        ]);

        $this->get(route('admin.messages'))
            ->assertOk()
            ->assertSee('Orphaned message')
            ->assertSee('Deleted client');

        $this->getJson(route('admin.messages.show', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertJsonPath('conversation.client.name', 'Deleted client');
    }

    // --------------------------------------------------------- attachments

    public function test_a_user_image_upload_is_stored_as_an_image_and_previewed_inline(): void
    {
        $this->withoutVite();
        Storage::fake('public');
        Config::set('filesystems.default', 'public');

        $user = $this->makeUser();

        $this->actingAs($user)->post(route('user.messages.send'), [
            'body' => 'Here is the venue',
            'attachments' => [UploadedFile::fake()->create('venue.png', 20, 'image/png')],
        ])->assertOk()->assertJsonPath('ok', true);

        $message = Message::latest('id')->firstOrFail();
        $this->assertSame('image', $message->attachments[0]['kind']);
        $this->assertSame('venue.png', $message->attachments[0]['name']);

        // After a reload the thread shows a real <img>, not a file link.
        $this->get(route('user.account.messages'))
            ->assertOk()
            ->assertSee('chat-attach--image', false)
            ->assertSee('<img src=', false)
            ->assertSee('alt="venue.png"', false)
            ->assertDontSee('chat-attach__name', false);
    }

    public function test_a_user_pdf_upload_stays_a_file_link(): void
    {
        $this->withoutVite();
        Storage::fake('public');
        Config::set('filesystems.default', 'public');

        $user = $this->makeUser();

        $this->actingAs($user)->post(route('user.messages.send'), [
            'body' => 'Contract attached',
            'attachments' => [UploadedFile::fake()->create('contract.pdf', 20, 'application/pdf')],
        ])->assertOk();

        $message = Message::latest('id')->firstOrFail();
        $this->assertSame('file', $message->attachments[0]['kind']);

        $this->get(route('user.account.messages'))
            ->assertOk()
            ->assertSee('chat-attach__name', false)
            ->assertSee('contract.pdf')
            ->assertDontSee('chat-attach--image', false);
    }

    public function test_an_admin_image_upload_is_stored_as_an_image_and_previewed_inline(): void
    {
        $this->withoutVite();
        Storage::fake('public');
        Config::set('filesystems.default', 'public');

        $user = $this->makeUser();
        $conversation = $this->makeConversation($user, [
            ['from' => 'client', 'body' => 'Can I see a sample?'],
        ]);

        $this->post(route('admin.messages.send', ['conversation' => $conversation->id]), [
            'body' => 'Sample set',
            'attachments' => [UploadedFile::fake()->create('sample.jpg', 20, 'image/jpeg')],
        ])->assertOk();

        $message = Message::where('sender_type', 'admin')->latest('id')->firstOrFail();
        $this->assertSame('image', $message->attachments[0]['kind']);

        $this->get(route('admin.messages'))
            ->assertOk()
            ->assertSee('chat-attach--image', false)
            ->assertSee('alt="sample.jpg"', false);
    }

    public function test_a_legacy_image_attachment_without_the_image_kind_still_previews(): void
    {
        $this->withoutVite();
        $user = $this->makeUser();
        $conversation = $this->makeConversation($user, [
            ['from' => 'client', 'body' => 'Old upload'],
        ]);

        // Rows written before the `image` kind existed have kind = "file" but a .png name.
        $conversation->messages()->latest('id')->firstOrFail()->update([
            'attachments' => [[
                'name' => 'legacy-photo.png',
                'url' => '/storage/messages/legacy-photo.png',
                'kind' => 'file',
                'size' => '10 KB',
            ]],
        ]);

        $this->actingAs($user)->get(route('user.account.messages'))
            ->assertOk()
            ->assertSee('chat-attach--image', false)
            ->assertSee('/storage/messages/legacy-photo.png', false)
            ->assertSee('alt="legacy-photo.png"', false);
    }

    public function test_attachment_urls_are_rebuilt_against_the_app_base_path(): void
    {
        $user = $this->makeUser();
        $conversation = $this->makeConversation($user, [
            ['from' => 'client', 'body' => 'Pic'],
        ]);
        $message = $conversation->messages()->firstOrFail();
        $message->update([
            'attachments' => [[
                'name' => 'a.png',
                'url' => '/storage/messages/a.png',
                'kind' => 'image',
                'size' => '1 KB',
            ]],
        ]);

        // The stored root-relative path is resolved to an absolute URL so it no longer
        // 404s when the app is served from a sub-directory.
        $formatted = app(MessageService::class)->formatMessage($message->fresh());
        $this->assertStringStartsWith('http', $formatted['attachments'][0]['url']);
        $this->assertStringContainsString('/storage/messages/a.png', $formatted['attachments'][0]['url']);
    }

    // -------------------------------------------------------- reply / edit / unsend

    public function test_a_client_can_reply_to_a_message_and_the_quote_is_shown(): void
    {
        $this->withoutVite();
        $user = $this->makeUser();
        $conversation = $this->makeConversation($user, [
            ['from' => 'admin', 'body' => 'Original question'],
        ]);
        $parent = $conversation->messages()->firstOrFail();

        $this->actingAs($user)->post(route('user.messages.send'), [
            'body' => 'My answer',
            'reply_to_id' => $parent->id,
        ])->assertOk()->assertJsonPath('message.reply_to.body', 'Original question');

        $reply = Message::where('sender_type', 'client')->latest('id')->firstOrFail();
        $this->assertSame($parent->id, $reply->reply_to_id);

        $this->actingAs($user)->get(route('user.account.messages'))
            ->assertOk()
            ->assertSee('chat-msg__quote', false)
            ->assertSee('Original question');
    }

    public function test_a_client_can_edit_their_own_message(): void
    {
        $user = $this->makeUser();
        $conversation = $this->makeConversation($user, [
            ['from' => 'client', 'body' => 'Typo here'],
        ]);
        $message = $conversation->messages()->firstOrFail();

        $this->actingAs($user)->patchJson(route('user.messages.update', ['message' => $message->id]), [
            'body' => 'Fixed text',
        ])->assertOk()
            ->assertJsonPath('message.body', 'Fixed text')
            ->assertJsonPath('message.edited', true);

        $message->refresh();
        $this->assertSame('Fixed text', $message->body);
        $this->assertNotNull($message->edited_at);
    }

    public function test_a_client_can_unsend_their_own_message(): void
    {
        $user = $this->makeUser();
        $conversation = $this->makeConversation($user, [
            ['from' => 'client', 'body' => 'Secret message'],
            ['from' => 'client', 'body' => 'Keep me'],
        ]);
        $target = $conversation->messages()->where('body', 'Secret message')->firstOrFail();

        $this->actingAs($user)->deleteJson(route('user.messages.destroy', ['message' => $target->id]))
            ->assertOk()
            ->assertJsonPath('ok', true);

        // Soft delete: the row is kept, it just leaves the thread.
        $this->assertSoftDeleted('messages', ['id' => $target->id]);
        $this->assertSame(1, $conversation->messages()->count());
        $this->assertSame(1, $conversation->unreadCountFor('admin'));
    }

    public function test_a_client_cannot_edit_or_unsend_another_clients_message(): void
    {
        $owner = $this->makeUser();
        $intruder = $this->makeUser();
        $conversation = $this->makeConversation($owner, [
            ['from' => 'client', 'body' => 'Owner only'],
        ]);
        $message = $conversation->messages()->firstOrFail();

        $this->actingAs($intruder)
            ->patchJson(route('user.messages.update', ['message' => $message->id]), ['body' => 'Hacked'])
            ->assertForbidden();
        $this->actingAs($intruder)
            ->deleteJson(route('user.messages.destroy', ['message' => $message->id]))
            ->assertForbidden();

        $this->assertSame('Owner only', $message->fresh()->body);
        $this->assertNull($message->fresh()->deleted_at);
    }

    public function test_a_client_cannot_edit_or_unsend_an_admin_message(): void
    {
        $user = $this->makeUser();
        $conversation = $this->makeConversation($user, [
            ['from' => 'admin', 'body' => 'Admin words'],
        ]);
        $message = $conversation->messages()->firstOrFail();

        $this->actingAs($user)
            ->patchJson(route('user.messages.update', ['message' => $message->id]), ['body' => 'nope'])
            ->assertForbidden();
        $this->actingAs($user)
            ->deleteJson(route('user.messages.destroy', ['message' => $message->id]))
            ->assertForbidden();
    }

    public function test_guests_are_rejected_from_edit_and_unsend(): void
    {
        $user = $this->makeUser();
        $conversation = $this->makeConversation($user, [
            ['from' => 'client', 'body' => 'Hello'],
        ]);
        $message = $conversation->messages()->firstOrFail();

        $this->patchJson(route('user.messages.update', ['message' => $message->id]), ['body' => 'x'])->assertUnauthorized();
        $this->deleteJson(route('user.messages.destroy', ['message' => $message->id]))->assertUnauthorized();
    }

    public function test_an_admin_can_edit_and_unsend_their_own_reply(): void
    {
        $user = $this->makeUser();
        $conversation = $this->makeConversation($user, [
            ['from' => 'client', 'body' => 'Question'],
            ['from' => 'admin', 'body' => 'Old reply'],
        ]);
        $reply = $conversation->messages()->where('sender_type', 'admin')->firstOrFail();

        $this->patchJson(route('admin.messages.update', ['message' => $reply->id]), ['body' => 'New reply'])
            ->assertOk()
            ->assertJsonPath('message.body', 'New reply');
        $this->assertNotNull($reply->fresh()->edited_at);

        $this->deleteJson(route('admin.messages.destroy', ['message' => $reply->id]))->assertOk();
        $this->assertSoftDeleted('messages', ['id' => $reply->id]);
    }

    public function test_an_admin_cannot_edit_a_client_message(): void
    {
        $user = $this->makeUser();
        $conversation = $this->makeConversation($user, [
            ['from' => 'client', 'body' => 'Client words'],
        ]);
        $message = $conversation->messages()->firstOrFail();

        $this->patchJson(route('admin.messages.update', ['message' => $message->id]), ['body' => 'nope'])
            ->assertForbidden();
    }

    // ------------------------------------------------------- admin live feed

    public function test_admin_updates_returns_new_client_messages_after_an_id(): void
    {
        $user = $this->makeUser();
        $conversation = $this->makeConversation($user, [
            ['from' => 'client', 'body' => 'First note', 'read_at' => now()],
        ]);
        $first = $conversation->messages()->firstOrFail();

        $this->getJson(route('admin.messages.updates', ['after' => $first->id]))
            ->assertOk()
            ->assertJsonCount(0, 'messages')
            ->assertJsonPath('unread', 0);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'client',
            'sender_id' => $user->id,
            'body' => 'Brand new note',
        ]);

        $this->getJson(route('admin.messages.updates', ['after' => $first->id]))
            ->assertOk()
            ->assertJsonPath('unread', 1)
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.body', 'Brand new note')
            ->assertJsonPath('messages.0.from', 'client')
            ->assertJsonPath('messages.0.conversation_id', $conversation->id);
    }

    public function test_admin_updates_ignores_admin_messages(): void
    {
        $user = $this->makeUser();
        $conversation = $this->makeConversation($user, [
            ['from' => 'admin', 'body' => 'Admin only'],
        ]);
        $message = $conversation->messages()->firstOrFail();

        $this->getJson(route('admin.messages.updates', ['after' => $message->id - 1]))
            ->assertOk()
            ->assertJsonCount(0, 'messages');
    }

    public function test_the_admin_page_exposes_the_live_updates_feed(): void
    {
        $this->withoutVite();
        $user = $this->makeUser();
        $this->makeConversation($user, [['from' => 'client', 'body' => 'Hi']]);

        $this->get(route('admin.messages'))
            ->assertOk()
            ->assertSee('data-chat-updates-url', false)
            ->assertSee(route('admin.messages.updates'), false);
    }
}
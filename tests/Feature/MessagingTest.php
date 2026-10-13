<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
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
}
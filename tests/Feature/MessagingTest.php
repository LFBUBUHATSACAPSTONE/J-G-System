<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingEvent;
use App\Models\BookingPayment;
use App\Models\BookingSchedule;
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
            $table->string('status')->default('pending');
            $table->string('client_name')->nullable();
            $table->string('client_email')->nullable();
            $table->string('client_phone')->nullable();
            $table->text('client_address')->nullable();
            $table->unsignedBigInteger('package_id')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        // Booking detail tables: the chat thread renders the client's reflected bookings,
        // so the minimum columns Booking::toAdminArray() reads must exist.
        Schema::connection('sqlite')->create('packages', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->decimal('price', 10, 2)->nullable();
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('booking_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id')->index();
            $table->string('event_name')->nullable();
            $table->string('event_type')->nullable();
            $table->string('event_type_other')->nullable();
            $table->string('event_location')->nullable();
            $table->string('event_contact_person')->nullable();
            $table->unsignedInteger('guest_count')->nullable();
            $table->string('venue_type')->nullable();
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('booking_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id')->index();
            $table->date('event_start_date')->nullable();
            $table->date('event_end_date')->nullable();
            $table->string('event_start_time')->nullable();
            $table->string('event_end_time')->nullable();
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('booking_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id')->index();
            $table->string('payment_method')->nullable();
            $table->string('payment_state')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('payment_receipt_path')->nullable();
            $table->decimal('payment_amount', 10, 2)->nullable();
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

    // An approved/pending booking with the detail rows the chat card reads, so the
    // "reflected bookings" behaviour can be exercised end to end.
    private function makeBooking(User $user, string $status, string $reference): Booking
    {
        $booking = Booking::create([
            'user_id' => $user->id,
            'reference' => $reference,
            'status' => $status,
            'client_name' => $user->first_name.' '.$user->last_name,
            'client_email' => $user->email,
            'client_phone' => $user->phone,
            'submitted_at' => now(),
        ]);

        $booking->eventDetails()->create([
            'event_name' => 'Birthday',
            'event_type' => 'Birthday Party',
            'event_location' => 'Quezon City',
        ]);

        $booking->schedule()->create([
            'event_start_date' => now()->addMonth()->toDateString(),
            'event_end_date' => now()->addMonth()->toDateString(),
        ]);

        $booking->payment()->create([
            'payment_method' => 'full',
            'payment_state' => 'paid',
            'payment_amount' => 3000,
        ]);

        return $booking;
    }

    // A message created outside the edit window by backdating its created_at.
    private function ageMessage(Message $message, int $minutes): Message
    {
        $when = now()->subMinutes($minutes);
        DB::table('messages')->where('id', $message->id)->update([
            'created_at' => $when,
            'updated_at' => $when,
        ]);

        return $message->refresh();
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

    // ------------------------------------------------- reflected bookings

    public function test_the_thread_reflects_only_approved_bookings(): void
    {
        $this->withoutVite();
        $user = $this->makeUser();
        $this->makeBooking($user, 'approved', 'APR-100');
        $pending = $this->makeBooking($user, 'pending', 'PEN-200');
        $this->makeBooking($user, 'declined', 'DEC-201');

        $conversation = $this->makeConversation($user, [
            ['from' => 'client', 'body' => 'Hello'],
        ]);
        // Linked to the still-pending request: it must NOT be reflected, only approved.
        $conversation->update(['booking_id' => $pending->id]);

        $this->actingAs($user)->get(route('user.account.messages'))
            ->assertOk()
            ->assertSee('APR-100')
            ->assertDontSee('PEN-200')
            ->assertDontSee('DEC-201');

        $this->get(route('admin.messages'))
            ->assertOk()
            ->assertSee('APR-100')
            ->assertDontSee('PEN-200')
            ->assertDontSee('DEC-201');
    }

    public function test_the_reflected_statuses_are_configurable(): void
    {
        $this->withoutVite();
        Config::set('admin.messages.reflected_booking_statuses', ['approved', 'pending']);

        $user = $this->makeUser();
        $this->makeBooking($user, 'approved', 'CFG-410');
        $this->makeBooking($user, 'pending', 'CFG-411');
        $this->makeBooking($user, 'declined', 'CFG-412');

        $this->makeConversation($user, [['from' => 'client', 'body' => 'Hello']]);

        $this->actingAs($user)->get(route('user.account.messages'))
            ->assertOk()
            ->assertSee('CFG-410')
            ->assertSee('CFG-411')
            ->assertDontSee('CFG-412');
    }

    public function test_the_landing_chat_widget_reflects_only_approved_bookings(): void
    {
        $this->withoutVite();
        $user = $this->makeUser();
        $this->makeBooking($user, 'approved', 'WIDG-600');
        $this->makeBooking($user, 'pending', 'WIDG-601');

        $this->makeConversation($user, [['from' => 'client', 'body' => 'Hi']]);

        $this->actingAs($user)->get(route('user.landing'))
            ->assertOk()
            ->assertSee('WIDG-600')
            ->assertDontSee('WIDG-601')
            ->assertSee('data-booking-id', false);
    }

    public function test_the_customer_chat_reflects_bookings_before_any_message(): void
    {
        $this->withoutVite();
        $user = $this->makeUser();
        $this->makeBooking($user, 'approved', 'NOMSG-700');

        // No conversation exists at all: the client only has a booking so far.
        $this->assertDatabaseCount('conversations', 0);

        $this->actingAs($user)->get(route('user.account.messages'))
            ->assertOk()
            ->assertSee('NOMSG-700')
            ->assertSee('data-booking-id', false);

        $this->actingAs($user)->get(route('user.landing'))
            ->assertOk()
            ->assertSee('NOMSG-700')
            ->assertSee('data-booking-id', false);
    }

    public function test_an_approved_booking_card_is_pinned_between_the_messages_around_it(): void
    {
        $user = $this->makeUser();
        $conversation = $this->makeConversation($user, [['from' => 'client', 'body' => 'Before']]);

        // The first message happened ten minutes ago...
        DB::table('messages')->where('conversation_id', $conversation->id)
            ->update(['created_at' => now()->subMinutes(10)]);

        // ...the booking was approved five minutes ago...
        $booking = $this->makeBooking($user, 'approved', 'PIN-900');
        DB::table('bookings')->where('id', $booking->id)
            ->update(['approved_at' => now()->subMinutes(5)]);

        // ...and a fresh message arrived after it.
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'admin',
            'body' => 'After',
        ]);

        $thread = Conversation::with([
            'booking.eventDetails', 'booking.schedule', 'booking.payment', 'booking.package',
            'messages.replyTo',
        ])->findOrFail($conversation->id);

        $types = array_map(
            fn ($item) => $item['type'],
            app(MessageService::class)->formatConversationForUser($thread)['items'],
        );

        // The card sits where it was approved and does not drift to the bottom.
        $this->assertSame(['message', 'booking', 'message'], $types);
    }

    public function test_a_new_approved_booking_adds_a_card_without_removing_the_old_one(): void
    {
        $user = $this->makeUser();
        $first = $this->makeBooking($user, 'approved', 'KEEP-500');
        $conversation = $this->makeConversation($user, [['from' => 'client', 'body' => 'Hi']]);

        // The first booking was approved a day ago; the second is approved just now, so
        // the newest card is unambiguously last.
        DB::table('bookings')->where('id', $first->id)->update(['approved_at' => now()->subDay()]);
        $second = $this->makeBooking($user, 'approved', 'KEEP-501');
        DB::table('bookings')->where('id', $second->id)->update(['approved_at' => now()]);

        $thread = Conversation::with([
            'booking.eventDetails', 'booking.schedule', 'booking.payment', 'booking.package',
            'messages.replyTo',
        ])->findOrFail($conversation->id);

        $items = app(MessageService::class)->formatConversationForUser($thread)['items'];
        $cards = array_values(array_filter($items, fn ($item) => $item['type'] === 'booking'));

        // Both cards are present, oldest approval first: the new one never drops the old.
        $this->assertSame(
            [$first->id, $second->id],
            array_map(fn ($item) => $item['booking']['id'], $cards),
        );
    }

    // ----------------------------------------------------- edit window

    public function test_format_message_reports_the_edit_window(): void
    {
        Config::set('admin.messages.edit_window_minutes', 15);

        $user = $this->makeUser();
        $conversation = $this->makeConversation($user, [
            ['from' => 'client', 'body' => 'Fresh'],
        ]);
        $message = $conversation->messages()->firstOrFail();

        $fresh = app(MessageService::class)->formatMessage($message);
        $this->assertTrue($fresh['editable']);
        $this->assertNotNull($fresh['edit_expires_at']);

        $old = app(MessageService::class)->formatMessage($this->ageMessage($message, 60));
        $this->assertFalse($old['editable']);
        $this->assertNotNull($old['edit_expires_at']);
    }

    public function test_a_zero_edit_window_leaves_messages_editable(): void
    {
        Config::set('admin.messages.edit_window_minutes', 0);

        $user = $this->makeUser();
        $conversation = $this->makeConversation($user, [
            ['from' => 'client', 'body' => 'Aged'],
        ]);
        $old = app(MessageService::class)->formatMessage(
            $this->ageMessage($conversation->messages()->firstOrFail(), 999),
        );

        $this->assertTrue($old['editable']);
        $this->assertNull($old['edit_expires_at']);
    }

    public function test_a_client_cannot_edit_or_unsend_outside_the_window(): void
    {
        Config::set('admin.messages.edit_window_minutes', 15);

        $user = $this->makeUser();
        $conversation = $this->makeConversation($user, [
            ['from' => 'client', 'body' => 'Old note'],
        ]);
        $message = $this->ageMessage($conversation->messages()->firstOrFail(), 30);

        $this->actingAs($user)
            ->patchJson(route('user.messages.update', ['message' => $message->id]), ['body' => 'Too late'])
            ->assertForbidden();

        $this->actingAs($user)
            ->deleteJson(route('user.messages.destroy', ['message' => $message->id]))
            ->assertForbidden();

        $this->assertSame('Old note', $message->fresh()->body);
        $this->assertNull($message->fresh()->deleted_at);
    }

    public function test_an_admin_cannot_edit_a_reply_outside_the_window(): void
    {
        Config::set('admin.messages.edit_window_minutes', 15);

        $user = $this->makeUser();
        $conversation = $this->makeConversation($user, [
            ['from' => 'client', 'body' => 'Question'],
            ['from' => 'admin', 'body' => 'Old answer'],
        ]);
        $reply = $this->ageMessage(
            $conversation->messages()->where('sender_type', 'admin')->firstOrFail(),
            30,
        );

        $this->patchJson(route('admin.messages.update', ['message' => $reply->id]), ['body' => 'Late'])
            ->assertForbidden();
    }

    public function test_an_old_message_hides_the_edit_and_unsend_buttons(): void
    {
        $this->withoutVite();
        Config::set('admin.messages.edit_window_minutes', 15);

        $user = $this->makeUser();
        $conversation = $this->makeConversation($user, [
            ['from' => 'client', 'body' => 'Old note'],
        ]);
        $this->ageMessage($conversation->messages()->firstOrFail(), 30);

        $this->actingAs($user)->get(route('user.account.messages'))
            ->assertOk()
            ->assertSee('data-chat-action="reply"', false)
            ->assertDontSee('data-chat-action="edit"', false)
            ->assertDontSee('data-chat-action="unsend"', false);
    }

    // ------------------------------------------------- live booking cards

    public function test_the_user_feed_pops_a_newly_approved_booking_into_the_thread(): void
    {
        $user = $this->makeUser();
        $this->makeConversation($user, [['from' => 'client', 'body' => 'Hi']]);
        $booking = $this->makeBooking($user, 'approved', 'NEW-700');

        $response = $this->actingAs($user)
            ->getJson(route('user.messages.updates', [
                'since' => now()->subMinutes(5)->toIso8601String(),
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'bookings')
            ->assertJsonPath('bookings.0.id', $booking->id)
            ->assertJsonPath('bookings.0.reference', 'NEW-700')
            ->assertJsonPath('bookings.0.type', 'booking');

        $this->assertStringContainsString('chat-booking', $response->json('bookings.0.html'));
        $this->assertNotNull($response->json('bookings_synced_at'));

        // Re-polling from the cursor returns nothing, so the card is never doubled.
        $this->actingAs($user)
            ->getJson(route('user.messages.updates', [
                'since' => $response->json('bookings_synced_at'),
            ]))
            ->assertOk()
            ->assertJsonCount(0, 'bookings');
    }

    public function test_the_user_feed_reports_a_status_change_not_only_new_bookings(): void
    {
        $user = $this->makeUser();
        $this->makeConversation($user, [['from' => 'client', 'body' => 'Hi']]);
        $booking = $this->makeBooking($user, 'pending_payment', 'UPD-701');

        // The admin approves it: only the status changes, no new booking row.
        $booking->update(['status' => 'approved']);

        $this->actingAs($user)
            ->getJson(route('user.messages.updates', [
                'since' => now()->subMinutes(5)->toIso8601String(),
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'bookings')
            ->assertJsonPath('bookings.0.status', 'approved');
    }

    public function test_the_user_feed_needs_a_cursor_to_avoid_replaying_everything(): void
    {
        $user = $this->makeUser();
        $this->makeConversation($user, [['from' => 'client', 'body' => 'Hi']]);
        $this->makeBooking($user, 'approved', 'OLD-702');

        $this->actingAs($user)
            ->getJson(route('user.messages.updates'))
            ->assertOk()
            ->assertJsonCount(0, 'bookings');
    }

    public function test_the_admin_feed_pops_a_clients_new_booking_into_their_panel(): void
    {
        $user = $this->makeUser();
        $conversation = $this->makeConversation($user, [['from' => 'client', 'body' => 'Hi']]);
        $booking = $this->makeBooking($user, 'approved', 'ADM-703');

        $response = $this->getJson(route('admin.messages.updates', [
            'since' => now()->subMinutes(5)->toIso8601String(),
        ]))
            ->assertOk()
            ->assertJsonCount(1, 'bookings')
            ->assertJsonPath('bookings.0.id', $booking->id)
            ->assertJsonPath('bookings.0.conversation_id', $conversation->id);

        $this->assertStringContainsString('chat-booking', $response->json('bookings.0.html'));
        $this->assertNotNull($response->json('bookings_synced_at'));
    }

    public function test_the_admin_feed_skips_bookings_with_no_conversation_panel(): void
    {
        $user = $this->makeUser();
        $this->makeBooking($user, 'approved', 'ADM-704');

        $this->getJson(route('admin.messages.updates', [
            'since' => now()->subMinutes(5)->toIso8601String(),
        ]))
            ->assertOk()
            ->assertJsonCount(0, 'bookings');
    }

    public function test_the_pages_expose_the_bookings_since_cursor(): void
    {
        $this->withoutVite();
        $user = $this->makeUser();
        $this->makeConversation($user, [['from' => 'client', 'body' => 'Hi']]);

        $this->actingAs($user)->get(route('user.account.messages'))
            ->assertOk()
            ->assertSee('data-chat-bookings-since', false);

        $this->get(route('admin.messages'))
            ->assertOk()
            ->assertSee('data-chat-bookings-since', false);
    }

    // ----------------------------------------- booking cards trail the chat

    public function test_a_booking_approved_after_the_messages_trails_them(): void
    {
        $user = $this->makeUser();
        $conversation = $this->makeConversation($user, [
            ['from' => 'client', 'body' => 'First'],
            ['from' => 'admin', 'body' => 'Second'],
        ]);

        // Approved after the two messages, so its card lands below them.
        $approved = $this->makeBooking($user, 'approved', 'POS-800');
        DB::table('bookings')->where('id', $approved->id)
            ->update(['approved_at' => now()->addMinute()]);

        $thread = Conversation::with([
            'booking.eventDetails', 'booking.schedule', 'booking.payment', 'booking.package',
            'messages.replyTo',
        ])->findOrFail($conversation->id);

        $items = app(MessageService::class)->formatConversationForUser($thread)['items'];

        $this->assertSame(
            ['message', 'message', 'booking'],
            array_map(fn ($item) => $item['type'], $items),
        );
        $this->assertSame($approved->id, $items[2]['booking']['id']);
    }

    public function test_the_latest_approved_booking_is_the_last_item_in_the_thread(): void
    {
        $user = $this->makeUser();
        $older = $this->makeBooking($user, 'approved', 'OLD-801');
        $newer = $this->makeBooking($user, 'approved', 'NEW-802');
        $conversation = $this->makeConversation($user, [['from' => 'client', 'body' => 'Hi']]);

        // Approvals spread out: the older a day ago, the newer after the message, so the
        // newer card is unambiguously the very last item.
        DB::table('bookings')->where('id', $older->id)->update(['approved_at' => now()->subDay()]);
        DB::table('bookings')->where('id', $newer->id)->update(['approved_at' => now()->addMinute()]);

        $thread = Conversation::with([
            'booking.eventDetails', 'booking.schedule', 'booking.payment', 'booking.package',
            'messages.replyTo',
        ])->findOrFail($conversation->id);

        $items = app(MessageService::class)->formatConversationForUser($thread)['items'];
        $last = end($items);

        $this->assertSame('booking', $last['type']);
        $this->assertSame($newer->id, $last['booking']['id']);
    }
}
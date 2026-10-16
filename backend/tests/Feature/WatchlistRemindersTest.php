<?php

use App\Jobs\SendWatchlistReminders;
use App\Models\Performance;
use App\Models\Production;
use App\Models\User;
use App\Models\WatchlistItem;
use App\Services\ExpoPush;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

const WATCH_TOKEN = 'ExponentPushToken[abc123]';

beforeEach(function () {
    // A fixed winter date, so Sofia is UTC+2 and the message time is predictable.
    $this->travelTo(now()->setDate(2026, 11, 10)->setTime(9, 0));
    Http::fake([ExpoPush::ENDPOINT => Http::response(['data' => ['status' => 'ok', 'id' => 'x']])]);
});

function watching(?string $token, int $daysAhead): WatchlistItem
{
    $user = User::factory()->create(['expo_push_token' => $token]);
    $production = Production::factory()->create(['title' => 'Гняв']);
    Performance::factory()->for($production)->create(['starts_at' => now()->addDays($daysAhead)->setTime(17, 0)]);

    return WatchlistItem::create(['user_id' => $user->id, 'production_id' => $production->id]);
}

it('reminds about a watchlisted show playing within a week, once', function () {
    $item = watching(WATCH_TOKEN, 3);

    (new SendWatchlistReminders)->handle(app(ExpoPush::class));
    (new SendWatchlistReminders)->handle(app(ExpoPush::class));

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $r) => $r['to'] === WATCH_TOKEN
        && $r['title'] === 'Гняв'
        && str_contains($r['body'], ' в 19:00')   // 17:00 UTC is 19:00 in Sofia (winter)
        && ((array) $r['data'])['productionId'] === $item->production_id);
    expect($item->fresh()->reminded_at)->not->toBeNull();
});

it('stays quiet for shows further out, users without a token, or cancelled dates', function () {
    watching(WATCH_TOKEN, 20);
    watching(null, 2);
    $cancelled = watching(WATCH_TOKEN, 2);
    Performance::where('production_id', $cancelled->production_id)->update(['is_cancelled' => true]);

    (new SendWatchlistReminders)->handle(app(ExpoPush::class));

    Http::assertNothingSent();
});

it('reminds again after three weeks', function () {
    $item = watching(WATCH_TOKEN, 3);
    $item->update(['reminded_at' => now()->subWeeks(4)]);

    (new SendWatchlistReminders)->handle(app(ExpoPush::class));

    Http::assertSentCount(1);
});

it('registers and clears a push token', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->putJson('/api/v1/me/push-token', ['token' => WATCH_TOKEN])->assertNoContent();
    expect($user->fresh()->expo_push_token)->toBe(WATCH_TOKEN);

    $this->putJson('/api/v1/me/push-token', ['token' => 'not-a-token'])->assertUnprocessable();
    $this->putJson('/api/v1/me/push-token', ['token' => null])->assertNoContent();
    expect($user->fresh()->expo_push_token)->toBeNull();
});

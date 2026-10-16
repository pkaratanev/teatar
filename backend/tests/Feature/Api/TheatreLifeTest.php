<?php

use App\Enums\CreditRole;
use App\Enums\ReviewStatus;
use App\Models\Credit;
use App\Models\DiaryEntry;
use App\Models\Performance;
use App\Models\Person;
use App\Models\Production;
use App\Models\Report;
use App\Models\Review;
use App\Models\Theatre;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->production = Production::factory()->create();
    $this->user = User::factory()->create(['created_at' => now()->subWeek()]);
});

it('requires an account to rate, review or log', function () {
    $id = $this->production->id;

    $this->putJson("/api/v1/productions/{$id}/rating", ['half_stars' => 8])->assertUnauthorized();
    $this->putJson("/api/v1/productions/{$id}/review", ['body' => 'Хубаво'])->assertUnauthorized();
    $this->postJson("/api/v1/productions/{$id}/log")->assertUnauthorized();
});

it('rates in half stars and updates the production average', function () {
    Sanctum::actingAs($this->user);
    $id = $this->production->id;

    $this->putJson("/api/v1/productions/{$id}/rating", ['half_stars' => 7])
        ->assertOk()
        ->assertJsonPath('data.half_stars', 7)
        ->assertJsonPath('data.rating', 3.5);

    $this->putJson("/api/v1/productions/{$id}/rating", ['half_stars' => 10])->assertJsonPath('data.rating', 5);
    expect($this->production->fresh())->ratings_count->toBe(1)->rating_average->toBe(5.0);

    $this->deleteJson("/api/v1/productions/{$id}/rating")->assertJsonPath('data.rating', null);
    expect($this->production->fresh())->ratings_count->toBe(0)->rating_average->toBeNull();
});

it('rejects ratings outside ½–5 stars', function (int $value) {
    Sanctum::actingAs($this->user);

    $this->putJson("/api/v1/productions/{$this->production->id}/rating", ['half_stars' => $value])->assertUnprocessable();
})->with([0, 11]);

it('keeps one review per user per production, editable', function () {
    Sanctum::actingAs($this->user);
    $id = $this->production->id;

    $this->putJson("/api/v1/productions/{$id}/review", ['body' => 'Първо впечатление'])->assertOk();
    $this->putJson("/api/v1/productions/{$id}/review", ['body' => 'Размислих', 'has_spoilers' => true])
        ->assertJsonPath('data.review.body', 'Размислих')
        ->assertJsonPath('data.review.has_spoilers', true);

    expect(Review::count())->toBe(1);
});

it('keeps a hidden review hidden when its author edits it', function () {
    Sanctum::actingAs($this->user);
    Review::create(['user_id' => $this->user->id, 'production_id' => $this->production->id, 'body' => 'спам', 'status' => ReviewStatus::Hidden]);

    $this->putJson("/api/v1/productions/{$this->production->id}/review", ['body' => 'вече не е спам'])->assertOk();

    expect(Review::sole()->status)->toBe(ReviewStatus::Hidden);
});

it('logs a visit with rating and review in one tap and drops it from the watchlist', function () {
    Sanctum::actingAs($this->user);
    $id = $this->production->id;
    $performance = Performance::factory()->for($this->production)->create(['starts_at' => now()->subDay()]);

    $this->putJson("/api/v1/productions/{$id}/watchlist")->assertJsonPath('data.on_watchlist', true);

    $this->postJson("/api/v1/productions/{$id}/log", [
        'seen_on' => now('Europe/Sofia')->subDay()->toDateString(),
        'performance_id' => $performance->id,
        'half_stars' => 9,
        'review' => 'Великолепно!',
    ])
        ->assertCreated()
        ->assertJsonPath('data.half_stars', 9)
        ->assertJsonPath('data.review.body', 'Великолепно!')
        ->assertJsonPath('data.on_watchlist', false)
        ->assertJsonCount(1, 'data.seen_on');
});

it('allows several visits on different days but not twice on the same day', function () {
    Sanctum::actingAs($this->user);
    $id = $this->production->id;

    $this->postJson("/api/v1/productions/{$id}/diary", ['seen_on' => '2026-01-10'])->assertCreated();
    $this->postJson("/api/v1/productions/{$id}/diary", ['seen_on' => '2026-01-10'])->assertCreated();
    $this->postJson("/api/v1/productions/{$id}/diary", ['seen_on' => '2026-03-02'])->assertCreated()->assertJsonCount(2, 'data.seen_on');

    expect(DiaryEntry::count())->toBe(2);
});

it('rejects a visit in the future or a performance of another production', function () {
    Sanctum::actingAs($this->user);
    $other = Performance::factory()->create();

    $this->postJson("/api/v1/productions/{$this->production->id}/diary", ['seen_on' => now('Europe/Sofia')->addDays(2)->toDateString()])
        ->assertUnprocessable()->assertJsonValidationErrors('seen_on');
    $this->postJson("/api/v1/productions/{$this->production->id}/diary", ['performance_id' => $other->id])
        ->assertUnprocessable()->assertJsonValidationErrors('performance_id');
});

it('lets users delete only their own diary entries', function () {
    $mine = DiaryEntry::create(['user_id' => $this->user->id, 'production_id' => $this->production->id, 'seen_on' => '2026-01-10']);
    $theirs = DiaryEntry::create(['user_id' => User::factory()->create()->id, 'production_id' => $this->production->id, 'seen_on' => '2026-01-10']);
    Sanctum::actingAs($this->user);

    $this->deleteJson("/api/v1/diary/{$theirs->id}")->assertNotFound();
    $this->deleteJson("/api/v1/diary/{$mine->id}")->assertNoContent();

    expect(DiaryEntry::pluck('id')->all())->toBe([$theirs->id]);
});

it('shows my own state on the production page when signed in', function () {
    $id = $this->production->id;
    $this->getJson("/api/v1/productions/{$id}")->assertOk()->assertJsonMissingPath('me');

    Sanctum::actingAs($this->user);
    $this->putJson("/api/v1/productions/{$id}/rating", ['half_stars' => 6]);

    $this->getJson("/api/v1/productions/{$id}")->assertJsonPath('me.half_stars', 6)->assertJsonPath('me.on_watchlist', false);
});

it('lists public reviews with the author rating and hides shadow-banned authors', function () {
    $banned = User::factory()->create(['shadow_banned_at' => now()]);
    Review::create(['user_id' => $this->user->id, 'production_id' => $this->production->id, 'body' => 'Чудесно']);
    Review::create(['user_id' => $banned->id, 'production_id' => $this->production->id, 'body' => 'спам']);
    \App\Models\Rating::create(['user_id' => $this->user->id, 'production_id' => $this->production->id, 'half_stars' => 8]);

    $this->getJson("/api/v1/productions/{$this->production->id}/reviews")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.body', 'Чудесно')
        ->assertJsonPath('data.0.half_stars', 8)
        ->assertJsonPath('data.0.user.username', $this->user->username);
});

it('reports a review once per reporter, but not your own', function () {
    $review = Review::create(['user_id' => User::factory()->create()->id, 'production_id' => $this->production->id, 'body' => 'обида']);
    $own = Review::create(['user_id' => $this->user->id, 'production_id' => $this->production->id, 'body' => 'мое']);
    Sanctum::actingAs($this->user);

    $this->postJson("/api/v1/reviews/{$review->id}/report", ['reason' => 'abuse'])->assertNoContent();
    $this->postJson("/api/v1/reviews/{$review->id}/report", ['reason' => 'spam'])->assertNoContent();
    $this->postJson("/api/v1/reviews/{$own->id}/report", ['reason' => 'spam'])->assertUnprocessable();

    expect(Report::sole())->reason->toBe('spam');
});

it('builds the profile: diary, histogram, favourite theatre, most-seen actor', function () {
    $theatre = Theatre::factory()->create(['name' => 'Театър Тест']);
    $actor = Person::factory()->create(['name' => 'Актьор Тестов']);
    $a = Production::factory()->for($theatre)->create();
    $b = Production::factory()->for($theatre)->create();
    foreach ([$a, $b] as $p) {
        Credit::create(['production_id' => $p->id, 'person_id' => $actor->id, 'role_type' => CreditRole::Actor]);
    }

    Sanctum::actingAs($this->user);
    $this->postJson("/api/v1/productions/{$a->id}/log", ['half_stars' => 10]);
    $this->postJson("/api/v1/productions/{$b->id}/log", ['half_stars' => 7, 'seen_on' => now('Europe/Sofia')->subDays(3)->toDateString()]);

    $stats = $this->getJson("/api/v1/users/{$this->user->username}")->assertOk()->json('data.stats');

    expect($stats['seen_total'])->toBe(2)
        ->and($stats['ratings_histogram'])->toBe([0, 0, 0, 0, 0, 0, 1, 0, 0, 1])
        ->and($stats['favourite_theatre']['name'])->toBe('Театър Тест')
        ->and($stats['most_seen_actor'])->toBe(['id' => $actor->id, 'name' => 'Актьор Тестов', 'times' => 2]);

    $this->getJson('/api/v1/me/diary')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.half_stars', 10);
    $this->getJson('/api/v1/me/ratings')->assertOk()->assertJsonCount(2, 'data');
});

it('lists my watchlist with the next date', function () {
    Performance::factory()->for($this->production)->create(['starts_at' => now()->addDays(4)]);
    Sanctum::actingAs($this->user);

    $this->putJson("/api/v1/productions/{$this->production->id}/watchlist");

    $this->getJson('/api/v1/me/watchlist')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $this->production->id)
        ->assertJson(fn ($json) => $json->whereType('data.0.next_performance_at', 'string')->etc());
});

it('slows down review spam from brand-new accounts', function () {
    $fresh = User::factory()->create(['created_at' => now()]);
    Sanctum::actingAs($fresh);
    $productions = Production::factory(11)->create();

    foreach ($productions->take(10) as $p) {
        $this->putJson("/api/v1/productions/{$p->id}/review", ['body' => 'Добре'])->assertOk();
    }
    $this->putJson("/api/v1/productions/{$productions->last()->id}/review", ['body' => 'Добре'])->assertTooManyRequests();
});

<?php

use App\Models\Follow;
use App\Models\Person;
use App\Models\Production;
use App\Models\Review;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->me = User::factory()->create();
    $this->friend = User::factory()->create();
    $this->production = Production::factory()->create();
});

it('follows and unfollows a user, but not yourself', function () {
    Sanctum::actingAs($this->me);

    $this->putJson("/api/v1/users/{$this->friend->username}/follow")
        ->assertOk()->assertJsonPath('data.is_following', true)->assertJsonPath('data.followers_count', 1);
    $this->putJson("/api/v1/users/{$this->friend->username}/follow")->assertJsonPath('data.followers_count', 1);

    $this->getJson("/api/v1/users/{$this->friend->username}")
        ->assertJsonPath('data.is_following', true)
        ->assertJsonPath('data.followers_count', 1)
        ->assertJsonPath('data.is_me', false);
    $this->getJson("/api/v1/users/{$this->me->username}")->assertJsonPath('data.following_count', 1)->assertJsonPath('data.is_me', true);

    $this->deleteJson("/api/v1/users/{$this->friend->username}/follow")->assertJsonPath('data.is_following', false);
    $this->putJson("/api/v1/users/{$this->me->username}/follow")->assertUnprocessable();
});

it('stores short morph names, not class names', function () {
    Sanctum::actingAs($this->me);
    $person = Person::factory()->create();

    $this->putJson("/api/v1/users/{$this->friend->username}/follow");
    $this->putJson("/api/v1/people/{$person->id}/follow")->assertJsonPath('data.is_following', true);

    expect(Follow::pluck('followable_type')->sort()->values()->all())->toBe(['person', 'user']);
});

it('shows follow state on a person page', function () {
    $person = Person::factory()->create();
    Sanctum::actingAs($this->me);
    $this->putJson("/api/v1/people/{$person->id}/follow");

    $this->getJson("/api/v1/people/{$person->id}")
        ->assertJsonPath('social.is_following', true)
        ->assertJsonPath('social.followers_count', 1);
});

it('builds a feed of reviews by the people you follow', function () {
    $stranger = User::factory()->create();
    $banned = User::factory()->create(['shadow_banned_at' => now()]);
    Review::create(['user_id' => $this->friend->id, 'production_id' => $this->production->id, 'body' => 'От приятел']);
    Review::create(['user_id' => $stranger->id, 'production_id' => $this->production->id, 'body' => 'От непознат']);
    Review::create(['user_id' => $banned->id, 'production_id' => $this->production->id, 'body' => 'Спам']);

    Sanctum::actingAs($this->me);
    $this->putJson("/api/v1/users/{$this->friend->username}/follow");
    $this->putJson("/api/v1/users/{$banned->username}/follow");

    $this->getJson('/api/v1/me/feed')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.body', 'От приятел')
        ->assertJsonPath('data.0.production.id', $this->production->id);
});

it('likes and unlikes a review and keeps an accurate count', function () {
    $review = Review::create(['user_id' => $this->friend->id, 'production_id' => $this->production->id, 'body' => 'Чудесно']);
    $other = User::factory()->create();

    Sanctum::actingAs($this->me);
    $this->putJson("/api/v1/reviews/{$review->id}/like")->assertJsonPath('data.likes_count', 1)->assertJsonPath('data.liked_by_me', true);
    $this->putJson("/api/v1/reviews/{$review->id}/like")->assertJsonPath('data.likes_count', 1);

    Sanctum::actingAs($other);
    $this->putJson("/api/v1/reviews/{$review->id}/like")->assertJsonPath('data.likes_count', 2);

    Sanctum::actingAs($this->me);
    $this->getJson("/api/v1/productions/{$this->production->id}/reviews")->assertJsonPath('data.0.liked_by_me', true);
    $this->deleteJson("/api/v1/reviews/{$review->id}/like")->assertJsonPath('data.likes_count', 1);
});

it('does not let you like your own review', function () {
    $review = Review::create(['user_id' => $this->me->id, 'production_id' => $this->production->id, 'body' => 'Мое']);
    Sanctum::actingAs($this->me);

    $this->putJson("/api/v1/reviews/{$review->id}/like")->assertUnprocessable();
});

it('lists who I follow', function () {
    $person = Person::factory()->create(['name' => 'Актьор Тестов']);
    Sanctum::actingAs($this->me);
    $this->putJson("/api/v1/users/{$this->friend->username}/follow");
    $this->putJson("/api/v1/people/{$person->id}/follow");

    $this->getJson('/api/v1/me/following')
        ->assertJsonPath('data.users.0.username', $this->friend->username)
        ->assertJsonPath('data.people.0.name', 'Актьор Тестов');
});

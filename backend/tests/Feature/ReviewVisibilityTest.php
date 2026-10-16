<?php

use App\Enums\ReviewStatus;
use App\Models\Production;
use App\Models\Review;
use App\Models\User;

it('hides shadow-banned reviews from everyone except their author', function () {
    $production = Production::factory()->create();
    $banned = User::factory()->create(['shadow_banned_at' => now()]);
    $normal = User::factory()->create();

    $hiddenReview = Review::create(['user_id' => $banned->id, 'production_id' => $production->id, 'body' => 'спам']);
    Review::create(['user_id' => $normal->id, 'production_id' => $production->id, 'body' => 'Чудесно!']);
    Review::create(['user_id' => $normal->id, 'production_id' => Production::factory()->create()->id, 'body' => 'скрито', 'status' => ReviewStatus::Hidden]);

    expect(Review::visibleTo(null)->count())->toBe(1)
        ->and(Review::visibleTo($normal)->count())->toBe(1) // own hidden review stays hidden
        ->and(Review::visibleTo($banned)->pluck('id'))->toContain($hiddenReview->id);
});

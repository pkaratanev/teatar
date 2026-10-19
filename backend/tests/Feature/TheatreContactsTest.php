<?php

use App\Models\Theatre;
use App\Support\TheatreContacts;
use Database\Seeders\LaunchTheatresSeeder;

it('seeds the launch theatres with address, coordinates and box-office phone', function () {
    $this->seed(LaunchTheatresSeeder::class);

    $national = Theatre::where('adapter', 'national')->firstOrFail();
    expect($national->address)->toBe('ул. „Дякон Игнатий“ 5')
        ->and($national->lat)->toBe(42.6941714)
        ->and($national->getAttribute('phone'))->toBe('02 811 92 27');

    // No adapter: matched by name.
    expect(Theatre::where('name', 'Театрална работилница „Сфумато“')->firstOrFail()->lat)->toBe(42.697929);
});

it('fills only empty fields, so an admin edit stays', function () {
    $theatre = Theatre::factory()->create(['adapter' => 'satirata', 'address' => 'Моят адрес', 'phone' => null, 'lat' => null, 'lng' => null]);

    expect(TheatreContacts::fill($theatre))->toBeTrue();

    $theatre->refresh();
    expect($theatre->address)->toBe('Моят адрес')
        ->and($theatre->getAttribute('phone'))->toBe('0882 565 653')
        ->and($theatre->lng)->toBe(23.3263186)
        ->and(TheatreContacts::fill($theatre))->toBeFalse();
});

it('shows the phone in the API', function () {
    $theatre = Theatre::factory()->create(['phone' => '02 123 45 67']);

    $this->getJson("/api/v1/theatres/{$theatre->id}")->assertOk()->assertJsonPath('data.phone', '02 123 45 67');
});

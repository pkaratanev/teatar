<?php

it('shows the privacy policy with the contact address', function () {
    config(['teatar.contact_email' => 'hello@teatar.test']);

    $this->get('/privacy')
        ->assertOk()
        ->assertSee('Политика за поверителност')
        ->assertSee('mailto:hello@teatar.test', false)
        ->assertSee(url('/delete-account'));
});

it('explains how to delete an account with and without the app', function () {
    config(['teatar.contact_email' => 'hello@teatar.test']);

    $this->get('/delete-account')
        ->assertOk()
        ->assertSee('Изтрий профила')
        ->assertSee('Изтрий завинаги')
        ->assertSee('mailto:hello@teatar.test', false);
});

it('still renders without a contact address', function () {
    config(['teatar.contact_email' => null]);

    $this->get('/privacy')->assertOk()->assertDontSee('mailto:', false);
    $this->get('/delete-account')->assertOk()->assertDontSee('mailto:', false);
});

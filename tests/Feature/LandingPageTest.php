<?php

it('The landing page matches what the app does', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSeeInOrder(['their place on the list', 'two stops away', "you're next"], false)
        ->assertSee("each customer's name, address and phone number", false);
    expect(strtolower($response->getContent()))->not->toContain('one stop away');
});

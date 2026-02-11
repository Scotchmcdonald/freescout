<?php

test('login page loads', function () {
    $this->visit('/login')
        ->assertSee('Email');
})->group('smoke');

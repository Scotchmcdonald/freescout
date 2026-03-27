<?php

arch('controllers do not use DB facade')
    ->expect('App\Http\Controllers')
    ->not->toUse('Illuminate\Support\Facades\DB');

arch('unit tests do not use Laravel TestCase')
    ->expect('Tests\Unit')
    ->not->toUse('Tests\TestCase');

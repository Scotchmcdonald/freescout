<?php

namespace Illuminate\Foundation\Auth\Access;

trait AuthorizesRequests
{
    public function authorize(mixed $ability, mixed $arguments = []): mixed
    {
        return null;
    }
}

namespace Illuminate\Foundation\Validation;

trait ValidatesRequests {}

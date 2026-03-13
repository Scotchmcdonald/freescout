<?php

namespace Illuminate\Foundation\Auth\Access;

trait AuthorizesRequests
{
    public function authorize(mixed $ability, mixed $arguments = []): mixed
    {
        return null;
    }

    public function authorizeForUser(mixed $user, mixed $ability, mixed $arguments = []): mixed
    {
        return null;
    }

    public function authorizeResource(mixed $model, mixed $parameter = null, array $options = [], mixed $request = null): void {}
}

namespace Illuminate\Foundation\Validation;

trait ValidatesRequests
{
    /**
     * @param  array<string, mixed>  $rules
     * @param  array<string, mixed>  $messages
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function validate(mixed $request, array $rules, array $messages = [], array $attributes = []): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $rules
     * @param  array<string, mixed>  $messages
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function validateWithBag(string $errorBag, mixed $request, array $rules, array $messages = [], array $attributes = []): array
    {
        return [];
    }
}

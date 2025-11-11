<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static \Illuminate\Database\Eloquent\Builder<Option> whereIn(string $column, mixed $values)
 * @method static Option updateOrCreate(array $attributes, array $values = [])
 */
class Option extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'name';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    protected $fillable = [
        'name',
        'value',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get an option value by name.
     */
    public static function getValue(string $name, mixed $default = null): mixed
    {
        $option = static::where('name', $name)->first();

        return $option ? $option->value : $default;
    }

    /**
     * Set an option value by name.
     */
    public static function setValue(string $name, mixed $value): bool
    {
        static::updateOrCreate(
            ['name' => $name],
            ['value' => $value]
        );

        return true;
    }

    /**
     * Delete an option by name.
     */
    public static function deleteOption(string $name): bool
    {
        return static::where('name', $name)->delete() > 0;
    }

    /**
     * Alias for getValue for backward compatibility.
     */
    public static function get(string $name, mixed $default = null): mixed
    {
        return static::getValue($name, $default);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

abstract class ReadOnlyModel extends Model
{
    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['*'];

    /**
     * Prevent saving a read-only database view model.
     *
     * @param  array<string, mixed>  $options
     */
    public function save(array $options = []): bool
    {
        throw new LogicException(sprintf('Model [%s] adalah view read-only dan tidak dapat disimpan.', static::class));
    }

    /**
     * Prevent deleting a read-only database view model.
     */
    public function delete(): ?bool
    {
        throw new LogicException(sprintf('Model [%s] adalah view read-only dan tidak dapat dihapus.', static::class));
    }
}

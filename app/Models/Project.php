<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = ['name', 'objective', 'graph'];

    protected $hidden = ['graph'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['graph' => 'array'];
    }

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** @return HasMany<ProjectNote, $this> */
    public function notes(): HasMany
    {
        return $this->hasMany(ProjectNote::class);
    }

    public function streamPrefix(): string
    {
        return "sebra:project:{$this->getKey()}";
    }
}

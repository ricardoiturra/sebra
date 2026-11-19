<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectNote extends Model
{
    protected $fillable = ['title', 'markdown', 'source', 'revision'];

    public function sourceUrl(): ?string
    {
        if (! str_starts_with($this->source ?? '', 'newrelic:')) {
            return null;
        }

        $path = implode('/', array_map(rawurlencode(...), explode('/', substr($this->source, strlen('newrelic:')))));

        return 'https://github.com/newrelic/docs-website/blob/develop/'.$path;
    }
}

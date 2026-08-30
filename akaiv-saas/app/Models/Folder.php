<?php

namespace App\Models;

use App\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Folder extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'workspace_id',
        'parent_folder_id',
        'case_id',
        'name',
        'depth',
        'path_cache',
        'is_system',
        'created_by',
    ];

    protected $casts = [
        'depth' => 'integer',
        'is_system' => 'boolean',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_folder_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Folder::class, 'parent_folder_id');
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class, 'case_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function pathSegments(): array
    {
        if ($this->path_cache !== null && $this->path_cache !== '') {
            return explode('/', $this->path_cache);
        }
        $segments = [$this->name];
        $current = $this->parent;
        while ($current !== null) {
            array_unshift($segments, $current->name);
            $current = $current->parent;
        }
        return $segments;
    }
}

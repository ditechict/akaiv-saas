<?php

namespace App\Models;

use App\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CaseFile extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToOrganization;

    protected $table = 'cases';

    protected $fillable = [
        'organization_id',
        'workspace_id',
        'case_number',
        'suit_number',
        'title',
        'parties_json',
        'court_name',
        'bench_judge_name',
        'jurisdiction',
        'notes',
        'date_filed',
        'date_judgment',
        'status',
        'created_by',
    ];

    protected $casts = [
        'date_filed' => 'date',
        'date_judgment' => 'date',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function getPartiesAttribute(): array
    {
        if ($this->parties_json === null) {
            return [];
        }
        return json_decode($this->parties_json, true) ?? [];
    }

    public function setPartiesAttribute(array $parties): void
    {
        $this->attributes['parties_json'] = json_encode($parties);
    }
}

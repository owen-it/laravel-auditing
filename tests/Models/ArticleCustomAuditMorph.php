<?php

namespace OwenIt\Auditing\Tests\Models;

use Illuminate\Database\Eloquent\Relations\MorphMany;

class ArticleCustomAuditMorph extends Article
{
    protected $table = 'articles';

    /**
     * @return MorphMany<CustomAudit>
     */
    public function audits(): MorphMany
    {
        return $this->morphMany(CustomAudit::class, 'auditable');
    }
}

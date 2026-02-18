<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function sourceLanguages(): HasMany
    {
        return $this->hasMany(UserSourceLanguage::class);
    }

    public function targetLanguages(): HasMany
    {
        return $this->hasMany(UserTargetLanguage::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(Translation::class);
    }

    public function practiceAttempts(): HasMany
    {
        return $this->hasMany(PracticeAttempt::class);
    }

    public function activeSourceLanguage(): ?UserSourceLanguage
    {
        return $this->sourceLanguages()->where('is_active', true)->with('language')->first();
    }

    /**
     * Get translations that are due for review.
     * Returns a Collection of translations ready for practice.
     */
    public function getTranslationsDueForReview(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->translations()
            ->where(function ($query) {
                $query->whereNull('next_review_at')
                    ->orWhere('next_review_at', '<=', now());
            })
            ->orderByRaw('next_review_at IS NULL, next_review_at ASC')
            ->get();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    // ========== CONSTANTES POUR LES TYPES D'UTILISATEURS ==========
    public const TYPE_SUPERADMIN = 'superadmin';

    public const TYPE_SECRETAIRE = 'secretaire';

    public const TYPE_TECHNICIEN = 'technicien';

    public const TYPE_BIOLOGISTE = 'biologiste';

    public const TYPES = [
        self::TYPE_SUPERADMIN => 'Super Administrateur',
        self::TYPE_SECRETAIRE => 'Secrétaire',
        self::TYPE_TECHNICIEN => 'Technicien',
        self::TYPE_BIOLOGISTE => 'Biologiste',
    ];

    // ========== ATTRIBUTS ==========
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'type',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    // ========== MÉTHODES D'AUTHENTIFICATION ==========
    public function getAuthIdentifierName()
    {
        return 'id';
    }

    public function getAuthIdentifier()
    {
        return $this->getAttribute($this->getAuthIdentifierName());
    }

    public function getAuthPassword()
    {
        return $this->password;
    }

    public function getRememberToken()
    {
        return $this->remember_token;
    }

    public function setRememberToken($value)
    {
        $this->remember_token = $value;
    }

    public function getRememberTokenName()
    {
        return 'remember_token';
    }

    // ========== ACCESSEURS ==========
    public function getTypeNameAttribute()
    {
        return self::TYPES[$this->type] ?? 'Inconnu';
    }

    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->name);
        $initials = '';
        foreach ($words as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }

        return substr($initials, 0, 2);
    }

    // ========== MÉTHODES DE VÉRIFICATION DES RÔLES (via Spatie) ==========
    public function isAdmin(): bool
    {
        return $this->hasRole(self::TYPE_SUPERADMIN);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(self::TYPE_SUPERADMIN);
    }

    public function isSecretaire(): bool
    {
        return $this->hasRole(self::TYPE_SECRETAIRE);
    }

    public function isTechnicien(): bool
    {
        return $this->hasRole(self::TYPE_TECHNICIEN);
    }

    public function isBiologiste(): bool
    {
        return $this->hasRole(self::TYPE_BIOLOGISTE);
    }

    /**
     * Vérifie si l'utilisateur a une permission spécifique (via Spatie)
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->hasPermissionTo($permission);
    }

    public function canAccessAdmin(): bool
    {
        return $this->isSuperAdmin();
    }

    public function canManagePrescriptions(): bool
    {
        return $this->hasPermission('prescriptions.voir');
    }

    public function canPerformAnalyses(): bool
    {
        return $this->hasPermission('analyses.effectuer');
    }

    public function canValidateResults(): bool
    {
        return $this->hasPermission('analyses.valider');
    }

    // ========== SCOPES ==========
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeSecretaires($query)
    {
        return $query->where('type', self::TYPE_SECRETAIRE);
    }

    public function scopeTechniciens($query)
    {
        return $query->where('type', self::TYPE_TECHNICIEN);
    }

    public function scopeBiologistes($query)
    {
        return $query->where('type', self::TYPE_BIOLOGISTE);
    }

    public function scopeAdmins($query)
    {
        return $query->where('type', self::TYPE_SUPERADMIN);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%");
        });
    }

    // ========== RELATIONS ==========
    public function prescriptions()
    {
        return $this->hasMany(Prescription::class, 'secretaire_id');
    }

    public function analyses()
    {
        return $this->hasMany(Analyse::class, 'technicien_id');
    }

    public function validatedResults()
    {
        return $this->hasMany(Resultat::class, 'biologiste_id');
    }

    // ========== MÉTHODES UTILITAIRES ==========
    public static function getAvailableTypes(): array
    {
        return self::TYPES;
    }

    public static function isValidType(string $type): bool
    {
        return array_key_exists($type, self::TYPES);
    }

    public static function getCountByType(): array
    {
        $counts = [];
        foreach (self::TYPES as $type => $label) {
            $counts[$type] = self::where('type', $type)->count();
        }

        return $counts;
    }

    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    public function getAvatarAttribute(): string
    {
        return 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&color=7F9CF5&background=EBF4FF';
    }
}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable {
    
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'otp',
        'otp_expires_at',
        'email_verified_at',
        'role',
        'department',
        'program',
        'year_level',
        'phone_number',
        'age',
        'gender',
        'profile_image',
        'terms_accepted_at',
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
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['profile_image_url', 'unit'];

    /**
     * Get the full URL for the profile image.
     *
     * @return string|null
     */
    public function getProfileImageUrlAttribute()
    {
        if (!$this->profile_image) {
            $name = urlencode($this->first_name . ' ' . $this->last_name);
            return "https://ui-avatars.com/api/?name={$name}&background=random&color=fff&size=128&bold=true";
        }
        return asset('storage/' . $this->profile_image);
    }

    /**
     * Get the unit attribute (maps to department).
     *
     * @return string|null
     */
    public function getUnitAttribute()
    {
        return $this->department;
    }

    /**
     * Set the unit attribute (maps to department).
     *
     * @param string|null $value
     * @return void
     */
    public function setUnitAttribute($value)
    {
        $this->attributes['department'] = $value;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'otp_expires_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            // 'password' => 'hashed', // Disabled to prevent double hashing when using Hash::make manually
        ];
    }

    public function chatMessages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function crisisAlerts()
    {
        return $this->hasMany(CrisisAlert::class);
    }

    public function emotionLogs()
    {
        return $this->hasMany(EmotionLog::class);
    }

    public function sessionLogs()
    {
        return $this->hasMany(SessionLog::class);
    }
}

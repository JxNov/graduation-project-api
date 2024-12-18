<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    const _GENDERS = [
        'Male' => 'Male',
        'Female' => 'Female',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'image',
        'date_of_birth',
        'gender',
        'address',
        'phone_number',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    private mixed $roles;

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions');
    }

    public function hasPermission($permission): bool
    {
        if ($this->permissions()->where('value', $permission)->exists()) {
            return true;
        }

        $roles = $this->roles()->get();
        foreach ($roles as $role) {
            if ($role->permissions()->where('value', $permission)->exists()) {
                return true;
            }
        }

        return false;
    }

    public function generations(): BelongsToMany
    {
        return $this->belongsToMany(Generation::class, 'user_generations');
    }

    public function academicYears(): BelongsToMany
    {
        return $this->belongsToMany(AcademicYear::class, 'user_generations');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_users');
    }

    public function isAdmin(): bool
    {
        return $this->roles()->where('name', 'admin')->exists();
    }
    public function isTeacher(): bool
    {
        return $this->roles()->where('name', 'teacher')->exists();
    }

    public function classes()
    {
        return $this->belongsToMany(Classes::class, 'class_students', 'student_id', 'class_id');
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'subject_teachers', 'teacher_id', 'subject_id');
    }

    public function homeroomClasses(): HasOne
    {
        return $this->hasOne(Classes::class, 'teacher_id');
    }

    public function teachingClasses(): BelongsToMany
    {
        return $this->belongsToMany(Classes::class, 'class_teachers', 'teacher_id', 'class_id');
    }
    public function subjectScores()
    {
        return $this->hasMany(Score::class, 'student_id');
    }

    public function chatBotSessions()
    {
        return $this->hasMany(ChatBotSession::class, 'user_id', 'id');
    }

    public function attendanceDetails()
    {
        return $this->hasMany(AttendanceDetail::class, 'student_id', 'id');
    }

    public function finalScores()
    {
        return $this->hasMany(FinalScore::class, 'student_id');
    }

    public function articles()
    {
        return $this->hasMany(Article::class, 'teacher_id');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'teacher_id');
    }

    // protected static function booted()
    // {
    //     static::deleting(function ($user) {
    //         if ($user->generations->isNotEmpty()) {
    //             $user->generations()->updateExistingPivot($user->generations->pluck('id'), ['deleted_at' => now()]);
    //         }

    //         if ($user->roles->isNotEmpty()) {
    //             $user->roles()->updateExistingPivot($user->roles->pluck('id'), ['deleted_at' => now()]);
    //         }

    //         if ($user->permissions->isNotEmpty()) {
    //             $user->permissions()->updateExistingPivot($user->permissions->pluck('id'), ['deleted_at' => now()]);
    //         }

    //         if ($user->permissions->isNotEmpty()) {
    //             $user->permissions()->updateExistingPivot($user->permissions->pluck('id'), ['deleted_at' => now()]);
    //         }

    //         if ($user->classes->isNotEmpty()) {
    //             $user->classes()->updateExistingPivot($user->classes->pluck('id'), ['deleted_at' => now()]);
    //         }

    //         if ($user->teachingClasses->isNotEmpty()) {
    //             $user->teachingClasses()->updateExistingPivot($user->teachingClasses->pluck('id'), ['deleted_at' => now()]);
    //         }

    //         if ($user->conversations->isNotEmpty()) {
    //             $user->conversations()->updateExistingPivot($user->conversations->pluck('id'), ['deleted_at' => now()]);
    //         }

    //         if ($user->conversations->isNotEmpty()) {
    //             $user->conversations()->updateExistingPivot($user->conversations->pluck('id'), ['deleted_at' => now()]);
    //         }

    //         if ($user->subjects->isNotEmpty()) {
    //             $user->subjects()->updateExistingPivot($user->subjects->pluck('id'), ['deleted_at' => now()]);
    //         }

    //         foreach ($user->articles as $article) {
    //             foreach ($article->comments as $comment) {
    //                 $comment->delete();
    //             }
    //             $article->delete();
    //         }

    //         foreach ($user->assignments as $assignment) {
    //             foreach ($assignment->submittedAssignments as $submittedAssignment) {
    //                 $submittedAssignment->delete();
    //             }
    //             $assignment->delete();
    //         }

    //         foreach ($user->attendanceDetails as $attendanceDetail) {
    //             $attendanceDetail->delete();
    //         }

    //         foreach ($user->messages as $message) {
    //             $message->delete();
    //         }

    //         foreach ($user->subjectScores as $subjectScore) {
    //             $subjectScore->delete();
    //         }

    //         foreach ($user->finalScores as $finalScore) {
    //             $finalScore->delete();
    //         }
    //     });

    //     static::restoring(function ($user) {

    //     });
    // }
}

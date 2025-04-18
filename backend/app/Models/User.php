<?php

namespace App\Models;

use App\Enums\UserRelationType;
use App\Enums\UserType;
use Database\Factories\UserFactory;
use DateTimeInterface;
use Eloquent;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Laravel\Fortify\Rules\Password;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * App\Models\User
 *
 * @property int $id
 * @property int|null $registration
 * @property int|null $siape
 * @property string $name
 * @property UserType $type
 * @property string|null $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property bool $is_admin
 * @property int|null $area_id
 * @property int|null $course_id
 * @property string|null $lattes_url
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $defended_at
 * @property string|null $lattes_id
 * @property string|null $lattes_updated_at
 * @property-read Collection|User[] $advisedes
 * @property-read int|null $advisedes_count
 * @property-read Collection|User[] $advisors
 * @property-read int|null $advisors_count
 * @property-read Collection|User[] $coadviseees
 * @property-read int|null $coadviseees_count
 * @property-read Collection|User[] $coadvisors
 * @property-read int|null $coadvisors_count
 * @property-read Collection|User[] $isAdvisoredBy
 * @property-read int|null $is_advisored_by_count
 * @property-read DatabaseNotificationCollection|DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection|PersonalAccessToken[] $tokens
 * @property-read int|null $tokens_count
 * @property-read Collection|Production[] $writerOf
 * @property-read int|null $writer_of_count
 * @method static UserFactory factory(...$parameters)
 * @method static Builder|User newModelQuery()
 * @method static Builder|User newQuery()
 * @method static Builder|User query()
 * @method static Builder|User whereCourseId($value)
 * @method static Builder|User whereCreatedAt($value)
 * @method static Builder|User whereDefendedAt($value)
 * @method static Builder|User whereEmail($value)
 * @method static Builder|User whereEmailVerifiedAt($value)
 * @method static Builder|User whereId($value)
 * @method static Builder|User whereIsAdmin($value)
 * @method static Builder|User whereLattesId($value)
 * @method static Builder|User whereLattesUpdatedAt($value)
 * @method static Builder|User whereLattesUrl($value)
 * @method static Builder|User whereName($value)
 * @method static Builder|User wherePassword($value)
 * @method static Builder|User whereRegistration($value)
 * @method static Builder|User whereRememberToken($value)
 * @method static Builder|User whereSiape($value)
 * @method static Builder|User whereAreaId($value)
 * @method static Builder|User whereTwoFactorRecoveryCodes($value)
 * @method static Builder|User whereTwoFactorSecret($value)
 * @method static Builder|User whereType($value)
 * @method static Builder|User whereUpdatedAt($value)
 * @mixin Eloquent
 */
class User extends BaseModel implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    use HasApiTokens, HasFactory, Notifiable;
    use Authenticatable, Authorizable, CanResetPassword, MustVerifyEmail;

    protected $fillable = [
        'registration',
        'siape',
        'name',
        'type',
        'area_id',
        'email',
        'password',
        'course_id',
        'lattes_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
        'email_verified_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'bool',
        'type' => UserType::class,
        'siape' => 'int',
        'course_id' => 'int',
    ];

    protected $attributes = [
        'is_admin' => false,
    ];

     /**
      * @return array creation rules to validate attributes.
     */
    public static function creationRules(): array
    {
        return [
            'registration' => 'nullable|int|required_if:type,' . UserType::STUDENT->value,
            'siape' => 'nullable|int|required_if:type,' . UserType::PROFESSOR->value,
            'name' => 'required|string|max:255',
            'type' => ['required', new Enum(UserType::class)],
            'area_id' => [
                'nullable',
                'int',
                Rule::exists(Area::class, 'id')
            ],
            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => ['required', 'string', new Password, 'confirmed'],
            'course_id' => [
                'nullable',
                'int',
                Rule::exists(Course::class, 'id'),
                'required_if:type,' . UserType::STUDENT->value,
            ],
            'lattes_url' => 'nullable|string|max:255',
        ];
    }

    /**
     * Create or update a user of type student
     *
     * @param array array with user data
     * @return User instance of user model
     */
    public static function createOrUpdateStudent(array $data): User
    {
        $data['type'] = UserType::STUDENT->value;
        $password = Hash::make(Str::random(12));
        // TODO: Will this override the password????
        $data['password'] = $password;
        $data['password_confirmation'] = $password;

        return User::updateOrCreate(
            Arr::only($data, ['registration']),
            $data
        );
    }

    /**
     * Create or update a user of type professor
     *
     * @param array array with user data
     * @return User instance of user model
     */
    public static function createOrUpdateTeacher(array $data): User
    {
        $data['type'] = UserType::PROFESSOR->value;
        $password = Hash::make(Str::random(12));
        // TODO: Will this override the password? Reuse?
        $data['password'] = $password;
        $data['password_confirmation'] = $password;

        return User::updateOrCreate(
            Arr::only($data, ['siape']),
            $data
        );
    }

    /**
     * Establishes a relationship of belongsToMany with the production model
     *
     * @return BelongsToMany Relation of belongsToMany user -> production
     */
    public function writerOf(): BelongsToMany
    {
        return $this->belongsToMany(Production::class, 'users_productions', 'users_id', 'productions_id');
    }

    /**
     * Find a user based on a given name
     *
     * @param string $UserName, a string of user`name
     * @return User  instance of user model.
     */
    public function findUserByName($UserName): User
    {
        return User::where('name', $UserName)->firstOrFail();
    }

    /**
     * Find a user with type professor based on your siape
     *
     * @param int $siape teacher's siape
     * @return User  instance of user model.
     */
    public function findProfessorBySiape(int $siape): User
    {
        return User::where('siape', $siape)->firstOrFail();
    }

    /**
     * Find a user with type student  based on your registration
     *
     * @param int $registration of user
     * @return User  instance of user model.
     */
    public function findStudentByRegistration($registration): User
    {
        return User::where('registration', $registration)->firstOrFail();
    }

    /**
     * @return array update rules to validate attributes.
     */
    public function updateRules(): array
    {
        $courseIdRules = [
            'int',
            Rule::exists(Course::class, 'id'),
        ];
        if ($this->type === UserType::PROFESSOR) {
            $courseIdRules[] = 'nullable';
        }

        return [
            'name' => 'string|max:255',
            'area_id' => [
                'nullable',
                'int',
                Rule::exists(Area::class, 'id')
            ],
            'course_id' => $courseIdRules,
            'lattes_url' => 'nullable|string|max:255',
        ];
    }

    /**
     * Establishes a relationship of belongsToMany with the production model
     *
     * @return BelongsToMany<User> Relation of belongsToMany user -> production
     */
    public function advisors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_user', 'student_user_id', 'professor_user_id')
            ->wherePivot('relation_type', UserRelationType::ADVISOR);
    }

    public function advisedes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_user', 'professor_user_id', 'student_user_id')
            ->wherePivot('relation_type', UserRelationType::ADVISOR);

    }

    public function coadvisors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_user', 'student_user_id', 'professor_user_id')
            ->wherePivot('relation_type', UserRelationType::CO_ADVISOR);
    }

    public function coadviseees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_user', 'professor_user_id', 'student_user_id')
            ->wherePivot('relation_type', UserRelationType::CO_ADVISOR);
    }

    /**
     * @param 'mestrando'|'doutorando'|'completed'|null $selectedFilter
     * @return array<string, int>
     */
    public static function userCountPerSubArea(?string $selectedFilter): array
    {
        // TODO: filter
        $query = DB::table('users')
            ->join('areas', 'users.area_id', '=', 'areas.id')
            ->join('courses', 'users.course_id', '=', 'courses.id')
            ->groupBy('areas.subarea')
            ->selectRaw('areas.subarea, COUNT(users.id) AS userCount');

        $query = match ($selectedFilter) {
            'mestrando' => $query->where('courses.name', '=', 'Mestrado'),
            'doutorando' => $query->where('courses.name', '=', 'Doutorado'),
            'completed' => $query->whereNotNull('users.defended_at'),
            default => $query,
        };

        return $query->get()->pluck('userCount', 'subarea')->toArray();
    }

    /**
     * @param 'mestrando'|'doutorando'|'completed'|null $selectedFilter
     * @return array<string, int>
     */
    public static function userCountPerArea(?string $selectedFilter): array
    {
        // TODO: filter
        $query = DB::table('users')
            ->join('areas', 'users.area_id', '=', 'areas.id')
            ->join('courses', 'users.course_id', '=', 'courses.id')
            ->groupBy('areas.area')
            ->selectRaw('areas.area, COUNT(users.id) AS userCount');

        $query = match ($selectedFilter) {
            'mestrando' => $query->where('courses.name', '=', 'Mestrado'),
            'doutorando' => $query->where('courses.name', '=', 'Doutorado'),
            'completed' => $query->whereNotNull('users.defended_at'),
            default => $query,
        };

        return $query->get()->pluck('userCount', 'area')->toArray();
    }

    public function areasMasterFilter(): array
    {
        $data = DB::table('users')
            ->join('users_subareas', 'users_subareas.users_id', '=', 'users.id')
            ->join('subareas', 'users_subareas.subareas_id', '=', 'subareas.id')
            ->join('areas', 'areas.id', '=', 'subareas.area_id')
            ->select(DB::raw('areas.area_name, count(areas.id) as area_count'))
            ->where('users.type', '=', UserType::STUDENT)
            ->where('users.course_id', '=', 1)
            ->groupBy('areas.area_name')
            ->orderBy('areas.area_name')
            ->get();

        $dataFields = [];
        $dataCount = [];
        for ($counter = 0; $counter < count($data); $counter++) {
            $dataFields[$counter] = $data[$counter]->area_name;
            $dataCount[$counter] = $data[$counter]->area_count;
        }

        return [$dataFields, $dataCount];
    }

    public function areasDoctorFilter(): array
    {
        $data = DB::table('users')
            ->join('users_subareas', 'users_subareas.users_id', '=', 'users.id')
            ->join('subareas', 'users_subareas.subareas_id', '=', 'subareas.id')
            ->join('areas', 'areas.id', '=', 'subareas.area_id')
            ->select(DB::raw('areas.area_name, count(areas.id) as area_count'))
            ->where('users.type', '=', UserType::STUDENT)
            ->where('users.course_id', '=', 2)
            ->groupBy('areas.area_name')
            ->orderBy('areas.area_name')
            ->get();

        $dataFields = [];
        $dataCount = [];
        for ($counter = 0; $counter < count($data); $counter++) {
            $dataFields[$counter] = $data[$counter]->area_name;
            $dataCount[$counter] = $data[$counter]->area_count;
        }

        return [$dataFields, $dataCount];
    }

    public function subareasDoctorFilter(): array
    {
        $data = DB::table('users')
            ->join('users_subareas', 'users_subareas.users_id', '=', 'users.id')
            ->join('subareas', 'users_subareas.subareas_id', '=', 'subareas.id')
            ->select(DB::raw('subareas.subarea_name, count(users_subareas.subareas_id) as subarea_count'))
            ->where('users.type', '=', UserType::STUDENT)
            ->where('users.course_id', '=', 2)
            ->groupBy('subareas.subarea_name')
            ->get();

        $dataSubfields = [];
        $dataCount = [];
        for ($counter = 0; $counter < count($data); $counter++) {
            $dataSubfields[$counter] = $data[$counter]->subarea_name;
            $dataCount[$counter] = $data[$counter]->subarea_count;
        }

        return [$dataSubfields, $dataCount];
    }


    public function areasActiveFilter(): array
    {
        $data = DB::table('users')
            ->join('users_subareas', 'users_subareas.users_id', '=', 'users.id')
            ->join('subareas', 'users_subareas.subareas_id', '=', 'subareas.id')
            ->join('areas', 'areas.id', '=', 'subareas.area_id')
            ->select(DB::raw('areas.area_name, count(areas.id) as area_count'))
            ->where('users.type', '=', UserType::STUDENT)
            ->where('users.defended_at', '=', null)
            ->groupBy('areas.area_name')
            ->orderBy('areas.area_name')
            ->get();

        $dataFields = [];
        $dataCount = [];
        for ($counter = 0; $counter < count($data); $counter++) {
            $dataFields[$counter] = $data[$counter]->area_name;
            $dataCount[$counter] = $data[$counter]->area_count;
        }

        return [$dataFields, $dataCount];
    }

    public function subareasActiveFilter(): array
    {
        $data = DB::table('users')
            ->join('users_subareas', 'users_subareas.users_id', '=', 'users.id')
            ->join('subareas', 'users_subareas.subareas_id', '=', 'subareas.id')
            ->select(DB::raw('subareas.subarea_name, count(users_subareas.subareas_id) as subarea_count'))
            ->where('users.type', '=', UserType::STUDENT)
            ->where('users.defended_at', '=', null)
            ->groupBy('subareas.subarea_name')
            ->get();

        $dataSubfields = [];
        $dataCount = [];
        for ($counter = 0; $counter < count($data); $counter++) {
            $dataSubfields[$counter] = $data[$counter]->subarea_name;
            $dataCount[$counter] = $data[$counter]->subarea_count;
        }

        return [$dataSubfields, $dataCount];
    }

    public function areasNotActiveFilter(): array
    {
        $data = DB::table('users')
            ->join('users_subareas', 'users_subareas.users_id', '=', 'users.id')
            ->join('subareas', 'users_subareas.subareas_id', '=', 'subareas.id')
            ->join('areas', 'areas.id', '=', 'subareas.area_id')
            ->select(DB::raw('areas.area_name, count(areas.id) as area_count'))
            ->where('users.type', '=', UserType::STUDENT)
            ->where('users.defended_at', '!=', null)
            ->groupBy('areas.area_name')
            ->orderBy('areas.area_name')
            ->get();

        $dataFields = [];
        $dataCount = [];
        for ($counter = 0; $counter < count($data); $counter++) {
            $dataFields[$counter] = $data[$counter]->area_name;
            $dataCount[$counter] = $data[$counter]->area_count;
        }

        return [$dataFields, $dataCount];
    }

    public function subareasNotActiveFilter(): array
    {
        $data = DB::table('users')
            ->join('users_subareas', 'users_subareas.users_id', '=', 'users.id')
            ->join('subareas', 'users_subareas.subareas_id', '=', 'subareas.id')
            ->select(DB::raw('subareas.subarea_name, count(users_subareas.subareas_id) as subarea_count'))
            ->where('users.type', '=', UserType::STUDENT)
            ->where('users.defended_at', '!=', null)
            ->groupBy('subareas.subarea_name')
            ->get();

        $dataFields = [];
        $dataCount = [];
        for ($counter = 0; $counter < count($data); $counter++) {
            $dataSubfields[$counter] = $data[$counter]->subarea_name;
            $dataCount[$counter] = $data[$counter]->subarea_count;
        }

        return [$dataFields, $dataCount];
    }


    public function areasCompletedFilter(): array
    {
        $data = DB::table('users')
            ->join('users_subareas', 'users_subareas.users_id', '=', 'users.id')
            ->join('subareas', 'users_subareas.subareas_id', '=', 'subareas.id')
            ->join('areas', 'areas.id', '=', 'subareas.area_id')
            ->select(DB::raw('areas.area_name, count(areas.id) as area_count'))
            ->where('users.type', '=', UserType::STUDENT)
            ->where('users.defended_at', '!=', null)
            ->groupBy('areas.area_name')
            ->orderBy('areas.area_name')
            ->get();

        $dataFields = [];
        $dataCount = [];
        for ($counter = 0; $counter < count($data); $counter++) {
            $dataFields[$counter] = $data[$counter]->area_name;
            $dataCount[$counter] = $data[$counter]->area_count;
        }

        return [$dataFields, $dataCount];
    }

    public function subareasCompletedFilter(): array
    {
        $data = DB::table('users')
            ->join('users_subareas', 'users_subareas.users_id', '=', 'users.id')
            ->join('subareas', 'users_subareas.subareas_id', '=', 'subareas.id')
            ->select(DB::raw('subareas.subarea_name, count(users_subareas.subareas_id) as subarea_count'))
            ->where('users.type', '=', UserType::STUDENT)
            ->where('users.defended_at', '!=', null)
            ->groupBy('subareas.subarea_name')
            ->get();

        $dataFields = [];
        $dataCount = [];
        for ($counter = 0; $counter < count($data); $counter++) {
            $dataSubfields[$counter] = $data[$counter]->subarea_name;
            $dataCount[$counter] = $data[$counter]->subarea_count;
        }

        return [$dataFields, $dataCount];
    }

    public function sendPasswordResetNotification($token)
    {
        ResetPasswordNotification::createUrlUsing(function (User $user, string $token) {
            return config('app.front_url') . '/reset-password?' .
                http_build_query(['token' => $token, 'email' => $user->getEmailForPasswordReset()]);
        });

        $this->notify(new ResetPasswordNotification($token));
    }

    public function updateLattes(array $data): void
    {
        foreach ($data['productions'] as $production) {
            if (!$production['doi']) {
                continue;
            }
            $this->writerOf()->updateOrCreate(Arr::only($production, ['doi']), $production);
        }
        $this->lattes_updated_at = $data['lattes_updated_at'];
        $this->save();
    }

    protected function checkIfUserAlreadyExist($sigaaId)
    {
        return User::find($sigaaId);
    }

    protected function checkIfUserWasFound($user)
    {
        if (is_null($user)) {
            return 'error';
        }
        return $user;
    }

    public function programs(): BelongsToMany
    {
            die("NOT IMPLEMENTED");
    }

    public function findUserSubareas($id){
        $user = User::findOrFail($id);
        $user['subareas'] = User::where('users.id', '=' , $id)
            ->join('users_subareas', 'users_subareas.users_id', '=', 'users.id')
            ->join('subareas', 'subareas.id', '=', 'users_subareas.subareas_id')
            ->get(['users_subareas.subareas_id', 'subareas.subarea_name']);
        return $user;
    }
}

<?php

namespace App\Console\Commands;

use App\Domain\Sigaa\DefenseScraping;
use App\Domain\Sigaa\StudentScraping;
use App\Domain\Sigaa\TeacherScraping;
use App\Enums\UserRelationType;
use App\Enums\UserType;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SigaaScrapingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scraping:sigaa-scraping {programId=1820 : ID do curso no site SIGAA}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SIGAA Web Scraping';

    /**
     * Execute the console command.
     * @throws Exception
     */
    public function handle(): int
    {
        // @see https://sigaa.ufba.br/sigaa/public/programa/lista.jsf
        $programId = $this->argument('programId');
        if (! is_numeric($programId) || $programId <= 0) {
            $this->error('Informe um ID de curso válido');
            return 1;
        }

        $teacherScraping = new TeacherScraping();
        try {
            $teachers = $teacherScraping->scrapingByProgram((int)$programId);
            Storage::put("teachers-{$programId}.json", json_encode($teachers));
            $this->createOrUpdateTeachers($teachers);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

        $studentScraping = new StudentScraping();
        try {
            $students = $studentScraping->scrapingByProgram((int)$programId);
            Storage::put("students-{$programId}.json", json_encode($students));
            $this->createOrUpdateStudents($students);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

        $defenseScraping = new DefenseScraping();
        try {
            $data = $defenseScraping->scrapingByProgram((int)$programId);
            Storage::put("defenses-{$programId}.json", json_encode($data));
            $this->updateDefended($data);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

        return 0;
    }

    private function createOrUpdateTeachers(array $teachers): void
    {
        foreach ($teachers as $teacher) {
            try {
                $user = User::createOrUpdateTeacher($teacher);
                $this->info("Docente {$user->name} cadastrado/atualizado com sucesso");
            } catch (ValidationException $exception) {
                $msg = "Erro ao cadastrar/atualizar docente {$teacher['name']}: {$exception->getMessage()}";
                $this->error($msg);
                Log::error($msg, $teacher);
            }
        }
    }

    private function createOrUpdateStudents(array $students): void
    {
        foreach ($students as $student) {
            try {
                $user = DB::transaction(function () use ($student) {
                    $user = User::createOrUpdateStudent($student);
                    $allTeachers = $this->getAdvisorIds($student['teachers']);

                    $advisors = Arr::where($allTeachers, function ($i) {
                        return $i['relation_type'] === UserRelationType::ADVISOR->value;
                    });
                    $user->advisors()->sync($advisors);
                    $advisor = $user->advisors()->first();
                    if ($advisor) {
                        $user->area_id = $advisor->area_id;
                        $user->save();
                    }

                    $coAdvisors = Arr::where($allTeachers, function ($i) {
                        return $i['relation_type'] === UserRelationType::CO_ADVISOR->value;
                    });
                    $user->coadvisors()->sync($coAdvisors);

                    return $user;
                });

                $this->info("Discente {$user->name} cadastrado/atualizado com sucesso");
            } catch (ValidationException $exception) {
                $msg = "Erro ao cadastrar/atualizar discente {$student['name']}: {$exception->getMessage()}";
                $this->error($msg);
                Log::error($msg, $student);
            }
        }
    }

    private function getAdvisorIds(array $teachers): array
    {
        $advisorIds = [];
        foreach ($teachers as $teacher) {
            if (empty($teacher['siape'])) {
                continue;
            }
            $teacherModel = User::where('siape', $teacher['siape'])->first();
            if (empty($teacherModel)) {
                $this->warn(
                    "Docente {$teacher['name']} ({$teacher['relation_type']} - {$teacher['siape']}) não encontrado!"
                );
                $teacherModel = User::createOrUpdateTeacher($teacher);
            }
            $advisorIds[$teacherModel->id] = ['relation_type' => $teacher['relation_type']];
        }

        return $advisorIds;
    }

    private function updateDefended(array $data)
    {
        foreach ($data as $item) {
            $user = User::where('type', UserType::STUDENT->value)
                ->where('name', $item['student'])
                ->first();

            $teacher = User::where('type', UserType::PROFESSOR->value)
                ->where('name', $item['teacher'])
                ->first(['id']);

            if ($user) {
                $advisor = $user->advisors()->first();
                if ($advisor) {
                    $user->area_id = $advisor->area_id;
                }
                $user->defended_at = $item['date'];
                $user->save();
                $this->info("{$user->name} defendeu.");
            } else {
                $registration = User::whereNotNull('registration')
                    ->orderBy('registration')
                    ->first(['registration'])
                    ->registration;

                $user = User::createOrUpdateStudent([
                    'registration' => $registration -1,
                    'name' => $item['student'],
                    'course_id' => $item['course_id'],
                ]);
            }
            if ($teacher && $user->advisors()->where('id', $teacher->id)->doesntExist()) {
                $user->advisors()->attach([$teacher->id => ['relation_type' => 'advisor']]);
            }
        }
    }
}

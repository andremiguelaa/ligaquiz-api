<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\User;
use Storage;
use Image;

class ImportUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import users from old app';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $startTime = microtime(true);
        $oldUsers = DB::connection('mysql_old')->table('users')->get();
        User::query()->truncate();
        foreach ($oldUsers as $user) {
            $roles = [];
            if ($user->role === 2) {
                $roles['admin'] = true;
            }
            if ($user->id === 3 || $user->id === 9) { // Paulo and Sofia
                $roles['quiz_editor'] = true;
                $roles['answer_reviewer'] = true;
            }
            if ($user->id === 8) { // Jorge
                $roles['national_ranking_manager'] = true;
            }
            if ($user->role === 1) {
                $roles['special_quiz_editor'] = true;
            }
            if (!$user->active) {
                $roles['blocked'] = true;
            }
            if ($user->role !== 2) {
                $roles['regular_player'] = $user->subscription;
            }
            if ($user->avatar) {
                $url = 'https://ligaquiz.pt/files/user_'.$user->id.'.jpg';
                $image = file_get_contents($url);
                $avatar = 'avatar'.$user->id.'.jpg';
                Image::make($image)->fit(200, 200, function ($constraint) {
                    $constraint->upsize();
                })->save(storage_path('app/public/avatars/' . $avatar));
            } else {
                $avatar = null;
            }
            $reminders = [
                'quiz' => [
                    'daily' => boolval($user->daily_reminder),
                    'deadline' => boolval($user->deadline_reminder)
                ],
                'special_quiz' => [
                    'daily' => boolval($user->daily_reminder),
                    'deadline' => boolval($user->deadline_reminder)
                ],
            ];
            User::create([
                'id' => $user->id,
                'email' => trim($user->email),
                'name' => trim($user->name),
                'surname' => trim($user->surname),
                'password' => $user->password,
                'roles' => $roles,
                'avatar' => $avatar,
                'reminders' => $reminders,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at
            ]);
            $this->line(
                '<fg=green>Imported:</> <fg=yellow>'.$user->id.'</> <fg=red>=></> '
                    .trim($user->name).' '.trim($user->surname).' ('.trim($user->email).')'
            );
        }
        $endTime = microtime(true);
        $timeDiff = $endTime - $startTime;
        $this->line('');
        $this->line(
            '<fg=green>Success:</> <fg=yellow>'
            .$oldUsers->count().' users imported ('.abs(round($timeDiff*100))/100
            .'s)</>'
        );
        $this->line('');
    }
}

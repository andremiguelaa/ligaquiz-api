<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\User;

class SyncUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync users from old app';

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
        $oldUsers = DB::connection('mysql_old')->table('users')->get();
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
                $roles['answer_reviewer'] = true;
            }
            if (!$user->active) {
                $roles['blocked'] = true;
            }
            if ($user->role !== 2) {
                $roles['regular_player'] = $user->subscription;
            }
            $avatar = $user->avatar ? 'avatar'.$user->id.'.jpg' : null;
            // todo: get and store image
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
            User::updateOrCreate(
                ['id' => $user->id],
                [
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
            ]
            );
            $this->line(
                '<fg=green>Synced:</> <fg=yellow>'.$user->id.'</> <fg=red>=></> '
                    .trim($user->name).' '.trim($user->surname).' ('.trim($user->email).')'
            );
        }
        $this->line('');
        $this->line('<fg=green>Success:</> <fg=yellow>'.$oldUsers->count().' users synced</>');
        $this->line('');
    }
}

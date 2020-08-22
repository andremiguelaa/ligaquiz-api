<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Question;
use App\Media;
use Storage;

class CleanMedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:media';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean not used media files';

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
        $questionMediaIds = Question::whereNotNull('media_id')
            ->select('media_id')
            ->get()
            ->pluck('media_id')
            ->toArray();
        $weekAgo = Carbon::now()->addDays(-7);
        $nonUsedMedia = Media::whereNotIn('id', $questionMediaIds)
            ->where('created_at', '<', $weekAgo)
            ->get();
        foreach ($nonUsedMedia as $file) {
            Storage::delete($file->filename);
            $file->delete();
        }
    }
}

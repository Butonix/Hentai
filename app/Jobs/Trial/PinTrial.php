<?php

namespace App\Jobs\Trial;

use App\Http\Modules\RichContentService;
use App\Jobs\Job;
use App\Models\Pin;

class PinTrial extends Job
{
    protected $id;
    /**
     * 0 帖子创建
     */
    protected $type;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id, $type)
    {
        $this->id = $id;
        $this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $pin = Pin::find($this->id);
        if (is_null($pin))
        {
            return;
        }

        $content = $pin->content()->first();

        $richContentService = new RichContentService();

        $risk = $richContentService->detectContentRisk($content->text);

        if ($risk['risk_score'] > 0)
        {
            $pin->deletePin(2);
        }
        else if ($risk['use_review'] > 0)
        {
            $pin->reviewPin(1);
        }
        else
        {
            $pin->reflowPin();
        }

        return;
    }
}
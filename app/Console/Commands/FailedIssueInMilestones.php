<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Vinkla\GitLab\Facades\GitLab;
use Gitlab\ResultPager;

class FailedIssueInMilestones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'milestone:failed {project}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'list failed issues in milestones.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->pager = new ResultPager(app('gitlab.connection'));
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $projectId = $this->argument('project');

        $todoLabels = ['Todo', 'Doing', 'Done'];
        $taskLabels = ['跟进', '开发', '设计', '联调', '提测', '上线', '其他', '技术调研'];

        foreach ($this->pager->fetchall(
            Gitlab::api('milestones'),
            'all',
            [
                $projectId,
                [
                    'per_page' => 100,
                ]
            ]
        ) as $milestone) {

            $issues = $this->pager->fetchall(
                Gitlab::api('milestones'),
                'issues',
                [
                    $projectId,
                    $milestone['id'],
                    [
                        'per_page' => 100,
                    ]
                ]
            );

            foreach ($issues as $issue) {

                $todoStatus = false;
                $taskStatus = false;

                foreach ($issue['labels'] as $label)  {
                    if (! $todoStatus) {
                        $todoStatus = in_array($label, $todoLabels)?true:false;
                    }
                    if (! $taskStatus) {
                        $taskStatus = in_array($label, $taskLabels)?true:false;
                    }
                }

                if (!$todoStatus || !$taskStatus) {
                    $todoMessage = !$todoStatus? implode(' Or ', $todoLabels):'';
                    $taskMessage = !$taskStatus? implode(' Or ', $taskLabels):'';

                    $message = sprintf("link:%s,add %s", $issue['web_url'], implode('', [$todoMessage, $taskMessage]));
                    $this->info($message);
                }

            }
        }


    }
}

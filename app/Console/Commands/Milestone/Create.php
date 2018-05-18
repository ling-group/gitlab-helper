<?php

namespace App\Console\Commands\Milestone;

use Illuminate\Console\Command;
use Vinkla\GitLab\Facades\GitLab;

class Create extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'milestone:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create a milestone';

    protected $titles = [
		'luka server' => 'From %s To %s Luka Server Task',
		'luka server meeting' => 'From %s To %s Luka Server Meeting',
	];


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

		$projectId = $this->ask('请输入项目ID');
        foreach (range(1, 10) as $k => $day) {
			$choices[$k] = date('Y-m-d 周N', strtotime(sprintf("%s day", $day)));
		}
        $daySelected = $this->choice('选择ID', $choices);
        $daySelectedIndex = array_search($daySelected, $choices);

        $start_date = date('Y年m月d日', strtotime(sprintf('%s day', $daySelectedIndex)));
        $due_date = date('Y年m月d日', strtotime(sprintf('%s day', $daySelectedIndex+4)));

        $titleSelected = $this->choice('select titles', array_keys($this->titles));

        $data = [
            'title' => sprintf($this->titles[$titleSelected], $start_date, $due_date),
            'due_date' => $start_date,
            'start_date' => $due_date,
        ];

        $this->info(json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        if ($this->confirm('Do you wish to continue?')) {
            Gitlab::api('milestones')->create($projectId, $data);
        }

	}
}

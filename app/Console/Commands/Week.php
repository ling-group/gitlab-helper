<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Vinkla\GitLab\Facades\GitLab;
use Gitlab\ResultPager;
use GuzzleHttp\Client;
use App\Console\Commands\DingTalk\Markdown;
use GuzzleHttp\Psr7\Request;
use Exception;

class Week extends Command
{
    protected $projectColor = '#AD8D43';
    protected $todoListColor = '#0033CC';
    protected $taskStatusColor = '#5CB85C';

    const PROJECT_ID = 907;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'week:log';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'week log';

    protected $pager;

    protected $client;

    protected $doingStatus = '进行中';

    protected $exceptionIssue;

    public function __construct()
    {
        parent::__construct();

        $this->guzzleClient = new Client;
        $this->mobiles = json_decode(env('MOBILES'), true);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $this->pager = new ResultPager($this->client = app('gitlab.connection'));

            $labels = $this->getProjectLabels(self::PROJECT_ID);
            $projectLabels = $this->filterLabels($labels, $this->projectColor);
            $todoListLabels = $this->filterLabels($labels, $this->todoListColor);
            $taskStatusLabels = $this->filterLabels($labels, $this->taskStatusColor);
            $milestones = $this->getProjectActiveMilestones(self::PROJECT_ID);
            $messages = [];
            foreach ($milestones as $milestone) {
                $issues = $this->getProjectIssuesInMilestone($milestone['id'], self::PROJECT_ID);
                foreach ($issues as $issue) {
                    $todoListLabel = $this->getTodoListStatusForIssue($issue, $todoListLabels);
                    $projectLabel = $this->getProjectStatusForIssue($issue, $projectLabels);
                    foreach ($projectLabel as $project) {
                        if (!in_array($project, ['CMS', 'Luka API', 'Account API'])) {
                            $project = '其他';
                        }
                        $messages[$milestone['title']][$project][$todoListLabel][] = $this->dealSingleIssue($issue, $taskStatusLabels, $todoListLabel == 'Doing');
                    }
                }
            }

            foreach ($messages as $week => $items) {
                $this->info($week);
                foreach ($items as $project => $item) {
                    $this->info("\t".$project);
                    foreach ($item as $k => $vs) {
                        foreach (array_flip(array_flip($vs)) as $v) {
                            $this->info("\t\t". $v);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            if ($this->exceptionIssue && $this->exceptionIssue['assignee']) {
                $mobile = $this->mobiles[$this->exceptionIssue['assignee']['username']]??"";
                $markdown = $this->buildMessage(
                    $this->exceptionIssue['title'],
                    $this->exceptionIssue['web_url'],
                    $mobile
                );
                $this->send($markdown);
            }
        }
    }

    protected function getProjectStatusForIssue($issue, $projectLabels)
    {
        $projects = [];
        foreach ($issue['labels'] as $label) {
            if (isset($projectLabels[$label])) {
                $projects[] = $label;
            }
        }

        if (! $projects) {
            $this->exceptionIssue = $issue;

            throw new \Exception(sprintf('The issue %s, project\'s labels is empty', $issue['iid']));
        }

        return $projects;
    }

    protected function getTodoListStatusForIssue($issue, $todoListLabels)
    {
        foreach ($issue['labels'] as $label) {
            if (isset($todoListLabels[$label])) {
                return $label;
            }
        }
        $this->exceptionIssue = $issue;

        throw new \Exception(sprintf('the issue:%d, please set todo label', $issue['iid']));
    }

    protected function dealSingleIssue($issue, $taskStatusLabels, $isDoing = false)
    {
        $taskStatus = [];
        foreach ($issue['labels'] as $label) {
            if (isset($taskStatusLabels[$label])) {
                $taskStatus[] = $label;
            }
        }
        $assignee = $issue['assignee']['username'];
        $issueId = $issue['iid'];
        $title = $issue['title'];

        return $this->printTask($isDoing?$this->doingStatus:implode(',', $taskStatus), $title, $issueId, $assignee);

    }

    protected function printTask($taskStatus, $title, $issueId, $assignee)
    {
        return sprintf("(%s) %s (#%d) @%s", $taskStatus, $title, $issueId, $assignee);
    }

    protected function filterLabels($labels, $color)
    {
        $results = [];
        foreach ($labels as $label) {
            if ($label['color'] == $color) {
                $results[$label['name']] = $label;
            }
        }

        return $results;
    }

    protected function getProjectLabels($projectId)
    {
        return $this->pager->fetchall(
            $this->client->api('projects'),
            'labels',
            [
                $projectId,
                [
                    'per_page' => '100'
                ]
            ]
        );
    }

    protected function getProjectActiveMilestones($projectId)
    {
        return GitLab::api('milestones')->all($projectId, ['state' => 'active']);
    }

    protected function getProjectIssuesInMilestone($milestoneId, $projectId)
    {
        return Gitlab::api('milestones')->issues($projectId, $milestoneId);
    }

    protected function buildMessage($title, $messageUrl, $mobile)
    {

        $lnk = new Markdown;
        $lnk->title = $title;
        $lnk->text = "@".$mobile;
        $lnk->messageUrl = $messageUrl;
        $lnk->atMobiles = $mobile;

        return $lnk;
    }

    protected function send($message)
    {
        $this->info($message);
        $request = new Request('POST', env('DINGDING_URL'), ['Content-Type' => 'application/json'], $message);
        $this->guzzleClient->send($request);
    }

}


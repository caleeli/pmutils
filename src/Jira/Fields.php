<?php

namespace David\PmUtils\Jira;

class Fields
{
    public $resolution;
    public ?Issue $parent;
    public $labels;
    public ?Assignee $assignee;
    public Status $status;
    public ?Project $project;
    public $summary;
    public IssueType $issueType;
    public Priority $priority;
    public $created;
    public $updated;
    public ?Reporter $reporter;
    public $timeoriginalestimate = 0;

    public function __construct($data)
    {
        foreach($data as $key => $value) {
            if ($key === 'parent') {
                continue;
            }
            if (substr($key, 0, 12) === 'customfield_' && empty($value)) {
                continue;
            }
            try {
                $value = new $key($value);
            } catch (\Throwable $e) {
                // do nothing
            }
        }
        $this->parent = isset($data['parent']) ? new Issue($data['parent']) : null;
        $this->resolution = $data['resolution'] ?? null;
        $this->labels = $data['labels'] ?? [];
        $this->assignee = isset($data['assignee']) ? new Assignee($data['assignee']) : null;
        $this->status = isset($data['status']) ? new Status($data['status']) : null;
        $this->project = isset($data['project']) ? new Project($data['project']) : null;
        $this->summary = $data['summary'] ?? null;
        $this->issueType = isset($data['issuetype']) ? new IssueType($data['issuetype']) : null;
        $this->priority = isset($data['priority']) ? new Priority($data['priority']) : null;
        $this->created = $data['created'] ?? null;
        $this->updated = $data['updated'] ?? null;
        $this->reporter = isset($data['reporter']) ? new Reporter($data['reporter']) : null;
        $this->timeoriginalestimate = $data['timeoriginalestimate'] ?? 0;
    }
}

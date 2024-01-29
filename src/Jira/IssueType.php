<?php

namespace David\PmUtils\Jira;

class IssueType {
    public $self;
    public $id;
    public $description;
    public $iconUrl;
    public $name;
    public $subtask;
    public $avatarId;

    public function __construct($data) {
        $this->self = $data['self'] ?? null;
        $this->id = $data['id'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->iconUrl = $data['iconUrl'] ?? null;
        $this->name = $data['name'] ?? null;
        $this->subtask = $data['subtask'] ?? null;
        $this->avatarId = $data['avatarId'] ?? null;
    }
}

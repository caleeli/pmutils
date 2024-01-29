<?php

namespace David\PmUtils\Jira;

class Project {
    public $self;
    public $id;
    public $key;
    public $name;
    public $projectTypeKey;
    public $simplified;
    public $avatarUrls;

    public function __construct($data) {
        $this->self = $data['self'] ?? null;
        $this->id = $data['id'] ?? null;
        $this->key = $data['key'] ?? null;
        $this->name = $data['name'] ?? null;
        $this->projectTypeKey = $data['projectTypeKey'] ?? null;
        $this->simplified = $data['simplified'] ?? null;
        $this->avatarUrls = $data['avatarUrls'] ?? [];
    }
}

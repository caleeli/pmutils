<?php

namespace David\PmUtils\Jira;

class Status {
    public $self;
    public $description;
    public $iconUrl;
    public $name;
    public $id;
    public $statusCategory;

    public function __construct($data) {
        $this->self = $data['self'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->iconUrl = $data['iconUrl'] ?? null;
        $this->name = $data['name'] ?? null;
        $this->id = $data['id'] ?? null;
        $this->statusCategory = isset($data['statusCategory']) ? new StatusCategory($data['statusCategory']) : null;
    }
}

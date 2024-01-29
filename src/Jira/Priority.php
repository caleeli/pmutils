<?php

namespace David\PmUtils\Jira;

class Priority {
    public $self;
    public $iconUrl;
    public $name;
    public $id;

    public function __construct($data) {
        $this->self = $data['self'] ?? null;
        $this->iconUrl = $data['iconUrl'] ?? null;
        $this->name = $data['name'] ?? null;
        $this->id = $data['id'] ?? null;
    }
}

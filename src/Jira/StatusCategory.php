<?php

namespace David\PmUtils\Jira;

class StatusCategory {
    public $self;
    public $id;
    public $key;
    public $colorName;
    public $name;

    public function __construct($data) {
        $this->self = $data['self'] ?? null;
        $this->id = $data['id'] ?? null;
        $this->key = $data['key'] ?? null;
        $this->colorName = $data['colorName'] ?? null;
        $this->name = $data['name'] ?? null;
    }
}

<?php

namespace David\PmUtils\Jira;

class Issue {
    public $expand;
    public $id;
    public $self;
    public $key;
    public $fields;

    public function __construct($data) {
        $this->expand = $data['expand'] ?? null;
        $this->id = $data['id'] ?? null;
        $this->self = $data['self'] ?? null;
        $this->key = $data['key'] ?? null;
        $this->fields = new Fields($data['fields'] ?? []);
    }
}

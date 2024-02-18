<?php

namespace David\PmUtils\Jira;

class IssueLinkType
{
    public $id;
    public $name;
    public $inward;
    public $outward;

    public function __construct(array $type)
    {
        $this->id = $type['id'];
        $this->name = $type['name'];
        $this->inward = $type['inward'];
        $this->outward = $type['outward'];
    }
}

<?php

namespace EventSmart\Multisite\core\services\database\service;

use EED_Batch;

abstract class DbServiceJobInfo
{
    abstract public function description(): string;


    abstract public function name(): string;


    abstract public function dbOptionName(): string;


    abstract public function product(): string;


    abstract public function version(): string;


    public function fullName(): string
    {
        return "{$this->product()} {$this->version()} {$this->name()}";
    }


    public function optionValue(): string
    {
        return "{$this->fullName()} - {$this->description()}";
    }


    public function code()
    {
        return substr(md5($this->fullName()), 0, 12);
    }


    public function prepForRequest(): array
    {
        return [
            'page'           => EED_Batch::PAGE_SLUG,
            'batch'          => EED_Batch::batch_job,
            'job_code'       => $this->code(),
            'job_name'       => $this->fullName(),
            'db_option_name' => $this->dbOptionName(),
        ];
    }
}

<?php
namespace Appercode\Helpers\Schema;

use Appercode\Schema;

class ViewData
{
    private $schema;

    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    public function getFieldSettings(string $field)
    {
        return isset($this->schema->viewData['fieldOptions'][$field]) ? $this->schema->viewData['fieldOptions'][$field] : null;
    }

    public function getFieldSettingValue(string $field, string $name)
    {
        $settings = $this->getFieldSettings($field);
        return (! is_null($settings) && isset($settings[$name])) ? $settings[$name] : null;
    }
}

<?php

namespace haiderjabbar\laravelsolr\Schema;

class SolrSchemaBuilder
{
    protected $fields = [];
    protected $currentField = null;

    public function name($name)
    {
        // Save the previous field if it exists
        if ($this->currentField) {
            $this->fields[] = $this->currentField;
        }

        // Start a new field definition
        $this->currentField = ['name' => $name];
        return $this;
    }

    public function type($type)
    {
        $this->currentField['type'] = $type;
        return $this;
    }

    public function required($required = true)
    {
        $this->currentField['required'] = $required;
        return $this;
    }

    public function indexed($indexed = true)
    {
        $this->currentField['indexed'] = $indexed;
        return $this;
    }

    public function stored($stored = true)
    {
        $this->currentField['stored'] = $stored;

        return $this;
    }

    public function multiValued($multiValued = true)
    {
        $this->currentField['multiValued'] = $multiValued;
        return $this;
    }

    public function getFields()
    {
        // Add the last field to fields before returning, if any
        if ($this->currentField) {
            $this->fields[] = $this->currentField;
            $this->currentField = null;
        }
        return $this->fields;
    }
}

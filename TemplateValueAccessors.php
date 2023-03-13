<?php

namespace Core;


trait TemplateValueAccessors
{

    /**
     * Variables are processed last and only if they are not in nested ${...} tags
     * Macros are meant for nesting tags.
     * Use variables for user input.
     */
    private array $variables = [];
    private array $macros = [];


    public function setValue(string $name, string $value)
    {
        $this->variables[$name] = $value;
    }

    public function setValues(array $variables)
    {
        foreach ($variables as $name => $value){
            $this->variables[$name] = $value;
        }
    }

    public function getValue(string $name)
    {
        if(isset($this->variables[$name]))
            return $this->variables[$name];
        return null;
    }

    public function getValues() : array
    {
        return $this->variables;
    }


    public function setMacro(string $name, string $value)
    {
        $this->macros[$name] = $value;
    }

    public function getMacro(string $name)
    {
        if(isset($this->macros[$name]))
            return $this->macros[$name];
        return null;
    }

    public function getMacros() : array
    {
        return $this->macros;
    }

}


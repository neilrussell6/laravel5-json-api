<?php namespace Neilrussell6\Laravel5JsonApi\Traits;

trait Validatable {

    public $messages = [
        'required' => 'The :attribute field is required.',
        'email' => 'A valid email is required for :attribute field.',
        'unique' => ':attribute field must be unique.',
        'min' => ':attribute field must be at least :min character in length.',
        'regex' => ':attribute field must be a valid password.',
        'confirmed' => ':attribute requires additional matching confirmation field.',
    ];
}
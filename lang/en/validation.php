<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'The :name :type must be accepted.',
    'accepted_if' => 'The :name :type must be accepted when :other is :value.',
    'active_url' => 'The :name :type is not a valid URL.',
    'after' => 'The :name :type must be a date after :date.',
    'after_or_equal' => 'The :name :type must be a date after or equal to :date.',
    'alpha' => 'The :name :type must only contain letters.',
    'alpha_dash' => 'The :name :type must only contain letters, numbers, dashes and underscores.',
    'alpha_num' => 'The :name :type must only contain letters and numbers.',
    'array' => 'The :name :type must be an array.',
    'before' => 'The :name :type must be a date before :date.',
    'before_or_equal' => 'The :name :type must be a date before or equal to :date.',
    'between' => [
        'array' => 'The :name :type must have between :min and :max items.',
        'file' => 'The :name :type must be between :min and :max kilobytes.',
        'numeric' => 'The :name :type must be between :min and :max.',
        'string' => 'The :name :type must be between :min and :max characters.',
    ],
    'boolean' => 'The :name :type must be true or false.',
    'confirmed' => 'The :name :type confirmation does not match.',
    'current_password' => 'The password is incorrect.',
    'date' => 'The :name :type is not a valid date.',
    'date_equals' => 'The :name :type must be a date equal to :date.',
    'date_format' => 'The :name :type does not match the format :format.',
    'declined' => 'The :name :type must be declined.',
    'declined_if' => 'The :name :type must be declined when :other is :value.',
    'different' => 'The :name :type and :other must be different.',
    'digits' => 'The :name :type must be :digits digits.',
    'digits_between' => 'The :name :type must be between :min and :max digits.',
    'dimensions' => 'The :name :type has invalid image dimensions.',
    'distinct' => 'The :name :type has a duplicate value.',
    'doesnt_end_with' => 'The :name :type may not end with one of the following: :values.',
    'doesnt_start_with' => 'The :name :type may not start with one of the following: :values.',
    'email' => 'The :name :type must be a valid email address.',
    'ends_with' => 'The :name :type must end with one of the following: :values.',
    'enum' => 'The selected :name :type is invalid.',
    'exists' => 'The selected :name :type is invalid.',
    'file' => 'The :name :type must be a file.',
    'filled' => 'The :name :type must have a value.',
    'gt' => [
        'array' => 'The :name :type must have more than :value items.',
        'file' => 'The :name :type must be greater than :value kilobytes.',
        'numeric' => 'The :name :type must be greater than :value.',
        'string' => 'The :name :type must be greater than :value characters.',
    ],
    'gte' => [
        'array' => 'The :name :type must have :value items or more.',
        'file' => 'The :name :type must be greater than or equal to :value kilobytes.',
        'numeric' => 'The :name :type must be greater than or equal to :value.',
        'string' => 'The :name :type must be greater than or equal to :value characters.',
    ],
    'image' => 'The :name :type must be an image.',
    'in' => 'The selected :name :type is invalid.',
    'in_array' => 'The :name :type does not exist in :other.',
    'integer' => 'The :name :type must be an integer.',
    'ip' => 'The :name :type must be a valid IP address.',
    'ipv4' => 'The :name :type must be a valid IPv4 address.',
    'ipv6' => 'The :name :type must be a valid IPv6 address.',
    'json' => 'The :name :type must be a valid JSON string.',
    'lt' => [
        'array' => 'The :name :type must have less than :value items.',
        'file' => 'The :name :type must be less than :value kilobytes.',
        'numeric' => 'The :name :type must be less than :value.',
        'string' => 'The :name :type must be less than :value characters.',
    ],
    'lte' => [
        'array' => 'The :name :type must not have more than :value items.',
        'file' => 'The :name :type must be less than or equal to :value kilobytes.',
        'numeric' => 'The :name :type must be less than or equal to :value.',
        'string' => 'The :name :type must be less than or equal to :value characters.',
    ],
    'mac_address' => 'The :name :type must be a valid MAC address.',
    'max' => [
        'array' => 'The :name :type must not have more than :max items.',
        'file' => 'The :name :type must not be greater than :max kilobytes.',
        'numeric' => 'The :name :type must not be greater than :max.',
        'string' => 'The :name :type must not be greater than :max characters.',
    ],
    'max_digits' => 'The :name :type must not have more than :max digits.',
    'mimes' => 'The :name :type must be a file of type: :values.',
    'mimetypes' => 'The :name :type must be a file of type: :values.',
    'min' => [
        'array' => 'The :name :type must have at least :min items.',
        'file' => 'The :name :type must be at least :min kilobytes.',
        'numeric' => 'The :name :type must be at least :min.',
        'string' => 'The :name :type must be at least :min characters.',
    ],
    'min_digits' => 'The :name :type must have at least :min digits.',
    'multiple_of' => 'The :name :type must be a multiple of :value.',
    'not_in' => 'The selected :name :type is invalid.',
    'not_regex' => 'The :name :type format is invalid.',
    'numeric' => 'The :name :type must be a number.',
    'password' => [
        'letters' => 'The :name :type must contain at least one letter.',
        'mixed' => 'The :name :type must contain at least one uppercase and one lowercase letter.',
        'numbers' => 'The :name :type must contain at least one number.',
        'symbols' => 'The :name :type must contain at least one symbol.',
        'uncompromised' => 'The given :name :type has appeared in a data leak. Please choose a different :name :type.',
    ],
    'present' => 'The :name :type must be present.',
    'prohibited' => 'The :name :type is prohibited.',
    'prohibited_if' => 'The :name :type is prohibited when :other is :value.',
    'prohibited_unless' => 'The :name :type is prohibited unless :other is in :values.',
    'prohibits' => 'The :name :type prohibits :other from being present.',
    'regex' => 'The :name :type format is invalid.',
    'required' => 'The :name :type is required.',
    'required_array_keys' => 'The :name :type must contain entries for: :values.',
    'required_if' => 'The :name :type is required when :other is :value.',
    'required_if_accepted' => 'The :name :type is required when :other is accepted.',
    'required_unless' => 'The :name :type is required unless :other is in :values.',
    'required_with' => 'The :name :type is required when :values is present.',
    'required_with_all' => 'The :name :type is required when :values are present.',
    'required_without' => 'The :name :type is required when :values is not present.',
    'required_without_all' => 'The :name :type is required when none of :values are present.',
    'same' => 'The :name :type and :other must match.',
    'size' => [
        'array' => 'The :name :type must contain :size items.',
        'file' => 'The :name :type must be :size kilobytes.',
        'numeric' => 'The :name :type must be :size.',
        'string' => 'The :name :type must be :size characters.',
    ],
    'starts_with' => 'The :name :type must start with one of the following: :values.',
    'string' => 'The :name :type must be a string.',
    'timezone' => 'The :name :type must be a valid timezone.',
    'unique' => 'The :name :type has already been taken.',
    'uploaded' => 'The :name :type failed to upload.',
    'url' => 'The :name :type must be a valid URL.',
    'uuid' => 'The :name :type must be a valid UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

];

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

    'accepted' => ':attribute 항목에 동의해야 해요.',
    'accepted_if' => ':other이(가) :value일 때 :attribute 항목에 동의해야 해요.',
    'active_url' => ':attribute 항목은 올바른 URL이어야 해요.',
    'after' => ':attribute 항목은 :date 이후의 날짜여야 해요.',
    'after_or_equal' => ':attribute 항목은 :date 이후이거나 같은 날짜여야 해요.',
    'alpha' => ':attribute 항목에는 문자만 입력할 수 있어요.',
    'alpha_dash' => ':attribute 항목에는 문자, 숫자, 대시, 밑줄만 입력할 수 있어요.',
    'alpha_num' => ':attribute 항목에는 문자와 숫자만 입력할 수 있어요.',
    'any_of' => ':attribute 항목이 올바르지 않아요.',
    'array' => ':attribute 항목은 배열이어야 해요.',
    'array_keys' => ':attribute 항목에는 다음 키만 포함할 수 있어요: :values.',
    'ascii' => ':attribute 항목에는 1바이트 영숫자와 기호만 입력할 수 있어요.',
    'base64' => ':attribute 항목은 올바른 Base64 문자열이어야 해요.',
    'before' => ':attribute 항목은 :date 이전의 날짜여야 해요.',
    'before_or_equal' => ':attribute 항목은 :date 이전이거나 같은 날짜여야 해요.',
    'between' => [
        'array' => ':attribute 항목은 :min개에서 :max개 사이여야 해요.',
        'file' => ':attribute 항목은 :min킬로바이트에서 :max킬로바이트 사이여야 해요.',
        'numeric' => ':attribute 항목은 :min에서 :max 사이여야 해요.',
        'string' => ':attribute 항목은 :min자에서 :max자 사이여야 해요.',
    ],
    'boolean' => ':attribute 항목은 참 또는 거짓이어야 해요.',
    'can' => ':attribute 항목에 허용되지 않은 값이 있어요.',
    'confirmed' => ':attribute 항목 확인이 일치하지 않아요.',
    'contains' => ':attribute 항목에 필수 값이 없어요.',
    'current_password' => '비밀번호가 올바르지 않아요.',
    'date' => ':attribute 항목은 올바른 날짜여야 해요.',
    'date_equals' => ':attribute 항목은 :date와 같은 날짜여야 해요.',
    'date_format' => ':attribute 항목이 :format 형식과 일치해야 해요.',
    'decimal' => ':attribute 항목은 소수점 이하 :decimal자리여야 해요.',
    'declined' => ':attribute 항목은 거부되어야 해요.',
    'declined_if' => ':other이(가) :value일 때 :attribute 항목은 거부되어야 해요.',
    'different' => ':attribute 항목과 :other은(는) 서로 달라야 해요.',
    'digits' => ':attribute 항목은 :digits자리 숫자여야 해요.',
    'digits_between' => ':attribute 항목은 :min자리에서 :max자리 사이의 숫자여야 해요.',
    'dimensions' => ':attribute 항목의 이미지 크기가 올바르지 않아요.',
    'distinct' => ':attribute 항목에 중복된 값이 있어요.',
    'doesnt_contain' => ':attribute 항목에는 다음 값을 포함할 수 없어요: :values.',
    'doesnt_end_with' => ':attribute 항목은 다음 중 하나로 끝나면 안 돼요: :values.',
    'doesnt_start_with' => ':attribute 항목은 다음 중 하나로 시작하면 안 돼요: :values.',
    'email' => ':attribute 항목은 올바른 이메일 주소여야 해요.',
    'encoding' => ':attribute 항목은 :encoding 인코딩이어야 해요.',
    'ends_with' => ':attribute 항목은 다음 중 하나로 끝나야 해요: :values.',
    'enum' => '선택한 :attribute 값이 올바르지 않아요.',
    'exists' => '선택한 :attribute 값이 올바르지 않아요.',
    'extensions' => ':attribute 항목은 다음 확장자 중 하나여야 해요: :values.',
    'file' => ':attribute 항목은 파일이어야 해요.',
    'filled' => ':attribute 항목에 값을 입력해야 해요.',
    'gt' => [
        'array' => ':attribute 항목은 :value개보다 많아야 해요.',
        'file' => ':attribute 항목은 :value킬로바이트보다 커야 해요.',
        'numeric' => ':attribute 항목은 :value보다 커야 해요.',
        'string' => ':attribute 항목은 :value자보다 길어야 해요.',
    ],
    'gte' => [
        'array' => ':attribute 항목은 :value개 이상이어야 해요.',
        'file' => ':attribute 항목은 :value킬로바이트 이상이어야 해요.',
        'numeric' => ':attribute 항목은 :value 이상이어야 해요.',
        'string' => ':attribute 항목은 :value자 이상이어야 해요.',
    ],
    'hex_color' => ':attribute 항목은 올바른 16진수 색상이어야 해요.',
    'image' => ':attribute 항목은 이미지여야 해요.',
    'in' => '선택한 :attribute 값이 올바르지 않아요.',
    'in_array' => ':attribute 항목은 :other에 있어야 해요.',
    'in_array_keys' => ':attribute 항목에는 다음 키 중 하나 이상이 포함되어야 해요: :values.',
    'integer' => ':attribute 항목은 정수여야 해요.',
    'ip' => ':attribute 항목은 올바른 IP 주소여야 해요.',
    'ipv4' => ':attribute 항목은 올바른 IPv4 주소여야 해요.',
    'ipv6' => ':attribute 항목은 올바른 IPv6 주소여야 해요.',
    'json' => ':attribute 항목은 올바른 JSON 문자열이어야 해요.',
    'list' => ':attribute 항목은 목록이어야 해요.',
    'lowercase' => ':attribute 항목은 소문자여야 해요.',
    'lt' => [
        'array' => ':attribute 항목은 :value개보다 적어야 해요.',
        'file' => ':attribute 항목은 :value킬로바이트보다 작아야 해요.',
        'numeric' => ':attribute 항목은 :value보다 작아야 해요.',
        'string' => ':attribute 항목은 :value자보다 짧아야 해요.',
    ],
    'lte' => [
        'array' => ':attribute 항목은 :value개를 넘을 수 없어요.',
        'file' => ':attribute 항목은 :value킬로바이트 이하여야 해요.',
        'numeric' => ':attribute 항목은 :value 이하여야 해요.',
        'string' => ':attribute 항목은 :value자 이하여야 해요.',
    ],
    'mac_address' => ':attribute 항목은 올바른 MAC 주소여야 해요.',
    'max' => [
        'array' => ':attribute 항목은 :max개를 넘을 수 없어요.',
        'file' => ':attribute 항목은 :max킬로바이트를 넘을 수 없어요.',
        'numeric' => ':attribute 항목은 :max을(를) 넘을 수 없어요.',
        'string' => ':attribute 항목은 :max자를 넘을 수 없어요.',
    ],
    'max_digits' => ':attribute 항목은 :max자리를 넘을 수 없어요.',
    'mimes' => ':attribute 항목은 다음 형식의 파일이어야 해요: :values.',
    'mimetypes' => ':attribute 항목은 다음 형식의 파일이어야 해요: :values.',
    'min' => [
        'array' => ':attribute 항목은 최소 :min개여야 해요.',
        'file' => ':attribute 항목은 최소 :min킬로바이트여야 해요.',
        'numeric' => ':attribute 항목은 최소 :min이어야 해요.',
        'string' => ':attribute 항목은 최소 :min자여야 해요.',
    ],
    'min_digits' => ':attribute 항목은 최소 :min자리여야 해요.',
    'missing' => ':attribute 항목이 없어야 해요.',
    'missing_if' => ':other이(가) :value일 때 :attribute 항목이 없어야 해요.',
    'missing_unless' => ':other이(가) :value이(가) 아니면 :attribute 항목이 없어야 해요.',
    'missing_with' => ':values이(가) 있을 때 :attribute 항목이 없어야 해요.',
    'missing_with_all' => ':values이(가) 모두 있을 때 :attribute 항목이 없어야 해요.',
    'multiple_of' => ':attribute 항목은 :value의 배수여야 해요.',
    'not_in' => '선택한 :attribute 값이 올바르지 않아요.',
    'not_regex' => ':attribute 항목의 형식이 올바르지 않아요.',
    'numeric' => ':attribute 항목은 숫자여야 해요.',
    'password' => [
        'letters' => ':attribute 항목에는 문자가 하나 이상 포함되어야 해요.',
        'mixed' => ':attribute 항목에는 대문자와 소문자가 각각 하나 이상 포함되어야 해요.',
        'numbers' => ':attribute 항목에는 숫자가 하나 이상 포함되어야 해요.',
        'symbols' => ':attribute 항목에는 기호가 하나 이상 포함되어야 해요.',
        'uncompromised' => '입력한 :attribute 값이 데이터 유출에서 발견됐어요. 다른 :attribute 값을 선택해 주세요.',
    ],
    'present' => ':attribute 항목이 있어야 해요.',
    'present_if' => ':other이(가) :value일 때 :attribute 항목이 있어야 해요.',
    'present_unless' => ':other이(가) :value이(가) 아니면 :attribute 항목이 있어야 해요.',
    'present_with' => ':values이(가) 있을 때 :attribute 항목이 있어야 해요.',
    'present_with_all' => ':values이(가) 모두 있을 때 :attribute 항목이 있어야 해요.',
    'prohibited' => ':attribute 항목은 사용할 수 없어요.',
    'prohibited_if' => ':other이(가) :value일 때 :attribute 항목은 사용할 수 없어요.',
    'prohibited_if_accepted' => ':other이(가) 동의됨일 때 :attribute 항목은 사용할 수 없어요.',
    'prohibited_if_declined' => ':other이(가) 거부됨일 때 :attribute 항목은 사용할 수 없어요.',
    'prohibited_unless' => ':other이(가) :values에 포함되지 않으면 :attribute 항목은 사용할 수 없어요.',
    'prohibits' => ':attribute 항목이 있으면 :other은(는) 사용할 수 없어요.',
    'regex' => ':attribute 항목의 형식이 올바르지 않아요.',
    'required' => ':attribute 항목은 필수예요.',
    'required_array_keys' => ':attribute 항목에는 다음 항목이 포함되어야 해요: :values.',
    'required_if' => ':other이(가) :value일 때 :attribute 항목은 필수예요.',
    'required_if_accepted' => ':other이(가) 동의됨일 때 :attribute 항목은 필수예요.',
    'required_if_declined' => ':other이(가) 거부됨일 때 :attribute 항목은 필수예요.',
    'required_unless' => ':other이(가) :values에 포함되지 않으면 :attribute 항목은 필수예요.',
    'required_with' => ':values이(가) 있을 때 :attribute 항목은 필수예요.',
    'required_with_all' => ':values이(가) 모두 있을 때 :attribute 항목은 필수예요.',
    'required_without' => ':values이(가) 없을 때 :attribute 항목은 필수예요.',
    'required_without_all' => ':values이(가) 모두 없을 때 :attribute 항목은 필수예요.',
    'same' => ':attribute 항목이 :other와 일치해야 해요.',
    'size' => [
        'array' => ':attribute 항목은 :size개여야 해요.',
        'file' => ':attribute 항목은 :size킬로바이트여야 해요.',
        'numeric' => ':attribute 항목은 :size이어야 해요.',
        'string' => ':attribute 항목은 :size자여야 해요.',
    ],
    'starts_with' => ':attribute 항목은 다음 중 하나로 시작해야 해요: :values.',
    'string' => ':attribute 항목은 문자열이어야 해요.',
    'timezone' => ':attribute 항목은 올바른 시간대여야 해요.',
    'unique' => ':attribute 값은 이미 사용 중이에요.',
    'uploaded' => ':attribute 업로드에 실패했어요.',
    'uppercase' => ':attribute 항목은 대문자여야 해요.',
    'url' => ':attribute 항목은 올바른 URL이어야 해요.',
    'ulid' => ':attribute 항목은 올바른 ULID여야 해요.',
    'uuid' => ':attribute 항목은 올바른 UUID여야 해요.',

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

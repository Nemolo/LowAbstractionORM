<?php

class AlternativeSample extends \SincAppSviluppo\domain\AEntity {
    const RELATIONSHIPS = [
        'alternateSampleUser' => [
            "class" => AlternativeSampleUser::class,
            "type" => self::RELATIONSHIP_TO_ONE,
            "localKeys" => ['id'],
            "targetKeys" => ['nonSo'], // optional?
        ],
        'alternateSampleUser2' => [
            "class" => AlternativeSampleUser::class,
            "tergetRelationship" => "alternateSamples",
            "type" => self::RELATIONSHIP_TO_ONE,
        ]
    ];
    public $id;
    public $alternativeSampleUserId;
}

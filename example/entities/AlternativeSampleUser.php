<?php

class AlternativeSampleUser extends \SincAppSviluppo\domain\AEntity {
    const RELATIONSHIPS = [
        'alternateSamples' => [
            "class" => AlternativeSample::class,
            "type" => self::RELATIONSHIP_TO_MANY,
            "localKeys" => ['id'],
            "targetKeys" => ['nonSo'], // optional?
            "targetRelationship" => ''
        ]
    ];
    public $id;
}

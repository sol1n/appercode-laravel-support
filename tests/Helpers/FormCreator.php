<?php

namespace Tests\Helpers;

class FormCreator
{
    public static function simpleControl($code, $type)
    {
        return [
            "title" => [
                "ru" => "$code $type title",
                "en" => "$code $type title en"
            ],
            "description" => [
                "ru" => "$code $type description",
                "en" => "$code $type description en"
            ],
            "placeholder" => [
                "ru" => "$code $type placeholder",
                "en" => "$code $type placeholder en"
            ],
            "options" => null,
            "class" => "$code textBox control class",
            "id" => "id" . rand(1000, 9999),
            "displayCondition" => [
                "someId" => [
                    '$in' => [1,2,3]
                ]
            ],
            "validateConditions" => [
                [
                    "booleanExpression" => [
                        "someId" => [
                            '$in' => [1,2,3]
                        ]
                    ],
                    "errorMessage" => [
                        "ru" => "$code errorMessage",
                        "en" => "$code errorMessage en"
                    ]
                ]
            ],
            "type" => "$type",
            "viewData" => [
                "key" => "value"
            ],
            "correctValues" => null
        ];
    }

    public static function listControl($code, $type)
    {
        return [
            "title" => [
                "ru" => "$code $type title",
                "en" => "$code $type title en"
            ],
            "description" => [
                "ru" => "$code $type description",
                "en" => "$code $type description en"
            ],
            "placeholder" => [
                "ru" => "$code $type placeholder",
                "en" => "$code $type placeholder en"
            ],
            "options" => [
                [
                    "title" => [
                        "ru" => "Option 1 title",
                        "en" => "Option 1 title en"
                    ],
                    "value" => 0,
                    "class" => "Option 1 class"
                ],
                [
                    "title" => [
                        "ru" => "Option 2 title",
                        "en" => "Option 2 title en"
                    ],
                    "value" => 1,
                    "class" => "Option 2 class",
                    "displayCondition" => [
                        "someId" => [
                            '$in' => [1,2,3]
                        ]
                    ]
                ]
            ],
            "class" => "$code textBox control class",
            "id" => "id" . rand(1000, 9999),
            "displayCondition" => [
                "someId" => [
                    '$in' => [1,2,3]
                ]
            ],
            "validateConditions" => [
                [
                    "booleanExpression" => [
                        "someId" => [
                            '$in' => [1,2,3]
                        ]
                    ],
                    "errorMessage" => [
                        "ru" => "$code errorMessage",
                        "en" => "$code errorMessage en"
                    ]
                ]
            ],
            "type" => "$type",
            "viewData" => [
                "key" => "value"
            ],
            "correctValues" => null
        ];
    }

    public static function control($code, $type)
    {
        switch ($type) {
            case 'textBox':
            case 'multilineTextBox':
            case 'numberInput':
            case 'floatInput':
            case 'dateTimePicker':
            case 'textBlock':
                return self::simpleControl($code, $type);
            case 'checkBox':
            case 'checkBoxList':
            case 'radioButtons':
            case 'comboBox':
            case 'ImagesCheckBoxList':
            case 'RatingInput':
                return self::listControl($code, $type);
            default:
                return (object)[];
        }
    }

    public static function group($code, $questions)
    {
        $controls = [];
        foreach ($questions as $controlType) {
            $controls[] = self::control($code, $controlType);
        }

        return [
            "controls" => $controls,
            "class" => "group-class",
            "displayCondition" => [
                "someId" => [
                    '$in' => [1,2,3]
                ]
            ],
            "viewData" => [
                "key" => "value"
            ]
        ];
    }

    public static function section($code, $questions = [])
    {
        return [
            "title" => [
                "ru" => "$code section title",
                "en" => "$code section title en"
            ],
            "description" => [
                "ru" => "$code section description",
                "en" => "$code section description en"
            ],
            "groups" => [
                self::group($code, $questions)
            ],
            "class" => "section-class",
            "displayCondition" => [
                "someId" => [
                    '$in' => [1,2,3]
                ]
            ],
            "viewData" => [
                "key" => "value"
            ]
        ];
    }

    public static function part($code = '', $questions = [])
    {
        return [
            "title" => [
                "ru" => "$code title",
                "en" => "$code title en"
            ],
            "description" => [
                "ru" => "$code description",
                "en" => "$code description en"
            ],
            "sections" => [
                self::section($code, $questions)
            ],
            "class" => "part-class",
            "displayCondition" => [
                "someId" => [
                    '$in' => [1,2,3]
                ]
            ],
            "viewData" => [
                "key" => "value"
            ]
        ];
    }
}

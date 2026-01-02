<?php

return [
    'ctrl' => [
        'title' => 'form.db:form_definition',
        'label' => 'label',
        'crdate' => 'crdate',
        'tstamp' => 'tstamp',
        'versioningWS' => false,
        'default_sortby' => 'label',
        'delete' => 'deleted',
        'rootLevel' => 1,
        'security' => [
            'ignoreRootLevelRestriction' => true,
        ],
        'typeicon_classes' => [
            'default' => 'content-form',
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '
                --div--;core.form.tabs:general, label, identifier, configuration,
            ',
        ],
    ],
    'columns' => [
        'label' => [
            'label' => 'form.db:form_definition.label',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim',
                'readOnly' => true,
                'required' => true,
            ],
        ],
        'identifier' => [
            'label' => 'form.db:form_definition.identifier',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim,unique',
                'readOnly' => true,
                'required' => true,
            ],
        ],
        'configuration' => [
            'label' => 'form.db:form_definition.configuration',
            'config' => [
                'type' => 'json',
                'readOnly' => true,
                'required' => true,
            ],
        ],
    ],

];

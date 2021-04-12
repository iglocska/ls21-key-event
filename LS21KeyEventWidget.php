<?php

class LS21KeyEventWidget
{
    public $title = 'LS21 key event list';
    public $render = 'Index';
    public $width = 4;
    public $height = 2;
    public $params = [
        'key_event_tag' => 'The tag used to mark key events. (defaults to key_event)',
        'limit' => 'How many events should be listed? Defaults to 5',
        'page' => 'Which page do you wish to view? (Default: 1)',
        'org' => 'organisation ID, or list of organisations IDs to filter on'
    ];
    public $description = 'Monitor incoming events based on your own filters.';
    public $cacheLifetime = false;
    public $autoRefreshDelay = 60;
    private $__default_fields = ['organisation', 'title', 'overview', 'capability', 'impact', 'actions', 'status', 'related'];

	public function handler($user, $options = array())
	{
        if (empty($options['key_event_tag'])) {
            $options['key_event_tag'] = 'key_event';
        }
        $this->Event = ClassRegistry::init('Event');
        $params = [
            'limit' => 5,
            'page' => 1,
            'extensionList' => 1,
            'order' => 'Event.id DESC',
            'tags' => [$options['key_event_tag']]
        ];
        $field_options = [
            'organisation' => [
                'name' => 'Org',
                'data_path' => 'Orgc.name',
                'url' => Configure::read('MISP.baseurl') . '/organisations/view',
                'url_params_data_paths' => 'Orgc.id',
                'element' => 'links'
            ],
            'title' => [
                'name' => 'Title',
                'url' => Configure::read('MISP.baseurl') . '/events/view',
                'element' => 'links',
                'data_path' => 'Event.info',
                'url_params_data_paths' => 'Event.id'
            ],
            'overview' => [
                'name' => 'Event overview',
                'data_path' => 'KeyEvent.overview'
            ],
            'capability' => [
                'name' => 'Capability',
                'data_path' => 'KeyEvent.capability'
            ],
            'impact' => [
                'name' => 'Impact on capability',
                'data_path' => 'KeyEvent.impact-on-capability'
            ],
            'actions' => [
                'name' => 'Actions taken and results',
                'data_path' => 'KeyEvent.actions-taken-and-results'
            ],
            'status' => [
                'name' => 'Event status',
                'data_path' => 'KeyEvent.event-status'
            ],
            'related' => [
                'name' => 'Related',
                'data_path' => 'ExtendedBy.text',
                'url' => Configure::read('MISP.baseurl') . '/events/view',
                'url_params_data_paths' => 'ExtendedBy.id',
                'element' => 'links'
            ],
        ];
        $fields = [];
        if (empty($options['fields'])) {
            $options['fields'] = $this->__default_fields;
        }
        $preFilter = [];
        if (!empty($options['org'])) {
            $preFilter['org'] = $options['org'];
        }
        foreach ($options['fields'] as $field) {
            if (!empty($field_options[$field])) {
                $fields[] = $field_options[$field];
            }
        }
        foreach (['limit', 'page'] as $field) {
            if (!empty($options[$field])) {
                $params[$field] = $options[$field];
            }
        }
        if (!empty($preFilter)) {
            $params['eventid'] = $this->Event->filterEventIds($user, $preFilter);
            if (empty($params['eventid'])) {
                $params['eventid'] = [-1];
            }
        }
        $events = $this->Event->fetchEvent($user, $params);
        $data = [];
        foreach ($events as $event) {
            $keyEventObject = false;
            $baseurl = Configure::read('MISP.baseurl');
            if (!empty($event['Object'])) {
                foreach ($event['Object'] as $object) {
                    if ($object['name'] === 'ls21-key-event') {
                        $keyEventObject = $object;
                    }
                    break;
                }
                if (!empty($keyEventObject)) {
                    $keyEventExtracted = [];
                    foreach ($keyEventObject['Attribute'] as $attribute) {
                        if (in_array($attribute['object_relation'], ['overview', 'capability', 'impact-on-capability', 'event-status', 'actions-taken-and-results'])) {
                            $keyEventExtracted[$attribute['object_relation']] = $attribute['value'];
                        }
                    }
                    $extendedById = [];
                    $extendedByText = [];
                    if (!empty($event['Event']['ExtendedBy'])) {
                        foreach ($event['Event']['ExtendedBy'] as $extensionEvent) {
                            $extendedById[] = $extensionEvent['id'];
                            $extendedByText[] = sprintf(
                                '[%s]: %s',
                                $extensionEvent['Orgc']['name'],
                                mb_substr($extensionEvent['info'], 0, 20)
                            );
                        }
                    }
                    $data[] = [
                        'Event' => [
                            'id' => $event['Event']['id'],
                            'info' => $event['Event']['info']
                        ],
                        'Orgc' => [
                            'id' => $event['Orgc']['id'],
                            'name' => $event['Orgc']['name']
                        ],
                        'KeyEvent' => $keyEventExtracted,
                        'ExtendedBy' => [
                            'id' => $extendedById,
                            'text' => $extendedByText
                        ]
                    ];

                }
            }
        }
        return [
            'data' => $data,
            'fields' => $fields
        ];
	}
}

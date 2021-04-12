<?php

class LS21YTNotificationWidget
{
    public $title = 'LS21 yellow team notifications';
    public $render = 'Index';
    public $width = 4;
    public $height = 2;
    public $params = [
        'yt_notification_tag' => 'The tag used to mark notifications. (defaults to yt_info_request)',
        'limit' => 'How many notification events should be listed? Defaults to 100'
    ];
    public $description = 'Monitor incoming events based on your own filters.';
    public $cacheLifetime = 3;
    public $autoRefreshDelay = 3;

	public function handler($user, $options = array())
	{
        if (empty($options['yt_notification'])) {
            $options['yt_notification'] = 'yt_info_request';
        }
        $this->Event = ClassRegistry::init('Event');
        $params = [
            'limit' => 100,
            'page' => 1,
            'order' => 'Event.id DESC',
            'metadata' => 1,
            'tags' => [$options['yt_notification']]
        ];
        $fields = [
            [
                'name' => 'Published',
                'data_path' => 'Event.publish_timestamp',
                'class' => 'short',
                'element' => 'timestamp',
                'ago' => true
            ],
            [
                'name' => 'Id',
                'url' => Configure::read('MISP.baseurl') . '/events/view',
                'element' => 'links',
                'data_path' => 'Event.id',
                'url_params_data_paths' => 'Event.id',
                'class' => 'short'
            ],
            [
                'name' => 'Info',
                'data_path' => 'Event.info'
            ]
        ];
        foreach (['limit', 'page'] as $field) {
            if (!empty($options[$field])) {
                $params[$field] = $options[$field];
            }
        }
        $data = $this->Event->fetchEvent($user, $params);
        return [
            'data' => $data,
            'fields' => $fields
        ];
	}
}

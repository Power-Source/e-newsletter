<?php
defined('ABSPATH') || exit;

class AutomatedChannels {
    public static function defaults(): array {
        return [
            'id' => 0,
            'name' => '',
            'track' => 1,
            'frequency' => 'weekly',
            'enabled' => 0,
            'day_1' => 0,
            'day_2' => 0,
            'day_3' => 0,
            'day_4' => 0,
            'day_5' => 0,
            'day_6' => 0,
            'day_7' => 0,
            'hour' => 0,
            'hour2_enabled' => 0,
            'hour2' => 0,
            'subject' => '',
            'list' => 0,
            'sender_name' => '',
            'sender_email' => '',
            'last_sent' => 0,
            'last_sent_1' => 0,
            'last_sent_2' => 0,
            'sent' => 0,
            'email' => null,
        ];
    }

    public static function normalize(array $channel, $id = null): array {
        $base = self::defaults();
        $merged = array_merge($base, $channel);
        if (empty($merged['id'])) {
            $merged['id'] = (string)($id ?? '');
        }
        // force integer fields
        foreach (['track','enabled','day_1','day_2','day_3','day_4','day_5','day_6','day_7','hour','hour2_enabled','hour2','last_sent','last_sent_1','last_sent_2','sent'] as $key) {
            $merged[$key] = isset($merged[$key]) ? (int)$merged[$key] : 0;
        }
        return $merged;
    }

    public static function all(): array {
        $channels = get_option('tnp_automated_channels', []);
        $out = [];
        foreach ($channels as $id => $ch) {
            if (!is_array($ch)) continue;
            $norm = self::normalize($ch, $id);
            $out[$norm['id']] = $norm;
        }
        return $out;
    }

    public static function get($id): array {
        $all = self::all();
        if (isset($all[$id])) return $all[$id];
        $def = self::defaults();
        $def['id'] = (string)$id;
        return $def;
    }

    public static function save(array $channels): void {
        update_option('tnp_automated_channels', $channels);
    }
}

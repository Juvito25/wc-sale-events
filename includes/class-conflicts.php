<?php
defined('ABSPATH') || exit;

class WSE_Conflicts {

    public static function resolve(array $events, float $regular_price): ?array {
        if (empty($events)) return null;
        if (count($events) === 1) return $events[0];

        // Usar la regla del primer evento como regla global (o podría ser configurable)
        $rule = $events[0]['conflict_rule'] ?? 'highest_discount';

        switch ($rule) {
            case 'highest_discount':
                usort($events, function ($a, $b) use ($regular_price) {
                    return self::final_price($regular_price, $a) <=> self::final_price($regular_price, $b);
                });
                return $events[0];

            case 'lowest_discount':
                usort($events, function ($a, $b) use ($regular_price) {
                    return self::final_price($regular_price, $b) <=> self::final_price($regular_price, $a);
                });
                return $events[0];

            case 'first_event':
                usort($events, fn($a, $b) => strtotime($a['start_date']) <=> strtotime($b['start_date']));
                return $events[0];

            case 'last_event':
                usort($events, fn($a, $b) => strtotime($b['start_date']) <=> strtotime($a['start_date']));
                return $events[0];

            default:
                // Desempate por prioridad
                usort($events, fn($a, $b) => ($b['priority'] ?? 5) <=> ($a['priority'] ?? 5));
                return $events[0];
        }
    }

    public static function final_price(float $regular_price, array $event): float {
        $type  = $event['discount_type'] ?? 'percent';
        $value = floatval($event['discount_value'] ?? 0);
        if ($type === 'percent') {
            return max(0.01, $regular_price * (1 - $value / 100));
        }
        return max(0.01, $regular_price - $value);
    }
}

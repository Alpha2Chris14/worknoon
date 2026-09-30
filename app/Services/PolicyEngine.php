<?php

namespace App\Services;

use App\Models\Order;

/** Deterministic policy engine. The LLM can never loosen these rules. */
class PolicyEngine
{
    const WINDOW_DAYS = 30;
    const CHANGE_OF_MIND_DAYS = 14;
    const REVIEW_THRESHOLD = 500.0;

    /** @return array{hard: ?string, fired: string[]}
     *  Priority: ownership mismatch (escalate) > any denial > amount review (escalate). */
    public function evaluate(Order $order, string $requestEmail, ?string $reason): array
    {
        $fired = [];
        $denied = false;
        $escalated = false;
        $age = (int) abs($order->placed_at->startOfDay()->diffInDays(today()));
        $mismatch = strcasecmp($order->customer->email, $requestEmail) !== 0;

        if ($mismatch) $fired[] = 'R6: order does not belong to the email provided (conflicting request)';
        if ($order->status === 'refunded') {
            $denied = true;
            $fired[] = 'R7: order was already refunded';
        }
        if ($order->final_sale) {
            $denied = true;
            $fired[] = 'R1: final sale item is not eligible';
        }
        if ($age > self::WINDOW_DAYS) {
            $denied = true;
            $fired[] = "R2: order is {$age} days old (window is " . self::WINDOW_DAYS . ')';
        }
        if ($order->status === 'shipped') {
            $denied = true;
            $fired[] = 'Order not yet delivered; cannot be refunded';
        }
        if ($reason === 'changed_mind' && $age > self::CHANGE_OF_MIND_DAYS) {
            $denied = true;
            $fired[] = 'R3: change of mind only accepted within ' . self::CHANGE_OF_MIND_DAYS . ' days';
        }
        if ($order->total > self::REVIEW_THRESHOLD) {
            $escalated = true;
            $fired[] = sprintf('R5: amount $%.2f exceeds $%d human-review threshold', $order->total, self::REVIEW_THRESHOLD);
        }

        $hard = $mismatch ? 'ESCALATED' : ($denied ? 'DENIED' : ($escalated ? 'ESCALATED' : null));
        return ['hard' => $hard, 'fired' => $fired];
    }
}

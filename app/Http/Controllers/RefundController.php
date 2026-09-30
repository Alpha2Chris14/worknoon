<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\RefundRequest;
use App\Services\LlmService;
use App\Services\PolicyEngine;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function store(Request $request, PolicyEngine $policy, LlmService $llm)
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'min:5', 'max:120'],
            'order_id' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9-]+$/'],
            'message' => ['required', 'string', 'min:3', 'max:1000'],
        ]);
        $orderId = strtoupper($data['order_id']);
        $order = Order::with('customer')->find($orderId);
        $analysis = $llm->classify($data['message']);

        if (!$order) {
            $decision = 'ESCALATED';
            $fired = ['Order not found: cannot verify request'];
        } else {
            ['hard' => $decision, 'fired' => $fired] = $policy->evaluate($order, $data['email'], $analysis['reason']);
            if ($decision === null) { // rules are silent: AI may only tighten, or approve eligible reasons
                if ($analysis['suspicious']) {
                    $decision = 'ESCALATED';
                    $fired[] = 'R6: AI flagged the request as suspicious/injection attempt';
                } elseif (in_array($analysis['reason'], ['damaged', 'wrong_item', 'changed_mind'], true)) {
                    $decision = 'APPROVED';
                    $fired[] = "Eligible: '{$analysis['reason']}' within policy window";
                } else {
                    $decision = 'ESCALATED';
                    $fired[] = 'Reason unclear or not covered by automatic rules';
                }
            } elseif ($decision === 'DENIED' && $analysis['suspicious']) {
                $fired[] = 'Note: AI also flagged this request as suspicious';
            }
        }

        $reply = $llm->writeReply($decision, $fired, $orderId);
        RefundRequest::create([
            'email' => $data['email'],
            'order_id' => $orderId,
            'message' => $data['message'],
            'decision' => $decision,
            'reason_category' => $analysis['reason'],
            'rules_fired' => $fired,
            'llm_analysis' => $analysis,
            'reply' => $reply,
            'llm_used' => $analysis['llm_used'],
        ]);
        return response()->json(['decision' => $decision, 'reply' => $reply]);
    }

    public function index(Request $request)
    {
        return RefundRequest::latest('id')->limit(min((int) $request->query('limit', 50), 200))->get();
    }

    /** Helper for testers: shows seed data so they know which order/email pairs to try. */
    public function orders()
    {
        return Order::with('customer:id,email')->orderBy('id')->get()->map(fn($o) => [
            'order_id' => $o->id,
            'email' => $o->customer->email,
            'item' => $o->item,
            'total' => $o->total,
            'placed_at' => $o->placed_at->toDateString(),
            'final_sale' => $o->final_sale,
            'status' => $o->status,
        ]);
    }
}

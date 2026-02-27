<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class BillingController extends Controller
{
    public function show(Request $request)
    {
        $team = $request->user()->currentTeam;

        return Inertia::render('Billing/Show', [
            'team' => $team,
            'subscription' => $team?->subscription('default'),
            'onGracePeriod' => $team?->subscription('default')?->onGracePeriod() ?? false,
            'subscribed' => $team?->subscribed('default') ?? false,
            'plans' => config('steno.plans'),
            'stripeKey' => config('cashier.key'),
            'intent' => $team?->createSetupIntent(),
        ]);
    }

    public function subscribe(Request $request)
    {
        $request->validate([
            'plan' => ['required', 'in:individual,business'],
            'payment_method' => ['required', 'string'],
        ]);

        $team = $request->user()->currentTeam;
        $plan = config("steno.plans.{$request->plan}");

        $team->newSubscription('default', $plan['price_yearly'])
            ->create($request->payment_method);

        $team->update(['plan_type' => $request->plan]);

        return back()->with('success', 'Subscribed successfully!');
    }

    public function changePlan(Request $request)
    {
        $request->validate([
            'plan' => ['required', 'in:individual,business'],
        ]);

        $team = $request->user()->currentTeam;
        $plan = config("steno.plans.{$request->plan}");

        $team->subscription('default')->swap($plan['price_yearly']);
        $team->update(['plan_type' => $request->plan]);

        return back()->with('success', 'Plan updated.');
    }

    public function cancel(Request $request)
    {
        $team = $request->user()->currentTeam;
        $team->subscription('default')->cancel();

        return back()->with('success', 'Subscription cancelled. Access continues until end of billing period.');
    }

    public function resume(Request $request)
    {
        $team = $request->user()->currentTeam;
        $team->subscription('default')->resume();

        return back()->with('success', 'Subscription resumed.');
    }

    public function portal(Request $request)
    {
        return $request->user()->currentTeam->redirectToBillingPortal(route('billing'));
    }
}

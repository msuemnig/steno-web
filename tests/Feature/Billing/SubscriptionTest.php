<?php

namespace Tests\Feature\Billing;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a user with a personal team, optionally overriding team attributes.
     */
    private function createUserWithPlan(array $teamAttributes = []): User
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(array_merge(
            ['owner_id' => $user->id],
            $teamAttributes,
        ));
        $team->members()->attach($user->id, ['role' => 'owner']);
        $user->update(['current_team_id' => $team->id]);

        return $user->refresh();
    }

    /**
     * Create an active subscription record for a team (bypassing Stripe).
     */
    private function createSubscription(Team $team, string $plan = 'individual'): \Laravel\Cashier\Subscription
    {
        $priceId = config("steno.plans.{$plan}.price_yearly") ?: "price_test_{$plan}";

        $subscription = $team->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_test_' . Str::random(10),
            'stripe_status' => 'active',
            'stripe_price' => $priceId,
            'quantity' => 1,
        ]);

        $subscription->items()->create([
            'stripe_id' => 'si_test_' . Str::random(10),
            'stripe_product' => 'prod_test_' . $plan,
            'stripe_price' => $priceId,
            'quantity' => 1,
        ]);

        return $subscription;
    }

    // -------------------------------------------------------------------------
    // Authentication & Authorization
    // -------------------------------------------------------------------------

    public function test_billing_page_requires_authentication(): void
    {
        $response = $this->get(route('billing'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_billing_page(): void
    {
        if (config('cashier.secret') === 'sk_test_fake') {
            $this->markTestSkipped('Stripe test key required — set STRIPE_SECRET in .env to a real test key.');
        }

        $user = $this->createUserWithPlan();
        $response = $this->actingAs($user)->get(route('billing'));
        $response->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // Subscribe
    // -------------------------------------------------------------------------

    public function test_subscribe_requires_valid_plan(): void
    {
        $user = $this->createUserWithPlan();

        $response = $this->actingAs($user)->post(route('billing.subscribe'), [
            'plan' => 'nonexistent_plan',
            'payment_method' => 'pm_test_' . Str::random(10),
        ]);

        $response->assertSessionHasErrors('plan');
    }

    public function test_subscribe_requires_payment_method(): void
    {
        $user = $this->createUserWithPlan();

        $response = $this->actingAs($user)->post(route('billing.subscribe'), [
            'plan' => 'individual',
            // payment_method intentionally omitted
        ]);

        $response->assertSessionHasErrors('payment_method');
    }

    public function test_subscribe_rejects_empty_payment_method(): void
    {
        $user = $this->createUserWithPlan();

        $response = $this->actingAs($user)->post(route('billing.subscribe'), [
            'plan' => 'individual',
            'payment_method' => '',
        ]);

        $response->assertSessionHasErrors('payment_method');
    }

    public function test_subscribe_validates_plan_must_be_individual_or_business(): void
    {
        $user = $this->createUserWithPlan();

        // Try "free" -- not in the allowed set
        $response = $this->actingAs($user)->post(route('billing.subscribe'), [
            'plan' => 'free',
            'payment_method' => 'pm_test_abc',
        ]);

        $response->assertSessionHasErrors('plan');

        // Try "enterprise" -- also not in the allowed set
        $response = $this->actingAs($user)->post(route('billing.subscribe'), [
            'plan' => 'enterprise',
            'payment_method' => 'pm_test_abc',
        ]);

        $response->assertSessionHasErrors('plan');
    }

    public function test_subscribe_requires_authentication(): void
    {
        $response = $this->post(route('billing.subscribe'), [
            'plan' => 'individual',
            'payment_method' => 'pm_test_abc',
        ]);

        $response->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // Cancel Subscription
    // -------------------------------------------------------------------------

    public function test_cancel_subscription(): void
    {
        if (config('cashier.secret') === 'sk_test_fake') {
            $this->markTestSkipped('Stripe test key required — cancel() calls the Stripe API directly.');
        }

        $user = $this->createUserWithPlan(['plan_type' => 'individual']);
        $team = $user->currentTeam;
        $this->createSubscription($team, 'individual');

        $response = $this->actingAs($user)->post(route('billing.cancel'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $team->refresh();
        $subscription = $team->subscription('default');
        $this->assertNotNull($subscription->ends_at);
    }

    public function test_unsubscribed_team_cannot_cancel(): void
    {
        $user = $this->createUserWithPlan();

        // No subscription exists -- calling cancel should error.
        $response = $this->actingAs($user)->post(route('billing.cancel'));

        // The controller calls $team->subscription('default')->cancel() which will
        // throw an error when subscription is null (calling cancel on null).
        $response->assertStatus(500);
    }

    public function test_cancel_requires_authentication(): void
    {
        $response = $this->post(route('billing.cancel'));

        $response->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // Resume Subscription
    // -------------------------------------------------------------------------

    public function test_resume_subscription(): void
    {
        if (config('cashier.secret') === 'sk_test_fake') {
            $this->markTestSkipped('Stripe test key required — resume() calls the Stripe API directly.');
        }

        $user = $this->createUserWithPlan(['plan_type' => 'individual']);
        $team = $user->currentTeam;
        $subscription = $this->createSubscription($team, 'individual');

        $subscription->update(['ends_at' => now()->addDays(30)]);

        $response = $this->actingAs($user)->post(route('billing.resume'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $subscription->refresh();
        $this->assertNull($subscription->ends_at);
    }

    public function test_resume_requires_authentication(): void
    {
        $response = $this->post(route('billing.resume'));

        $response->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // Change Plan
    // -------------------------------------------------------------------------

    public function test_change_plan_requires_active_subscription(): void
    {
        $user = $this->createUserWithPlan();

        // No subscription -- attempting to swap should error.
        $response = $this->actingAs($user)->post(route('billing.change-plan'), [
            'plan' => 'business',
        ]);

        // The controller calls $team->subscription('default')->swap(...) on null.
        $response->assertStatus(500);
    }

    public function test_change_plan_requires_valid_plan(): void
    {
        $user = $this->createUserWithPlan(['plan_type' => 'individual']);
        $team = $user->currentTeam;
        $this->createSubscription($team, 'individual');

        $response = $this->actingAs($user)->post(route('billing.change-plan'), [
            'plan' => 'nonexistent',
        ]);

        $response->assertSessionHasErrors('plan');
    }

    public function test_change_plan_requires_plan_field(): void
    {
        $user = $this->createUserWithPlan(['plan_type' => 'individual']);
        $team = $user->currentTeam;
        $this->createSubscription($team, 'individual');

        $response = $this->actingAs($user)->post(route('billing.change-plan'), []);

        $response->assertSessionHasErrors('plan');
    }

    public function test_change_plan_swaps_subscription(): void
    {
        if (config('cashier.secret') === 'sk_test_fake') {
            $this->markTestSkipped('Stripe test key required — swap() calls the Stripe API directly.');
        }

        $user = $this->createUserWithPlan(['plan_type' => 'individual']);
        $team = $user->currentTeam;
        $this->createSubscription($team, 'individual');

        $response = $this->actingAs($user)->post(route('billing.change-plan'), [
            'plan' => 'business',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $team->refresh();
        $this->assertEquals('business', $team->plan_type);
    }

    public function test_change_plan_requires_authentication(): void
    {
        $response = $this->post(route('billing.change-plan'), [
            'plan' => 'business',
        ]);

        $response->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // Billing Portal
    // -------------------------------------------------------------------------

    public function test_billing_portal_requires_authentication(): void
    {
        $response = $this->get(route('billing.portal'));

        $response->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // Subscription State Assertions
    // -------------------------------------------------------------------------

    public function test_team_with_active_subscription_is_subscribed(): void
    {
        $user = $this->createUserWithPlan(['plan_type' => 'individual']);
        $team = $user->currentTeam;
        $this->createSubscription($team, 'individual');

        $this->assertTrue($team->subscribed('default'));
    }

    public function test_team_without_subscription_is_not_subscribed(): void
    {
        $user = $this->createUserWithPlan();
        $team = $user->currentTeam;

        $this->assertFalse($team->subscribed('default'));
    }

    public function test_cancelled_subscription_on_grace_period_is_still_subscribed(): void
    {
        $user = $this->createUserWithPlan(['plan_type' => 'individual']);
        $team = $user->currentTeam;
        $subscription = $this->createSubscription($team, 'individual');

        // Simulate cancellation with grace period.
        $subscription->update(['ends_at' => now()->addDays(15)]);

        $this->assertTrue($team->subscribed('default'));
        $this->assertTrue($subscription->onGracePeriod());
    }

    public function test_expired_subscription_is_not_subscribed(): void
    {
        $user = $this->createUserWithPlan(['plan_type' => 'individual']);
        $team = $user->currentTeam;
        $subscription = $this->createSubscription($team, 'individual');

        // Simulate an expired subscription (ends_at in the past).
        $subscription->update(['ends_at' => now()->subDay()]);

        $this->assertFalse($team->subscribed('default'));
        $this->assertFalse($subscription->onGracePeriod());
    }
}

<?php

namespace Tests\Feature\Team;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Cashier\Subscription;
use Tests\TestCase;

class TeamInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_invite_member(): void
    {
        $owner = $this->createUserWithTeam();
        $team = $owner->currentTeam;

        $response = $this->actingAs($owner)->post("/teams/{$team->id}/invitations", [
            'email' => 'newmember@example.com',
            'role' => 'editor',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('team_invitations', [
            'team_id' => $team->id,
            'email' => 'newmember@example.com',
            'role' => 'editor',
        ]);
    }

    public function test_admin_can_invite_member(): void
    {
        $owner = $this->createUserWithTeam();
        $admin = User::factory()->create();
        $team = $owner->currentTeam;
        $this->addUserToTeam($admin, $team, 'admin');

        $response = $this->actingAs($admin)->post("/teams/{$team->id}/invitations", [
            'email' => 'invited@example.com',
            'role' => 'viewer',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('team_invitations', [
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'role' => 'viewer',
        ]);
    }

    public function test_editor_cannot_invite_member(): void
    {
        $owner = $this->createUserWithTeam();
        $editor = User::factory()->create();
        $team = $owner->currentTeam;
        $this->addUserToTeam($editor, $team, 'editor');

        $response = $this->actingAs($editor)->post("/teams/{$team->id}/invitations", [
            'email' => 'invited@example.com',
            'role' => 'viewer',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('team_invitations', [
            'team_id' => $team->id,
            'email' => 'invited@example.com',
        ]);
    }

    public function test_cannot_invite_existing_member(): void
    {
        $owner = $this->createUserWithTeam();
        $existingMember = User::factory()->create(['email' => 'existing@example.com']);
        $team = $owner->currentTeam;
        $this->addUserToTeam($existingMember, $team, 'editor');

        $response = $this->actingAs($owner)->post("/teams/{$team->id}/invitations", [
            'email' => 'existing@example.com',
            'role' => 'editor',
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseMissing('team_invitations', [
            'team_id' => $team->id,
            'email' => 'existing@example.com',
        ]);
    }

    public function test_user_can_accept_invitation(): void
    {
        $owner = $this->createUserWithTeam();
        $team = $owner->currentTeam;

        $invitedUser = $this->createUserWithTeam(['email' => 'invited@example.com']);

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'role' => 'editor',
            'token' => Str::random(64),
        ]);

        $response = $this->actingAs($invitedUser)->get("/team-invitations/{$invitation->token}/accept");

        $response->assertRedirect(route('teams.show', $team->id));

        // The user should now be a member of the team
        $this->assertTrue($team->fresh()->hasUser($invitedUser));
        $this->assertEquals('editor', $team->fresh()->userRole($invitedUser));

        // The invitation should be deleted
        $this->assertDatabaseMissing('team_invitations', ['id' => $invitation->id]);

        // The user's current team should have switched
        $invitedUser->refresh();
        $this->assertEquals($team->id, $invitedUser->current_team_id);
    }

    public function test_cannot_accept_invitation_if_already_member(): void
    {
        $owner = $this->createUserWithTeam();
        $team = $owner->currentTeam;

        $existingMember = $this->createUserWithTeam(['email' => 'member@example.com']);
        $this->addUserToTeam($existingMember, $team, 'editor');

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'member@example.com',
            'role' => 'viewer',
            'token' => Str::random(64),
        ]);

        $response = $this->actingAs($existingMember)->get("/team-invitations/{$invitation->token}/accept");

        $response->assertStatus(409);
    }

    public function test_unauthenticated_user_is_redirected_to_login_on_accept(): void
    {
        $owner = $this->createUserWithTeam();
        $team = $owner->currentTeam;

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'new@example.com',
            'role' => 'editor',
            'token' => Str::random(64),
        ]);

        $response = $this->get("/team-invitations/{$invitation->token}/accept");

        $response->assertRedirect(route('login'));
    }

    public function test_owner_can_cancel_invitation(): void
    {
        $owner = $this->createUserWithTeam();
        $team = $owner->currentTeam;

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'pending@example.com',
            'role' => 'editor',
            'token' => Str::random(64),
        ]);

        $response = $this->actingAs($owner)->delete("/team-invitations/{$invitation->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('team_invitations', ['id' => $invitation->id]);
    }

    public function test_admin_can_cancel_invitation(): void
    {
        $owner = $this->createUserWithTeam();
        $admin = User::factory()->create();
        $team = $owner->currentTeam;
        $this->addUserToTeam($admin, $team, 'admin');

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'pending@example.com',
            'role' => 'editor',
            'token' => Str::random(64),
        ]);

        $response = $this->actingAs($admin)->delete("/team-invitations/{$invitation->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('team_invitations', ['id' => $invitation->id]);
    }

    public function test_editor_cannot_cancel_invitation(): void
    {
        $owner = $this->createUserWithTeam();
        $editor = User::factory()->create();
        $team = $owner->currentTeam;
        $this->addUserToTeam($editor, $team, 'editor');

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'pending@example.com',
            'role' => 'editor',
            'token' => Str::random(64),
        ]);

        $response = $this->actingAs($editor)->delete("/team-invitations/{$invitation->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('team_invitations', ['id' => $invitation->id]);
    }

    public function test_business_plan_member_limit_enforced(): void
    {
        $owner = $this->createUserWithTeam();
        $team = $owner->currentTeam;

        // Set a known price ID for the business plan
        $businessPriceId = 'price_business_yearly_test';
        config(['steno.plans.business.price_yearly' => $businessPriceId]);
        config(['steno.plans.business.max_members' => 10]);

        // Create a Cashier subscription record directly in the DB so that
        // $team->subscribed('default') returns true and
        // $team->subscription('default')->stripe_price matches the business plan.
        $team->update(['stripe_id' => 'cus_test_' . Str::random(10)]);

        $subscriptionId = \Illuminate\Support\Facades\DB::table('subscriptions')->insertGetId([
            'team_id' => $team->id,
            'type' => 'default',
            'stripe_id' => 'sub_test_' . Str::random(10),
            'stripe_status' => 'active',
            'stripe_price' => $businessPriceId,
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('subscription_items')->insert([
            'subscription_id' => $subscriptionId,
            'stripe_id' => 'si_test_' . Str::random(10),
            'stripe_product' => 'prod_test',
            'stripe_price' => $businessPriceId,
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Add 9 more members (owner is already 1, total = 10 = max_members)
        for ($i = 0; $i < 9; $i++) {
            $member = User::factory()->create();
            $this->addUserToTeam($member, $team, 'editor');
        }

        // Verify we now have exactly 10 members
        $this->assertEquals(10, $team->members()->count());

        // Trying to invite an 11th member should fail
        $response = $this->actingAs($owner)->post("/teams/{$team->id}/invitations", [
            'email' => 'eleventh@example.com',
            'role' => 'editor',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('team_invitations', [
            'team_id' => $team->id,
            'email' => 'eleventh@example.com',
        ]);
    }
}

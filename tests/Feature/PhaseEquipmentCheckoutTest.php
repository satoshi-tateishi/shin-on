<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\Location;
use App\Models\Phase;
use App\Models\PhaseEquipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseEquipmentCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Phase $phase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->phase = Phase::factory()->create();
    }

    public function test_equipment_can_be_checked_out(): void
    {
        $equipment = Equipment::factory()->create();
        $phaseEquipment = PhaseEquipment::factory()
            ->reserved()
            ->forPhase($this->phase)
            ->forEquipment($equipment)
            ->create();

        $response = $this->actingAs($this->admin)
            ->patch("/phases/{$this->phase->id}/equipment/{$phaseEquipment->id}/checkout", [
                'checkout_date' => now()->format('Y-m-d'),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('phase_equipment', [
            'id' => $phaseEquipment->id,
            'status' => 'checked_out',
        ]);
    }

    public function test_equipment_can_be_checked_in(): void
    {
        $equipment = Equipment::factory()->create();
        $phaseEquipment = PhaseEquipment::factory()
            ->checkedOut()
            ->forPhase($this->phase)
            ->forEquipment($equipment)
            ->create();

        $response = $this->actingAs($this->admin)
            ->patch("/phases/{$this->phase->id}/equipment/{$phaseEquipment->id}/checkin", [
                'checkin_date' => now()->format('Y-m-d'),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('phase_equipment', [
            'id' => $phaseEquipment->id,
            'status' => 'checked_in',
        ]);
    }

    public function test_reserved_equipment_cannot_be_checked_in(): void
    {
        $equipment = Equipment::factory()->create();
        $phaseEquipment = PhaseEquipment::factory()
            ->reserved()
            ->forPhase($this->phase)
            ->forEquipment($equipment)
            ->create();

        $response = $this->actingAs($this->admin)
            ->patch("/phases/{$this->phase->id}/equipment/{$phaseEquipment->id}/checkin", [
                'checkin_date' => now()->format('Y-m-d'),
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }

    public function test_checked_out_equipment_cannot_be_checked_out_again(): void
    {
        $equipment = Equipment::factory()->create();
        $phaseEquipment = PhaseEquipment::factory()
            ->checkedOut()
            ->forPhase($this->phase)
            ->forEquipment($equipment)
            ->create();

        $response = $this->actingAs($this->admin)
            ->patch("/phases/{$this->phase->id}/equipment/{$phaseEquipment->id}/checkout", [
                'checkout_date' => now()->format('Y-m-d'),
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }

    public function test_bulk_checkout_checks_out_reserved_equipment(): void
    {
        $equipment1 = Equipment::factory()->create();
        $equipment2 = Equipment::factory()->create();

        PhaseEquipment::factory()
            ->reserved()
            ->forPhase($this->phase)
            ->forEquipment($equipment1)
            ->create();

        PhaseEquipment::factory()
            ->reserved()
            ->forPhase($this->phase)
            ->forEquipment($equipment2)
            ->create();

        $response = $this->actingAs($this->admin)
            ->patch("/phases/{$this->phase->id}/equipment/bulk-checkout");

        $response->assertRedirect();
        $this->assertEquals(2, PhaseEquipment::where('phase_id', $this->phase->id)
            ->where('status', 'checked_out')
            ->count());
    }

    public function test_bulk_checkout_reserved_only(): void
    {
        $equipment1 = Equipment::factory()->create();
        $equipment2 = Equipment::factory()->create();

        PhaseEquipment::factory()
            ->reserved()
            ->forPhase($this->phase)
            ->forEquipment($equipment1)
            ->create();

        PhaseEquipment::factory()
            ->checkedIn()
            ->forPhase($this->phase)
            ->forEquipment($equipment2)
            ->create();

        $response = $this->actingAs($this->admin)
            ->patch("/phases/{$this->phase->id}/equipment/bulk-checkout-reserved");

        $response->assertRedirect();
        $this->assertEquals(1, PhaseEquipment::where('phase_id', $this->phase->id)
            ->where('status', 'checked_out')
            ->count());
    }

    public function test_bulk_checkin_checks_in_all_checked_out_equipment(): void
    {
        $equipment1 = Equipment::factory()->create();
        $equipment2 = Equipment::factory()->create();

        PhaseEquipment::factory()
            ->checkedOut()
            ->forPhase($this->phase)
            ->forEquipment($equipment1)
            ->create();

        PhaseEquipment::factory()
            ->checkedOut()
            ->forPhase($this->phase)
            ->forEquipment($equipment2)
            ->create();

        $response = $this->actingAs($this->admin)
            ->patch("/phases/{$this->phase->id}/equipment/bulk-checkin");

        $response->assertRedirect();
        $this->assertEquals(2, PhaseEquipment::where('phase_id', $this->phase->id)
            ->where('status', 'checked_in')
            ->count());
    }

    public function test_checkout_requires_checkout_date(): void
    {
        $equipment = Equipment::factory()->create();
        $phaseEquipment = PhaseEquipment::factory()
            ->reserved()
            ->forPhase($this->phase)
            ->forEquipment($equipment)
            ->create();

        $response = $this->actingAs($this->admin)
            ->patch("/phases/{$this->phase->id}/equipment/{$phaseEquipment->id}/checkout", [
                // checkout_date missing
            ]);

        $response->assertSessionHasErrors('checkout_date');
    }

    public function test_checkin_requires_checkin_date(): void
    {
        $equipment = Equipment::factory()->create();
        $phaseEquipment = PhaseEquipment::factory()
            ->checkedOut()
            ->forPhase($this->phase)
            ->forEquipment($equipment)
            ->create();

        $response = $this->actingAs($this->admin)
            ->patch("/phases/{$this->phase->id}/equipment/{$phaseEquipment->id}/checkin", [
                // checkin_date missing
            ]);

        $response->assertSessionHasErrors('checkin_date');
    }

    public function test_main_warehouse_equipment_checkin_requires_location(): void
    {
        $mainWarehouse = Location::factory()->mainWarehouse()->create();
        $equipment = Equipment::factory()->create([
            'location_id' => $mainWarehouse->id,
        ]);
        $phaseEquipment = PhaseEquipment::factory()
            ->checkedOut()
            ->forPhase($this->phase)
            ->forEquipment($equipment)
            ->create();

        $response = $this->actingAs($this->admin)
            ->patch("/phases/{$this->phase->id}/equipment/{$phaseEquipment->id}/checkin", [
                'checkin_date' => now()->format('Y-m-d'),
                // to_location_id missing for main warehouse equipment
            ]);

        $response->assertSessionHasErrors('to_location_id');
    }

    public function test_main_warehouse_equipment_checkin_with_location(): void
    {
        $mainWarehouse = Location::factory()->mainWarehouse()->create();
        $returnLocation = Location::factory()->mainWarehouse()->create();
        $equipment = Equipment::factory()->create([
            'location_id' => $mainWarehouse->id,
        ]);
        $phaseEquipment = PhaseEquipment::factory()
            ->checkedOut()
            ->forPhase($this->phase)
            ->forEquipment($equipment)
            ->create();

        $response = $this->actingAs($this->admin)
            ->patch("/phases/{$this->phase->id}/equipment/{$phaseEquipment->id}/checkin", [
                'checkin_date' => now()->format('Y-m-d'),
                'to_location_id' => $returnLocation->id,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('phase_equipment', [
            'id' => $phaseEquipment->id,
            'status' => 'checked_in',
        ]);
    }

    public function test_checkout_requires_authentication(): void
    {
        $equipment = Equipment::factory()->create();
        $phaseEquipment = PhaseEquipment::factory()
            ->reserved()
            ->forPhase($this->phase)
            ->forEquipment($equipment)
            ->create();

        $response = $this->patch("/phases/{$this->phase->id}/equipment/{$phaseEquipment->id}/checkout", [
            'checkout_date' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect('/login');
    }
}

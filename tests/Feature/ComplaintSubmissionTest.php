<?php

namespace Tests\Feature;

use App\Models\Complaint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComplaintSubmissionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_can_submit_a_ticket_with_private_proof_image(): void
    {
        Storage::fake('private');

        $response = $this->post(route('complaint.store'), [
            'name' => 'Test Customer',
            'phone' => '01712345678',
            'email' => 'customer@example.com',
            'order_reference' => 'BG-TEST-1001',
            'description' => 'The delivered item was damaged inside the package.',
            'image' => UploadedFile::fake()->image('proof.jpg', 640, 480),
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('ticket_number');

        $ticketNumber = session('ticket_number');
        $this->assertMatchesRegularExpression('/^BG-[0-9]{6}-[0-9]{4}$/', $ticketNumber);

        $complaint = Complaint::where('ticket_number', $ticketNumber)->firstOrFail();
        $this->assertSame('customer@example.com', $complaint->email);
        $this->assertSame('BG-TEST-1001', $complaint->order_reference);
        $this->assertSame('pending', $complaint->status);
        Storage::disk('private')->assertExists($complaint->image);
    }

    public function test_ticket_submission_shows_required_field_errors(): void
    {
        $this->post(route('complaint.store'))
            ->assertSessionHasErrors(['name', 'phone', 'description']);
    }

    public function test_admin_can_update_ticket_status(): void
    {
        $complaint = Complaint::create([
            'ticket_number' => 'BG-260818-9999',
            'name' => 'Status Test',
            'phone' => '01712345678',
            'description' => 'A ticket created to verify status updates.',
            'status' => 'pending',
        ]);

        $this->withoutMiddleware()
            ->post(route('backEnd.complaints.status', $complaint), ['status' => 'resolved'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'resolved',
        ]);
    }
}

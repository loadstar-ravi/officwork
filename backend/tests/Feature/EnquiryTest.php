<?php

namespace Tests\Feature;

use App\Mail\EnquiryReceived;
use App\Models\Enquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * GIVEN I am viewing a product
 * AND I click on the Enquire button
 * AND I complete the subsequent form with my contact details
 * WHEN I submit the form
 * THEN the sales inbox will receive an email
 * AND the customer will see a "Thank you" message
 */
class EnquiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->fakeProductSource();
        config(['catalogue.enquiries.sales_inbox' => 'sales@shop.test']);
    }

    public function test_stores_the_enquiry_emails_the_sales_inbox_and_thanks_the_customer(): void
    {
        $response = $this->postJson('/api/v1/enquiries', $this->validEnquiry());

        $response->assertCreated()
            ->assertJsonPath('message', 'Thank you for your enquiry. Our sales team will be in touch soon.');

        $this->assertDatabaseHas('enquiries', [
            'product_id' => 3,
            'product_title' => 'Mens Cotton Jacket',
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'phone' => '+44 7700 900123',
            'message' => 'Do you have this in large?',
        ]);

        Mail::assertQueued(EnquiryReceived::class, function (EnquiryReceived $mail): bool {
            return $mail->hasTo('sales@shop.test')
                && $mail->hasReplyTo('ada@example.com', 'Ada Lovelace')
                && $mail->hasSubject('New enquiry: Mens Cotton Jacket');
        });
    }

    public function test_phone_and_message_are_optional(): void
    {
        $this->postJson('/api/v1/enquiries', [
            'product_id' => 3,
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ])->assertCreated();

        Mail::assertQueued(EnquiryReceived::class);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidEnquiries(): array
    {
        return [
            'missing name' => [['name' => null], 'name'],
            'name too long' => [['name' => str_repeat('a', 101)], 'name'],
            'missing email' => [['email' => null], 'email'],
            'invalid email' => [['email' => 'not-an-email'], 'email'],
            'invalid phone' => [['phone' => 'call me maybe'], 'phone'],
            'message too long' => [['message' => str_repeat('a', 2001)], 'message'],
            'missing product' => [['product_id' => null], 'product_id'],
            'non-numeric product' => [['product_id' => 'abc'], 'product_id'],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    #[DataProvider('invalidEnquiries')]
    public function test_rejects_invalid_contact_details(array $overrides, string $invalidField): void
    {
        $this->postJson('/api/v1/enquiries', [...$this->validEnquiry(), ...$overrides])
            ->assertUnprocessable()
            ->assertJsonValidationErrors($invalidField);

        $this->assertDatabaseCount('enquiries', 0);
        Mail::assertNothingQueued();
    }

    public function test_rejects_an_enquiry_for_a_product_that_does_not_exist(): void
    {
        $this->postJson('/api/v1/enquiries', [...$this->validEnquiry(), 'product_id' => 999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['product_id' => 'This product is no longer available.']);

        Mail::assertNothingQueued();
    }

    public function test_limits_how_many_enquiries_one_visitor_can_send(): void
    {
        config(['catalogue.enquiries.per_minute' => 2]);

        $this->postJson('/api/v1/enquiries', $this->validEnquiry())->assertCreated();
        $this->postJson('/api/v1/enquiries', $this->validEnquiry())->assertCreated();
        $this->postJson('/api/v1/enquiries', $this->validEnquiry())->assertTooManyRequests();

        $this->assertDatabaseCount('enquiries', 2);
    }

    public function test_the_email_shows_the_enquiry_and_escapes_customer_input(): void
    {
        $enquiry = Enquiry::factory()->create([
            'product_title' => 'Mens Cotton Jacket',
            'name' => '<script>alert(1)</script>',
            'email' => 'ada@example.com',
            'message' => "Line one\nLine two",
        ]);

        $mail = new EnquiryReceived($enquiry);

        $mail->assertSeeInHtml('Mens Cotton Jacket');
        $mail->assertSeeInHtml('ada@example.com');
        $mail->assertSeeInHtml('Line one<br />', escape: false);
        $mail->assertDontSeeInHtml('<script>alert(1)</script>', escape: false);
        $mail->assertSeeInText('Line two');
        // Plain text isn't HTML, so it must not contain HTML entities.
        $mail->assertSeeInText('<script>alert(1)</script>');
    }

    /**
     * @return array<string, mixed>
     */
    private function validEnquiry(): array
    {
        return [
            'product_id' => 3,
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'phone' => '+44 7700 900123',
            'message' => 'Do you have this in large?',
        ];
    }
}

<?php

namespace Tests\Unit\Services\Contact\Conversation;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Contact\Call;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Instance\Emotion\Emotion;
use App\Services\Activity\Activity\AttachContactToActivity;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Contact\Activity;

class AttachContactToActivityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_attaches_contacts()
    {
        $activity = factory(Activity::class)->create([]);
        $contactA = factory(Contact::class)->create([
            'account_id' => $activity->account_id,
        ]);
        $contactB = factory(Contact::class)->create([
            'account_id' => $activity->account_id,
        ]);
        $contactC = factory(Contact::class)->create([
            'account_id' => $activity->account_id,
        ]);

        $request = [
            'account_id' => $activity->account_id,
            'activity_id' => $activity->id,
            'contacts' => [$contactA->id, $contactB->id, $contactC->id],
        ];

        $activity = (new AttachContactToActivity)->execute($request);

        $this->assertDatabaseHas('activity_contact', [
            'activity_id' => $activity->id,
            'contact_id' => $contactA->id,
            'account_id' => $activity->account_id,
        ]);

        $this->assertDatabaseHas('activity_contact', [
            'activity_id' => $activity->id,
            'contact_id' => $contactB->id,
            'account_id' => $activity->account_id,
        ]);

        $this->assertDatabaseHas('activity_contact', [
            'activity_id' => $activity->id,
            'contact_id' => $contactC->id,
            'account_id' => $activity->account_id,
        ]);

        $this->assertInstanceOf(
            Activity::class,
            $activity
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $activity = factory(Activity::class)->create([]);
        $contactA = factory(Contact::class)->create([
            'account_id' => $activity->account_id,
        ]);

        $request = [
            'activity_id' => $activity->id,
            'contacts' => [$contactA->id],
        ];

        $this->expectException(ValidationException::class);
        (new AttachContactToActivity)->execute($request);
    }

    public function test_it_throws_an_exception_if_contact_is_not_linked_to_account()
    {
        $activity = factory(Activity::class)->create([]);
        $account = factory(Account::class)->create([]);
        $contactA = factory(Contact::class)->create([
            'account_id' => $activity->account_id,
        ]);

        $request = [
            'activity_id' => $activity->id,
            'account_id' => $account->id,
            'contacts' => [$contactA->id],
        ];

        $this->expectException(ModelNotFoundException::class);
        (new AttachContactToActivity)->execute($request);
    }
}
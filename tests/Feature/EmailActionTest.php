<?php

namespace Tests\Feature;

use App\Mail\WorkflowNotificationMail;
use App\Models\WorkflowAction;
use App\Services\Workflow\Actions\EmailAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsFixtures;
use Tests\TestCase;

class EmailActionTest extends TestCase
{
    use RefreshDatabase, BuildsFixtures;

    private function run_(array $recipients, string $subject = 'Hallo {{projekt_name}}'): array
    {
        $project = $this->makeProject('draft', 'product', ['name' => 'Beschaffung X']);
        $action  = new WorkflowAction(['type' => 'email', 'config' => ['recipients' => $recipients, 'subject' => $subject, 'body' => 'Status: {{status_neu}}']]);

        return (new EmailAction())->execute($project, $action, null);
    }

    public function test_mails_go_to_users_groups_roles_and_external_addresses_without_duplicates(): void
    {
        Mail::fake();
        $role   = $this->makeRole();
        $group  = $this->makeGroup();
        $shared = $this->makeUser('shared@example.test', [$role]);
        $group->users()->attach((string) $shared->cis_row_id);
        $this->makeUser('role@example.test', [$role]);
        $member = $this->makeUser('group@example.test');
        $group->users()->attach((string) $member->cis_row_id);

        $result = $this->run_([
            ['type' => 'role', 'value' => (string) $role->cis_row_id],
            ['type' => 'group', 'value' => (string) $group->cis_row_id],
            ['type' => 'user', 'value' => (string) $shared->cis_row_id],
            ['type' => 'email', 'value' => 'extern@example.test'],
            ['type' => 'email', 'value' => 'kein-gueltiges-mail'],
            ['type' => 'role', 'value' => 'gibt-es-nicht'],
        ]);

        $this->assertSame('sent', $result['status']);
        $sent = collect(Mail::sent(WorkflowNotificationMail::class))->flatMap(fn ($m) => $m->to)->pluck('address')->sort()->values()->all();
        $this->assertSame(['extern@example.test', 'group@example.test', 'role@example.test', 'shared@example.test'], $sent);
    }

    public function test_project_variables_are_replaced_as_plain_text(): void
    {
        Mail::fake();
        $this->run_([['type' => 'email', 'value' => 'a@example.test']], 'Projekt {{projekt_name}} {{unbekannt}}');

        Mail::assertSent(WorkflowNotificationMail::class, fn ($mail) => $mail->envelope()->subject === 'Projekt Beschaffung X {{unbekannt}}');
    }

    public function test_no_valid_recipients_is_skipped_not_failed(): void
    {
        Mail::fake();

        $this->assertSame('skipped', $this->run_([['type' => 'role', 'value' => 'weg']])['status']);
        Mail::assertNothingSent();
    }

    public function test_email_action_requires_recipients_subject_and_body(): void
    {
        $errors = (new EmailAction())->validate(['recipients' => [], 'subject' => '', 'body' => '']);

        $this->assertSame(['recipients', 'subject', 'body'], array_keys($errors));
    }
}

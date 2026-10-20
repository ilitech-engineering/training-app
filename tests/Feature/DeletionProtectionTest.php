<?php
namespace Tests\Feature;

use App\Models\{Automation, Course, CourseSession, Meeting, MessageLog, Registration};
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Http};
use Tests\TestCase;

/**
 * T-01 / T-01.1 regression tests.
 * STATUS: written but NOT EXECUTED (the repository is an overlay without a Laravel skeleton or a database).
 *  - Model-level tests are database-agnostic. Foreign-key tests are skipped unless the connection is MySQL/MariaDB.
 *  - The Livewire/Filament test relies on Filament testing helpers whose names are NOT verified here and on an admin user
 *    allowed into the panel; it is skipped when Livewire or the User model is missing.
 */
class DeletionProtectionTest extends TestCase
{
    use RefreshDatabase;

    private const FK_RULES = [
        'course_sessions.course_id' => 'CASCADE', 'meetings.course_session_id' => 'CASCADE',
        'registrations.course_id' => 'RESTRICT', 'registrations.course_session_id' => 'RESTRICT',
        'automations.course_id' => 'CASCADE', 'automations.promote_course_id' => 'SET NULL',
        'message_logs.registration_id' => 'CASCADE', 'message_logs.automation_id' => 'RESTRICT',
    ];

    private function course(array $a = []): Course
    {
        return Course::create($a + ['slug' => 'c-' . uniqid(), 'title' => ['ar' => 'x', 'fr' => 'x', 'en' => 'x'], 'weeks' => 3]);
    }

    private function session(Course $c, array $a = []): CourseSession   // creating a session also generates its weekly meetings
    {
        return CourseSession::create($a + ['course_id' => $c->id, 'starts_at' => now()->addWeek(), 'price' => 0]);
    }

    private function registration(CourseSession $s, array $a = []): Registration
    {
        return Registration::create($a + ['course_id' => $s->course_id, 'course_session_id' => $s->id, 'name' => 'Test User', 'phone' => '0550000000', 'status' => 'pending']);
    }

    private function automation(array $a = []): Automation
    {
        return Automation::create($a + ['name' => 'a', 'anchor' => 'registration', 'channels' => ['mail'], 'body' => ['fr' => 'x']]);
    }

    private function onMysql(): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) $this->markTestSkipped('MySQL/MariaDB required for foreign-key behaviour.');
    }

    // ---- course / session / meetings -------------------------------------------------------------------------------

    public function test_course_with_registration_cannot_be_deleted(): void
    {
        $s = $this->session($c = $this->course()); $this->registration($s);
        $this->assertFalse($c->delete());
        $this->assertDatabaseHas('courses', ['id' => $c->id]);
    }

    public function test_session_with_registration_cannot_be_deleted(): void
    {
        $s = $this->session($this->course()); $this->registration($s);
        $this->assertFalse($s->delete());
        $this->assertDatabaseHas('course_sessions', ['id' => $s->id]);
    }

    public function test_course_and_session_with_only_cancelled_registrations_remain_protected(): void
    {
        $s = $this->session($c = $this->course()); $this->registration($s, ['status' => 'cancelled']);
        $this->assertFalse($s->delete());
        $this->assertFalse($c->delete());
    }

    public function test_empty_course_deletes_and_cascades_sessions_and_meetings(): void
    {
        $s = $this->session($c = $this->course());
        $this->assertSame(3, Meeting::where('course_session_id', $s->id)->count());
        $this->assertTrue($c->delete());
        $this->assertDatabaseMissing('course_sessions', ['id' => $s->id]);
        $this->assertSame(0, Meeting::where('course_session_id', $s->id)->count());
    }

    public function test_empty_session_deletes(): void
    {
        $s = $this->session($this->course());
        $this->assertTrue($s->delete());
        $this->assertSame(0, Meeting::where('course_session_id', $s->id)->count());
    }

    public function test_destroy_goes_through_the_model_protection(): void
    {
        $s = $this->session($c = $this->course()); $confirmed = $this->registration($s, ['status' => 'confirmed']); $pending = $this->registration($s, ['phone' => '0550000001']);
        $this->assertSame(0, Course::destroy($c->id));
        $this->assertSame(0, Registration::destroy($confirmed->id));
        $this->assertSame(1, Registration::destroy($pending->id));
        $this->assertDatabaseHas('courses', ['id' => $c->id]);
        $this->assertDatabaseHas('registrations', ['id' => $confirmed->id]);
    }

    public function test_meetings_cannot_be_deleted_once_the_session_has_registrations(): void
    {
        $s = $this->session($this->course());
        $this->assertTrue($s->meetings()->first()->delete());   // no registrations yet
        $this->registration($s);
        $this->assertFalse($s->meetings()->first()->delete());
    }

    // ---- registrations (T-01 + T-01.1) -------------------------------------------------------------------------------

    public function test_free_confirmed_registration_cannot_be_deleted(): void
    {
        $r = $this->registration($this->session($this->course()), ['status' => 'confirmed', 'method' => 'free', 'amount' => 0]);
        $this->assertNotNull($r->fresh()->confirmed_at);
        $this->assertFalse($r->delete());
        $this->assertDatabaseHas('registrations', ['id' => $r->id]);
    }

    public function test_status_downgrade_does_not_unprotect_a_confirmed_registration(): void   // R-1
    {
        $r = $this->registration($this->session($this->course()), ['status' => 'confirmed', 'method' => 'free']);
        $first = $r->fresh()->confirmed_at->toDateTimeString();
        $r->update(['status' => 'pending']);
        $this->assertFalse($r->fresh()->delete());
        $r->update(['status' => 'cancelled']);
        $this->assertFalse($r->fresh()->delete());
        $this->assertSame($first, $r->fresh()->confirmed_at->toDateTimeString());
    }

    public function test_confirmed_at_cannot_be_cleared_or_overwritten(): void
    {
        $r = $this->registration($this->session($this->course()), ['status' => 'confirmed']);
        $first = $r->fresh()->confirmed_at->toDateTimeString();
        $r->update(['status' => 'pending', 'confirmed_at' => null]);
        $this->assertSame($first, $r->fresh()->confirmed_at->toDateTimeString());
        $r->update(['confirmed_at' => now()->addYear()]);
        $this->assertSame($first, $r->fresh()->confirmed_at->toDateTimeString());
    }

    public function test_paid_registration_cannot_be_deleted(): void
    {
        $r = $this->registration($this->session($this->course()), ['status' => 'cancelled', 'paid_at' => now()]);
        $this->assertFalse($r->delete());
    }

    public function test_invoiced_registration_cannot_be_deleted_even_with_empty_string_invoice_id(): void
    {
        $s = $this->session($this->course());
        $this->assertFalse($this->registration($s, ['invoice_id' => '3140458'])->delete());
        $this->assertFalse($this->registration($s, ['invoice_id' => '', 'phone' => '0550000002'])->delete());   // non-null counts
    }

    public function test_unpaid_pending_and_cancelled_registrations_remain_deletable(): void
    {
        $s = $this->session($this->course());
        $this->assertTrue($this->registration($s, ['status' => 'pending'])->delete());
        $this->assertTrue($this->registration($s, ['status' => 'cancelled'])->delete());
    }

    public function test_deleting_an_eligible_registration_cascades_its_message_logs(): void
    {
        $r = $this->registration($this->session($this->course()));
        $log = MessageLog::create(['registration_id' => $r->id, 'automation_id' => $this->automation()->id, 'channel' => 'mail']);
        $this->assertTrue($r->delete());
        $this->assertDatabaseMissing('message_logs', ['id' => $log->id]);
    }

    // confirmed_at is set through every supported application path
    public function test_confirmed_at_is_set_when_a_registration_is_created_confirmed(): void
    {
        $this->assertNotNull($this->registration($this->session($this->course()), ['status' => 'confirmed'])->fresh()->confirmed_at);
    }

    public function test_confirmed_at_is_set_by_a_status_update_to_confirmed(): void   // Filament edit form = Eloquent update
    {
        $r = $this->registration($this->session($this->course()));
        $this->assertNull($r->fresh()->confirmed_at);
        $r->update(['status' => 'confirmed']);
        $this->assertNotNull($r->fresh()->confirmed_at);
    }

    public function test_confirmed_at_is_set_by_mark_paid(): void   // admin "Mark paid" action, return URL and webhook all call markPaid()
    {
        $r = $this->registration($this->session($this->course()), ['amount' => 60000, 'method' => 'ccp']);
        $r->markPaid();
        $r = $r->fresh();
        $this->assertSame('confirmed', $r->status); $this->assertNotNull($r->paid_at); $this->assertNotNull($r->confirmed_at);
    }

    public function test_confirmed_at_is_set_by_the_public_free_registration_form(): void
    {
        $s = $this->session($c = $this->course());
        $this->post(route('register', $c), ['course_session_id' => $s->id, 'name' => 'Test User', 'phone' => '0550123456', 'lang' => 'fr'])->assertRedirect();
        $r = Registration::firstOrFail();
        $this->assertSame('confirmed', $r->status); $this->assertNotNull($r->confirmed_at);
    }

    public function test_confirmed_at_is_set_by_the_slickpay_return_url(): void
    {
        config(['training.slickpay.key' => 'test-key']);
        Http::fake(['*' => Http::response(['success' => 1, 'completed' => 1])]);
        $r = $this->registration($this->session($this->course(), ['price' => 60000]), ['method' => 'cib', 'amount' => 60000, 'invoice_id' => '3140458']);
        $this->get(route('slickpay.return', $r))->assertRedirect(route('thanks', $r));
        $this->assertNotNull($r->fresh()->confirmed_at);
    }

    public function test_confirmed_at_is_set_by_the_slickpay_webhook(): void
    {
        config(['training.slickpay.key' => 'test-key']);
        Http::fake(['*' => Http::response(['success' => 1, 'completed' => 1])]);
        $r = $this->registration($this->session($this->course(), ['price' => 60000]), ['method' => 'cib', 'amount' => 60000, 'invoice_id' => '3140458']);
        $this->postJson('/webhooks/slickpay', ['id' => 3140458])->assertOk();
        $this->assertNotNull($r->fresh()->confirmed_at);
    }

    // ---- automations ---------------------------------------------------------------------------------------------------

    public function test_automation_with_message_logs_cannot_be_deleted_but_empty_one_can(): void
    {
        $r = $this->registration($this->session($this->course()));
        $used = $this->automation(); $empty = $this->automation(['name' => 'b']);
        MessageLog::create(['registration_id' => $r->id, 'automation_id' => $used->id, 'channel' => 'mail']);
        $this->assertFalse($used->delete());
        $this->assertDatabaseHas('automations', ['id' => $used->id]);
        $this->assertTrue($empty->delete());
    }

    public function test_course_is_not_deletable_when_a_scoped_automation_already_has_history(): void
    {
        $r = $this->registration($this->session($this->course()));   // registration of ANOTHER course
        $x = $this->course(); $a = $this->automation(['course_id' => $x->id]);
        MessageLog::create(['registration_id' => $r->id, 'automation_id' => $a->id, 'channel' => 'mail']);
        $this->assertFalse($x->delete());
    }

    public function test_course_with_an_automation_without_logs_can_still_be_deleted(): void
    {
        $x = $this->course(); $a = $this->automation(['course_id' => $x->id]);
        $this->assertTrue($x->delete());
        $this->assertDatabaseMissing('automations', ['id' => $a->id]);   // courses -> automations stays CASCADE
    }

    public function test_deleting_a_promoted_course_sets_promote_course_id_to_null(): void
    {
        $promoted = $this->course(); $a = $this->automation(['promote_course_id' => $promoted->id]);
        $this->assertTrue($promoted->delete());
        $this->assertNull($a->fresh()->promote_course_id);
        $this->assertDatabaseHas('automations', ['id' => $a->id]);
    }

    // ---- meetings repeater guard (T-01.1, R-2) ---------------------------------------------------------------------------

    public function test_removed_meeting_detection_only_applies_when_the_session_has_registrations(): void
    {
        $s = $this->session($this->course());
        $this->assertSame([], $s->meetingIdsRemovedBy([]));   // no registrations: removal allowed

        $this->registration($s);
        $ids = $s->meetings()->pluck('id')->all();
        $all = array_map(fn ($id) => "record-{$id}", $ids);
        $this->assertSame([], $s->meetingIdsRemovedBy($all));
        $this->assertSame([$ids[0]], $s->meetingIdsRemovedBy(array_slice($all, 1)));
        $this->assertEqualsCanonicalizing($ids, $s->meetingIdsRemovedBy([]));
        $this->assertSame([], $s->meetingIdsRemovedBy(array_merge($all, ['0e4f-new-row'])));   // extra new rows are fine
    }

    /** Needs Livewire + an admin allowed into the panel; Filament testing helper names are UNVERIFIED. */
    public function test_edit_page_refuses_a_save_that_removes_meetings_and_does_not_look_successful(): void
    {
        foreach ([\Livewire\Livewire::class, \App\Models\User::class] as $class) {
            if (!class_exists($class)) $this->markTestSkipped("$class is not available in this environment.");
        }
        $s = $this->session($this->course()); $this->registration($s);
        $this->actingAs(\App\Models\User::factory()->create());   // must be allowed into the panel (canAccessPanel), see audit F-10

        $page = \Livewire\Livewire::test(\App\Filament\Resources\CourseSessionResource\Pages\EditCourseSession::class, ['record' => $s->getKey()]);
        $rows = $page->get('data.meetings'); array_shift($rows);
        $page->set('data.meetings', $rows)->call('save')
            ->assertNotified('Meetings cannot be removed')
            ->assertNoRedirect();
        $this->assertSame(3, $s->meetings()->count());   // nothing was saved
    }

    // ---- foreign keys (MySQL / MariaDB only) -----------------------------------------------------------------------------

    public function test_foreign_key_delete_rules_match_t01_for_the_application_tables(): void
    {
        $this->onMysql();
        $tables = ['course_sessions', 'meetings', 'registrations', 'automations', 'message_logs'];
        $marks = implode(',', array_fill(0, count($tables), '?'));
        $rows = DB::select("select k.TABLE_NAME t, k.COLUMN_NAME c, r.DELETE_RULE d
            from information_schema.KEY_COLUMN_USAGE k
            join information_schema.REFERENTIAL_CONSTRAINTS r on r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA and r.CONSTRAINT_NAME = k.CONSTRAINT_NAME and r.TABLE_NAME = k.TABLE_NAME
            where k.TABLE_SCHEMA = database() and k.REFERENCED_TABLE_NAME is not null and k.TABLE_NAME in ($marks)", $tables);
        $got = collect($rows)->mapWithKeys(fn ($o) => ["{$o->t}.{$o->c}" => $o->d])->all();   // other packages' tables are ignored
        foreach (self::FK_RULES as $column => $rule) $this->assertSame($rule, $got[$column] ?? null, "delete rule of $column");
        $this->assertSame([], array_diff_key($got, self::FK_RULES), 'unexpected FK in an application table (message_logs.meeting_id must stay FK-less)');
    }

    public function test_database_refuses_raw_deletes_that_would_orphan_history(): void
    {
        $this->onMysql();
        $s = $this->session($c = $this->course()); $r = $this->registration($s); $a = $this->automation();
        MessageLog::create(['registration_id' => $r->id, 'automation_id' => $a->id, 'channel' => 'mail']);
        foreach ([['courses', $c->id], ['course_sessions', $s->id], ['automations', $a->id]] as [$table, $id]) {
            try { DB::table($table)->where('id', $id)->delete(); $this->fail("raw delete on $table should be refused"); }
            catch (QueryException $e) { $this->assertDatabaseHas($table, ['id' => $id]); }
        }
    }
}

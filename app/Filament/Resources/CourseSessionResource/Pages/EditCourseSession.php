<?php
namespace App\Filament\Resources\CourseSessionResource\Pages;
use App\Filament\Resources\CourseSessionResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;

class EditCourseSession extends EditRecord
{
    protected static string $resource = CourseSessionResource::class;

    /**
     * T-01.1: Filament 3.x EditRecord::save() calls beforeSave() inside form->getState(afterValidate: ...), i.e. BEFORE the
     * meetings repeater persists its removals and before the record is updated. Throwing Halt (caught by the page) aborts the
     * whole save: nothing is written, no "saved" notification is sent and there is no redirect.
     */
    protected function beforeSave(): void
    {
        $submitted = array_keys((array) data_get($this->form->getRawState(), 'meetings', []));

        if ($this->getRecord()->meetingIdsRemovedBy($submitted) === []) return;

        Notification::make()->danger()->persistent()
            ->title('Meetings cannot be removed')
            ->body('This session already has registrations. Change the dates or topics of its meetings instead of deleting them. Nothing was saved.')
            ->send();

        throw new Halt();
    }

    protected function getHeaderActions(): array
    {
        $why = 'This session has registrations and cannot be deleted. Close it (status = Closed) instead.';

        return [
            // T-01 / T-01.1: disabled + tooltip (Filament refuses to mount a disabled action server-side); the model hook and
            // the DB foreign keys still refuse if both are bypassed, and failureNotificationTitle covers the race case.
            DeleteAction::make()
                ->disabled(fn ($record) => ! $record->canBeDeleted())
                ->tooltip(fn ($record) => $record->canBeDeleted() ? null : $why)
                ->failureNotificationTitle($why),
        ];
    }
}

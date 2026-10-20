<?php
namespace App\Filament\Resources\CourseResource\Pages;
use App\Filament\Resources\CourseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCourse extends EditRecord
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        $why = 'This course has registrations (or automations with sent messages) and cannot be deleted. Set it to inactive instead.';

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

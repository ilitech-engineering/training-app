<?php
namespace App\Filament\Resources\AutomationResource\Pages;
use App\Filament\Resources\AutomationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAutomation extends EditRecord
{
    protected static string $resource = AutomationResource::class;

    protected function getHeaderActions(): array
    {
        $why = 'This automation has message history and cannot be deleted. Deactivate it (Active = off) instead.';

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

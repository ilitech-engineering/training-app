<?php
namespace App\Filament\Resources\RegistrationResource\Pages;
use App\Filament\Resources\RegistrationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRegistration extends EditRecord
{
    protected static string $resource = RegistrationResource::class;

    protected function getHeaderActions(): array
    {
        $why = 'Confirmed, paid or invoiced registrations are historical records and cannot be deleted. Cancel it instead.';

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

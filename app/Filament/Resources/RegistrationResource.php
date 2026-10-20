<?php
namespace App\Filament\Resources;
use App\Filament\Resources\RegistrationResource\Pages;
use App\Models\{CourseSession, Registration};
use Filament\Forms\Components\{Select, TextInput, Toggle};
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RegistrationResource extends Resource
{
    protected static ?string $model = Registration::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form->columns(3)->schema([
            Select::make('course_id')->relationship('course', 'slug')->required()->live(),
            Select::make('course_session_id')->label('Session')->required()->options(fn ($get) => CourseSession::where('course_id', $get('course_id'))->orderBy('starts_at')->get()->mapWithKeys(fn ($s) => [$s->id => $s->starts_at->format('d/m/Y H:i')])),
            Select::make('locale')->options(['ar' => 'AR', 'fr' => 'FR', 'en' => 'EN'])->default('ar'),
            TextInput::make('name')->required(), TextInput::make('phone')->required(), TextInput::make('email')->email(),
            Select::make('status')->options(['pending' => 'Pending', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled'])->default('confirmed'),
            Select::make('method')->options(['free' => 'Free', 'cib' => 'CIB/Edahabia', 'ccp' => 'CCP/BaridiMob', 'cash' => 'Cash (on site)'])->default('cash'),
            TextInput::make('amount')->numeric()->default(0), Toggle::make('consent')->label('Marketing consent'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->columns([
            Tables\Columns\TextColumn::make('created_at')->dateTime('d/m H:i')->sortable(),
            Tables\Columns\TextColumn::make('name')->searchable(), Tables\Columns\TextColumn::make('phone')->searchable(), Tables\Columns\TextColumn::make('course.slug'),
            Tables\Columns\TextColumn::make('session.starts_at')->dateTime('d/m/Y'), Tables\Columns\TextColumn::make('method')->badge(),
            Tables\Columns\TextColumn::make('amount')->money('DZD'), Tables\Columns\TextColumn::make('status')->badge()->color(fn ($s) => ['confirmed' => 'success', 'pending' => 'warning', 'cancelled' => 'danger'][$s]),
        ])->filters([
            Tables\Filters\SelectFilter::make('course_id')->relationship('course', 'slug'), Tables\Filters\SelectFilter::make('status')->options(['pending' => 'Pending', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled']),
            Tables\Filters\SelectFilter::make('method')->options(['free' => 'Free', 'cib' => 'CIB', 'ccp' => 'CCP', 'cash' => 'Cash']),
        ])->actions([
            Tables\Actions\Action::make('paid')->label('Mark paid')->icon('heroicon-o-check-badge')->color('success')->requiresConfirmation()
                ->visible(fn (Registration $r) => $r->status === 'pending')->action(fn (Registration $r) => $r->markPaid()),   // triggers the "payment confirmed" automation
            Tables\Actions\Action::make('whatsapp')->icon('heroicon-o-chat-bubble-left')->url(fn (Registration $r) => 'https://wa.me/' . \App\Services\Messenger::dz($r->phone))->openUrlInNewTab(),
            Tables\Actions\EditAction::make(),
        ])->bulkActions([Tables\Actions\BulkAction::make('csv')->label('Export CSV')->action(fn ($records) => response()->streamDownload(function () use ($records) {
            $f = fopen('php://output', 'w'); fputcsv($f, ['name', 'phone', 'email', 'status', 'amount']);
            foreach ($records as $r) fputcsv($f, [$r->name, $r->phone, $r->email, $r->status, $r->amount]); fclose($f);
        }, 'registrations.csv'))]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListRegistrations::route('/'), 'create' => Pages\CreateRegistration::route('/create'), 'edit' => Pages\EditRegistration::route('/{record}/edit')];
    }
}

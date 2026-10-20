<?php
namespace App\Filament\Resources;
use App\Filament\Resources\AutomationResource\Pages;
use App\Models\Automation;
use Filament\Forms\Components\{CheckboxList, Section, Select, Textarea, TextInput, Toggle};
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/** Emails / WhatsApp / SMS rules: "send X, on channels Y, N minutes before/after registration, payment or each class". */
class AutomationResource extends Resource
{
    protected static ?string $model = Automation::class;
    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('When & to whom')->columns(3)->schema([
                TextInput::make('name')->required(),
                Select::make('course_id')->relationship('course', 'slug')->placeholder('All courses'),
                Toggle::make('active')->default(true),
                Select::make('anchor')->required()->options(['registration' => 'After registration', 'paid' => 'After payment confirmed', 'meeting' => 'Around each class'])->default('registration'),
                TextInput::make('offset_minutes')->numeric()->default(0)->helperText('Minutes. Negative = before (e.g. -1440 = 24h before each class). 2880 = 2 days after.'),
                Select::make('audience')->options(['all' => 'Everyone', 'free' => 'Free registrations', 'paid' => 'Paid registrations'])->default('all'),
                CheckboxList::make('channels')->options(['mail' => 'Email', 'whatsapp' => 'WhatsApp', 'sms' => 'SMS'])->required()->columns(3),
                Toggle::make('is_promo')->label('Promotion (only people who gave marketing consent)'),
                Select::make('promote_course_id')->relationship('promote', 'slug')->placeholder('— none —')->label('Course promoted ({promo} link)'),
            ]),
            Section::make('Message')->description('Placeholders: {name} {course} {date} {time} {location} {link} {page} {promo} {ticket}')->schema(
                collect(['ar', 'fr', 'en'])->flatMap(fn ($l) => [TextInput::make("subject.$l")->label("Subject ($l) — email"), Textarea::make("body.$l")->label("Message ($l)")->rows(4)->required($l === 'fr')])->all()
            ),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable(), Tables\Columns\TextColumn::make('anchor')->badge(), Tables\Columns\TextColumn::make('offset_minutes')->suffix(' min'),
            Tables\Columns\TextColumn::make('audience'), Tables\Columns\TextColumn::make('channels')->badge(), Tables\Columns\IconColumn::make('active')->boolean(),
        ])->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListAutomations::route('/'), 'create' => Pages\CreateAutomation::route('/create'), 'edit' => Pages\EditAutomation::route('/{record}/edit')];
    }
}

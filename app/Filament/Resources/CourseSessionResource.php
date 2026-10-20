<?php
namespace App\Filament\Resources;
use App\Filament\Resources\CourseSessionResource\Pages;
use App\Models\CourseSession;
use Filament\Forms\Components\{DateTimePicker, Repeater, Section, Select, TextInput};
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/** One dated cohort of a course. Creating it generates the weekly classes (6 by default); edit them below. */
class CourseSessionResource extends Resource
{
    protected static ?string $model = CourseSession::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Sessions (dates)';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->columns(3)->schema([
                Select::make('course_id')->relationship('course', 'slug')->required(),
                DateTimePicker::make('starts_at')->required()->seconds(false)->label('First class'),
                Select::make('mode')->options(['online' => 'Online', 'offline' => 'In person', 'hybrid' => 'Hybrid'])->default('online')->live(),
                TextInput::make('location')->label('Address (in person / hybrid)'),
                TextInput::make('meeting_url')->label('Online link (Meet / Zoom / Teams)'),
                Select::make('status')->options(['open' => 'Open', 'closed' => 'Closed'])->default('open'),
                TextInput::make('seats')->numeric()->default(20),
                TextInput::make('price')->numeric()->default(0)->helperText('0 = free'),
                TextInput::make('early_price')->numeric()->helperText('Optional'), TextInput::make('early_seats')->numeric()->default(0)->helperText('Seats sold at the early price'),
            ]),
            Repeater::make('meetings')->relationship()->visibleOn('edit')->orderColumn('n')->deletable(fn ($record) => ! ($record?->registrations()->exists() ?? false))->columns(4)->schema([
                TextInput::make('n')->numeric()->label('#'), DateTimePicker::make('starts_at')->seconds(false)->required(), TextInput::make('topic'), TextInput::make('meeting_url'),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('starts_at', 'desc')->columns([
            Tables\Columns\TextColumn::make('course.slug')->sortable(), Tables\Columns\TextColumn::make('starts_at')->dateTime('d/m/Y H:i')->sortable(),
            Tables\Columns\TextColumn::make('mode')->badge(), Tables\Columns\TextColumn::make('price')->money('DZD'),
            Tables\Columns\TextColumn::make('registrations_count')->counts('registrations')->label('Registered'), Tables\Columns\TextColumn::make('seats'), Tables\Columns\TextColumn::make('status')->badge(),
        ])->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListCourseSessions::route('/'), 'create' => Pages\CreateCourseSession::route('/create'), 'edit' => Pages\EditCourseSession::route('/{record}/edit')];
    }
}

<?php
namespace App\Filament\Resources;
use App\Filament\Resources\CourseResource\Pages;
use App\Models\Course;
use Filament\Forms\Components\{Builder, Repeater, RichEditor, Section, Select, Tabs, Textarea, TextInput, Toggle};
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    /** Landing-page building blocks: add / remove / reorder them freely, per language. */
    public static function blocks(): array
    {
        $t = fn ($n) => TextInput::make($n);
        $a = fn ($n) => Textarea::make($n)->rows(3);
        $lines = fn ($n) => Textarea::make($n)->rows(4)->helperText('One item per line');
        return [
            Block::make('hero')->icon('heroicon-o-sparkles')->schema([$t('pill'), $t('title'), $t('subtitle'), $a('text'), $t('instructor'), $lines('facts'), $t('cta'), $t('cta2')]),
            Block::make('cards')->icon('heroicon-o-squares-2x2')->schema([Select::make('variant')->options(['light' => 'Light', 'dark' => 'Dark'])->default('light'), $t('title'), $a('lead'), Repeater::make('items')->schema([$t('icon'), $t('title'), $a('text')])->columns(3)]),
            Block::make('checklist')->icon('heroicon-o-check-circle')->schema([$t('title'), $lines('items')]),
            Block::make('compare')->icon('heroicon-o-scale')->schema([$t('bad_title'), $lines('bad'), $t('good_title'), $lines('good')]),
            Block::make('curriculum')->icon('heroicon-o-calendar-days')->schema([$t('title'), $a('lead'), Repeater::make('weeks')->schema([$t('title'), $lines('points')])]),
            Block::make('bonuses')->icon('heroicon-o-gift')->schema([$t('title'), Repeater::make('items')->schema([$t('icon'), $t('title'), $a('text'), $t('value')])->columns(4), $t('total')]),
            Block::make('instructor')->icon('heroicon-o-user')->schema([$t('title'), $t('name'), $t('linkedin'), $a('bio'), $lines('stats'), $t('skills')->helperText('Comma separated'), $t('cases_title'), $lines('cases')]),
            Block::make('guarantee')->icon('heroicon-o-shield-check')->schema([$t('title'), $a('text')]),
            Block::make('faq')->icon('heroicon-o-question-mark-circle')->schema([$t('title'), Repeater::make('items')->schema([$t('q'), $a('a')])]),
            Block::make('testimonials')->icon('heroicon-o-chat-bubble-left-right')->schema([$t('title'), Repeater::make('items')->schema([$t('name'), $a('text')])]),
            Block::make('richtext')->icon('heroicon-o-document-text')->schema([RichEditor::make('content')]),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('General')->columns(3)->schema([
                TextInput::make('slug')->required()->alphaDash()->unique(ignoreRecord: true)->helperText('training.ilitech-engineering.dz/{slug}'),
                TextInput::make('weeks')->numeric()->default(6)->required()->helperText('Number of weekly classes generated for each session'),
                Toggle::make('active')->default(true),
                TextInput::make('title.ar')->label('Title AR')->required(), TextInput::make('title.fr')->label('Title FR'), TextInput::make('title.en')->label('Title EN'),
            ]),
            Section::make('Payment & contact')->columns(2)->collapsed()->schema([
                TextInput::make('contact.whatsapp')->helperText('213XXXXXXXXX'), TextInput::make('contact.group')->label('WhatsApp group link'),
                TextInput::make('contact.ccp_number'), TextInput::make('contact.ccp_name'), TextInput::make('contact.rip'),
            ]),
            Section::make('Paid add-ons (checkout)')->collapsed()->schema([
                Repeater::make('addons')->columns(5)->schema([TextInput::make('key')->required()->alphaDash(), TextInput::make('label.ar'), TextInput::make('label.fr'), TextInput::make('label.en'), TextInput::make('price')->numeric()->required()]),
            ]),
            Section::make('SEO')->collapsed()->schema(collect(['ar', 'fr', 'en'])->flatMap(fn ($l) => [TextInput::make("seo.title.$l")->label("Title $l"), Textarea::make("seo.description.$l")->label("Description $l")->rows(2)])->all()),
            Tabs::make('Landing page')->columnSpanFull()->tabs(collect(['ar', 'fr', 'en'])->map(
                fn ($l) => Tab::make(strtoupper($l))->schema([Builder::make("content.$l")->label('Blocks')->blocks(self::blocks())->collapsible()->cloneable()])
            )->all()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('slug')->searchable(), Tables\Columns\TextColumn::make('title.fr')->label('Title'),
            Tables\Columns\TextColumn::make('sessions_count')->counts('sessions')->label('Sessions'), Tables\Columns\TextColumn::make('registrations_count')->counts('registrations')->label('Registrations'),
            Tables\Columns\IconColumn::make('active')->boolean(),
        ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\Action::make('open')->icon('heroicon-o-arrow-top-right-on-square')->url(fn (Course $r) => route('course', $r->slug))->openUrlInNewTab()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListCourses::route('/'), 'create' => Pages\CreateCourse::route('/create'), 'edit' => Pages\EditCourse::route('/{record}/edit')];
    }
}

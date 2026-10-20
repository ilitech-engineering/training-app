<?php
namespace App\Filament\Resources;
use App\Filament\Resources\MessageLogResource\Pages;
use App\Models\MessageLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MessageLogResource extends Resource
{
    protected static ?string $model = MessageLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $navigationLabel = 'Sent messages';
    public static function canCreate(): bool { return false; }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->columns([
            Tables\Columns\TextColumn::make('created_at')->dateTime('d/m H:i')->sortable(), Tables\Columns\TextColumn::make('registration.name')->searchable(),
            Tables\Columns\TextColumn::make('automation.name'), Tables\Columns\TextColumn::make('channel')->badge(),
            Tables\Columns\TextColumn::make('status')->badge()->color(fn ($s) => ['sent' => 'success', 'failed' => 'danger', 'skipped' => 'gray'][$s] ?? 'warning'), Tables\Columns\TextColumn::make('error')->limit(60),
        ])->filters([Tables\Filters\SelectFilter::make('status')->options(['queued' => 'Queued', 'sent' => 'Sent', 'failed' => 'Failed', 'skipped' => 'Skipped'])]);
    }

    public static function getPages(): array { return ['index' => Pages\ListMessageLogs::route('/')]; }
}

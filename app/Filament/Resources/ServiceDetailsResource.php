<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use App\Models\ServiceDetails;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use App\Filament\Resources\ServiceDetailsResource\Pages;

class ServiceDetailsResource extends Resource
{
   protected static ?string $model = ServiceDetails::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $modelLabel = 'Service Detail';
    protected static ?string $pluralModelLabel = 'Service Details';
    protected static ?string $navigationGroup = 'Services';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Service Selection
                Forms\Components\Section::make('Service Selection')
                    ->schema([
                        Select::make('service_id')
                            ->relationship('service', 'name')
                            ->required()
                            ->unique(
                                table: ServiceDetails::class,
                                column: 'service_id',
                                ignoreRecord: true
                            ),
                    ]),

                // Basic Information
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $set('slug', Str::slug($state));
                            }),

                        Forms\Components\TextInput::make('subtitle')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(
                                table: ServiceDetails::class,
                                column: 'slug',
                                ignoreRecord: true
                            ),

                        Forms\Components\RichEditor::make('description')
                            ->required()
                            ->columnSpanFull(),
                    ]),

                // FAQs Section
                Forms\Components\Section::make('FAQs')
                    ->schema([
                        Forms\Components\Repeater::make('faqs')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('question')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\RichEditor::make('answer')
                                    ->required()
                                    ->columnSpanFull(),
                            ])
                            ->defaultItems(1)
                            ->columnSpanFull()
                            ->addActionLabel('Add FAQ')
                            ->reorderable()
                    ]),

                // What's Included
                Forms\Components\Section::make("What's Included")
                    ->schema([
                        Forms\Components\Repeater::make('whatIncludes')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('item')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->defaultItems(3)
                            ->columnSpanFull()
                            ->addActionLabel('Add Item')
                            ->reorderable()
                    ]),

                // Case Studies
                Forms\Components\Section::make('Case Studies')
                    ->schema([
                        Forms\Components\Repeater::make('caseStudies')
                            ->relationship()
                            ->schema([
                                Forms\Components\RichEditor::make('description')
                                    ->columnSpanFull()
                                    ->required(),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->columnSpanFull()
                            ->addActionLabel('Add Case Study')
                            ->reorderable()
                    ]),

                // Service Images - Using Repeater for direct relationship management
                Forms\Components\Section::make('Service Images')
                    ->schema([
                         Forms\Components\FileUpload::make('images')
                            ->image()
                            ->multiple()
                            ->maxFiles(5)
                            ->acceptedFileTypes(['image/*'])
                            ->columnSpanFull()
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()->limit(50),
                Tables\Columns\TextColumn::make('subtitle')
                    ->searchable()->limit(50)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('faqs_count')
                    ->label('FAQs')
                    ->counts('faqs'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('service')
                    ->relationship('service', 'name')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('service.name');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceDetails::route('/'),
            'create' => Pages\CreateServiceDetails::route('/create'),
            'edit' => Pages\EditServiceDetails::route('/{record}/edit'),
        ];
    }
}

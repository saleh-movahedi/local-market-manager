<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StoreProductResource\Pages;
use App\Filament\Resources\StoreProductResource\RelationManagers;
use App\Models\StoreProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StoreProductResource extends Resource
{
    protected static ?string $model = StoreProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('store_id')
                    ->relationship('store', 'name')
                    ->required(),
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required(),
                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\TextInput::make('ok_price')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('discount_percent')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('supply_status')
                    ->required(),
                Forms\Components\TextInput::make('max_order_limit')
                    ->required()
                    ->numeric()
                    ->default(5),
                Forms\Components\TextInput::make('cost_price')
                    ->numeric(),
                Forms\Components\TextInput::make('vat_rate')
                    ->required()
                    ->numeric()
                    ->default(9),
                Forms\Components\DateTimePicker::make('last_synced_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('product.thumbnail_url')
                    ->label('عکس')
                    ->size(40)
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder.png')),
                Tables\Columns\TextColumn::make('store.name')
                    ->label('فروشگاه')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('محصول')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('price')
                    ->label('قیمت اصلی')
                    ->money('IRT')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ok_price')
                    ->label('قیمت نهایی')
                    ->money('IRT')
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount_percent')
                    ->label('تخفیف')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('موجودی')
                    ->sortable(),
                Tables\Columns\TextColumn::make('supply_status')
                    ->label('وضعیت')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'available' => 'success',
                        'unavailable' => 'danger',
                        'low' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_order_limit')
                    ->label('حداکثر')
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_synced_at')
                    ->label('آخرین سینک')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListStoreProducts::route('/'),
            'create' => Pages\CreateStoreProduct::route('/create'),
            'edit' => Pages\EditStoreProduct::route('/{record}/edit'),
        ];
    }
}

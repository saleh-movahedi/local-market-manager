<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MerchantProductResource\Pages;
use App\Filament\Resources\MerchantProductResource\RelationManagers;
use App\Models\MerchantProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MerchantProductResource extends Resource
{
    protected static ?string $model = MerchantProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('merchant_id')
                    ->relationship('merchant', 'name')
                    ->required(),
                Forms\Components\Select::make('store_product_id')
                    ->relationship('storeProduct', 'id')
                    ->required(),
                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('custom_price')
                    ->numeric(),
                Forms\Components\TextInput::make('custom_ok_price')
                    ->numeric(),
                Forms\Components\TextInput::make('custom_discount')
                    ->numeric(),
                Forms\Components\TextInput::make('margin_percent')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('merchant.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('storeProduct.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('custom_price')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('custom_ok_price')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('custom_discount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('margin_percent')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
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
            'index' => Pages\ListMerchantProducts::route('/'),
            'create' => Pages\CreateMerchantProduct::route('/create'),
            'edit' => Pages\EditMerchantProduct::route('/{record}/edit'),
        ];
    }
}

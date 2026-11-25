<?php

namespace App\Filament\Resources\StoreResource\Pages;

use App\Filament\Resources\StoreResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Models\Category;
use App\Jobs\SyncCategoryProducts;

class EditStore extends EditRecord
{
    protected static string $resource = StoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sync')
                ->label('سینک محصولات')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('سینک محصولات فروشگاه')
                ->modalDescription('این عملیات محصولات تمام دسته‌بندی‌ها را از API اوکالا دریافت و در دیتابیس ذخیره می‌کند. ممکن است چند دقیقه طول بکشد.')
                ->modalSubmitActionLabel('شروع سینک')
                ->modalCancelActionLabel('انصراف')
                ->action(function () {
                    $store = $this->record;
                    $categories = Category::where('level', 2)->get();
                    $totalCategories = $categories->count();
                    $syncedCount = 0;
                    $errorCount = 0;

                    foreach ($categories as $category) {
                        try {
                            \App\Jobs\SyncCategoryProducts::dispatchSync($this->record->id, $category->id);
                            $syncedCount++;
                        } catch (\Exception $e) {
                            $errorCount++;
                        }
                    }

                    Notification::make()
                        ->title('سینک به صف اضافه شد')
                        ->body("{$syncedCount} دسته‌بندی برای فروشگاه {$this->record->name} به صف اضافه شد. پردازش در پس‌زمینه انجام می‌شود.")
                        ->success()
                        ->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }
}

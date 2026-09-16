<?php

declare(strict_types=1);

namespace App\Filament\Resources\Finances\ExpenseResource\Pages;

use App\Filament\Resources\Finances\Concerns\HandlesFinancialDocuments;
use App\Filament\Resources\Finances\ExpenseResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditExpense extends EditRecord
{
    use HandlesFinancialDocuments;

    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();

        return $data;
    }

    protected function afterSave(): void
    {
        $this->removeFinancialDocuments($this->record, $this->data['remove_documents'] ?? []);
        $this->storeFinancialDocuments($this->record, $this->data['documents_uploads'] ?? []);
    }
}

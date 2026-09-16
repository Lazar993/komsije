<?php

declare(strict_types=1);

namespace App\Filament\Resources\Finances\ExpenseResource\Pages;

use App\Filament\Resources\Finances\Concerns\HandlesFinancialDocuments;
use App\Filament\Resources\Finances\ExpenseResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateExpense extends CreateRecord
{
    use HandlesFinancialDocuments;

    protected static string $resource = ExpenseResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->storeFinancialDocuments($this->record, $this->data['documents_uploads'] ?? []);
    }
}

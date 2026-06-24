<?php

namespace App\Filament\Resources\CatalogItemResource\Pages;

use App\Filament\Resources\CatalogItemResource;
use App\Models\CatalogItemTaxonomyTerm;
use App\Models\CatalogTaxonomy;
use Filament\Resources\Pages\EditRecord;

class EditCatalogItem extends EditRecord
{
    protected static string $resource = CatalogItemResource::class;

    private ?int $primaryGroupTermId = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['primary_group_term_id'] = CatalogItemResource::primaryGroupTermId($this->record);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->primaryGroupTermId = isset($data['primary_group_term_id'])
            ? (int) $data['primary_group_term_id']
            : null;

        unset($data['primary_group_term_id']);

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->primaryGroupTermId === null) {
            return;
        }

        $taxonomy = CatalogTaxonomy::query()
            ->where('code', 'primary_group')
            ->first();

        if ($taxonomy === null) {
            return;
        }

        $this->record->taxonomyAssignments()
            ->where('catalog_taxonomy_id', $taxonomy->id)
            ->where('catalog_taxonomy_term_id', '!=', $this->primaryGroupTermId)
            ->delete();

        CatalogItemTaxonomyTerm::query()->updateOrCreate(
            [
                'catalog_item_id' => $this->record->id,
                'catalog_taxonomy_term_id' => $this->primaryGroupTermId,
            ],
            [
                'catalog_taxonomy_id' => $taxonomy->id,
                'source' => 'manual',
            ]
        );
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return '产品资料更新成功';
    }

    protected function getValidationErrorNotificationTitle(): ?string
    {
        return '保存失败：表单内容有误';
    }
}

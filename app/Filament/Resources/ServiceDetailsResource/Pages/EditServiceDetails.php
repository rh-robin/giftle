<?php

namespace App\Filament\Resources\ServiceDetailsResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\ServiceDetailsResource;

class EditServiceDetails extends EditRecord
{
    protected static string $resource = ServiceDetailsResource::class;

    protected $formData = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load existing images and videos into the form
        $data['images'] = $this->record->images->pluck('images')->toArray();
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->formData = $data;
        unset($data['images']);
        return $data;
    }

    protected function afterSave(): void
    {
        $project = $this->record;

        // Optionally preserve existing files
        $existingImages = $project->images->pluck('images')->toArray();


        // Handle images
        if (isset($this->formData['images']) && is_array($this->formData['images'])) {
            $newImages = $this->formData['images'];
            $imagesToDelete = array_diff($existingImages, $newImages);
            $project->images()->whereIn('images', $imagesToDelete)->delete();
            foreach ($newImages as $imagePath) {
                if (!in_array($imagePath, $existingImages)) {
                    $project->images()->create(['images' => $imagePath]);
                }
            }
        }
    }
}

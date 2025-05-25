<?php

namespace App\Filament\Resources\ServiceDetailsResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\ServiceDetailsResource;

class CreateServiceDetails extends CreateRecord
{
    protected static string $resource = ServiceDetailsResource::class;
    protected $formData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->formData = $data;
        unset($data['images']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $project = $this->record;

        // Save images
        if (isset($this->formData['images']) && is_array($this->formData['images'])) {
            foreach ($this->formData['images'] as $imagePath) {
                $project->images()->create([
                    'images' => $imagePath,
                ]);
            }
        }

    }
}

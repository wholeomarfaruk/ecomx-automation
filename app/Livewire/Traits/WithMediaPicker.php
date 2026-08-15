<?php

namespace App\Livewire\Traits;

trait WithMediaPicker
{
    public function getListeners()
    {
        $listeners = method_exists(get_parent_class($this), 'getListeners')
            ? parent::getListeners()
            : [];

        return array_merge($listeners, [
            'mediaSelected' => 'mediaSelected',
        ]);
    }

    public function mediaSelected($field, $id)
    {
        if (is_array($id)) {
            $existing = $this->$field ?? [];
            $this->$field = array_values(array_unique(array_merge($existing, $id), SORT_REGULAR));
        } else {
            $this->$field = $id;
        }
    }

    public function removeMedia($field, $id = null)
    {
        $current = $this->$field ?? null;

        if (is_array($current) && $id !== null) {
            $this->$field = array_values(array_filter(
                $current,
                function ($item) use ($id) {
                    if (is_array($item)) {
                        return ($item['id'] ?? $item['path'] ?? null) != $id;
                    }
                    return $item != $id;
                }
            ));
        } else {
            $this->$field = null;
        }
    }
}
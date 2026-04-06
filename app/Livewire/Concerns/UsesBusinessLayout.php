<?php

namespace App\Livewire\Concerns;

use Illuminate\Contracts\View\View;

trait UsesBusinessLayout
{
    protected function renderInBusinessLayout(
        string $view,
        array $data = [],
        string $title = 'Dashboard',
        string $sectionLabel = 'Mi Negocio'
    ): View {
        return view($view, $data)->layout('layouts.business', [
            'title' => $title,
            'sectionLabel' => $sectionLabel,
        ]);
    }
}

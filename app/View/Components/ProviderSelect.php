<?php

namespace App\View\Components;

use Illuminate\Http\Request;
use Illuminate\View\Component;
use Illuminate\View\View;

class ProviderSelect extends Component
{
    /** @var array<string, string>  */
    public array $providers;

    public ?string $selected;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->providers = app()->make('mealProviders');
        $this->selected = $request->input('provider');
    }

    public function isSelected(string $option): bool
    {
        return $option === $this->selected;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|string
     */
    public function render()
    {
        return view('components.provider-select');
    }
}

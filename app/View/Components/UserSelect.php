<?php

namespace App\View\Components;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\View\Component;
use Illuminate\View\View;

class UserSelect extends Component
{
    /** @var Collection|User[] */
    public Collection|array $users;
    public ? int $selected;
    public $showOptionAll;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(Request $request, $showOptionAll = false)
    {
        $this->users = User::orderBy('name')->get();
        $this->selected = $request->input('user_id');
        $this->showOptionAll = $showOptionAll;
    }

    public function isSelected($option)
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
        return view('components.user-select');
    }
}

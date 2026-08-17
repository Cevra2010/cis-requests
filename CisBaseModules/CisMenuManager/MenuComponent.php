<?php

namespace CisFoundation\CisMenuManager;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * The view Component for accessing the Menu in Blade View
 */
class MenuComponent extends Component
{
    /**
     * Slug of the Menu
     *
     * @var string
     */
    protected string $slug;


    /**
     * Register menu Slug
     *
     * @param string $slug
     */
    public function __construct(string $slug)
    {
        $this->slug = $slug;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|\Closure|string
     */
    public function render()
    {
        $menu = \CisFoundation\CisMenuManager\Facades\Menu::get($this->slug)->finalize();
        return view($menu->getTemplate(),[
            'menu' => $menu,
        ]);
    }
}

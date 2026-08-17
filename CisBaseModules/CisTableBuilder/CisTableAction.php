<?php
namespace CisFoundation\CisTableBuilder;

use CisFoundation\CisTableBuilder\Exception\MethodNotFoundException;
use Illuminate\Support\Facades\Route;

class CisTableAction {

    /**
     * Link name of the action
     *
     * @var string
     */
    public string $name;

    /**
     * @var string
     */
    public string $slug;

    /**
     * Route name
     *
     * @var string
     */
    public string $route;

    protected string $cssClass = '';

    /**
     * Parameters for the route
     *
     * @var array
     */
    public array $parameters;

    /**
     * Method of the link [post,get]
     *
     * @var string
     */
    public string $method = "get";


    /**
     * Setting default vars
     *
     * @returns CisTableAction
     */
    public function __construct()
    {
        $this->parameters = [];
        return $this;
    }

    /**
     * Returns the final link for the action
     *
     * @param mixed $data
     * @return string
     * @throws MethodNotFoundException
     */
    public function getLink(mixed $data): string
    {
        $cssClass = ($this->cssClass) ? 'class="'.$this->cssClass.'"' : null;
        if($this->method == 'get') {
            return sprintf('<a href="%s" %s id="options-action-%s">%s</a>',
                $this->getUrl($data),
                $cssClass,
                $this->slug,
                $this->name
            );
        }
        elseif($this->method == 'post') {
            return '<form action="'.$this->getUrl($data).'"><a href="#submit" onclick="this.parentNode.submit()">'.$this->name.'</a></form>';
        }
        else {
            throw new MethodNotFoundException('The Url-Method "'.$this->method.'" did not exist.');
        }
    }

    /**
     * Generates the url for the action
     *
     * @param mixed $data
     * @return Route
     */
    private function getUrl($data) {

        if(count($this->parameters)) {
            foreach($this->parameters as $key => $source) {

                /* no source <=> key conneciton */
                if(!$key) {
                    $key = $source;
                }

                if(substr($source,0,5) == "func:") {
                    $methodName =  substr($source,5);
                    $routeParameters[$key] = $data->$methodName();
                }
                else {
                    $routeParameters[$key] = $data->$source;
                }
            }
            return route($this->route,$routeParameters);
        }
        else {
            return route($this->route);
        }
    }

    /**
     * Set the Name
     *
     * @param string $name
     * @return void
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * Set the route
     *
     * @param string $route
     * @return void
     */
    public function setRoute($route)
    {
        $this->route = $route;
        return $this;
    }

    /**
     * Set the parameters
     *
     * @param array $parameters
     * @return CisTableAction
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * Set the method
     *
     * @param string $method
     * @return void
     */
    public function setMethod($method) {
        $this->method = $method;
    }

    public function setSlug($slug) : CisTableAction {
        $this->slug = $slug;
        return $this;
    }

    public function setCssClass($cssClass) : CisTableAction {
        $this->cssClass = $cssClass;
        return $this;
    }

}

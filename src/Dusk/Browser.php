<?php

namespace BeyondCode\DuskDashboard\Dusk;

use BeyondCode\DuskDashboard\BrowserActionCollector;
use Laravel\Dusk\Component;
use Laravel\Dusk\ElementResolver;


class Browser extends \Laravel\Dusk\Browser
{
    use Concerns\InteractsWithAuthentication,
        Concerns\InteractsWithCookies,
        Concerns\InteractsWithElements,
        Concerns\InteractsWithJavascript,
        Concerns\InteractsWithMouse,
        Concerns\MakesAssertions,
        Concerns\MakesUrlAssertions,
        Concerns\WaitsForElements;

    /** @var BrowserActionCollector */
    protected $actionCollector;

    /** @var string */
    protected $testName;

    public function __construct($driver, $resolver = null, $testName = null)
    {
        if ($testName) {
            $this->testName = $testName;
        }

        parent::__construct($driver, $resolver);
    }


    public function setActionCollector(BrowserActionCollector $collector)
    {
        $this->actionCollector = $collector;
    }

    /**
     * @return BrowserActionCollector|null
     */
    public function getActionCollector()
    {
        return $this->actionCollector;
    }

    /** {@inheritdoc} */
    public function visit($url)
    {
        $browser = parent::visit($url);

        $this->actionCollector->collect(__FUNCTION__, func_get_args(), $this);

        return $browser;
    }

    /** {@inheritdoc} */
    public function visitRoute($route, $parameters = [])
    {
        $browser = parent::visitRoute($route, $parameters);

        $this->actionCollector->collect(__FUNCTION__, func_get_args(), $this);

        return $browser;
    }

    /** {@inheritdoc} */
    public function refresh()
    {
        $browser = parent::refresh();

        $this->actionCollector->collect(__FUNCTION__, func_get_args(), $this);

        return $browser;
    }

    public function getCurrentPageSource()
    {
        $this->ensurejQueryIsAvailable();

        $this->restoreHtml();

        return $this->driver->executeScript('return document.documentElement.innerHTML;');
    }

    protected function restoreHtml()
    {
        $this->driver->executeScript("jQuery('input').attr('value', function() { return jQuery(this).val(); });");

        $this->driver->executeScript("jQuery('input[type=checkbox]').each(function() { jQuery(this).attr('checked', jQuery(this).prop(\"checked\")); });");

        $this->driver->executeScript("jQuery('textarea').each(function() { jQuery(this).html(jQuery(this).val()); });");

        $this->driver->executeScript("jQuery('input[type=radio]').each(function() { jQuery(this).attr('checked', this.checked); });");

        $this->driver->executeScript("jQuery('select option').each(function() { jQuery(this).attr('selected', this.selected); });");
    }

    /**
     * Execute a Closure with a scoped browser instance.
     *
     * @param  string  $selector
     * @param  \Closure  $callback
     * @return \Laravel\Dusk\Browser
     */
    public function with($selector, \Closure $callback)
    {
        $browser = new static(
            $this->driver, new ElementResolver($this->driver, $this->resolver->format($selector))
        );

        $browser->setActionCollector(new BrowserActionCollector($this->testName));

        if ($this->page) {
            $browser->onWithoutAssert($this->page);
        }

        if ($selector instanceof Component) {
            $browser->onComponent($selector, $this->resolver);
        }

        call_user_func($callback, $browser);

        return $this;
    }

    protected function getTestName()
    {
        return class_basename(static::class);
    }
}

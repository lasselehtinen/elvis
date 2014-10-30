<?php namespace Lasselehtinen\Elvis\Facades;

use Illuminate\Support\Facades\Facade;

class Elvis extends Facade
{
  /**
   * Get the registered name of the component.
   *
   * @return string
   */
  protected static function getFacadeAccessor() { return 'elvis'; }

}

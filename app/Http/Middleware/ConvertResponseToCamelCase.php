<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\KeyCaseHelper;
use Illuminate\Http\JsonResponse;

class ConvertResponseToCamelCase
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
  public function handle(Request $request, Closure $next)
  {
      $response = $next($request);

    if ($response instanceof JsonResponse) {
        $response->setData(resolve(KeyCaseHelper::class)->convert(
            KeyCaseHelper::CASE_CAMEL,
            json_decode($response->content(), true)
        ));
    }

      return $response;
  }
}

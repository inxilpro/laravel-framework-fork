<?php

namespace Illuminate\Foundation\Auth\Access;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Reflector;
use Illuminate\Support\Str;
use InvalidArgumentException;
use ReflectionMethod;

trait AuthorizesRequests
{
    /**
     * Authorize a given action for the current user.
     *
     * @param  mixed  $ability
     * @param  mixed|array  $arguments
     * @return \Illuminate\Auth\Access\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorize($ability, $arguments = [])
    {
        [$ability, $arguments] = $this->parseAbilityAndArguments($ability, $arguments);

        return app(Gate::class)->authorize($ability, $arguments);
    }

    /**
     * Authorize a given action for a user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|mixed  $user
     * @param  mixed  $ability
     * @param  mixed|array  $arguments
     * @return \Illuminate\Auth\Access\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeForUser($user, $ability, $arguments = [])
    {
        [$ability, $arguments] = $this->parseAbilityAndArguments($ability, $arguments);

        return app(Gate::class)->forUser($user)->authorize($ability, $arguments);
    }

    /**
     * Guesses the ability's name if it wasn't provided.
     *
     * @param  mixed  $ability
     * @param  mixed|array  $arguments
     * @return array
     */
    protected function parseAbilityAndArguments($ability, $arguments)
    {
        if (is_string($ability) && ! str_contains($ability, '\\')) {
            return [$ability, $arguments];
        }

        $method = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2]['function'];

        return [$this->normalizeGuessedAbilityName($method), $ability];
    }

    /**
     * Normalize the ability name that has been guessed from the method name.
     *
     * @param  string  $ability
     * @return string
     */
    protected function normalizeGuessedAbilityName($ability)
    {
        $map = $this->resourceAbilityMap();

        return $map[$ability] ?? $ability;
    }

    /**
     * Authorize a resource action based on the incoming request.
     *
     * @param  string|null  $model
     * @param  string|null  $parameter
     * @param  array  $options
     * @param  \Illuminate\Http\Request|null  $request
     * @return void
     * @throws \InvalidArgumentException
     */
    public function authorizeResource($model = null, $parameter = null, array $options = [], $request = null)
    {
        if (! $model) {
            $modelAndParameter = $this->guessModelAndParameterNameForResource();
            $model = $modelAndParameter[0];
            $parameter = $parameter ?: $modelAndParameter[1];
        }

        $parameter = $parameter ?: Str::snake(class_basename($model));

        $middleware = [];

        foreach ($this->resourceAbilityMap() as $method => $ability) {
            $modelName = in_array($method, $this->resourceMethodsWithoutModels()) ? $model : $parameter;

            $middleware["can:{$ability},{$modelName}"][] = $method;
        }

        foreach ($middleware as $middlewareName => $methods) {
            $this->middleware($middlewareName, $options)->only($methods);
        }
    }

    /**
     * Guess the model that is associated with this resource controller.
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function guessModelAndParameterNameForResource()
    {
        $methods = array_keys(Arr::except($this->resourceAbilityMap(), $this->resourceMethodsWithoutModels()));

        foreach ($methods as $method) {
            if ($modelAndParameterName = $this->getModelAndParameterNameFromResourceMethod($method)) {
                return $modelAndParameterName;
            }
        }

        throw new InvalidArgumentException('Unable to guess model for authorizeResource().');
    }

    /**
     * Get the last Model parameter from a resource method.
     *
     * @param  string  $method
     * @return null|array
     */
    protected function getModelAndParameterNameFromResourceMethod($method)
    {
        if (! method_exists($this, $method)) {
            return null;
        }

        return collect((new ReflectionMethod($this, $method))->getParameters())
            ->map(function ($parameter) {
                return [Reflector::getParameterClassName($parameter), $parameter->getName()];
            })
            ->filter(function ($parameter) {
                return $parameter[0]
                    && class_exists($parameter[0])
                    && is_subclass_of($parameter[0], Model::class, true);
            })
            ->last();
    }

    /**
     * Get the map of resource methods to ability names.
     *
     * @return array
     */
    protected function resourceAbilityMap()
    {
        return [
            'index' => 'viewAny',
            'show' => 'view',
            'create' => 'create',
            'store' => 'create',
            'edit' => 'update',
            'update' => 'update',
            'destroy' => 'delete',
        ];
    }

    /**
     * Get the list of resource methods which do not have model parameters.
     *
     * @return array
     */
    protected function resourceMethodsWithoutModels()
    {
        return ['index', 'create', 'store'];
    }
}

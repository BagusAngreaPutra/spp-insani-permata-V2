<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class PreserveListContext
{
    private const URLS_KEY = 'preserved_list_context.urls';
    private const LAST_ROUTE_KEY = 'preserved_list_context.last_route';
    private const ORIGIN_KEY = 'preserved_list_context.origin';

    public function handle(Request $request, Closure $next): Response
    {
        $routeName = (string) optional($request->route())->getName();

        if ($routeName === '' || !$request->hasSession()) {
            return $next($request);
        }

        $isListPage = $request->isMethod('GET') && $this->isListRoute($routeName);
        $routeContext = $isListPage ? $this->contextFromListRoute($routeName) : null;
        $lastRoute = (array) $request->session()->get(self::LAST_ROUTE_KEY, []);
        $rememberedUrls = (array) $request->session()->get(self::URLS_KEY, []);

        if ($isListPage && $routeContext) {
            $rememberedUrl = $rememberedUrls[$routeContext] ?? null;
            $cameFromAction = ($lastRoute['is_action'] ?? false)
                && ($request->session()->get(self::ORIGIN_KEY) === $routeContext);

            // Tautan Kembali/Batal biasanya menuju route index tanpa query.
            // Saat datang dari form, kembalikan dahulu ke URL daftar terakhir.
            if (
                $cameFromAction
                && $request->query() === []
                && $this->isSafeRememberedUrl($request, $rememberedUrl)
                && $this->normalizeUrl($rememberedUrl) !== $this->normalizeUrl($request->fullUrl())
            ) {
                return redirect()->to($rememberedUrl);
            }

            $rememberedUrls[$routeContext] = $request->fullUrl();
            $request->session()->put(self::URLS_KEY, $rememberedUrls);
            $request->session()->put(self::ORIGIN_KEY, $routeContext);
        } elseif ($request->isMethod('GET') && $this->isActionPage($routeName)) {
            if (($lastRoute['is_list'] ?? false) && isset($lastRoute['context'])) {
                $request->session()->put(self::ORIGIN_KEY, $lastRoute['context']);
            }
        }

        $activeContext = $isListPage
            ? $routeContext
            : $this->resolveActiveContext($routeName, $request);

        $request->attributes->set('preserved_list_context', $activeContext);
        $request->attributes->set('is_preserved_list_page', $isListPage);

        $response = $next($request);

        if (!$request->isMethod('GET') && $response instanceof RedirectResponse) {
            $originContext = $request->session()->get(self::ORIGIN_KEY)
                ?: $this->longestMatchingContext($routeName, array_keys($rememberedUrls));
            $rememberedUrl = $originContext ? ($rememberedUrls[$originContext] ?? null) : null;

            if (
                $originContext
                && $this->isSafeRememberedUrl($request, $rememberedUrl)
                && $this->redirectTargetsAList($response->getTargetUrl(), $rememberedUrl, $originContext)
            ) {
                $response->setTargetUrl($rememberedUrl);
            }
        }

        if ($request->isMethod('GET') && ($isListPage || $this->isActionPage($routeName))) {
            $request->session()->put(self::LAST_ROUTE_KEY, [
                'name' => $routeName,
                'context' => $activeContext,
                'is_list' => $isListPage,
                'is_action' => $this->isActionPage($routeName),
            ]);
        }

        return $response;
    }

    private function isListRoute(string $routeName): bool
    {
        return preg_match('/(^|\.)index(?:\.|$)/', $routeName) === 1;
    }

    private function contextFromListRoute(string $routeName): ?string
    {
        $position = strpos($routeName, '.index');

        if ($position !== false) {
            return substr($routeName, 0, $position);
        }

        return str_ends_with($routeName, 'index')
            ? rtrim(substr($routeName, 0, -strlen('index')), '.')
            : null;
    }

    private function isActionPage(string $routeName): bool
    {
        return preg_match('/\.(create|edit|show)$/', $routeName) === 1
            || str_contains($routeName, '.koreksi.')
            || str_ends_with($routeName, '.koreksi')
            || str_contains($routeName, '.stok.edit');
    }

    private function resolveActiveContext(string $routeName, Request $request): ?string
    {
        $rememberedContexts = array_keys((array) $request->session()->get(self::URLS_KEY, []));
        $originContext = $request->session()->get(self::ORIGIN_KEY);

        if ($this->isActionPage($routeName) && $originContext) {
            return $originContext;
        }

        return $this->longestMatchingContext($routeName, $rememberedContexts);
    }

    private function longestMatchingContext(string $routeName, array $contexts): ?string
    {
        $matching = array_values(array_filter(
            $contexts,
            fn (string $context) => $routeName === $context || str_starts_with($routeName, $context . '.')
        ));

        usort($matching, fn (string $left, string $right) => strlen($right) <=> strlen($left));

        return $matching[0] ?? null;
    }

    private function redirectTargetsAList(string $targetUrl, string $rememberedUrl, string $context): bool
    {
        $targetPath = $this->normalizePath($targetUrl);

        if ($targetPath === $this->normalizePath($rememberedUrl)) {
            return true;
        }

        foreach (Route::getRoutes() as $route) {
            $name = (string) $route->getName();

            if (!$this->isListRoute($name) || $this->contextFromListRoute($name) !== $context) {
                continue;
            }

            if (str_contains($route->uri(), '{')) {
                continue;
            }

            if ($targetPath === $this->normalizePath(url($route->uri()))) {
                return true;
            }
        }

        return false;
    }

    private function isSafeRememberedUrl(Request $request, mixed $url): bool
    {
        if (!is_string($url) || $url === '') {
            return false;
        }

        $parts = parse_url($url);

        return isset($parts['host'])
            && strcasecmp((string) $parts['host'], $request->getHost()) === 0
            && (!isset($parts['scheme']) || strcasecmp((string) $parts['scheme'], $request->getScheme()) === 0);
    }

    private function normalizeUrl(string $url): string
    {
        return rtrim($url, '/');
    }

    private function normalizePath(string $url): string
    {
        $path = '/' . ltrim((string) parse_url($url, PHP_URL_PATH), '/');

        return rtrim($path, '/') ?: '/';
    }
}

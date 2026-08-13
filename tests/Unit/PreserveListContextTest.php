<?php

namespace Tests\Unit;

use App\Http\Middleware\PreserveListContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class PreserveListContextTest extends TestCase
{
    public function test_successful_update_returns_to_the_same_list_page_and_filters(): void
    {
        $session = $this->makeSessionStore();
        $middleware = new PreserveListContext();

        $middleware->handle(
            $this->request('GET', 'http://localhost/jenis_pembayaran?page=4&search=spp', 'jenis_pembayaran.index', $session),
            fn () => new Response('list')
        );

        $middleware->handle(
            $this->request('GET', 'http://localhost/jenis_pembayaran/15/edit', 'jenis_pembayaran.edit', $session),
            fn () => new Response('edit')
        );

        $response = $middleware->handle(
            $this->request('PUT', 'http://localhost/jenis_pembayaran/15', 'jenis_pembayaran.update', $session),
            fn () => new RedirectResponse('http://localhost/jenis_pembayaran')
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(
            'http://localhost/jenis_pembayaran?page=4&search=spp',
            $response->getTargetUrl()
        );
    }

    public function test_cancel_from_edit_returns_to_the_remembered_list_page(): void
    {
        $session = $this->makeSessionStore();
        $middleware = new PreserveListContext();

        $middleware->handle(
            $this->request('GET', 'http://localhost/siswa?page=125', 'siswa.index', $session),
            fn () => new Response('list')
        );

        $middleware->handle(
            $this->request('GET', 'http://localhost/siswa/321/edit', 'siswa.edit', $session),
            fn () => new Response('edit')
        );

        $response = $middleware->handle(
            $this->request('GET', 'http://localhost/siswa', 'siswa.index', $session),
            fn () => new Response('base-list')
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://localhost/siswa?page=125', $response->getTargetUrl());
    }

    public function test_reset_from_a_list_does_not_restore_the_old_query(): void
    {
        $session = $this->makeSessionStore();
        $middleware = new PreserveListContext();

        $middleware->handle(
            $this->request('GET', 'http://localhost/sekolah?page=5&search=tk', 'sekolah.index', $session),
            fn () => new Response('filtered-list')
        );

        $response = $middleware->handle(
            $this->request('GET', 'http://localhost/sekolah', 'sekolah.index', $session),
            fn () => new Response('reset-list')
        );

        $this->assertNotInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('reset-list', $response->getContent());
    }

    private function request(
        string $method,
        string $url,
        string $routeName,
        Store $session
    ): Request {
        $request = Request::create($url, $method);
        $route = new Route([$method], parse_url($url, PHP_URL_PATH), fn () => null);
        $route->name($routeName);
        $request->setRouteResolver(fn () => $route);
        $request->setLaravelSession($session);

        return $request;
    }

    private function makeSessionStore(): Store
    {
        $session = new Store('preserve-list-test', new ArraySessionHandler(120));
        $session->start();

        return $session;
    }
}

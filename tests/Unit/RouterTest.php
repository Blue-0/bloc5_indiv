<?php

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use Core\Router;

class RouterTest extends TestCase
{
    private $router;

    protected function setUp(): void
    {
        $this->router = new Router();
    }

    public function testAddAndGetRoutes()
    {
        $this->router->add('admin/{controller}/{action}', ['namespace' => 'Admin']);
        $routes = $this->router->getRoutes();
        
        $this->assertCount(1, $routes);
        $expectedRegex = '/^admin\/(?P<controller>[a-z-]+)\/(?P<action>[a-z-]+)$/i';
        $this->assertArrayHasKey($expectedRegex, $routes);
    }

    public function testMatchSimpleRoute()
    {
        $this->router->add('home', ['controller' => 'Home', 'action' => 'index']);
        
        $this->assertTrue($this->router->match('home'));
        $params = $this->router->getParams();
        $this->assertEquals('Home', $params['controller']);
        $this->assertEquals('index', $params['action']);
    }

    public function testMatchDynamicRoute()
    {
        $this->router->add('{controller}/{action}');
        
        $this->assertTrue($this->router->match('posts/new'));
        $params = $this->router->getParams();
        $this->assertEquals('posts', $params['controller']);
        $this->assertEquals('new', $params['action']);
    }

    public function testMatchRouteWithRegex()
    {
        $this->router->add('product/{id:\d+}', ['controller' => 'Product', 'action' => 'show']);
        
        $this->assertTrue($this->router->match('product/42'));
        $params = $this->router->getParams();
        $this->assertEquals('Product', $params['controller']);
        $this->assertEquals('show', $params['action']);
        $this->assertEquals('42', $params['id']);
        
        // Ne doit pas correspondre si l'id n'est pas numérique
        $this->assertFalse($this->router->match('product/abc'));
    }

    public function testNoRouteMatched()
    {
        $this->router->add('home', ['controller' => 'Home', 'action' => 'index']);
        $this->assertFalse($this->router->match('about'));
    }
}

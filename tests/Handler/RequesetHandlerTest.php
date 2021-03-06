<?php
/**
 * This file is part of the BEAR.Middleware package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace BEAR\Middleware;

use BEAR\Middleware\Handler\ResourceHandler;
use BEAR\Middleware\Module\StreamModule;
use BEAR\Resource\Exception\ResourceNotFoundException;
use BEAR\Resource\Module\ResourceModule;
use BEAR\Resource\RenderInterface;
use BEAR\Resource\ResourceInterface;
use BEAR\Sunday\Provide\Router\WebRouter;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Uri;

class RequesetHandlerTest extends TestCase
{
    /**
     * @var ResourceHandler
     */
    private $handler;

    protected function setUp() : void
    {
        $injector = new Injector(new StreamModule(new ResourceModule(__NAMESPACE__)));
        $resource = $injector->getInstance(ResourceInterface::class);
        $renderer = $injector->getInstance(RenderInterface::class); // singleton renderer
        $this->handler = new ResourceHandler($resource, new WebRouter('page://self'), $renderer);
    }

    public function testMissingRoute()
    {
        $this->expectException(ResourceNotFoundException::class);
        $request = ServerRequestFactory::fromGlobals();
        $request = $request->withUri(new Uri('http://localhost/not_found'));
        $requestHandler = $this->handler;
        $requestHandler($request, new Response, function ($req, $resp) {
        });
    }

    public function caseProvider()
    {
        return [
            ['http://localhost/item', '{
    "msg": "hello world",
    "stream": "Konichiwa stream !
"
}
'],
            ['http://localhost/one', 'Konichiwa stream !
'],
            ['http://localhost/greeting', '{
    "greeting": "Hello BEAR"
}
'],
            ['http://localhost/greeting2', '{
    "value": "Hello BEAR"
}
']
        ];
    }

    /**
     * @dataProvider caseProvider
     */
    public function testRouteMatchAndStream($uri, $expected)
    {
        $request = ServerRequestFactory::fromGlobals();
        $request = $request->withUri(new Uri($uri));
        $requestHandler = $this->handler;
        $requestHandler(
            $request,
            new Response,
            function ($req, $response) use ($expected) {
                $this->assertInstanceOf(Response::class, $response);
                $this->assertSame($expected, (string) $response->getBody());
            }
        );
    }
}
